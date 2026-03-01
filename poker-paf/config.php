<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Configuration Poker</title>
    <style>
        .player-row { margin-bottom: 10px; }
    </style>
    <link rel="stylesheet" href="config.css">
</head>
<body>
    <div class="container">
        <button onclick="window.location.href='index.php'" class="btn-back">⬅ Accueil</button>
        <h1>Configuration de la partie</h1>
        
        <form action="start_game.php" method="post">
            <label>Somme de départ :</label>
            <input type="number" name="start_money" value="1000" required><br><br>
            
            <label>Blind :</label>
            <input type="number" name="blind" value="20" required><br><br>

            <label>Joueurs :</label>
            <label class="info">(Maximum 8 joueurs)</label><br>
            <div id="players_container">
                <div class="player-row">
                    <input type="text" name="players[]" placeholder="Nom du joueur" required>
                </div>
            </div>
            
            <br>
            <button type="button" onclick="addPlayer()">➕ Ajouter un joueur</button>
            <br>
            
            <input type="submit" value="Démarrer la partie">
        </form>
    </div>

    <script>
        function addPlayer() {
            // 1. On récupère le conteneur
            const container = document.getElementById('players_container');
            
            // 2. On crée une nouvelle ligne
            const newRow = document.createElement('div');
            newRow.className = 'player-row';
            
            // 3. On met le HTML dedans (avec le bouton supprimer intégré)
            newRow.innerHTML = `
                <input type="text" name="players[]" placeholder="Nom du joueur" required>
                <button type="button" onclick="removePlayer(this)">🗑️</button>
            `;
            
            // 4. On l'ajoute au conteneur
            container.appendChild(newRow);
        }

        function removePlayer(btn) {
            // On supprime le parent du bouton (la div 'player-row')
            btn.parentElement.remove();
        }

        function deleteGame(idPartie) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cette partie ? Tous les joueurs associés seront effacés.")) {
                
                // On prépare les données à envoyer
                let formData = new FormData();
                formData.append('game_id', idPartie);

                fetch('delete_game.php', { 
                    method: 'POST',
                    body: formData // On envoie l'ID au PHP
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    // Au lieu de reload, on peut rediriger vers l'accueil
                    window.location.href = 'index.php'; 
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Impossible de supprimer la partie.');
                });
            }
        }
    </script>
</body>
</html>