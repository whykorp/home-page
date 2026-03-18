<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
try {
    // 1. Changer la blind de la partie
    $stmt = $db->prepare("UPDATE games SET last_bet = ? WHERE id = ?");
    $stmt->execute([$amount, $game_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>