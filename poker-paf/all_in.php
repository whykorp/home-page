<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$current_player_id = intval($_POST['current_player_id']);

try {
    // 1. Récupérer l'argent du joueur actuel
    $stmt = $db->prepare("SELECT money FROM players WHERE id = ?");
    $stmt->execute([$current_player_id]);
    $player = $stmt->fetch();
    $money = $player['money'];

    // 2. Mettre à jour la blind du joueur actuel avec tout son argent
    $stmt = $db->prepare("UPDATE players SET current_bet = ? WHERE id = ?");
    $stmt->execute([$money, $current_player_id]);
    
    // 3. Retirer tout l'argent du joueur actuel
    $stmt = $db->prepare("UPDATE players SET money = 0 WHERE id = ?");
    $stmt->execute([$current_player_id]);

    // 4. Ajouter le montant au pot de la partie
    $stmt = $db->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
    $stmt->execute([$money, $game_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>