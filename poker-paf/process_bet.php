<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$amount = intval($_POST['amount']);

try {
    $db->beginTransaction();

    // 1. Récupérer l'ID du joueur actuel
    $stmt = $db->prepare("SELECT current_player_id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $player_id = $game['current_player_id'];

    // 2. Retirer l'argent au joueur
    $stmt = $db->prepare("UPDATE players SET money = money - ? WHERE id = ?");
    $stmt->execute([$amount, $player_id]);

    // 3. Ajouter l'argent au POT de la partie
    $stmt = $db->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
    $stmt->execute([$amount, $game_id]);

    // 4. Mettre à jour la mise actuelle (last_bet) pour les suivants
    $stmt = $db->prepare("UPDATE games SET last_bet = ? WHERE id = ?");
    $stmt->execute([$amount, $game_id]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}