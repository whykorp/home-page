<html>
    <head>
    <?php
        // Connexion à la base de données
        $conn = mysqli_connect("localhost", "root", "votre_mot_de_passe", "ruty");

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

                echo "<li><input type='checkbox' $completed onchange='updateTask($taskId, this.checked)'> $taskName</li>";
            }
        } else {
            echo "Aucune tâche trouvée.";
        }
        $conn->close();
        ?>
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
