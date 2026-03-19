
const hashedAdminPassword = "7215d31f702fe2faf2a7df114c6427007bd254740c6b9cbaa2a5505060088929";

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

window.onload = async function() {
    loadData();
};

async function loadData() {
    const container = document.getElementById('games_list');
    const response = await SqlRequest('get_all_games');
    const games = response.games;

    if (Object.keys(games).length <= 0) {
        container.innerHTML = "<p>Aucune partie en cours.</p>";
        return;
    }

    let futurHtml = "";

for (const game of games) {
    // 1. On récupère les joueurs de manière asynchrone AVANT de construire le HTML du jeu
    const players = await getPlayers(game.id);
    
    // 2. On prépare le HTML de la liste des joueurs
    let playersHtml = "";
    players.forEach(player => {
        if (player.game_id === game.id) {
            playersHtml += `<p>${player.name}</p>`;
        }
    });

    // 3. On assemble le tout
    futurHtml += `
        <li>
            <details class="mon-accordeon">
                <summary>${game.name}</summary>
                <div class='container-parent'>
                    <div class='right'>
                        <p>Start Money: ${game.start_money}</p>
                        <p>Blind: ${game.start_blind}</p>
                        <button class="btn-join-list" onclick="window.location.href='game.html?game_id=${game.id}'">Rejoindre</button>
                        <button class="btn-admin-join-list" onclick="joinGameAsAdmin(${game.id})">Rejoindre en tant qu'Admin</button>
                    </div>
                    <div class='left'>
                        ${playersHtml}
                    </div>
                </div>
            </details>
        </li>
    `;
}

container.innerHTML = '<ul>' + futurHtml + "</ul>";
}

async function getPlayers(id) {
    const response = await SqlRequest('getPlayers', {game_id: id});
    const players = response.players;
    return players;
}

async function joinGameAsAdmin(gameId) {
    // Redirige vers la page de connexion admin avec le gameId en paramètre
    window.location.href = `admin-login.html?game_id=${gameId}`;
}

// Récupérer le formulaire de admin-login
const adminLoginForm = document.getElementById('admin-login-form');

adminLoginForm.addEventListener('submit', async function(event) {
    event.preventDefault();
     // Désactiver le bouton pour éviter les soumissions multiples
    const password = this.querySelector('input[type="password"]').value;
    const urlParams = new URLSearchParams(window.location.search);
    const gameId = urlParams.get('game_id');
    // Vérification du mot de passe
    const hashedInputPassword = CryptoJS.SHA256(password).toString();

    if (hashedInputPassword !== hashedAdminPassword) {
        alert("Mot de passe incorrect. Veuillez réessayer.");
        return;
    }

    // Requete SQL pour définir l'utilisateur comme admin du jeu
    const response = await SqlRequest('adminLogin', {game_id: gameId});

    if (response.success) {
        window.location.href = `admin-game.html?game_id=${gameId}`; // Redirige vers la page d'administration du jeu
    } else {
        alert("Erreur lors de la connexion. Veuillez réessayer."); // Affiche une alerte en cas d'erreur
    }
});


