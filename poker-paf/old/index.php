<?php
require_once 'db.php';
session_start();
session_destroy(); // On détruit la session pour supprimer l'ID de la partie en cours

// Récupérer la liste des parties en cours
$stmt = $db->query("SELECT id, start_money, start_blind FROM games ORDER BY id DESC");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Poker PAF</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='index.css'>
    <script src='main.js'></script>
</head>
<body>
    <div class="welcome-container">
        <h1>Welcome to Poker PAF</h1>
        <button onclick="window.location.href='config.php'">Créer une partie</button><br><br>

        <label>Rejoindre une partie :</label><br>
        <input type="text" id="join_game_id" placeholder="ID de la partie">
        <button onclick="joinGame()">Rejoindre</button><br><br>
        <h2>Parties en cours :</h2>
        <div id="games_list">
            <?php if (count($games) > 0): ?>
                <ul>
                    <?php foreach ($games as $game): ?>
                        <li>
                            ID: <?php echo $game['id']; ?> - 
                            Start Money: <?php echo $game['start_money']; ?> - 
                            Blind: <?php echo $game['start_blind']; ?>
                            <button class="btn-join-list" onclick="window.location.href='game.php?game_id=<?php echo $game['id']; ?>'">Rejoindre</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucune partie en cours.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    function joinGame() {
        const gameId = document.getElementById('join_game_id').value;
        if (gameId) {
            // Rediriger vers une page de jeu avec l'ID en paramètre
            window.location.href = `game.php?game_id=${gameId}`;
        } else {
            alert('Veuillez entrer un ID de partie valide.');
        }
    }
</html>