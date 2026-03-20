<?php
session_start();

$game_id = isset($_GET['game_id']) ? $_GET['game_id'] :null;

$host = 'localhost';
$db   = 'poker_paf';
$user = 'root';
$pass = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// charger la BDD
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(['error' => 'Connexion échouée']));
}

$stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game_name = $stmt->fetchColumn();

// Récupération du game_id depuis les paramètres GET

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Choix du joueur - PokerPaf</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='css/playerSelector.css'>
</head>
<body>
    <div class="container">
    <button class="back-btn" onclick="window.location.href='index.html'">◀️ Retour à l'accueil</button>
    <h1>Rejoindre la partie <?php echo htmlspecialchars($game_name); ?></h1>
    <br>
    <h2>Choix du joueur</h2>
    <p>Veuillez cliquer sur le nom du joueur pour rejoindre la partie :</p>
    <div class="player-selection">
        <?php foreach ($players as $player): ?>
            <button class="join-player-btn" onclick="window.location.href='game.html?game_id=<?php echo $game_id; ?>&player_id=<?php echo $player['id']; ?>'">
                <?php echo htmlspecialchars($player['name']); ?>
            </button>
        <?php endforeach; ?>
    </div>
    </div>
</body>
</html>