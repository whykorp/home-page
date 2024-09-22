<?php
include 'todo_db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = intval($_POST['task_id']); // Assurez-vous que c'est un entier
    $completed = intval($_POST['completed']); // 0 ou 1 seulement

    // Mise à jour de l'état de la tâche
    $sql = "UPDATE tasks SET completed = $completed WHERE id = $task_id";

    if ($conn->query($sql) === TRUE) {
        echo "Task updated successfully";
    } else {
        echo "Error updating task: " . $conn->error;
    }

    $conn->close();
}
?>
