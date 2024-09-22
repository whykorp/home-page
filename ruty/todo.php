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
   <!-- Bouton "+" pour ajouter une tâche -->
   <div id="add-task-button">+</div>

<!-- Menu latéral pour ajouter une tâche -->
<div id="task-menu">
    <span id="close-menu">&times;</span>
    <h2>Ajouter une tâche</h2>
    <form id="add-task-form" action="add_task.php" method="POST">
        <label for="task_name">Nom de la tâche :</label><br>
        <input type="text" id="task_name" name="task_name" required><br><br>

        <label for="description">Description :</label><br>
        <textarea id="description" name="description"></textarea><br><br>

        <label for="tags">Tags (séparés par des virgules) :</label><br>
        <input type="text" id="tags" name="tags[]" placeholder="ex : urgent, maison"><br><br>

        <input type="submit" value="Ajouter la tâche">
    </form>
</div>

<script>
    // Récupération des éléments
    const addButton = document.getElementById("add-task-button");
    const taskMenu = document.getElementById("task-menu");
    const closeMenuButton = document.getElementById("close-menu");

    // Afficher le menu lorsqu'on clique sur le bouton "+"
    addButton.addEventListener("click", function() {
        taskMenu.classList.add("open");
    });

    // Cacher le menu lorsqu'on clique sur le bouton de fermeture
    closeMenuButton.addEventListener("click", function() {
        taskMenu.classList.remove("open");
    });

    // Optionnel : Fermer le menu si l'utilisateur clique en dehors
    window.addEventListener("click", function(event) {
        if (event.target == taskMenu) {
            taskMenu.classList.remove("open");
        }
    });
</script>

    <h2>Liste des tâches</h2>
<ul>
    <?php
    include 'todo_db.php';

    // Sélectionner les tâches
    $sql = "SELECT tasks.id, tasks.task_name, tasks.description, tasks.completed, GROUP_CONCAT(tags.tag_name) as tags
            FROM tasks
            LEFT JOIN task_tags ON tasks.id = task_tags.task_id
            LEFT JOIN tags ON task_tags.tag_id = tags.id
            GROUP BY tasks.id";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $taskId = $row["id"];
            $taskName = $row["task_name"];
            $description = $row["description"];
            $tags = $row["tags"];
            $completed = $row["completed"] ? "checked" : "";

            echo "<li>";
            echo "<input type='checkbox' class='task-checkbox' data-task-id='$taskId' $completed>";
            echo " <span class='" . ($completed ? "completed" : "") . "'>$taskName - $description [Tags: $tags]</span>";
            echo "</li>";
        }
    } else {
        echo "Aucune tâche trouvée.";
    }

    $conn->close();
    ?>
</ul>

<script>
    // Ajout d'un event listener pour gérer le changement d'état de la checkbox
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.getAttribute('data-task-id');
            const completed = this.checked ? 1 : 0;

            // Envoyer une requête AJAX pour mettre à jour l'état de la tâche dans la base de données
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_task.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(`task_id=${taskId}&completed=${completed}`);

            // Mettre à jour l'affichage du texte (grisé si complété)
            const taskText = this.nextElementSibling;
            if (completed) {
                taskText.classList.add('completed');
            } else {
                taskText.classList.remove('completed');
            }
        });
    });
</script>

<style>
    /* Style pour les tâches complétées */
    .completed {
        text-decoration: line-through;
        color: gray;
    }
</style>
    </body>
</html>
