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

// Trouver qui est le dealer
foreach ($players as $p) {
    if ($p['is_dealer']) {
        $dealerID = $p['id'];
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
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
</head>
<body>
    <div class="game-container">
    <div class="stats-bar">
        <div class="stat-item">POT TOTAL: <strong id="main-pot"><?php echo $game['pot'] ?? 0; ?></strong></div>
        <div class="stat-item">MISE ACTUELLE: <strong id="current-bet"><?php echo $game['last_bet'] ?? 0; ?></strong></div>
        <button onclick="deleteGame()" class="btn-back">Fermer la table</button>
        <button onclick="changePlayer()" class="btn-back">Joueur suivant</button>
        <button onclick="EndGame()" class="btn-back">Terminer la partie</button>
        <a href="index.php" class="btn-back">⬅ Quitter</a>
    </div>

    <div class="table-container">
        <div class="poker-table">
            <div class="pot-area">
                <div class="total-pot"><?php echo $game['pot'] ?? 0; ?></div>
                <div id="Mise" class="current-bet-display">Mise: <?php echo $game['last_bet'] ?? 0; ?></div>
                <button class="btn-next-round" onclick="StartNewGame()">NOUVELLE PARTIE</button>
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
                <input type="number" id="raise-amount" placeholder="Mise" min="<?php echo $game['last_bet'] ?? 0; ?>">
                <button class="btn-validate" onclick="Relancer()">OK</button>
            </div>
            <button class="btn btn-allin" onclick="Tapis()">TAPIS</button>
        </div>
    </div>
</div>
<script>
    // --- VARIABLES GLOBALES ---
    let actualGameID = new URLSearchParams(window.location.search).get('game_id');
    let current_blind = 0;
    let totalBlind = 0;    // Corrigé (utilisé dans UpdateLabels)
    let currentPlayerId = 0;
    let money = {};        // Pour stocker les soldes
    let players = [];      // Pour stocker les infos des joueurs (id, money, blind, isDealer)
    <?php foreach ($players as $p): ?>
        players.push(
            {
                id: <?php echo $p['id']; ?>,
                money: <?php echo $p['money']; ?>,
                blind: <?php echo $p['current_bet'] ?? 0; ?>,
                isDealer: <?php echo $p['is_dealer'] ? 'true' : 'false'; ?>
            }
        );
    <?php endforeach; ?>      // Pour l'affichage
    let dealerFound = false;

    // --- INITIALISATION ---
    // On charge les données une première fois
    window.onload = () => {
        UpdateLabels();
        getCurrentPlayer();
    };

    // On regarde si le joueur est le dealer
    players.forEach(player => {
        if (dealerFound){
           // Relancer(<?php echo $game['starting_blind'] ?? 0; ?>); // Le joueur après le dealer commence avec une relance
            return;
        }
        if (player.isDealer) {
            document.querySelector('.dealer-badge').style.display = 'block';
            //Relancer(<?php echo $game['starting_blind'] ?? 0; ?>*2); // Le dealer commence toujours avec une relance
            dealerFound = true;
        }
    });

    // --- LES FONCTIONS DE JEU (Logique visuelle) ---

    function Suivre() {
        console.log("Action : Suivre");
        let player = players.find(pl => pl.id == currentPlayerId);

        if (!(player === undefined)) {
            // 1. On vérifie si le joueur a assez d'argent AVANT de lancer le fetch
            // Note : currentPlayerId et money doivent être à jour via UpdateLabels
            let delta_blind = current_blind - player.blind;
            
            if (delta_blind <= 0) { // Permet de changer de joueur pour rester
                alert("Vous avez déjà mis assez pour suivre !");
                changePlayer();
                return;
            }

            if (player.money < delta_blind) { // Correction ici : on compare avec le delta, pas la blinde totale
                alert("Vous n'avez pas assez d'argent pour suivre, tapis requis");
                return;
            }

            // 2. On prépare l'envoi
            let formData = new FormData();
            formData.append('game_id', actualGameID);
            formData.append('amount', delta_blind); // On utilise le delta entre la blinde déjà posée et la blinde actuelle

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

        // Je suis pas sûr que ce soit néccessaire, voir c'est le problème:   UpdateLabels(); // On met a jour les valeurs affichées
        // du fait que ça fait trop de fois changer de joueur                 changePlayer(); // Enfin on change de joueur
    }

    function Relancer(amount) {
        if (amount === undefined) {
            amount = parseInt(document.getElementById('raise-amount').value) + current_blind - (players.find(pl => pl.id == currentPlayerId)?.blind || 0); // On ajoute la blinde actuelle pour que le joueur puisse entrer directement le montant total de sa relance
        }

        if (money[currentPlayerId] >= amount) { // Sécurise au cas où je me tromperais en nottant
            console.log("Vous n'avez pas suffisament d'argent")
            return;
        }

        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('amount', amount); // On envoie le montant total de la blinde à atteindre (ex: si la blinde est à 10 et que le joueur a déjà mis 4, il doit relancer à 6 pour atteindre les 10)

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
        getActualPlayerMoney();
        getActualPlayerBlind();
        GetCurrentBlind();
        getTotalGameBlind();

        document.querySelectorAll('.player-slot').forEach(slot => {
            const playerId = slot.getAttribute('data-id');
            const playerInfo = players.find(p => p.id == playerId);
            if (playerInfo) {
                slot.querySelector('.player-money').textContent = playerInfo.money + " 🪙";
                // CORRECTION ICI : playerInfo au lieu de player
                slot.querySelector('.player-bet').textContent = "Mise: " + playerInfo.blind + " 🪙";
            }
        });

        console.log("Blind actuel :", current_blind);

        document.getElementById('main-pot').textContent = totalBlind + " 🪙";
        document.getElementById('current-bet').textContent = current_blind + " 🪙";
        document.getElementById('Mise').textContent = "Mise: " + current_blind;
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

        fetch('get_player_money.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // On cherche le joueur dans le tableau et on met à jour son argent
                let p = players.find(pl => pl.id == data.player_id);
                if(p) p.money = data.money;
            }
        });
    }

    function getActualPlayerBlind() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('get_player_blind.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // On cherche le joueur et on met à jour sa mise
                let p = players.find(pl => pl.id == data.player_id);
                if(p) p.blind = data.blind;
            }
        });
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
                current_blind = data.blind; // On met à jour l'affichage de la blind
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

    function GetCurrentBlind() {
        let blinds = []; 
        <?php foreach ($players as $p): ?>
            blinds.push(
                <?php echo $p['current_bet'] ?? 0; ?>
            );
        <?php endforeach; ?>
        current_blind = Math.max(...blinds);
        console.log("Blind actuel recalculé :", current_blind);
    }

    // --- FONCTION EN CAS DE VICTOIRE ---
    function EndGame(winnerId, amountWon) {
        console.log("Fin de partie détectée !");
        
        // On cherche par CLASSE car dans ton PHP c'est class="table-container"
        const container = document.querySelector('.table-container'); 
        
        if (!container) {
            console.error("ERREUR : Le conteneur .table-container n'existe pas dans le HTML !");
            return;
        }

        // On vérifie si le panel n'existe pas déjà pour éviter les doublons
        if (document.querySelector('.win-panel')) return;

        const newRow = document.createElement('div');
        newRow.className = 'win-panel';

        // Construction du contenu
        newRow.innerHTML = `
            <h2>La partie est terminée ! Qui a gagné ?</h2>
            <div id="winner-buttons-area"></div>
        `;
        
        container.appendChild(newRow);

        // Ajout des boutons pour chaque joueur
        const area = document.getElementById('winner-buttons-area');
        players.forEach(p => {
            const btn = document.createElement('button');
            btn.className = 'btn-win';
            btn.innerText = p.name || "Joueur";
            btn.onclick = () => declareWinner(p.id);
            area.appendChild(btn);
        });

        // Optionnel : Petit effet de flou sur la table
        const table = document.querySelector('.poker-table');
        if (table) table.style.filter = 'blur(4px) brightness(0.7)';
    }

    function declareWinner(winnerId) {
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('winner_id', winnerId);

        fetch('declare_winner.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                confetti();
                // Changer la div "win-panel" pour afficher le gagnant
                const winPanel = document.querySelector('.win-panel');
                if (winPanel) {
                    winPanel.innerHTML = `
                        <h2>${data.winner_name} gagne la partie et remporte ${data.amount_won} 🪙 !</h2>
                        <button class="btn-back" onclick="window.location.href='index.php'">Retour à l'accueil</button>
                        <button class="btn-replay" onclick="StartNewGame()">Rejouer</button>
                    `;
                }
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    function StartNewGame() {
        let formData = new FormData();
        formData.append('game_id', actualGameID);

        fetch('start_new_game.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Nouvelle partie lancée !");
                location.reload(); // On recharge la page pour afficher la nouvelle partie
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }
</script>
</body>
</html>
