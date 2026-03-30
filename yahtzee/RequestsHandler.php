<?php
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
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, $options);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Connexion échouée']);
    exit;
}

// Lecture de l'input JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune action spécifiée']);
    exit;
}

$action = $data['action'];
$params = $data['params'] ?? [];

switch ($action) {

    // --- INITIALISATION ---
    
    case 'createGame':
        // On insère la game avec un admin_id temporaire à 0
        $stmt = $pdo->prepare("INSERT INTO games (game_name, admin_id) VALUES (?, 0)");
        $stmt->execute([$params['game_name']]);
        $game_id = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'game_id' => $game_id]);
        exit;

    case 'addPlayer':
        // Ajout d'un joueur avec sa couleur et définition du tour
        $stmt = $pdo->prepare("INSERT INTO players (game_id, player_name, player_color, is_turn) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            (int)$params['game_id'], 
            $params['name'], 
            $params['color'], 
            (int)$params['is_turn']
        ]);
        $player_id = $pdo->lastInsertId();

        // Si c'est le premier joueur ajouté, on le définit comme admin de la game
        if (isset($params['is_admin']) && $params['is_admin'] === true) {
            $update = $pdo->prepare("UPDATE games SET admin_id = ? WHERE id = ?");
            $update->execute([$player_id, (int)$params['game_id']]);
            $_SESSION['current_player_id'] = $player_id; // On stocke en session qui est l'admin
        }

        echo json_encode(['success' => true, 'player_id' => $player_id]);
        exit;

    // --- RÉCUPÉRATION ---

    case 'getGameData':
        $game_id = (int)$params['game_id'];
        
        // 1. Infos Game
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        // 2. Infos Players
        $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ? ORDER BY id ASC");
        $stmt->execute([$game_id]);
        $players = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'game' => $game, 
            'players' => $players,
            'session_player_id' => $_SESSION['current_player_id'] ?? null
        ]);
        exit;

    case 'get_all_games':
        // Liste pour l'index
        $stmt = $pdo->query("SELECT g.*, (SELECT COUNT(*) FROM players WHERE game_id = g.id) as nb_players FROM games g");
        echo json_encode(['success' => true, 'games' => $stmt->fetchAll()]);
        exit;

    // --- GAMEPLAY & SCORES ---

    case 'saveScore':
        // $params contient 'player_id', 'column' (ex: score_brelan), et 'value'
        $column = $params['column'];
        $allowed_columns = [
            'score_1','score_2','score_3','score_4','score_5','score_6',
            'score_brelan','score_carre','score_full','score_petite_suite',
            'score_grande_suite','score_yahtzee','score_chance'
        ];

        if (!in_array($column, $allowed_columns)) {
            echo json_encode(['success' => false, 'error' => 'Colonne invalide']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE players SET $column = ? WHERE id = ?");
        $stmt->execute([(int)$params['value'], (int)$params['player_id']]);
        
        // On recalcule le total automatiquement ici ou en JS plus tard
        echo json_encode(['success' => true]);
        exit;

    case 'nextTurn':
        $game_id = (int)$params['game_id'];
        $current_player_id = (int)$params['player_id'];

        // 1. Retirer le tour au joueur actuel
        $pdo->prepare("UPDATE players SET is_turn = 0 WHERE id = ?")->execute([$current_player_id]);

        // 2. Trouver le suivant
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id, $current_player_id]);
        $next = $stmt->fetchColumn();

        if (!$next) {
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$game_id]);
            $next = $stmt->fetchColumn();
        }

        $pdo->prepare("UPDATE players SET is_turn = 1 WHERE id = ?")->execute([$next]);
        echo json_encode(['success' => true, 'next_player_id' => $next]);
        exit;

    // --- ADMIN ---

    case 'delete_game':
        $game_id = (int)$params['game_id'];
        $player_id = $_SESSION['current_player_id'] ?? 0;

        // Vérification admin
        $stmt = $pdo->prepare("SELECT admin_id FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $admin_id = $stmt->fetchColumn();

        if ($admin_id == $player_id) {
            $pdo->prepare("DELETE FROM games WHERE id = ?")->execute([$game_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Action réservée à l\'admin']);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        exit;
}