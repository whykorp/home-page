<?php
require_once 'db.php';

// Création d'un partie
$start_money = $_POST['start_money'];
$start_blind = $_POST['blind'];
    
$stmt = $db->prepare("INSERT INTO games (start_money, start_blind) VALUES (:start_money, :start_blind)");
$stmt->execute([
    ':start_money' => $start_money,
    ':start_blind' => $start_blind
]);

// Récupération de l'ID de la partie créée
$game_id = $db->lastInsertId();

// Création des joueurs
$players = $_POST['players'];
foreach ($players as $player_name) {
    $stmt = $db->prepare("INSERT INTO players (name, game_id, money) VALUES (:name, :game_id, :start_money)");
    $stmt->execute([
        ':name' => $player_name,
        ':game_id' => $game_id,
        ':start_money' => $start_money
    ]);
}

// Redirection vers la page de jeu
header("Location: game.php?game_id=$game_id");
session_start();
$_SESSION['game_id'] = $game_id; // Stocker l'ID de la partie dans la session pour y accéder plus tard
exit();
?>