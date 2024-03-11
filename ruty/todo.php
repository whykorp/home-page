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
<h2>Ajouter une tâche</h2>
    <form action="add_task.php" method="post">
        <input type="text" name="task_name" placeholder="Nom de la tâche" required>
        <button type="submit">Ajouter</button>
    </form>

<!-- Liste de tâches -->
<h2>Tâches en cours</h2>
<ul id="todo-list">
    <?php
     // Connexion à la base de données
    $conn = mysqli_connect("localhost", "user1", "admin123", "ruty");

    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données : " . $conn->connect_error);
    }

    // Sélectionner les tâches depuis la base de données
    $sql = "SELECT id, task_name, completed FROM tasks";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $taskId = $row["id"];
            $taskName = $row["task_name"];
            $completed = $row["completed"] ? "checked" : "";

            echo "<li class='task' data-task-id='$taskId'><input type='checkbox' $completed> $taskName</li>";
        }
    } else {
        echo "Aucune tâche trouvée.";
    }
    $conn->close();
    ?>
</ul>

<!-- Menu contextuel pour la tâche -->
<div id="task-menu" class="task-menu">
    <button id="close-menu">Fermer</button>
    <div id="task-name"></div>
    <div id="task-status"></div>
    <div id="task-description"></div>
    <div id="task-tags"></div>
    <button id="edit-task">Modifier</button>
    <button id="delete-task">Supprimer</button>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const todoList = document.getElementById("todo-list");
        const taskMenu = document.getElementById("task-menu");

        // Afficher le menu contextuel lorsqu'une tâche est cliquée
        todoList.addEventListener("click", function(event) {
            const targetTask = event.target.closest(".task");
            if (targetTask) {
                const taskId = targetTask.getAttribute("data-task-id");
                const taskName = targetTask.textContent;
                const taskMenuItems = taskMenu.querySelectorAll(".task-menu-item");
                document.getElementById("task-name").textContent = "Nom de la tâche: " + taskName;
                document.getElementById("task-status").textContent = "État de la tâche: Complétée/Incomplète";
                document.getElementById("task-description").textContent = "Description de la tâche: Description de la tâche ici.";
                document.getElementById("task-tags").textContent = "Tags associés à la tâche: Tag1, Tag2";
                taskMenuItems.forEach(item => item.style.display = "block"); // Afficher les éléments du menu
                taskMenu.style.display = "block"; // Afficher le menu
            }
        });

        // Fermer le menu contextuel lorsque le bouton "Fermer" est cliqué
        document.getElementById("close-menu").addEventListener("click", function() {
            taskMenu.style.display = "none";
        });
    });
</script>

</script>
    <!-- Script pour mettre à jour l'état d'une tâche -->
    <script>
        function updateTask(taskId, completed) {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log("Tâche mise à jour avec succès.");
                }
            };
            xhttp.open("GET", "update_task.php?id=" + taskId + "&completed=" + completed, true);
            xhttp.send();
        }
    </script>
    </body>
</html>
