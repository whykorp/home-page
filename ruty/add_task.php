<?php
// Vérifier si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer le nom de la tâche depuis le formulaire
    $taskName = $_POST["task_name"];

    // Connexion à la base de données
    $conn = mysqli_connect("localhost", "user1", "admin123", "ruty");

    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données : " . $conn->connect_error);
    }

    // Préparer et exécuter la requête SQL pour ajouter la tâche
    $sql = "INSERT INTO tasks (task_name) VALUES ('$taskName')";
    if ($conn->query($sql) === TRUE) {
        header("Location: http://www.example.com/another-page.php");
        exit();
    } else {
        echo "Erreur lors de l'ajout de la tâche : " . $conn->error;
    }

    $conn->close();
}
?>
