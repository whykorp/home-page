<?php
// Désactiver l'affichage des erreurs en sortie pour ne pas polluer le JSON
error_reporting(0); 
ini_set('display_errors', 0);

require_once 'db.php';
header('Content-Type: application/json');

$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
$winner_id = isset($_POST['winner_id']) ? intval($_POST['winner_id']) : 0;

try {
    if ($game_id === 0 || $winner_id === 0) {
        throw new Exception("ID de partie ou de joueur invalide.");
    }

    // 1. On récupère le pot de la table et le nom du gagnant
    $stmt = $db->prepare("SELECT pot FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT name FROM players WHERE id = ?");
    $stmt->execute([$winner_id]);
    $player = $stmt->fetch();

    if (!$game || !$player) {
        throw new Exception("Données introuvables en base.");
    }

    $total_pot = intval($game['pot']);
    $winner_name = $player['name'];

    // 2. On effectue les mises à jour
    $db->beginTransaction();

    // Ajouter l'argent au gagnant
    $stmt = $db->prepare("UPDATE players SET money = money + ? WHERE id = ?");
    $stmt->execute([$total_pot, $winner_id]);

    // Vider le pot de la partie et reset la mise actuelle
    $stmt = $db->prepare("UPDATE games SET pot = 0, last_bet = 0 WHERE id = ?");
    $stmt->execute([$game_id]);

    // Reset les mises individuelles
    $stmt = $db->prepare("UPDATE players SET current_bet = 0 WHERE game_id = ?");
    $stmt->execute([$game_id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'winner_name' => $winner_name,
        'amount_won' => $total_pot
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;