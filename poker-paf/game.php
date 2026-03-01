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
    <title>Poker PAF - La Table</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' href='game.css'>
</head>
<body>
    <div class="game-container">
    <div class="stats-bar">
        <div class="stat-item">POT TOTAL: <strong id="main-pot"><?php echo $game['pot'] ?? 0; ?></strong> 🪙</div>
        <div class="stat-item">MISE ACTUELLE: <strong id="current-bet"><?php echo $game['last_bet'] ?? 0; ?></strong></div>
        <button onclick="deleteGame(<?php echo $game_id; ?>)" class="btn-back">Fermer la table</button>
        <button onclick="changePlayer()" class="btn-back">Joueur suivant</button>
        <a href="index.php" class="btn-back">⬅ Quitter</a>
    </div>

    <div class="table-container">
        <div class="poker-table">
            <div class="pot-area">
                <div class="total-pot"><?php echo $game['pot'] ?? 0; ?> 🪙</div>
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
                        <span class="player-money"><?php echo $p['money']; ?> 🪙</span>
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
    // --- VARIABLES GLOBALES (Côté Navigateur) ---
    let actualGameID = new URLSearchParams(window.location.search).get('game_id');
    let current_blind = 0;
    let blinds = {}; // On stocke les mises en cours ici
    let money = {};  // On stocke le solde des joueurs ici

    // --- FONCTION POUR PARLER AU SERVEUR ---
    function sendActionToServer(actionType, amount = 0) {
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('action', actionType);
        formData.append('amount', amount);

        fetch('play_action.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Le serveur a validé, on rafraîchit la page pour voir le halo bouger
                location.reload(); 
            }
        });
    }

    // --- LES FONCTIONS DE JEU (Logique visuelle) ---

    function Suivre() {
        console.log("Action : Suivre");
        sendActionToServer('call');
    }

    function SeCoucher() {
        console.log("Action : Se coucher");
        sendActionToServer('fold');
    }

    function Relancer() {
        let val = document.getElementById('raise-amount').value;
        if (val > 0) {
            sendActionToServer('raise', val);
        } else {
            alert("Indique un montant !");
        }
    }

    function Tapis() {
        console.log("Action : TAPIS !");
        sendActionToServer('allin');
    }

    // Fonction pour mettre à jour les étiquettes de monnaie sur l'écran
    function UpdateLabels() {
        for (const id in money) {
            const label = document.getElementById(`label-${id}`);
            if (label) {
                label.innerText = (money[id] - (blinds[id] || 0)) + " 🪙";
            }
        }
    }

    function changePlayer() {
        console.log("Demande de changement de joueur...");
        
        // On utilise la fonction générique qu'on a créée ensemble
        // Si tu ne l'as pas, voici le code direct :
        let formData = new FormData();
        formData.append('game_id', actualGameID);
        formData.append('action', 'next_player'); // On envoie une action spécifique

        fetch('play_action.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log("Joueur changé avec succès !");
                location.reload(); // On recharge pour voir le halo se déplacer
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => console.error("Erreur fetch:", err));
    }

    // Au chargement, on bloque les actions tant que le guide n'est pas fini
    window.onload = () => enableActions(false);

    // Ta fonction deleteGame déjà existante (rappel)
    function deleteGame(idPartie) {
        if (confirm("Supprimer la partie ?")) {
            let formData = new FormData();
            formData.append('game_id', idPartie);

            fetch('delete_game.php', { method: 'POST', body: formData })
            .then(() => window.location.href = 'index.php');
        }
    }

</script>
</body>
</html>