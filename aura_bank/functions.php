<?php
// functions.php
require_once __DIR__ . '/config.php';

function get_top_users($limit = 10) {
    $pdo = pdo_connect();
    $stmt = $pdo->prepare("SELECT username, aura, discord_id, tier, profile_picture FROM users ORDER BY aura DESC LIMIT :lim");
    $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_user_by_discord($discord_id) {
    $pdo = pdo_connect();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE discord_id = :did LIMIT 1");
    $stmt->execute([':did' => $discord_id]);
    return $stmt->fetch();
}

function get_user_rank($aura) {
    $pdo = pdo_connect();
    $stmt = $pdo->prepare("SELECT COUNT(*) + 1 AS 'rank' FROM users WHERE aura > :aura");
    $stmt->execute([':aura' => $aura]);
    $r = $stmt->fetch();
    return $r ? (int)$r['rank'] : null;
}
