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

    // 3. Mettre à jour la blind du joueur actuel avec tout son argent
    $stmt = $db->prepare("UPDATE players SET current_bet = ? WHERE id = ?");
    $stmt->execute([$money, $current_player_id]);
    
    // 4. Retirer tout l'argent du joueur actuel
    $stmt = $db->prepare("UPDATE players SET money = 0 WHERE id = ?");
    $stmt->execute([$current_player_id]);

    // 5. Ajouter le montant au pot de la partie
    $stmt = $db->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
    $stmt->execute([$money, $game_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>