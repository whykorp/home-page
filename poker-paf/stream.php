<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$host = 'localhost'; $db = 'poker_paf'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$game_id = $_GET['game_id'] ?? 0;
// On stocke l'état précédent pour ne pas envoyer de doublons
$last_state_hash = "";

while (true) {
    // On récupère l'état global de la partie et des joueurs
    $stmt = $pdo->prepare("SELECT g.*, (SELECT COUNT(*) FROM players WHERE game_id = g.id AND is_folded=0) as active_count FROM games g WHERE g.id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $full_state = ['game' => $game, 'players' => $players];
    foreach ($players as &$player) {
        $player['id'] = (int)$player['id'];
        $player['money'] = (int)$player['money'];
        $player['current_bet'] = (int)$player['current_bet'];
        $player['is_dealer'] = (int)$player['is_dealer'];
        $player['is_folded'] = (int)$player['is_folded'];
    }
    unset($player); // Nettoyage de la référence
    $current_hash = md5(json_encode($full_state));

    // Si le hash a changé, c'est qu'une action a eu lieu (mise, tour suivant, fold...)
    if ($current_hash !== $last_state_hash) {
        echo "data: " . json_encode($full_state) . "\n\n";
        $last_state_hash = $current_hash;
    }

    ob_flush();
    flush();
    sleep(0.01); // On vérifie toutes les secondes
}