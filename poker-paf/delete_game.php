<?php

require_once 'db.php';

    // On récupère l'ID envoyé en POST, sinon on prend la dernière partie créée
    $game_id = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : null;
    log("ID de la partie à supprimer : " . $game_id);

    if ($game_id) {
        $stmt = $db->prepare("DELETE FROM games WHERE games.id = ?");
        $stmt->execute([$game_id]);
    } else {
        echo "Aucune partie à supprimer.";
        exit;
    }

    echo "La partie a bien été supprimée de la table poker_paf.";

?>