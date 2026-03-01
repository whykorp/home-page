<?php
require_once __DIR__ . '/functions.php';
$top = get_top_users(10);
require_once __DIR__ . '/config.php';
$logged = isset($_SESSION['discord_id']);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Banque de l'Aura — Classement</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;color:#222;padding:20px}
    .card{max-width:800px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    h1{margin:0 0 12px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
    th{background:#fbfbfc}
    .pos-1::before{content:"🥇 ";} 
    .pos-2::before{content:"🥈 ";} 
    .pos-3::before{content:"🥉 ";}
    .avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px}
    .user-link{display:flex;align-items:center;text-decoration:none;color:#222}
    .user-link:hover{text-decoration:underline}
    .user-id{font-size:12px;color:#666;margin-left:40px;margin-top:-4px}
    .welcome{display:flex;align-items:center;gap:12px;margin-bottom:18px;font-size:16px}
    .welcome img{width:40px;height:40px;border-radius:50%;object-fit:cover}
  </style>
</head>
<body>
  <div class="card">
    <?php if ($logged): ?>
    <div class="welcome" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <?php if(!empty($_SESSION['profile_picture'])): ?>
        <a href="profile.php?discord_id=<?= urlencode($_SESSION['discord_id']) ?>">
            <img src="<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
        </a>
        <?php endif; ?>
        <span style="font-size:16px;">
        Bienvenue, 
        <a href="profile.php?discord_id=<?= urlencode($_SESSION['discord_id']) ?>" style="text-decoration:none;color:#222;">
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        </a>
        </span>
    </div>
    <?php else: ?>
    <a href="login.php">Se connecter avec Discord</a>
    <?php endif; ?>

    <h1>🏆 Classement des meilleures auras</h1>
    <?php if(empty($top)): ?>
      <p>Le classement est vide.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>Utilisateur</th><th>Aura</th><th>Grade</th></tr></thead>
        <tbody>
        <?php foreach($top as $i => $row): ?>
          <?php $pos = $i + 1; $cls = $pos<=3 ? "pos-{$pos}" : ""; ?>
          <tr>
            <td class="<?= htmlspecialchars($cls) ?>"><?= $pos ?></td>
            <td>
              <?php if(!empty($row['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($row['profile_picture']) ?>" alt="" class="avatar">
              <?php endif; ?>
              <strong><?= htmlspecialchars($row['username']) ?></strong>
              <div class="user-id">ID: <?= htmlspecialchars($row['discord_id']) ?></div>
            </td>
            <td><?= (int)$row['aura'] ?></td>
            <td><?= htmlspecialchars($row['tier']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
