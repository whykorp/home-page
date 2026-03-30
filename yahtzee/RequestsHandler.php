<?php
// On commence par session_start AVANT tout envoi de texte
session_start();
header('Content-Type: application/json');

// Configuration BDD
$host = 'localhost';
$db   = 'yahtzee_paf';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, 
    PDO::ATTR_STRINGIFY_FETCHES  => false,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, $options);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Connexion échouée']);
    exit;
}

// Lecture de l'input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune action spécifiée']);
    exit;
}

$action = $data['action'];
$params = $data['params'] ?? [];

switch ($action) {
    case "":