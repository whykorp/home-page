<?php
// Vérifier si les paramètres sont passés dans l'URL
if (isset($_GET["id"]) && isset($_GET["completed"])) {
    // Récupérer l'ID de la tâche et l'état complété depuis l'URL
    $taskId = $_GET["id"];
    $completed = $_GET["completed"];

    // Connexion à la base de données
    $conn = mysqli_connect("localhost", "user1", "admin123", "ruty");

    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données : " . $conn->connect_error);
    }

    // Préparer et exécuter la requête SQL pour mettre à jour l'état de la tâche
    $sql = "UPDATE tasks SET completed = '$completed' WHERE id = '$taskId'";
    if ($conn->query($sql) === TRUE) {
        echo "État de la tâche mis à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour de l'état de la tâche : " . $conn->error;
    }

    $conn->close();
}
?>
