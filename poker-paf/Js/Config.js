// Fonction et variables essentiel

async function SqlRequest(action, params = {}) {
    try {
        const response = await fetch('RequestsHandler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                params: params
            })
        });

        const resultat = await response.json();

        if (resultat.success) {
            return resultat;
        } else {
            console.error("Erreur :", resultat.error);
        }
    } catch (erreur) {
        console.error("Erreur de communication :", erreur);
    }
}

// ------------------------------------

function addPlayer() {
    // 1. On récupère le conteneur
    const container = document.getElementById('players_container');
    
    // 2. On crée une nouvelle ligne
    const newRow = document.createElement('div');
    newRow.className = 'player-row';

    console.log(container.children.length); // Affiche le nombre de joueurs actuels (pour le debug)
    // 3. On met le HTML dedans (avec le bouton supprimer intégré)
    newRow.innerHTML = `
        <p>${container.children.length+1}</p>
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


const loginForm = document.getElementById('create_game_form');
loginForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    const start_money = parseInt(this.querySelector('input[name="start_money"]').value);
    const blind = parseInt(this.querySelector('input[name="blind"]').value);
    const name = this.querySelector('input[name="game_name"]').value;
    const players = this.querySelectorAll('input[name="players[]"]');
    let response = await SqlRequest('createGame', {name: name, start_money: start_money, blind: blind});
    console.log(response, response.success, response.game_id, parseInt(response.game_id));

    if (response.success) {
        const gameId = parseInt(response.game_id);
        for (const player of players) {
            await SqlRequest('addPlayer', {game_id: gameId, name: player.value, money: start_money});
        }
        const result = await SqlRequest('setFirstPlayer', {game_id: gameId})
        console.log(result)
        if (result.success) {
            window.location.href = 'admin-login.html?game_id=' + gameId; 
        } else {    
            console.error("Erreur lors de la définition du premier joueur :", result.error);
        }
    } else {
        console.error("Erreur lors de la création de la partie :", response.error);
    }
});