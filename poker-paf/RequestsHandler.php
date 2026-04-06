<?php
// On commence par session_start AVANT tout envoi de texte
session_start();
header('Content-Type: application/json');

// Configuration BDD
$host = 'localhost';
$db   = 'poker_paf';
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
    case 'getGame':
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([(int)$params['game_id']]);
        $game = $stmt->fetch();
        echo json_encode(['success' => true, 'game' => $game]);
        exit;

    case 'getPlayers':
        $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ?");
        $stmt->execute([(int)$params['game_id']]);
        $players = $stmt->fetchAll();
        echo json_encode(['success' => true, 'players' => $players]);
        exit;

    case 'createGame':
        $stmt = $pdo->prepare("INSERT INTO games (start_money, start_blind, name) VALUES (?, ?, ?)");
        $stmt->execute([(int)$params['start_money'], (int)$params['blind'], $params['name']]);
        echo json_encode(['success' => true, 'game_id' => $pdo->lastInsertId()]);
        exit;

    case 'addPlayer':
        $stmt = $pdo->prepare("INSERT INTO players (name, game_id, money) VALUES (?, ?, ?)");
        $stmt->execute([$params['name'], (int)$params['game_id'], (int)$params['money']]);
        echo json_encode(['success' => true]);
        exit;
    
    case 'setFirstPlayer':
        $game_id = (int)$params['game_id'];
    
        // 1. Récupérer le premier joueur (id le plus petit)
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($player) {
            $firstPlayerId = $player['id'];
    
            // 2. Définir ce joueur comme dealer
            $stmt = $pdo->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?");
            $stmt->execute([$firstPlayerId]);
    
            // 3. IMPORTANT : Définir aussi ce joueur comme "joueur actuel" dans la table games
            // pour que le jeu sache qui doit commencer à parler
            $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
            $stmt->execute([$firstPlayerId, $game_id]);
    
            echo json_encode([
                'success' => true, 
                'player_id' => $firstPlayerId
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Aucun joueur trouvé pour cette partie.'
            ]);
        }
        exit;

    case 'call':
        $game_id = $params['game_id'];
        $player_id = $params['player_id'];
    
        // 1. Récupérer la mise actuelle de la table
        $stmt = $pdo->prepare("SELECT last_bet FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $last_bet = $stmt->fetchColumn();
    
        // 2. Mettre à jour la mise du joueur (si c'est un call, il paye, si c'est un check, last_bet est 0)
        $stmt = $pdo->prepare("UPDATE players SET current_bet = ?, money = money - ? WHERE id = ?");
        $stmt->execute([$last_bet, $last_bet, $player_id]);
    
        // 3. Ajouter la mise au pot global
        $stmt = $pdo->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
        $stmt->execute([$last_bet, $game_id]);
    
        // 4. Passer au joueur suivant (ta fonction habituelle)
        moveToNextPlayer($game_id, $pdo);
    
        echo json_encode(['success' => true]);
        exit;

    case 'get_next_player':
        $game_id = (int)$params['game_id'];
        $current_id = (int)$params['current_player_id'];

        // On cherche le prochain joueur (ID plus grand, non couché, pas ruiné)
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_folded = 0 AND money > 0 AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id, $current_id]);
        $next = $stmt->fetch();

        if (!$next) {
            // Boucle : on revient au tout premier de la liste
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_folded = 0 AND money > 0 ORDER BY id ASC LIMIT 1");
            $stmt->execute([$game_id]);
            $next = $stmt->fetch();
        }

        if ($next) {
            echo json_encode(['success' => true, 'next_player_id' => $next['id']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Aucun joueur actif trouvé']);
        }

    case 'next_player':
        $game_id = (int)$params['game_id'];
        $current_id = (int)$params['current_player_id'];

        // On cherche le prochain joueur (ID plus grand, non couché, pas ruiné)
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_folded = 0 AND money > 0 AND id > ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$game_id, $current_id]);
        $next = $stmt->fetch();

        if (!$next) {
            // Boucle : on revient au tout premier de la liste
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_folded = 0 AND money > 0 ORDER BY id ASC LIMIT 1");
            $stmt->execute([$game_id]);
            $next = $stmt->fetch();
        }

        if ($next) {
            $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
            $stmt->execute([$next['id'], $game_id]);
            echo json_encode(['success' => true, 'next_player_id' => $next['id']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Aucun joueur actif trouvé']);
        }
        exit;
    
    case 'set_current_player':
        $game_id = (int)$params['game_id'];
        $player_id = (int)$params['player_id'];

        $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
        $stmt->execute([$player_id, $game_id]);
        echo json_encode(['success' => true]);

        exit;


    case 'fold':
        $stmt = $pdo->prepare("UPDATE players SET is_folded = 1 WHERE id = ?");
        $stmt->execute([(int)$params['player_id']]);
        echo json_encode(['success' => true]);
        exit;

    case 'raise':
        $game_id = (int)$params['game_id'];
        $player_id = (int)$params['player_id'];
        $bet_input = (int)$params['bet_input']; 

        $stmt = $pdo->prepare("SELECT last_bet FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $last_bet_table = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT money, current_bet FROM players WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();
        
        $target_bet = $last_bet_table + $bet_input;
        $to_withdraw = $target_bet - (int)$player['current_bet'];

        if ((int)$player['money'] < $to_withdraw) {
            echo json_encode(['success' => false, 'error' => 'Fonds insuffisants']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE players SET money = money - ?, current_bet = ? WHERE id = ?");
            $stmt->execute([$to_withdraw, $target_bet, $player_id]);
            $stmt = $pdo->prepare("UPDATE games SET pot = pot + ?, last_bet = ? WHERE id = ?");
            $stmt->execute([$to_withdraw, $target_bet, $game_id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'delta_bet' => $to_withdraw]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Erreur transaction']);
        }
        exit;

    case 'follow':
        $game_id = (int)$params['game_id'];
        $player_id = (int)$params['player_id'];

        $stmt = $pdo->prepare("SELECT last_bet FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $target = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT money, current_bet FROM players WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();
        
        $to_pay = $target - (int)$player['current_bet'];

        if ($to_pay > (int)$player['money']) {
            echo json_encode(['success' => false, 'error' => 'Pas assez pour suivre, faites Tapis !']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE players SET money = money - ?, current_bet = ? WHERE id = ?");
            $stmt->execute([$to_pay, $target, $player_id]);
            $stmt = $pdo->prepare("UPDATE games SET pot = pot + ? WHERE id = ?");
            $stmt->execute([$to_pay, $game_id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Erreur follow']);
        }
        exit;

        case 'all_in':
            $game_id = (int)$params['game_id'];
            $player_id = (int)$params['player_id'];
    
            try {
                $pdo->beginTransaction();
    
                // 1. Récupérer les jetons restants du joueur
                $stmt = $pdo->prepare("SELECT money, current_bet FROM players WHERE id = ?");
                $stmt->execute([$player_id]);
                $player = $stmt->fetch();
                $all_in_amount = (int)$player['money'];
                $new_player_bet = (int)$player['current_bet'] + $all_in_amount;
    
                // 2. Le joueur mise TOUT : money tombe à 0
                $stmt = $pdo->prepare("UPDATE players SET money = 0, current_bet = ? WHERE id = ?");
                $stmt->execute([$new_player_bet, $player_id]);
    
                // 3. Mise à jour de la table : on ajoute l'argent au pot
                // Et on met à jour le 'last_bet' SEULEMENT si le tapis est supérieur à la mise actuelle
                $stmt = $pdo->prepare("UPDATE games SET pot = pot + ?, last_bet = GREATEST(last_bet, ?) WHERE id = ?");
                $stmt->execute([$all_in_amount, $new_player_bet, $game_id]);
    
                $pdo->commit();
                echo json_encode(['success' => true, 'delta_bet' => $all_in_amount]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Erreur All-in']);
            }
            exit;
    
        case 'declare_winner':
            $game_id = (int)$params['game_id'];
            $winner_id = (int)$params['player_id'];
    
            try {
                $pdo->beginTransaction();
    
                // 1. Récupérer le pot total
                $stmt = $pdo->prepare("SELECT pot FROM games WHERE id = ?");
                $stmt->execute([$game_id]);
                $pot = (int)$stmt->fetchColumn();
    
                // 2. Donner le pot au gagnant et remettre ses stats à zéro pour le prochain tour
                $stmt = $pdo->prepare("UPDATE players SET money = money + ? WHERE id = ?");
                $stmt->execute([$pot, $winner_id]);
    
                // 3. Reset de la table (Pot et Mise à suivre)
                $stmt = $pdo->prepare("UPDATE games SET pot = 0, last_bet = 0 WHERE id = ?");
                $stmt->execute([$game_id]);
    
                // 4. Reset de TOUS les joueurs (Mises engagées et Fold) en une seule requête
                $stmt = $pdo->prepare("UPDATE players SET current_bet = 0, is_folded = 0 WHERE game_id = ?");
                $stmt->execute([$game_id]);
    
                // 5. Rotation du Dealer
                // On cherche le dealer actuel
                $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND is_dealer = 1 LIMIT 1");
                $stmt->execute([$game_id]);
                $current_dealer = $stmt->fetchColumn();
    
                if ($current_dealer) {
                    // On enlève l'ancien badge
                    $pdo->prepare("UPDATE players SET is_dealer = 0 WHERE id = ?")->execute([$current_dealer]);
                    
                    // On cherche le suivant (ID plus grand)
                    $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND id > ? AND money > 0 ORDER BY id ASC LIMIT 1");
                    $stmt->execute([$game_id, $current_dealer]);
                    $next_dealer = $stmt->fetchColumn();
    
                    // Si pas de suivant, on revient au premier
                    if (!$next_dealer) {
                        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND money > 0 ORDER BY id ASC LIMIT 1");
                        $stmt->execute([$game_id]);
                        $next_dealer = $stmt->fetchColumn();
                    }
    
                    $pdo->prepare("UPDATE players SET is_dealer = 1 WHERE id = ?")->execute([$next_dealer]);
                }
    
                $pdo->commit();
                echo json_encode(['success' => true, 'next_dealer_id' => $next_dealer, 'pot' => $pot]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la désignation du vainqueur']);
            }
            exit;
    
        case 'setup_blinds':
            $game_id = (int)$params['game_id'];
            $dealer_id = (int)$params['dealer_id'];
    
            try {
                $pdo->beginTransaction();
                // récupérer la blinde de la partie et le joueur après le dealer
                $stmt = $pdo->prepare("SELECT start_blind FROM games WHERE id = ?");
                $stmt->execute([$game_id]);
                $blind_amount = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND id > ? AND money > 0 ORDER BY id ASC LIMIT 1");
                $stmt->execute([$game_id, $dealer_id]);
                $postdealer_id = $stmt->fetchColumn();

                if (!$postdealer_id) {
                    $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND money > 0 ORDER BY id ASC LIMIT 1");
                    $stmt->execute([$game_id]);
                    $postdealer_id = $stmt->fetchColumn();
                }

                // update des blindes
                $small_blind_bet = $blind_amount / 2;

                $stmt = $pdo->prepare("UPDATE players SET current_bet = ?, money = money - ? WHERE id = ?");
                $stmt->execute([$small_blind_bet, $small_blind_bet, $postdealer_id]);

                $stmt = $pdo->prepare("UPDATE players SET current_bet = ?, money = money - ? WHERE id = ?");
                $stmt->execute([$blind_amount, $blind_amount, $dealer_id]);

                // update du pot
                $stmt = $pdo->prepare("UPDATE games SET pot = ?, last_bet = ? WHERE id = ?");
                $stmt->execute([$blind_amount + $small_blind_bet, $blind_amount, $game_id]);
    
                $pdo->commit();
                echo json_encode(['success' => true, 'postdealer_id' => $postdealer_id, 'dealer_id' => $dealer_id, 'blind_amount' => $blind_amount, 'small_blind' => $small_blind_bet]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la réinitialisation des blinds']);
            }
            exit;

        case 'add_money':
            $stmt = $pdo->prepare("UPDATE players SET money = money + ? WHERE id = ?");
            $stmt->execute([(int)$params['amount'], (int)$params['player_id']]);
            echo json_encode(['success' => true]);
            exit;
    
        case 'delete_game':
            $game_id = (int)$params['game_id'];
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM players WHERE game_id = ?")->execute([$game_id]);
                $pdo->prepare("DELETE FROM games WHERE id = ?")->execute([$game_id]);
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false]);
            }
            exit;
    
        case 'get_all_games':
            $stmt = $pdo->query("SELECT * FROM games ORDER BY id ASC");
            echo json_encode(['success' => true, 'games' => $stmt->fetchAll()]);
            exit;

        case 'set_player_loop':
            $game_id = (int)$params['game_id'];
            $player_id = (int)$params['player_id'];

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE games SET last_loop_player_id = ? WHERE id = ?");
            $stmt->execute([$player_id, $game_id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'player_id' => $player_id]);
            exit;    

        // Actions d'administration
        case 'adminLogin':
            $_SESSION['admin_logged_in'] = true;
            echo json_encode(['success' => true]);
            exit;
        
        case 'is_admin':
            echo json_encode([
                'success' => true, 
                'is_admin' => (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
            ]);
            exit;

        case 'toggle_lock':
            $game_id = (int)$params['game_id'];
            $status = (int)$params['status']; // 1 pour verrouillé, 0 pour ouvert
            
            $stmt = $pdo->prepare("UPDATE games SET is_locked = ? WHERE id = ?");
            $stmt->execute([$status, $game_id]);
            
            echo json_encode(['success' => true]);
            exit;
        
        case 'update_game_status':
            $game_id = $params['game_id'];
            $status = $params['status'];
    
            $stmt = $pdo->prepare("UPDATE games SET status = ? WHERE id = ?");
            $success = $stmt->execute([$status, $game_id]);
    
            echo json_encode(['success' => $success]);
            exit; // Important pour ne rien envoyer d'autre après
    
        // --- ACTION 1 : Changer uniquement le statut (ex: 'deciding', 'playing') ---
        case 'update_game_status':
            $game_id = $params['game_id'] ?? 0;
            $status = $params['status'] ?? '';

            $stmt = $pdo->prepare("UPDATE games SET status = ? WHERE id = ?");
            $success = $stmt->execute([$status, $game_id]);

            echo json_encode(['success' => $success]);
            exit;

        // --- ACTION 2 : Enregistrer le gagnant ---
        case 'set_winner':
            $game_id = $params['game_id'] ?? 0;
            $player_id = $params['player_id'] ?? 0;

            // Ici on ne change QUE le winner_id
            $stmt = $pdo->prepare("UPDATE games SET winner_id = ? WHERE id = ?");
            $success = $stmt->execute([$player_id, $game_id]);

            echo json_encode(['success' => $success]);
            exit;
        
    
        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
            exit;

        case 'log_submit':
            $action = $params['action'] ?? '';
            $game_id = (int)$params['game_id'];
            $player_id = (int)$params['player_id'];
            $parameters = $params['params'] ?? '';

            if ($action) {
                $stmt = $pdo->prepare("INSERT INTO logs (game_id, player_id, action, params) VALUES (?, ?, ?, ?)");
                $stmt->execute([$game_id, $player_id, $action, $parameters]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Pas résussi à enregistrer le log']);
            }
            exit;

        case 'log_revert':
            $game_id = (int)$params['game_id'];

            // Récupérer le log
            $stmt = $pdo->prepare("SELECT * FROM logs WHERE game_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$game_id]);
            $log = $stmt->fetch();

            if ($log) {
                $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
                $stmt->execute([$log['id']]);
                echo json_encode(['success' => true, 'log' => $log]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Log non trouvé']);
            }
            exit;

        case 'revert_money_modification':
            $game_id = (int)$params['game_id'];
            $player_id = (int)$params['player_id'];
            $amount = (int)$params['amount'];

            try {
                $pdo->beginTransaction();

                // 1. Rembourser le joueur
                $stmt = $pdo->prepare("UPDATE players SET money = money + ? WHERE id = ?");
                $stmt->execute([$amount, $player_id]);

                // 2. Revenir sur la mise du joueur
                $stmt = $pdo->prepare("UPDATE players SET current_bet = current_bet - ? WHERE id = ?");
                $stmt->execute([$amount, $player_id]);
            
                // 3. Retirer l'argent du pot
                $stmt = $pdo->prepare("UPDATE games SET pot = pot - ? WHERE id = ?");
                $stmt->execute([$amount, $game_id]);

                // 4. Recalculer le last_bet de la table (en prenant le max des current_bet des joueurs)
                $stmt = $pdo->prepare("UPDATE games SET last_bet = (SELECT MAX(current_bet) FROM players WHERE game_id = ?) WHERE id = ?");
                $stmt->execute([$game_id, $game_id]);

                // 5. Revenir au joueur qui a fait la modification
                $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
                $stmt->execute([$player_id, $game_id]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la réversion du log']);
            }
            exit;

        case 'unfold':
            $player_id = (int)$params['player_id'];

            try {
                $stmt = $pdo->prepare("UPDATE players SET is_folded = 0 WHERE id = ?");
                $stmt->execute([$player_id]);

                $stmt = $pdo->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
                $stmt->execute([$player_id, $game_id]);

                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'unfold']);
            }
            exit;
    }