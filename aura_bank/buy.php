<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

// CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Token CSRF invalide.');
}

// Vérif login
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Tu dois être connecté pour acheter.');
}

$user_id = (int)$_SESSION['user_id'];
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

$pdo = pdo_connect();

try {
    $pdo->beginTransaction();

    // Vérifie si le user possède déjà un item
    $stmt = $pdo->prepare("SELECT ui.id, i.name 
                           FROM user_items ui 
                           JOIN items i ON ui.item_id = i.id
                           WHERE ui.user_id = :uid 
                           LIMIT 1 FOR UPDATE");
    $stmt->execute([':uid' => $user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->rollBack();
        exit("Tu possèdes déjà un item actif : " . htmlspecialchars($existing['name']));
    }

    // Récupère item
    $stmt = $pdo->prepare("SELECT id, name, price FROM items WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $item_id]);
    $item = $stmt->fetch();
    if (!$item) {
        $pdo->rollBack();
        exit('Item introuvable.');
    }

    $total = (int)$item['price'];

    // Vérifie aura
    $stmt = $pdo->prepare("SELECT aura FROM users WHERE id = :uid FOR UPDATE");
    $stmt->execute([':uid' => $user_id]);
    $u = $stmt->fetch();
    if (!$u) {
        $pdo->rollBack();
        exit('Utilisateur introuvable.');
    }
    $aura = (int)$u['aura'];

    if ($aura < $total) {
        $pdo->rollBack();
        exit('Tu n\'as pas assez d\'aura pour cet achat.');
    }

    // Débite l'aura
    $stmt = $pdo->prepare("UPDATE users SET aura = aura - :amt WHERE id = :uid");
    $stmt->execute([':amt' => $total, ':uid' => $user_id]);

    // Ajoute item à user_items
    $stmt = $pdo->prepare("INSERT INTO user_items (user_id, item_id) VALUES (:uid, :iid)");
    $stmt->execute([':uid' => $user_id, ':iid' => $item_id]);

    // Log (version simplifiée, sans actor_discord_id ni type)
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, amount, reason) 
                           VALUES (:uid, :amount, :reason)");
    $stmt->execute([
        ':uid' => $user_id,
        ':amount' => -$total,
        ':reason' => 'Achat: ' . $item['name']
    ]);

    $pdo->commit();

    header('Location: shop.php?buy=ok');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Erreur détaillée : " . $e->getMessage();
    var_dump($item_id, $user_id, $total, $aura);
    exit;
}
