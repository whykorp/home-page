<?php
require_once __DIR__ . '/functions.php';

$discord_id = isset($_GET['discord_id']) ? trim($_GET['discord_id']) : null;
if (!$discord_id) {
    http_response_code(400);
    echo "Paramètre discord_id manquant.";
    exit;
}

$user = get_user_by_discord($discord_id);
if (!$user) {
    http_response_code(404);
    echo "Utilisateur introuvable.";
    exit;
}

$rank = get_user_rank($user['aura']);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Profil de <?= htmlspecialchars($user['username']) ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;color:#222;padding:20px}
    .card{max-width:700px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    .avatar{width:96px;height:96px;border-radius:50%;object-fit:cover}
    .header{display:flex;align-items:center;gap:16px;justify-content:space-between}
    .user-info{flex:1}
    .btn-group{display:flex;gap:8px;margin-top:4px}
    .btn{padding:6px 12px;border:none;border-radius:4px;background:#4CAF50;color:white;text-decoration:none;cursor:pointer;}
    .btn.logout{background:#f44336;}
    table{width:100%;border-collapse:collapse;margin-top:8px;}
    th, td{padding:8px;border-bottom:1px solid #ccc;text-align:left;}
  </style>
</head>
<body>
    <div class="card">
        <div class="header">
        <div style="display:flex;align-items:center;gap:16px">
            <?php if(!empty($user['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="" class="avatar">
            <?php endif; ?>
            <div class="user-info">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <div>ID Discord: <?= htmlspecialchars($user['discord_id']) ?></div>
            <div style="margin-top:8px;"><strong>Aura:</strong> <?= (int)$user['aura'] ?> — <strong>Grade:</strong> <?= htmlspecialchars($user['tier']) ?></div>
            <div style="margin-top:6px;"><strong>Rang:</strong> #<?= (int)$rank ?></div>
            </div>
        </div>
        <div class="btn-group">
            <a href="index.php" class="btn">Accueil</a>
            <a href="shop.php" class="btn">Boutique</a>
            <a href="logout.php" class="btn logout">Se déconnecter</a>
        </div>
    </div>

    <hr style="margin:14px 0">

    <h3>Historique récent</h3>
    <?php
    $pdo = pdo_connect();
    $stmt = $pdo->prepare("
        SELECT amount, reason, created_at 
        FROM logs 
        WHERE user_id = :uid OR user_id = :discord_id 
        ORDER BY created_at DESC
    ");
    $stmt->execute([
        ':uid' => $user['id'], 
        ':discord_id' => $user['discord_id']
    ]);
    $logs = $stmt->fetchAll();
    if (!$logs): ?>
        <p>Aucun historique.</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <thead>
                <tr style="background:#f0f0f0;text-align:left;">
                    <th style="padding:8px;border-bottom:1px solid #ccc;">Date</th>
                    <th style="padding:8px;border-bottom:1px solid #ccc;">Montant</th>
                    <th style="padding:8px;border-bottom:1px solid #ccc;">Raison</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $l): 
                    $amount = (int)$l['amount'];
                    $bg_color = $amount >= 0 ? '#d4f7d4' : '#f7d4d4'; // vert pale si gain, rouge pale si perte
                ?>
                    <tr style="background:<?= $bg_color ?>;border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?= htmlspecialchars($l['created_at']) ?></td>
                        <td style="padding:10px;"><?= $amount ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($l['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
  </div>
</body>
</html>
