<?php
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'poker_paf';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // C'est cette ligne qui fait la différence :
    PDO::ATTR_EMULATE_PREPARES   => false, 
    PDO::ATTR_STRINGIFY_FETCHES  => false,
];

// charger la BDD
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(['error' => 'Connexion échouée']));
}

// lire les données JSON envoyées par le client
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['error' => 'Aucune action spécifiée']);
    exit;
}

$action = $data['action'];
$params = $data['params'] ?? [];

$response = [];

switch ($action) {
    case 'getGame':
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$params['game_id']]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($game) {
            $response = ['success' => true, 'game' => $game];
        } else {
            $response = ['error' => 'Partie non trouvée'];
        }
        break;

    case 'getPlayers':
        $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
        $stmt->execute([$params['game_id']]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'players' => $players];
        break;

    case 'createGame':
        $stmt = $pdo->prepare("INSERT INTO games (start_money, start_blind, name) VALUES (?, ?, ?)");
        $stmt->execute([$params['start_money'], $params['blind'], $params['name']]);
        $game_id = $pdo->lastInsertId();
        $response = ['success' => true, 'game_id' => $game_id];
        break;

    case 'addPlayer':
        $stmt = $pdo->prepare("INSERT INTO players (name, game_id, money) VALUES (?, ?, ?)");
        $stmt->execute([$params['name'], $params['game_id'], $params['money']]);
        $player_id = $pdo->lastInsertId();
        $response = ['success' => true];
        break;

    case 'setFirstPlayer':
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$params['game_id']]);
        $first_player = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($first_player) {
            $first_player_id = $first_player['id'];
            $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
            $stmt->execute([$first_player_id, $params['game_id']]);
            $stmt = $pdo->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?");
            $stmt->execute([$first_player_id]);
            $response = ['success' => true];
        } else {
            $response = ['error' => 'Aucun joueur trouvé pour cette partie'];
        }
        break;

    case 'next_player':
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_folded = 0 AND id > ? AND money <> 0 ORDER BY id ASC LIMIT 1");
        $stmt->execute([$params['game_id'], $params['current_player_id']]);
        $next_player = $stmt->fetch();

        if (!$next_player) { // Si on est au dernier, on revient au premier pas couché
            $stmt = $pdo->prepare("SELECT id FROM players WHERE is_folded = 0 AND game_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$params['game_id']]);
            $next_player = $stmt->fetch();
        }

        // 3. Mise à jour de la BDD
        $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
        $stmt->execute([$next_player['id'], $params['game_id']]);

        $response = (['success' => true, 'next_player_id' => $next_player['id']]);
        break;

    case 'set_current_player':
        $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
        $stmt->execute([$params['player_id'], $params['game_id']]);
        $response = ['success' => true];
        break;

    case 'fold':
        $stmt = $pdo->prepare("UPDATE players SET is_folded = 1 WHERE id = ?");
        $stmt->execute([$params['player_id']]);
        $response = ['success' => true];
        break;

    case 'raise':
        $stmt = $pdo->prepare("UPDATE players SET money = money - ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['player_id']]);

        $stmt = $pdo->prepare("UPDATE players SET current_bet = current_bet + ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['player_id']]);

        $stmt = $pdo->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['game_id']]);

        $stmt = $pdo->prepare("UPDATE games SET last_bet = ? WHERE id = ?");
        $stmt->execute([$params['amount'] + $params['current_bet'], $params['game_id']]);

        $response = ['success' => true];
        break;

    case 'follow':
        $stmt = $pdo->prepare("UPDATE players SET money = money - ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['player_id']]);

        $stmt = $pdo->prepare("UPDATE players SET current_bet = current_bet + ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['player_id']]);

        $stmt = $pdo->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['game_id']]);
        
        $response = ['success' => true];
        break;

    case 'all_in':
        $stmt = $pdo->prepare("SELECT money FROM players WHERE id = ?");
        $stmt->execute([$params['player_id']]);
        $money = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE players SET money = 0, current_bet = current_bet + ? WHERE id = ?");
        $stmt->execute([$money, $params['player_id']]);

        $stmt = $pdo->prepare("UPDATE games SET pot = pot + ?, last_bet = last_bet + ? WHERE id = ?");
        $stmt->execute([$money, $money, $params['game_id']]);

        $response = ['success' => true];
        break;

    case 'declare_winner':
        $stmt = $pdo->prepare("SELECT pot FROM games WHERE id = ?");
        $stmt->execute([$params['game_id']]);
        $pot = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE games SET pot = 0, last_bet = 0 WHERE id = ?");
        $stmt->execute([$params['game_id']]);

        $stmt = $pdo->prepare("UPDATE players SET money = money + ? WHERE id = ?");
        $stmt->execute([$pot, $params['player_id']]);

        $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
        $stmt->execute([$params['game_id']]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($players as $player) {
            $stmt = $pdo->prepare("UPDATE players SET current_bet = 0, is_folded = 0 WHERE id = ?");
            $stmt->execute([$player['id']]);
        }

        $stmt = $pdo->prepare("SELECT id FROM players WHERE is_dealer = 1 AND game_id = ?");
        $stmt->execute([$params['game_id']]);
        $current_dealer = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE players SET is_dealer = 0 WHERE id = ?");
        $stmt->execute([$current_dealer]);
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$params['game_id'], $current_dealer]);
        $next_dealer = $stmt->fetchColumn();
        if (!$next_dealer) {
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$params['game_id']]);
            $next_dealer = $stmt->fetchColumn();
        }
        $stmt = $pdo->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?");
        $stmt->execute([$next_dealer]);
        
        $response = ['success' => true];
        break;

    case 'add_money':
        $stmt = $pdo->prepare("UPDATE players SET money = money + ? WHERE id = ?");
        $stmt->execute([$params['amount'], $params['player_id']]);
        $response = ['success' => true];
        break;

    case 'delete_game':
        try {
            // Supprimer les joueurs associés d'abord (intégrité BDD)
            $stmt = $pdo->prepare("DELETE FROM players WHERE game_id = ?");
            $stmt->execute([$params['game_id']]);
    
            // Supprimer la partie
            $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
            $stmt->execute([$params['game_id']]);
    
            $response = ['success' => true];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        break;

    case 'get_all_games':
        $stmt = $pdo->query("SELECT * FROM games ORDER BY id ASC");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'games' => $games];
        break;

    case 'adminLogin':
        // On vérifie si l'utilisateur est déjà admin
        session_start();
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $response = ['success' => true, 'message' => 'Déjà connecté en tant qu\'admin'];
            break;
        }
        // On dit que l'utilisateur est admin
        $_SESSION['admin_logged_in'] = true;
        $response = ['success' => true, 'message' => 'Connexion admin réussie'];
        break;
    
    case 'is_admin':
        session_start();
        $response = ['is_admin' => isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true];
        break;

    default:
        $response = ['error' => 'Action inconnue'];
}

echo json_encode($response);

?>