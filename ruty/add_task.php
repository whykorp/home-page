<?php
include 'todo_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $tags = $_POST['tags']; // Liste des tags

    // Insérer la tâche
    $sql = "INSERT INTO tasks (task_name, description) VALUES ('$task_name', '$description')";
    if ($conn->query($sql) === TRUE) {
        $task_id = $conn->insert_id;

        // Insérer les tags associés
        foreach ($tags as $tag_name) {
            $tag_sql = "INSERT INTO tags (tag_name) VALUES ('$tag_name') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
            $conn->query($tag_sql);
            $tag_id = $conn->insert_id;
            
            // Lier la tâche et les tags
            $conn->query("INSERT INTO task_tags (task_id, tag_id) VALUES ($task_id, $tag_id)");
        }
        echo "Tâche ajoutée avec succès.";
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
