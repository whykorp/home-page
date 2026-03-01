<?php
require_once 'db.php'; // Ta connexion PDO
header('Content-Type: application/json');

$game_id = intval($_POST['game_id']);
$action = $_POST['action'];
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;

try {
    // 1. Récupérer l'ID du joueur actuel
    $stmt = $db->prepare("SELECT current_player_id FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    $current_player_id = $game['current_player_id'];

    // 2. Logique de changement de joueur (Simplifiée)
    $stmt = $db->prepare("SELECT id FROM players WHERE game_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$game_id, $current_player_id]);
    $next_player = $stmt->fetch();

    if (!$next_player) { // Si on est au dernier, on revient au premier
        $stmt = $db->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id]);
        $next_player = $stmt->fetch();
    }

    $next_id = $next_player['id'];

    // 3. Mise à jour de la BDD
    $stmt = $db->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
    $stmt->execute([$next_id, $game_id]);

    echo json_encode(['success' => true, 'next_player_id' => $next_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}