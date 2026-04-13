<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURATION BDD ---
$host = 'localhost';
$db   = 'watchgether';
$user = 'root';
$pass = ''; // Vide par défaut sur Wamp

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, 
];

$apiKey = '23af653f99d2e7ac884415805e7ca84c';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, $options);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Connexion BDD échouée']);
    exit;
}

// --- LECTURE DE L'INPUT ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune action spécifiée']);
    exit;
}

$action = $data['action'];
$params = $data['params'] ?? [];

// Simulation d'un utilisateur connecté (à remplacer par ton système de login plus tard)
$current_user_id = $_SESSION['user_id'] ?? 1; 

switch ($action) {

    // --- RECHERCHE TMDB ---
    case 'searchTMDB':
        $query = urlencode($params['query']);
        $url = "https://api.themoviedb.org/3/search/multi?api_key=$apiKey&language=fr-FR&query=$query";
        
        $response = file_get_contents($url);
        if ($response) {
            echo $response; // On renvoie directement le JSON de TMDB au front
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur API TMDB']);
        }
        exit;

    // --- GESTION DES FILMS ---
    case 'addMovie':
        $stmt = $pdo->prepare("INSERT INTO movies (tmdb_id, titre, affiche_path, type, user_id, vu) VALUES (?, ?, ?, ?, ?, 0)");
        $success = $stmt->execute([
            $params['tmdb_id'],
            $params['titre'],
            $params['affiche_path'],
            $params['type'], // 'film' ou 'serie'
            $current_user_id
        ]);
        echo json_encode(['success' => $success]);
        exit;

    case 'getMyList':
        // Récupère les films ajoutés par l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE user_id = ? ORDER BY date_ajout DESC");
        $stmt->execute([$current_user_id]);
        echo json_encode(['success' => true, 'movies' => $stmt->fetchAll()]);
        exit;

    case 'getCommonList':
        // LA MAGIE : On cherche les doublons de tmdb_id entre deux utilisateurs
        // On part du principe que tu es l'ID 1 et ta copine l'ID 2 (à adapter)
        $partner_id = ($current_user_id == 1) ? 2 : 1; 

        $stmt = $pdo->prepare("
            SELECT m1.* FROM movies m1
            INNER JOIN movies m2 ON m1.tmdb_id = m2.tmdb_id
            WHERE m1.user_id = ? AND m2.user_id = ?
        ");
        $stmt->execute([$current_user_id, $partner_id]);
        echo json_encode(['success' => true, 'common_movies' => $stmt->fetchAll()]);
        exit;

    // --- ACTIONS SUR LE FILM ---
    case 'toggleViewed':
        // Alterne entre vu (1) et non vu (0)
        $stmt = $pdo->prepare("UPDATE movies SET vu = !vu WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([(int)$params['movie_id'], $current_user_id]);
        echo json_encode(['success' => $success]);
        exit;

    case 'deleteMovie':
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([(int)$params['movie_id'], $current_user_id]);
        echo json_encode(['success' => $success]);
        exit;

    // --- COMMENTAIRES ---
    case 'addComment':
        $stmt = $pdo->prepare("INSERT INTO commentaires (movie_id, user_id, contenu) VALUES (?, ?, ?)");
        $success = $stmt->execute([
            (int)$params['movie_id'],
            $current_user_id,
            $params['text']
        ]);
        echo json_encode(['success' => $success]);
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        exit;
}