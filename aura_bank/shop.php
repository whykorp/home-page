<?php
require_once __DIR__ . '/config.php';   // contient pdo_connect() et session start
require_once __DIR__ . '/functions.php'; // si nécessaire

// Vérifie session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = pdo_connect();

// Récupère les items
$stmt = $pdo->query("SELECT id, name, description, price FROM items ORDER BY price ASC");
$items = $stmt->fetchAll();

// Génère token CSRF simple
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Récupère saldo de l'utilisateur si connecté
$logged = isset($_SESSION['user_id']);
$userAura = null;
if ($logged) {
    $stmt = $pdo->prepare("SELECT aura FROM users WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $row = $stmt->fetch();
    $userAura = $row ? (int)$row['aura'] : 0;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Boutique — Banque de l'Aura</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;color:#222;padding:20px}
    .card{max-width:1000px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    h1{margin:0 0 12px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
    .item{border:1px solid #eee;padding:12px;border-radius:8px;background:#fafafa;display:flex;flex-direction:column;gap:8px}
    .badge{width:32px;height:32px;object-fit:cover}
    .item-icon{width:32px;height:32px;object-fit:cover;vertical-align:middle;margin-right:8px}
    .price{font-weight:700}
    .stock{font-size:13px;color:#666}
    .buy-form{margin-top:auto}
    .btn{display:inline-block;padding:8px 12px;border-radius:6px;background:#2d8aef;color:#fff;text-decoration:none;border:none;cursor:pointer}
    .btn.disabled{opacity:.5;cursor:not-allowed;background:#9bbbed}
    .row-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        padding: 10px 16px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .row-top .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .row-top img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    .balance{font-weight:700}
  </style>
</head>
<body>
  <div class="card">
  <div class="row-top">
  <h1>🛒 Boutique</h1>

  <?php if ($logged): ?>
    <div class="user-info">
      <div class="balance">
        💎 Solde : <strong><?= htmlspecialchars($userAura ?? 0) ?> aura</strong>
      </div>

      <div class="active_item">
        <?php
          $stmt = $pdo->prepare("SELECT i.name 
                                 FROM user_items ui 
                                 JOIN items i ON ui.item_id = i.id
                                 WHERE ui.user_id = :uid 
                                 LIMIT 1");
          $stmt->execute([':uid' => $_SESSION['user_id']]);
          $activeItem = $stmt->fetch();
        ?>
        <?php if ($activeItem): ?>
          🎖️ Item actif : <strong><?= htmlspecialchars($activeItem['name']) ?></strong>
        <?php else: ?>
          🎖️ Aucun item actif
        <?php endif; ?>
      </div>

      <div class="profile-link">
        <a href="profile.php?discord_id=<?= urlencode($_SESSION['discord_id']) ?>">Voir mon profil</a>
      </div>
    </div>
  <?php else: ?>
    <div class="login-link">
      <a href="login.php">Se connecter avec Discord pour acheter</a>
    </div>
  <?php endif; ?>
</div>


    <div class="grid">
      <?php foreach($items as $it): ?>
        <div class="item">
          <div>
            <span class="badge"><img class="item-icon" src="img/items/<?= htmlspecialchars($it['id']) ?>.png"></span>
            <strong><?= htmlspecialchars($it['name']) ?></strong>
          </div>
          <?php if(!empty($it['description'])): ?>
            <div style="font-size:14px;color:#333"><?= htmlspecialchars($it['description']) ?></div>
          <?php endif; ?>
          <div class="price"><?= (int)$it['price'] ?> aura</div>

          <form class="buy-form" method="post" action="buy.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
            <?php
              $canBuy = $logged && ($userAura !== null && $userAura >= (int)$it['price']);
            ?>
            <button class="btn <?= $canBuy ? '' : 'disabled' ?>" <?= $canBuy ? '' : 'disabled' ?>>
              Acheter
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
