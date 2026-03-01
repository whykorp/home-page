<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    http_response_code(400);
    exit("Paramètres manquants.");
}

// CSRF : vérifier state
if (!isset($_SESSION['oauth2_state']) || $_GET['state'] !== $_SESSION['oauth2_state']) {
    unset($_SESSION['oauth2_state']);
    http_response_code(400);
    exit("Échec de la vérification de sécurité (state).");
}
unset($_SESSION['oauth2_state']);

$code = $_GET['code'];

// Étape 1 : échange du code contre un access_token
$token_url = "https://discord.com/api/oauth2/token";
$post_fields = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'scope' => 'identify email'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
// ⚠️ remet le SSL, mieux pour la prod
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);

if ($response === false) {
    exit("Erreur cURL token: " . curl_error($ch));
}
curl_close($ch);

$token_data = json_decode($response, true);
if (!isset($token_data['access_token'])) {
    exit("Échec de l'échange de token: " . htmlspecialchars($response));
}
$access_token = $token_data['access_token'];

// Étape 2 : récupérer infos utilisateur
$ch = curl_init("https://discord.com/api/users/@me");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
$user_json = curl_exec($ch);
if ($user_json === false) {
    exit("Erreur cURL user: " . curl_error($ch));
}
curl_close($ch);

$user_data = json_decode($user_json, true);
if (!isset($user_data['id'])) {
    exit("Impossible de récupérer l'utilisateur Discord. Réponse: " . htmlspecialchars($user_json));
}

// Préparation des données
$discord_id = $user_data['id'];
$username = $user_data['username'] . (isset($user_data['discriminator']) && $user_data['discriminator'] !== "0" ? '#' . $user_data['discriminator'] : "");
$email = $user_data['email'] ?? null;
$avatar = !empty($user_data['avatar']) 
    ? "https://cdn.discordapp.com/avatars/{$discord_id}/{$user_data['avatar']}.png"
    : null;

// Étape 3 : DB
try {
    $pdo = pdo_connect();
} catch (Exception $e) {
    exit("Erreur DB : " . $e->getMessage());
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE discord_id = :did LIMIT 1");
$stmt->execute([':did' => $discord_id]);
$u = $stmt->fetch();

if ($u) {
    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, profile_picture = :avatar WHERE discord_id = :did");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':avatar' => $avatar,
        ':did' => $discord_id
    ]);
    $user_id = $u['id'];
} else {
    $stmt = $pdo->prepare("INSERT INTO users (discord_id, username, email, profile_picture, aura, tier) 
                           VALUES (:did, :username, :email, :avatar, 0, 'Aura')");
    $stmt->execute([
        ':did' => $discord_id,
        ':username' => $username,
        ':email' => $email,
        ':avatar' => $avatar
    ]);
    $user_id = $pdo->lastInsertId();
}

// Étape 4 : session
$_SESSION['user_id'] = $user_id;
$_SESSION['discord_id'] = $discord_id;
$_SESSION['username'] = $username;
$_SESSION['profile_picture'] = $avatar;

// Étape 5 : redirection
header("Location: index.php");
exit;
