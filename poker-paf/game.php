<?php
require_once 'db.php';
session_start();

// 1. Récupération de l'ID de la partie (Session ou URL)
$game_id = $_SESSION['game_id'] ?? (isset($_GET['game_id']) ? intval($_GET['game_id']) : null);

if (!$game_id) {
    die("Erreur : Aucune partie trouvée. Repassez par l'accueil.");
}

// 2. Récupération des infos de la partie
$stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Récupération des joueurs
$stmt = $db->prepare("SELECT * FROM players WHERE game_id = ? ORDER BY id ASC");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LE CORRECTIF POUR LE "0" ---
if (empty($players)) {
    die("Erreur : Aucun joueur dans cette partie.");
}

// Si la BDD dit 0, on force le premier joueur de la liste
if ($game['current_player_id'] == 0) {
    $first_player_id = $players[0]['id'];
    
    // On met à jour la base de données TOUT DE SUITE
    $update = $db->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
    $update->execute([$first_player_id, $game_id]);
    
    // On met à jour la variable locale pour que l'affichage suive
    $game['current_player_id'] = $first_player_id;
}

// 4. Trouver le nom du joueur actif pour le panneau du bas
$activePlayerName = "Inconnu";
foreach ($players as $p) {
    if ($p['id'] == $game['current_player_id']) {
        $activePlayerName = htmlspecialchars($p['name']);
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Poker PAF - Table N°<?php echo $game_id; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' type='text/css' href='game.css'>
</head>
<body>
    <div class="game-container">
    <div class="stats-bar">
        <div class="stat-item">POT TOTAL: <strong id="main-pot"><?php echo $game['pot'] ?? 0; ?></strong> 🪙</div>
        <div class="stat-item">MISE ACTUELLE: <strong id="current-bet"><?php echo $game['last_bet'] ?? 0; ?></strong></div>
        <button onclick="deleteGame()" class="btn-back">Fermer la table</button>
        <button onclick="changePlayer()" class="btn-back">Joueur suivant</button>
        <a href="index.php" class="btn-back">⬅ Quitter</a>
    </div>

    <div class="table-container">
        <div class="poker-table">
            <div class="pot-area">
                <div class="total-pot"><?php echo $game['pot'] ?? 0; ?></div>
                <div class="current-bet-display">Mise: <?php echo $game['last_bet'] ?? 0; ?></div>
                <button class="btn-next-round" onclick="startNewRound()">NOUVELLE MANCHE</button>
            </div>

            <?php foreach ($players as $index => $p): ?>
                <div class="player-slot slot-<?php echo $index; ?>" data-id="<?php echo $p['id']; ?>">
                    <?php 
                        $isActive = ((int)$p['id'] === (int)$game['current_player_id']); 
                    ?>
                    <div class="player-info <?php echo $isActive ? 'active' : ''; ?>">
                        <?php if (isset($p['is_dealer']) && $p['is_dealer']): ?>
                            <div class="dealer-badge">D</div>
                        <?php endif; ?>
                        
                        <span class="player-name">J<?php echo ($index + 1); ?> : <?php echo htmlspecialchars($p['name']); ?></span>
                        <span class="player-money"><?php echo $p['money']; ?> 🪙</span><br>
                        <span class="player-bet">Mise: <?php echo $p['current_bet'] ?? 0; ?> 🪙</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="action-panel">
        <?php
        // On recalcule le nom du joueur actif en fonction de l'ID actuel dans $game
        $activePlayerName = "Personne"; 

        foreach ($players as $p) {
            if ((int)$p['id'] === (int)$game['current_player_id']) {
                $activePlayerName = htmlspecialchars($p['name']);
                break;
            }
        }
        ?>
        <p class="turn-info">Au tour de : <strong id="active-player-name"><?php echo $activePlayerName; ?></strong></p>
        
        <div class="action-buttons">
            <button class="btn btn-fold" onclick="SeCoucher()">Se coucher</button>
            <button class="btn btn-call" onclick="Suivre()">Suivre</button>
            <div class="raise-group">
                <input type="number" id="raise-amount" placeholder="Mise" min="0">
                <button class="btn-validate" onclick="Relancer()">OK</button>
            </div>
            <button class="btn btn-allin" onclick="Tapis()">TAPIS</button>
        </div>
    </div>
</div>
<script>
    // --- VARIABLES GLOBALES ---
    let actualGameID = new URLSearchParams(window.location.search).get('game_id');
    let currentBlind = 0;  // Corrigé (utilisé dans UpdateLabels)
    let totalBlind = 0;    // Corrigé (utilisé dans UpdateLabels)
    let currentPlayerId = null;
    let money = {};        // Pour stocker les soldes
    let players = [];      // Pour l'affichage

    // --- INITIALISATION ---
    // On charge les données une première fois
    window.onload = () => {
        UpdateLabels();
        <?php foreach ($players as $p): ?>
            players.push({
                id: <?php echo $p['id']; ?>,
                money: <?php echo $p['money']; ?>,
                blind: <?php echo $p['current_bet'] ?? 0; ?>
            });
        <?php endforeach; ?>
    };

    // --- LES FONCTIONS DE JEU (Logique visuelle) ---

    function Suivre() {
        console.log("Action : Suivre");

        // 1. On vérifie si le joueur a assez d'argent AVANT de lancer le fetch
        // Note : currentPlayerId et money doivent être à jour via UpdateLabels
        if (money[currentPlayerId] < currentBlind) {
            alert("Vous n'avez pas assez d'argent pour suivre. Mise requise : " + currentBlind);
            return;
        }

        // 2. On prépare l'envoi
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('amount', currentBlind); // On utilise la variable globale directement

        // 3. On utilise process_bet.php (le fichier "tout-en-un")
        fetch('process_bet.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("Mise suivie avec succès !");
                // 4. Une fois que l'argent est retiré en BDD, on change de joueur
                changePlayer(); 
            } else {
                alert("Erreur serveur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function SeCoucher() {
        console.log("Action : Se coucher");
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('action', 'fold'); // On envoie une action spécifique pour se coucher
        fetch('fold_player.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("Joueur couché avec succès !");
                UpdateLabels(); // Met à jour les étiquettes de monnaie
                changePlayer(); // On change de joueur après s'être couché
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));

        UpdateLabels(); // On met a jour les valeurs affichées
        changePlayer(); // Enfin on change de joueur
    }

    function Relancer() {
        const amount = parseInt(document.getElementById('raise-amount').value);
        if (isNaN(amount) || amount <= 0) {
            alert("Veuillez entrer une mise valide.");
            return;
        }

        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('amount', amount);

        // UN SEUL fetch qui fait tout
        fetch('process_bet.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("Relance effectuée !");
                changePlayer(); // On change de joueur une fois que c'est fini
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur:", err));
    }

    function Tapis() {
        console.log("Action : TAPIS !");
        let currentPlayerId = getCurrentPlayer(); // On récupère la blind actuelle du joueur pour l'afficher dans le pot
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('current_player_id', currentPlayerId); // On envoie aussi l'ID du joueur actuel pour que le PHP puisse faire le lien
        fetch('all_in.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("TAPIS effectué avec succès !");
                UpdateLabels(); // Met à jour les étiquettes de monnaie
            } else {
                alert("Erreur : " + data.message);
            }
        })
        UpdateLabels(); // On met a jour les valeurs affichées
        changePlayer(); // Enfin on change de joueur
    }

    // Fonction pour mettre à jour les étiquettes de monnaie sur l'écran
    function UpdateLabels() {
        // On récupère les nouvelles valeurs depuis le serveur
        getActualPlayerMoney();
        getActualPlayerBlind();
        getActualGameBlind();
        getTotalGameBlind();

        // Ensuite, on met à jour les éléments du DOM avec les nouvelles valeurs
        document.querySelectorAll('.player-slot').forEach(slot => {
            const playerId = slot.getAttribute('data-id');
            const playerInfo = players.find(p => p.id == playerId);
            if (playerInfo) {
                slot.querySelector('.player-money').textContent = playerInfo.money + " 🪙";
                slot.querySelector('.player-bet').textContent = "Mise: " + player.blind + " 🪙";
            }
        });

        // Mettre à jour le pot et la mise actuelle
        document.getElementById('main-pot').textContent = totalBlind + " 🪙";
        document.getElementById('current-bet').textContent = currentBlind + " 🪙";
    }

    function changePlayer() {
        console.log("Demande de changement de joueur...");
        
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('action', 'next_player');

        fetch('change_player.php', {
            method: 'POST',
            body: formData
        })
        .then(r => {
            // On vérifie si la réponse est bien du JSON
            if (!r.ok) throw new Error("Erreur réseau");
            return r.json();
        })
        .then(data => {
            if (data.success) {
                console.log("Joueur changé avec succès !");
                // On attend un tout petit peu avant de recharger pour laisser la BDD respirer
                setTimeout(() => {
                    location.reload();
                }, 100);
            } else {
                alert("Erreur serveur : " + data.message);
            }
        })
        .catch(err => {
            console.error("Erreur complète :", err);
            alert("Erreur lors du changement de joueur. Vérifie la console (F12).");
        });
    }

    function deleteGame() {
        if (confirm("Supprimer la partie ?")) {
            let formData = new FormData();
            // On s'assure que actualGameID est bien défini
            formData.append('game_id', actualGameID);

            fetch('delete_game.php', { 
                method: 'POST', 
                body: formData 
            })
            .then(r => r.json()) // On parse la réponse JSON du PHP
            .then(data => {
                if (data.success) {
                    console.log("Supprimé !");
                    window.location.href = 'index.php';
                } else {
                    alert("Erreur lors de la suppression : " + data.message);
                }
            })
            .catch(err => {
                console.error("Erreur réseau :", err);
                // Optionnel : rediriger quand même si tu veux forcer
                // window.location.href = 'index.php';
            });
        }
    }

    function getCurrentPlayer() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_current_player.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentPlayerId = data.player_id; // On stocke l'ID du joueur actuel dans une variable globale
                return data.player_id; // On retourne l'ID du joueur actuel
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function getActualPlayerMoney() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_player_money.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                players.push({
                    id: data.player_id,
                    money: data.money
                });
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function getActualPlayerBlind() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_player_blind.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                players.push({
                    id: data.player_id,
                    blind: data.blind
                });
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function getActualGameBlind() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_actual_game_blind.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentBlind = data.blind; // On met à jour l'affichage de la blind
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function getTotalGameBlind() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_total_game_blind.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                totalBlind = data.total_blind; // On met à jour l'affichage du pot total
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

</script>
</body>
</html>