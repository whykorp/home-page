<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);

try {

    // 1. Vérifier que la partie existe
    $stmt = $db->prepare("SELECT id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    if (!$stmt->fetch()) {
        throw new Exception("La partie n'existe pas.");
    }

    // 2. Faire en sorte que le dealer de la partie soit le joueur suivant l'actuel dealer
    $stmt = $db->prepare("SELECT id FROM player WHERE is_dealer = 1 AND game_id = ?");
    $stmt->execute([$game_id]);
    $current_dealer = $stmt->fetch();
    if ($current_dealer) {
        $current_dealer_id = $current_dealer['id'];
        // On remet à 0 le dealer actuel
        $stmt = $db->prepare("UPDATE player SET is_dealer = 0 WHERE id = ?");
        $stmt->execute([$current_dealer_id]);
        // On cherche le prochain dealer
        $stmt = $db->prepare("SELECT id FROM player WHERE game_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id, $current_dealer_id]);
        $next_dealer = $stmt->fetch();
        if (!$next_dealer) { // Si on est au dernier, on revient au premier
            $stmt = $db->prepare("SELECT id FROM player WHERE game_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$game_id]);
            $next_dealer = $stmt->fetch();
        }
        $next_dealer_id = $next_dealer['id'];
        // On met à jour le nouveau dealer
        $stmt = $db->prepare("UPDATE player SET is_dealer = 1 WHERE id = ?");
        $stmt->execute([$next_dealer_id]);
    } else {
        // Si aucun dealer n'est défini, on choisit le premier joueur comme dealer
        $stmt = $db->prepare("SELECT id FROM player WHERE game_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id]);
        $first_player = $stmt->fetch();
        if ($first_player) {
            $first_player_id = $first_player['id'];
            $stmt = $db->prepare("UPDATE player SET is_dealer = 1 WHERE id = ?");
            $stmt->execute([$first_player_id]);
        } else {
            throw new Exception("Aucun joueur dans la partie pour devenir dealer.");
        }

        


    } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>