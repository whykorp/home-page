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

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const gameId = urlParams.get('game_id');

    if (!gameId) {
        alert("ID de partie manquant");
        window.location.href = '../index.html';
        return;
    }

    // Récupération des données via le RequestsHandler
    const response = await SqlRequest('getGameData', { game_id: gameId });

    if (response && response.success) {
        const game = response.game;
        const players = response.players;
        const sessionPlayerId = response.session_player_id;

        // 1. Nom de la partie
        document.getElementById('display-game-name').innerText = game.game_name;

        // 2. Bouton Admin (si le joueur actuel est l'admin)
        if (game.admin_id == sessionPlayerId) {
            const adminDiv = document.getElementById('admin-controls');
            adminDiv.innerHTML = `<button class="btn-admin-close" onclick="closeGame(${gameId})">Fermer la table</button>`;
        }

        // 3. Liste des joueurs
        const listContainer = document.getElementById('players-list');
        players.forEach(p => {
            const card = document.createElement('div');
            card.className = `player-card ${p.is_turn == 1 ? 'active' : ''}`;
            card.style.borderBottomColor = p.player_color;
            card.innerHTML = `
                <div style="color: ${p.player_color}; font-weight: bold;">${p.player_name}</div>
                <small>${p.is_turn == 1 ? '🎲 En train de jouer' : 'En attente'}</small>
            `;
            listContainer.appendChild(card);
        });

    } else {
        console.error("Erreur chargement:", response?.error);
    }
});

async function closeGame(id) {
    if(confirm("Voulez-vous fermer cette table ?")){
        const res = await SqlRequest('delete_game', { game_id: id });
        if(res.success) window.location.href = '../index.html';
    }
}