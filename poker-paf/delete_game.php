<?php

require_once 'db.php';

    // On récupère l'ID envoyé en POST, sinon on prend la dernière partie créée
    $game_id = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : null;

    if ($game_id) {
        $stmt = $db->prepare("DELETE FROM games WHERE id = :id");
        $stmt->execute([':id' => $game_id]);
    } else {
        // Mode "Nettoyage" : on supprime la toute dernière entrée
        $stmt = $db->prepare("DELETE FROM games ORDER BY id DESC LIMIT 1");
        $stmt->execute();
    }

    echo "La partie a bien été supprimée de la table poker_paf.";

?>