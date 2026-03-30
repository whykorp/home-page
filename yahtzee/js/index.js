async function SqlRequest(action, params = {}) {
    try {
        const response = await fetch('RequestsHandler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, params: params })
        });
        return await response.json();
    } catch (erreur) {
        console.error("Erreur de communication :", erreur);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const gamesListContainer = document.getElementById('games_list');
    
    // On récupère les parties via le handler
    const result = await SqlRequest('get_all_games');

    if (result && result.success && result.games.length > 0) {
        // On vide le placeholder
        gamesListContainer.innerHTML = '';

        result.games.forEach(game => {
            const gameElement = document.createElement('div');
            gameElement.className = 'game-item-row';
            
            gameElement.innerHTML = `
                <div class="game-info">
                    <span class="game-name">${game.game_name}</span>
                    <span class="game-players-count">${game.nb_players} Joueurs</span>
                </div>
                <button class="btn-join" onclick="window.location.href='game/index.html?game_id=${game.id}'">
                    Rejoindre
                </button>
            `;
            gamesListContainer.appendChild(gameElement);
        });
    } else {
        gamesListContainer.innerHTML = '<p class="placeholder-text">Aucune table ouverte pour le moment...</p>';
    }
});