<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
try {
    // 1. Récupérer l'ID du joueur actuel
    $stmt = $db->prepare("SELECT current_player_id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $current_player_id = $game['current_player_id'];

    // 2. Ajouter de l'argent au joueur actuel
    $stmt = $db->prepare("UPDATE players SET money = money + ? WHERE id = ?");
    $stmt->execute([$amount, $current_player_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>