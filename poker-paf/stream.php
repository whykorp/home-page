<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$host = 'localhost'; $db = 'poker_paf'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$game_id = $_GET['game_id'] ?? 0;
$last_state_hash = "";

while (true) {
    // 1. Récupérer les données de la partie
    $stmt = $pdo->prepare("SELECT g.*, (SELECT COUNT(*) FROM players WHERE game_id = g.id AND is_folded=0) as active_count FROM games g WHERE g.id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Récupérer les données des joueurs
    $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Typage propre AVANT de mettre dans le full_state
    foreach ($players as &$player) {
        $player['id'] = (int)$player['id'];
        $player['money'] = (int)$player['money'];
        $player['current_bet'] = (int)$player['current_bet'];
        $player['is_dealer'] = (int)$player['is_dealer'];
        $player['is_folded'] = (int)$player['is_folded'];
    }
    unset($player);

    // 4. Construction de l'objet final
    $full_state = [
        'game' => $game, 
        'players' => $players
    ];

    // 5. Calcul du hash sur l'objet finalisé
    $current_hash = md5(json_encode($full_state));

    if ($current_hash !== $last_state_hash) {
        echo "data: " . json_encode($full_state) . "\n\n";
        $last_state_hash = $current_hash;
        
        // On force l'envoi
        ob_flush();
        flush();
    }

    // 6. Pause raisonnable (500ms) pour ne pas tuer le CPU/Base de données
    sleep(0.01); 
}