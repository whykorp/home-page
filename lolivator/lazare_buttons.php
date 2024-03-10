<?php
// Se connecter à la base de données (à adapter en fonction de vos informations)
$host = "localhost";
$username = "root";
$password = "";
$database = "lolivator";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Échec de la connexion à la base de données : " . mysqli_connect_error());
}

// Récupérer le nombre de pièces de l'utilisateur "Lazare" depuis la base de données
$query = "SELECT nombre_de_pièces FROM utilisateurs WHERE nom='Lazare'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Erreur dans la requête SQL : " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($result);
$nombreDePiecesLazare = $row['nombre_de_pièces'];

// Afficher les boutons de l'utilisateur "Lazare"
echo "<h2>Lazare</h2>";
echo "<p>Nombre de pièces : $nombreDePiecesLazare</p>";

// Fermer la connexion à la base de données
mysqli_close($conn);
