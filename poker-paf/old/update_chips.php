<?php
require_once 'db.php'; // On n'appelle plus config.php qui pollue avec son HTML
header('Content-Type: application/json');

try {
    if (!isset($_POST['player_id'], $_POST['amount'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }

    $player_id = intval($_POST['player_id']);
    $amount = intval($_POST['amount']);

    // On met à jour
    $stmt = $db->prepare("UPDATE players SET money = money + :amount WHERE id = :id");
    $stmt->execute([
        ':amount' => $amount,
        ':id' => $player_id
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>