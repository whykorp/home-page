<?php
include 'todo_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $tags = $_POST['tags'];

    // Mise à jour de la tâche
    $sql = "UPDATE tasks SET task_name = '$task_name', description = '$description', tags = '$tags' WHERE id = $task_id";

    if ($conn->query($sql) === TRUE) {
        echo "Tâche mise à jour avec succès";
    } else {
        echo "Erreur: " . $conn->error;
    }

    $conn->close();
}
?>