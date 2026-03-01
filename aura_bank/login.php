<?php
require_once __DIR__ . '/config.php';

// scopes : identify pour ID + username, email si tu veux email
$scope = 'identify email';

// génère un state CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2_state'] = $state;

$params = http_build_query([
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state,
    'prompt' => 'consent'
]);

$discord_authorize_url = "https://discord.com/oauth2/authorize?$params";
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login Discord</title></head>
<body>
  <a href="<?= htmlspecialchars($discord_authorize_url) ?>">
    <img src="https://raw.githubusercontent.com/DiscordAssets/discord-open-graph/main/discord_logo.png" alt="Discord" style="height:28px;vertical-align:middle"> Se connecter avec Discord
  </a>
</body>
</html>
