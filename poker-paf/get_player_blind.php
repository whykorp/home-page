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

    // 2. Récupérer la blind du joueur actuel
    $stmt = $db->prepare("SELECT current_bet FROM players WHERE id = ?");
    $stmt->execute([$current_player_id]);
    $player = $stmt->fetch();
    $blind = $player['current_bet'];

    echo json_encode(['success' => true, 'blind' => $blind, 'player_id' => $current_player_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>