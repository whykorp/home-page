<?php
include 'todo_db.php';

if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];
    
    $sql = "SELECT * FROM tasks WHERE id = $task_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $task = $result->fetch_assoc();
        echo json_encode($task);
    } else {
        echo json_encode([]);
    }
}

$conn->close();
?>
