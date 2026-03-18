<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);

try {
    // 1. Récupérer l'ID du joueur actuel
    $stmt = $db->prepare("SELECT current_player_id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $current_player_id = $game['current_player_id'];

    // 2. Récupérer l'argent du joueur actuel
    $stmt = $db->prepare("SELECT money FROM players WHERE id = ?");
    $stmt->execute([$current_player_id]);
    $player = $stmt->fetch();
    $money = $player['money'];

    echo json_encode(['success' => true, 'money' => $money, 'player_id' => $current_player_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>