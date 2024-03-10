<?php
// Établir une connexion à la base de données (à adapter selon vos paramètres)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "notes_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("La connexion à la base de données a échoué : " . $conn->connect_error);
}

// Fonction pour afficher la liste des dossiers
function displayFolders() {
    global $conn;
    $sql = "SELECT DISTINCT folder FROM notes";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<li class="folder">' . $row["folder"] . '</li>';
        }
    }
}

// Fonction pour afficher la liste des notes d'un dossier
function displayNotes($folderName) {
    global $conn;
    $sql = "SELECT id, note FROM notes WHERE folder = '$folderName'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<li class="note" data-note-id="' . $row["id"] . '">' . $row["note"] . '</li>';
        }
    }
}

// Gérer l'ajout d'une nouvelle note
if (isset($_POST["addNote"])) {
    $folderName = $_POST["folderName"];
    $noteContent = $_POST["noteContent"];

    // Échapper les données pour éviter les injections SQL
    $folderName = $conn->real_escape_string($folderName);
    $noteContent = $conn->real_escape_string($noteContent);

    $sql = "INSERT INTO notes (folder, note) VALUES ('$folderName', '$noteContent')";
    if ($conn->query($sql) === TRUE) {
        echo "Nouvelle note ajoutée avec succès.";
    } else {
        echo "Erreur lors de l'ajout de la note : " . $conn->error;
    }
}

// Gérer la mise à jour du contenu d'une note
if (isset($_POST["updateNote"])) {
    $noteId = $_POST["noteId"];
    $newNoteContent = $_POST["newNoteContent"];

    // Échapper les données pour éviter les injections SQL
    $noteId = $conn->real_escape_string($noteId);
    $newNoteContent = $conn->real_escape_string($newNoteContent);

    $sql = "UPDATE notes SET note = '$newNoteContent' WHERE id = $noteId";
    if ($conn->query($sql) === TRUE) {
        echo "Contenu de la note mis à jour avec succès.";
    } else {
        echo "Erreur lors de la mise à jour de la note : " . $conn->error;
    }
}

// Gérer le chargement du contenu d'une note
if (isset($_POST["loadNote"])) {
    $noteId = $_POST["noteId"];
    $sql = "SELECT note FROM notes WHERE id = $noteId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row["note"];
    } else {
        echo "Note non trouvée.";
    }
}

// Fermer la connexion à la base de données
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/logo.png"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/stylenote.css">
    <script src="js/node_modules/rtf/lib/rtf.js"></script>
    <script src="js/scriptnotepad.js" async></script>
    <title>Ruty - Bloc-note</title>
</head>
<body>
    <header id="header">
        <img src="img/logo.png" alt="Logo Ruty" id="menu-trigger">
        <h1 class="welcome">Ruty</h1>
    </header>
    <!-- Menu de navigation -->
    <?php include 'menunav.php'; ?>
    <div id="app">
        <div id="sidebar">
            <div class="folders">
                <!-- Dossier actuel -->
                <p id="ActiveFolderInfo"></p>
                <!-- Bouton "Ajouter un dossier" -->
                <button id="add-folder">Ajouter un dossier</button>

                <!-- Liste des dossiers -->
                <ul id="folder-list">
                    <!-- Liste des dossiers ici -->

                </ul>
            </div>
            <div class="notes">
                <!-- Bouton "Ajouter une note" -->
                <button id="add-note">Ajouter une note</button>
                <!-- Bouton "Retour" pour quitter un dossier -->
                <button id="back-button">Retour</button>
                <ul id="note-list">
                    <!-- Liste des notes ici -->

                </ul>
            </div>
        </div>
        <div id="text-editor-container">
            <!-- Boutons pour la mise en forme -->
            <div id="formatting-buttons">
                <button id="bold-button">Gras</button>
                <button id="italic-button">Italique</button>
                <button id="underline-button">Souligné</button>
            </div>
            <div id="text-editor" contenteditable="true"></div>
        </div>
    </div>
    <script>
        // Fonction pour charger la liste des notes d'un dossier
        function loadNotes(folderName) {
            fetch(`/api/notes/${folderName}`)
                .then((response) => response.json())
                .then((notes) => {
                    // Afficher les notes dans l'interface utilisateur
                    const noteList = document.getElementById('note-list');
                    noteList.innerHTML = '';

                    notes.forEach((note) => {
                        const noteItem = document.createElement('li');
                        noteItem.textContent = note.name;
                        noteItem.classList.add('note');
                        noteList.appendChild(noteItem);

                        // Ajoutez un gestionnaire d'événements pour charger le contenu RTF au clic.
                        noteItem.addEventListener('click', () => {
                            loadRTFContent(note.rtfFile);
                        });
                    });
                })
                .catch((error) => {
                    console.error('Erreur lors du chargement des notes : ', error);
                });
        }
</script>
</body>
</html>
