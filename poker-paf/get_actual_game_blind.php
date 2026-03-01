<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);

try {
    // 1. Récupérer la blind de la partie
    $stmt = $db->prepare("SELECT last_bet FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $last_bet = $game['last_bet'];

    echo json_encode(['success' => true, 'blind' => $last_bet]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>