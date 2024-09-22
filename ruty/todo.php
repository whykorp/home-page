<html>
    <head>
        <title>Ruty - ToDo List</title>
        <link rel="stylesheet" href="styles/styletodo.css">
        <link rel="icon" href="img/logo.png">        
    </head>
    <body>
        <header id="header">
            <img src="img/logo.png" alt="Logo Ruty" id="menu-trigger">
            <h1 class="welcome">Ruty</h1>
        </header>
        <?php include 'menunav.php'; ?>
        
<!-- Ajouter une nouvelle tâche -->
<h1>Ajouter une tâche</h1>
    <form action="add_task.php" method="POST">
        <label for="task_name">Nom de la tâche :</label><br>
        <input type="text" id="task_name" name="task_name" required><br><br>

        <label for="description">Description :</label><br>
        <textarea id="description" name="description"></textarea><br><br>

        <label for="tags">Tags (séparés par des virgules) :</label><br>
        <input type="text" id="tags" name="tags[]" placeholder="ex : urgent, maison"><br><br>

        <input type="submit" value="Ajouter la tâche">
    </form>

    <h2>Liste des tâches</h2>
    <ul>
        <?php
        include 'todo_db.php';

        // Sélectionner les tâches
        $sql = "SELECT tasks.id, tasks.task_name, tasks.description, GROUP_CONCAT(tags.tag_name) as tags
                FROM tasks
                LEFT JOIN task_tags ON tasks.id = task_tags.task_id
                LEFT JOIN tags ON task_tags.tag_id = tags.id
                GROUP BY tasks.id";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . $row["task_name"] . " - " . $row["description"] . " [Tags: " . $row["tags"] . "]</li>";
            }
        } else {
            echo "Aucune tâche trouvée.";
        }

        $conn->close();
        ?>
    </ul>
    </body>
</html>
