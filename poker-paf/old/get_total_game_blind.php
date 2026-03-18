<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);

try {
    // 1. Récupérer la blind de la partie
    $stmt = $db->prepare("SELECT pot FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $pot = $game['pot'];

    echo json_encode(['success' => true, 'total_blind' => $pot]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>