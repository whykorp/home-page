<html>
    <head>
        <title>Ruty - ToDo List</title>
        <link rel="stylesheet" href="styles/styletodo.css">
        <link rel="icon" href="img/logo.png">   
        
<style>
        /* Style pour le bouton "+" */
        #add-task-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            font-size: 30px;
            text-align: center;
            line-height: 50px;
            cursor: pointer;
        }

        /* Style pour le menu latéral d'ajout de tâche */
        #task-menu {
    position: fixed;
    top: 0;
    right: -400px; /* Menu caché initialement */
    width: 300px;
    height: 100%;
    background-color: #f4f4f4;
    box-shadow: -2px 0 5px rgba(0,0,0,0.5);
    padding: 20px;
    transition: right 0.3s ease-in-out;
    z-index: 10; /* Un z-index plus élevé pour être au-dessus du menu en haut */
}

#task-menu.open {
    right: 0; /* Le menu est affiché lorsqu'il est ouvert */
}

/* Bouton de fermeture */
#close-menu {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 20px;
    cursor: pointer;
}
        #task-menu h2 {
            margin-top: 0;
        }

.add_task_title {
    color: black;
};

/* Menu latéral de modification de tâche */
#task-edit-menu {
    position: fixed;
    top: 0;
    right: -300px; /* Cacher initialement */
    width: 300px;
    height: 100%;
    background-color: #f4f4f4;
    box-shadow: -2px 0 5px rgba(0,0,0,0.5);
    padding: 20px;
    transition: right 0.3s ease-in-out;
}

#task-edit-menu.open {
    right: 0; /* Ouvre le menu lorsqu'il est actif */
}

#close-edit-menu {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 20px;
    cursor: pointer;
}


</style>
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
    <h2 class="add_task_title">Ajouter une tâche</h2>
    <?php
    include 'todo_db.php';
    // Sélectionner les tâches
    $sql = "SELECT tasks.id, tasks.task_name, tasks.completed FROM tasks";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $taskId = $row["id"];
            $taskName = $row["task_name"];
            $completed = $row["completed"] ? "checked" : "";

            // Chaque tâche est cliquable pour ouvrir le menu
            echo "<li onclick='openTaskMenu($taskId)'>";
            echo "<input type='checkbox' class='task-checkbox' data-task-id='$taskId' $completed onchange='updateTask($taskId, this.checked)'> ";
            echo "<span class='" . ($completed ? "completed" : "") . "'>$taskName</span>";
            echo "</li>";
        }
    } else {
        echo "Aucune tâche trouvée.";
    }

    $conn->close();
    ?>
<!-- Menu latéral pour modifier une tâche -->
<div id="task-edit-menu">
    <span id="close-edit-menu">&times;</span>
    <h2>Modifier la tâche</h2>
    <form id="edit-task-form">
        <input type="hidden" id="edit-task-id">

        <label for="edit-task-name">Nom de la tâche :</label><br>
        <input type="text" id="edit-task-name" name="task_name" required><br><br>

        <label for="edit-description">Description :</label><br>
        <textarea id="edit-description" name="description"></textarea><br><br>

        <label for="edit-tags">Tags (séparés par des virgules) :</label><br>
        <input type="text" id="edit-tags" name="tags[]" placeholder="ex : urgent, maison"><br><br>

        <input type="button" value="Modifier la tâche" onclick="updateTaskDetails()">
    </form>
    <script>
    // Ouvrir le menu pour modifier une tâche
    function openTaskMenu(taskId) {
        // Récupérer les données de la tâche à partir de l'ID
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_task.php?task_id=${taskId}`, true);
        xhr.onload = function() {
            if (this.status === 200) {
                const task = JSON.parse(this.responseText);
                
                // Pré-remplir le formulaire avec les données de la tâche
                document.getElementById('edit-task-id').value = task.id;
                document.getElementById('edit-task-name').value = task.task_name;
                document.getElementById('edit-description').value = task.description;
                document.getElementById('edit-tags').value = task.tags;

                // Ouvrir le menu latéral
                document.getElementById('task-edit-menu').classList.add('open');
            }
        };
        xhr.send();
    }

    // Fermer le menu de modification
    document.getElementById('close-edit-menu').addEventListener('click', function() {
        document.getElementById('task-edit-menu').classList.remove('open');
    });

    // Mettre à jour les détails de la tâche dans la base de données
    function updateTaskDetails() {
        const taskId = document.getElementById('edit-task-id').value;
        const taskName = document.getElementById('edit-task-name').value;
        const description = document.getElementById('edit-description').value;
        const tags = document.getElementById('edit-tags').value;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_task_details.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                alert('Tâche mise à jour avec succès.');
                // Rafraîchir la page ou mettre à jour l'affichage des tâches ici
                location.reload(); // Rafraîchir la page pour voir les changements
            }
        };
        xhr.send(`task_id=${taskId}&task_name=${taskName}&description=${description}&tags=${tags}`);
    }
</script>
</div>
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
