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

// Définir le premier joueur comme dealer
$stmt = $db->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$game_id]);
$first_player = $stmt->fetch();

$first_player_id = $first_player['id'];

$stmt = $db->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
$stmt->execute([$first_player_id, $game_id]);

$stmt = $db->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?");
$stmt->execute([$first_player_id]);

// Redirection vers la page de jeu
header("Location: game.php?game_id=$game_id");
session_start();
$_SESSION['game_id'] = $game_id; // Stocker l'ID de la partie dans la session pour y accéder plus tard
exit();
?>