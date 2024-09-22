<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ruty_todo";

// Connexion à MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>