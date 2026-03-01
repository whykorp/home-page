<?php
// config.php
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');        // ou 8035 si tu as changé
define('DB_NAME', 'banque_aura');
define('DB_USER', 'root');
define('DB_PASS', 'root');

define('DISCORD_CLIENT_ID', '1318190631914307645');
define('DISCORD_CLIENT_SECRET', 'zRXVwXeAYSSL1YbSwPkNVgrgBJN6-i1_');
define('DISCORD_REDIRECT_URI', 'http://128.78.3.237:8082/aura_bank/callback.php'); // adapter

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

function pdo_connect(){
    global $options;
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
    return new PDO($dsn, DB_USER, DB_PASS, $options);
}
