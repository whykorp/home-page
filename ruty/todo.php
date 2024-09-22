<html>
<head>
    <title>Ruty - ToDo List</title>
    <link rel="stylesheet" href="styles/styletodo.css">
    <link rel="icon" href="img/logo.png">   

    <style>
        /* Styles pour le bouton "+" et les menus */
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

        /* Menu pour ajouter et modifier des tâches */
        #task-menu, #task-edit-menu {
            position: fixed;
            top: 0;
            right: -400px;
            width: 300px;
            height: 100%;
            background-color: #f4f4f4;
            box-shadow: -2px 0 5px rgba(0,0,0,0.5);
            padding: 20px;
            transition: right 0.3s ease-in-out;
            z-index: 10;
        }

        #task-menu.open, #task-edit-menu.open {
            right: 0;
        }

        #close-menu, #close-edit-menu {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }

        .completed {
            text-decoration: line-through;
            color: gray;
        }
    </style>
</head>
<body>
    <header id="header">
        <img src="img/logo.png" alt="Logo Ruty" id="menu-trigger">
        <h1 class="welcome">Ruty</h1>
    </header>
    <?php include 'menunav.php'; ?>

    <div id="add-task-button">+</div>

    <!-- Menu d'ajout de tâche -->
    <div id="task-menu">
        <span id="close-menu">&times;</span>
        <h2>Ajouter une tâche</h2>
        <form id="add-task-form" action="add_task.php" method="POST">
            <label for="task_name">Nom de la tâche :</label><br>
            <input type="text" id="task_name" name="task_name" required><br><br>

            <label for="description">Description :</label><br>
            <textarea id="description" name="description"></textarea><br><br>

            <label for="category">Catégorie :</label><br>
            <input type="text" id="category" name="category" required placeholder="ex: Devoirs, Maison"><br><br>

            <input type="submit" value="Ajouter la tâche">
        </form>
    </div>

    <script>
        const addButton = document.getElementById("add-task-button");
        const taskMenu = document.getElementById("task-menu");
        const closeMenuButton = document.getElementById("close-menu");

        addButton.addEventListener("click", function() {
            taskMenu.classList.add("open");
        });

        closeMenuButton.addEventListener("click", function() {
            taskMenu.classList.remove("open");
        });
    </script>

    <h2>Liste des tâches</h2>
    <ul>
        <?php
        include 'todo_db.php';

        // Sélectionner les tâches avec leurs catégories
        $sql = "SELECT id, task_name, completed, category FROM tasks";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $taskId = $row["id"];
                $taskName = $row["task_name"];
                $completed = $row["completed"] ? "checked" : "";
                $category = $row["category"];

                echo "<li onclick='openTaskMenu($taskId)'>";
                echo "<input type='checkbox' class='task-checkbox' data-task-id='$taskId' $completed onchange='updateTask($taskId, this.checked)'> ";
                echo "<span class='" . ($completed ? "completed" : "") . "'>$taskName (Catégorie: $category)</span>";
                echo "</li>";
            }
        } else {
            echo "Aucune tâche trouvée.";
        }

        $conn->close();
        ?>
    </ul>

    <!-- Menu de modification de tâche -->
    <div id="task-edit-menu">
        <span id="close-edit-menu">&times;</span>
        <h2>Modifier la tâche</h2>
        <form id="edit-task-form">
            <input type="hidden" id="edit-task-id">

            <label for="edit-task-name">Nom de la tâche :</label><br>
            <input type="text" id="edit-task-name" name="task_name" required><br><br>

            <label for="edit-description">Description :</label><br>
            <textarea id="edit-description" name="description"></textarea><br><br>

            <label for="edit-category">Catégorie :</label><br>
            <input type="text" id="edit-category" name="category" required><br><br>

            <input type="button" value="Modifier la tâche" onclick="updateTaskDetails()">
        </form>
    </div>

    <script>
        function openTaskMenu(taskId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_task.php?task_id=${taskId}`, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const task = JSON.parse(this.responseText);
                    document.getElementById('edit-task-id').value = task.id;
                    document.getElementById('edit-task-name').value = task.task_name;
                    document.getElementById('edit-description').value = task.description;
                    document.getElementById('edit-category').value = task.category;
                    document.getElementById('task-edit-menu').classList.add('open');
                }
            };
            xhr.send();
        }

        document.getElementById('close-edit-menu').addEventListener('click', function() {
            document.getElementById('task-edit-menu').classList.remove('open');
        });

        function updateTaskDetails() {
            const taskId = document.getElementById('edit-task-id').value;
            const taskName = document.getElementById('edit-task-name').value;
            const description = document.getElementById('edit-description').value;
            const category = document.getElementById('edit-category').value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_task_details.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    alert('Tâche mise à jour avec succès.');
                    location.reload();
                }
            };
            xhr.send(`task_id=${taskId}&task_name=${taskName}&description=${description}&category=${category}`);
        }
    </script>
</body>
</html>
