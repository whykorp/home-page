<?php
require_once 'db.php';
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);

try {
    // 1. On récupère tous les joueurs
    $stmt = $db->prepare("SELECT id, is_dealer FROM players WHERE game_id = ? ORDER BY id ASC");
    $stmt->execute([$game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($players) == 0) throw new Exception("Pas de joueurs");

    // 2. On trouve l'ancien dealer pour désigner le nouveau
    $oldDealerIndex = -1;
    foreach ($players as $index => $p) {
        if ($p['is_dealer'] == 1) { $oldDealerIndex = $index; break; }
    }

    $nextDealerIndex = ($oldDealerIndex + 1) % count($players);
    $newDealerId = $players[$nextDealerIndex]['id'];

    // 3. Qui doit parler en premier ? (Normalement c'est celui après le Dealer)
    $firstPlayerIndex = ($nextDealerIndex + 1) % count($players);
    $firstPlayerId = $players[$firstPlayerIndex]['id'];

    // 4. MISE À JOUR DE LA BDD
    // On reset les dealers
    $db->prepare("UPDATE players SET is_dealer = 0 WHERE game_id = ?")->execute([$game_id]);
    // On met le nouveau dealer
    $db->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?")->execute([$newDealerId]);
    // On reset le POT, la MISE et on définit le JOUEUR ACTIF
    $db->prepare("UPDATE games SET pot = 0, last_bet = 0, current_player_id = ? WHERE id = ?")
       ->execute([$firstPlayerId, $game_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}