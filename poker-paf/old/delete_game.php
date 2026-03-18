<?php
require_once 'db.php';
session_start(); // Obligatoire si tu veux toucher aux sessions
header('Content-Type: application/json');

// 1. Récupérer l'ID envoyé par le JavaScript ($_POST)
$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : null;

if ($game_id) {
    try {
        // Supprimer les joueurs associés d'abord (intégrité BDD)
        $stmt = $db->prepare("DELETE FROM players WHERE game_id = ?");
        $stmt->execute([$game_id]);

        // Supprimer la partie
        $stmt = $db->prepare("DELETE FROM games WHERE id = ?");
        $stmt->execute([$game_id]);

        // Nettoyer la session si besoin
        if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
            unset($_SESSION['game_id']);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de partie manquant.']);
}
exit;
?>