<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$winner_id = intval($_POST['winner_id']);

try {

    // 1. Vérifier que le joueur est bien dans la partie
    $stmt = $db->prepare("SELECT id FROM players WHERE game_id = ? AND id = ?");
    $stmt->execute([$game_id, $winner_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Le joueur n'est pas dans cette partie.");
    }

    // 2. Récupérer le pot total de la partie
    require_once 'get_total_game_blind.php';
    $total_pot = get_total_game_blind($game_id);

    // 3. Mettre à jour le solde du gagnant
    $stmt = $db->prepare("UPDATE players SET money = money + ? WHERE id = ?");
    $stmt->execute([$total_pot, $winner_id]);

    // 4. Remettre à 0 le pot total de la partie
    $stmt = $db->prepare("UPDATE games SET pot = 0 WHERE id = ?");
    $stmt->execute([$game_id]);

    // 5. Remettre à 0 les mises de tous les joueurs
    $stmt = $db->prepare("UPDATE players SET current_bet = 0 WHERE game_id = ?");
    $stmt->execute([$game_id]);

    // 6. Remettre à 0 la blind actuelle de la partie
    $stmt = $db->prepare("UPDATE games SET last_bet = 0 WHERE id = ?");
    $stmt->execute([$game_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>