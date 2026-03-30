const defaultColors = ['#ff3131', '#d4af37', '#1a2a6c', '#00ff41', '#ff00ff'];

function updatePlayerInputs() {
    const count = document.getElementById('player-count').value;
    const container = document.getElementById('players-names-container');
    
    // On vide tout sauf le label
    container.innerHTML = '<label>Participants & Couleurs</label>';
    
    for (let i = 0; i < count; i++) {
        const row = document.createElement('div');
        row.className = 'player-row';

        // Création du sélecteur de couleur
        const colorInput = document.createElement('input');
        colorInput.type = 'color';
        colorInput.className = 'color-picker';
        colorInput.value = defaultColors[i] || '#ffffff'; // Couleur par défaut

        // Création du champ nom
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.placeholder = 'Joueur ' + (i + 1);
        nameInput.className = 'player-input';

        // Assemblage
        row.appendChild(colorInput);
        row.appendChild(nameInput);
        container.appendChild(row);
    }
}

async function SqlRequest(action, params = {}) {
    try {
        const response = await fetch('../RequestsHandler.php', {
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

const loginForm = document.getElementById('config-form'); // Vérifie bien que l'ID match ton HTML

loginForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    // 1. Récupération des infos de base
    const gameName = document.getElementById('game-name').value;
    const playerRows = document.querySelectorAll('.player-row');
    // 2. Création de la partie dans la BDD
    let response = await SqlRequest('createGame', { 
        game_name: gameName 
    });

    if (response && response.success) {
        const gameId = parseInt(response.game_id);

        // 3. Boucle sur les lignes de joueurs pour les ajouter un par un
        for (let i = 0; i < playerRows.length; i++) {
            const name = playerRows[i].querySelector('.player-input').value || `Joueur ${i + 1}`;
            const color = playerRows[i].querySelector('.color-picker').value;
            
            // Le premier joueur (index 0) sera l'admin et commencera le tour (is_turn: 1)
            const isAdmin = (i === 0);
            const isTurn = (i === 0) ? 1 : 0;

            await SqlRequest('addPlayer', {
                game_id: gameId,
                name: name,
                color: color,
                is_turn: isTurn,
                is_admin: isAdmin // Paramètre utilisé par le PHP pour l'admin_id
            });
        }

        // 4. Redirection vers la table de jeu
        console.log("Partie créée avec succès, ID:", gameId);
        window.location.href = '../game.html?game_id=' + gameId;

    } else {
        console.error("Erreur lors de la création de la partie :", response?.error);
        alert("Erreur : " + (response?.error || "Impossible de joindre le serveur"));
    }
});