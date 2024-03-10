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

// Récupérer le nombre de pièces de l'utilisateur "Noah" depuis la base de données
$query = "SELECT nombre_de_pièces FROM utilisateurs WHERE nom='Noah'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Erreur dans la requête SQL : " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($result);
$nombreDePiecesNoah = $row['nombre_de_pièces'];

// Afficher les boutons de l'utilisateur "Noah"
echo "<h2>Noah</h2>";
echo "<p>Nombre de pièces : $nombreDePiecesNoah</p>";

// Fermer la connexion à la base de données
mysqli_close($conn);