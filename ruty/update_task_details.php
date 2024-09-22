<?php
include 'todo_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = intval($_POST['task_id']);
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $tags = $_POST['tags'];

    // Mise à jour de la tâche dans la base de données
    $sql = "UPDATE tasks SET task_name = '$task_name', description = '$description', tags = '$tags' WHERE id = $task_id";

    if ($conn->query($sql) === TRUE) {
        echo "Tâche mise à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour de la tâche: " . $conn->error;
    }

    $conn->close();
}
?>