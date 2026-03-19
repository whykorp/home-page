

// Fonction et variables essentiel

const activePlayerLabel = document.getElementById('active-player-name');
let gameData = null;
let currentPlayer = null;
let playersData = [];

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

// Fonctions pour démarrer la page
window.onload = async function() {
    SqlRequest('is_admin').then(result => {
        if (!result.is_admin) {
            alert("Vous n'avez pas les droits pour accéder à cette page.");
            window.location.href = 'index.html';
        }
    });
    gameData = await getGame();
    playersData = await getPlayers();

    document.getElementById('title_page').textContent = "Vue Administrateur - " + gameData.name + " - PokerPaf";
    updateClientInterface();
}

async function updateClientInterface() {
    setupPlayers();
    getCurrentPlayer();

    activePlayerLabel.textContent = `${currentPlayer.name} (${currentPlayer.money} 🪙)`;
}

async function setupPlayers() {
    const PokerTable = document.getElementById('table');
    PokerTable.innerHTML = ''; // Clear existing players
    let newHtml = ``;

    newHtml += `
    <div class="pot-area">
        <div class="total-pot">${gameData.pot}</div>
        <div id="Mise" class="current-bet-display">Mise: ${gameData.last_bet}</div>
    </div>
    `;

    playersData.forEach((player, index) => {
        newHtml += `
            <div class="player-slot slot-${index}${player.is_folded ? ' blur-effect' : ''}${player.money <= 0 ? ' All-in-Blur' : ''}" onclick="changePlayer(${player.id})" data-id="${player.id}">
                <div class="player-info${gameData.current_player_id == player.id ? ' active' : ''}">
                    ${player.is_dealer ? '<div class="dealer-badge">D</div>' : ''}
                    
                    <span class="player-name">J${index + 1} : ${player.name}</span>
                    <span class="player-money">${player.money} 🪙</span><br>
                    <span class="player-bet">Mise: ${player.current_bet} 🪙</span>
                </div>
            </div>
        `;
    });

    PokerTable.innerHTML = newHtml;
}

async function getGame(id = null) {
    let gameId;
    if (id === null) {
        const urlParams = new URLSearchParams(window.location.search);
        gameId = urlParams.get('game_id');
    } else {
        gameId = id;
    }
    
    const response = await SqlRequest('getGame', { game_id: gameId });
    if (response.success) {
        return response.game;
    } else {
        console.error("Erreur lors de la récupération du jeu :", response.error);
        return null;
    }
}

async function getPlayers() {
    const response = await SqlRequest('getPlayers', { game_id: gameData.id });
    if (response.success) {
        return response.players;
    } else {
        console.error("Erreur lors de la récupération des joueurs :", response.error);
        return [];
    }
}

async function getCurrentPlayer() {
    currentPlayer = playersData.find(player => player.id === gameData.current_player_id);
}

// -----------------------------------------------------



// Fonctions pour les actions
async function changePlayer(id = null) {
    if (id === null) {
        const response = await SqlRequest('next_player', { game_id: gameData.id, current_player_id: gameData.current_player_id });
        if (response.success) {
            gameData.current_player_id = response.next_player_id;
        } else {
            console.error("Erreur lors du passage au joueur suivant :", response.error);
        }
    } else {
        const response = await SqlRequest('set_current_player', { game_id: gameData.id, player_id: id });
        if (response.success) {
            gameData.current_player_id = id;
        } else {
            console.error("Erreur lors du changement de joueur :", response.error);
        }
    }
    updateClientInterface();
}

async function playerFold() {
    const response = await SqlRequest('fold', { player_id: gameData.current_player_id });
    if (response.success) {
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du fold :", response.error);
    }
}

async function playerRaise() {
    const betAmount = parseInt(document.getElementById('raise-amount').value);

    if (betAmount <= 0) {
        alert("Veuillez entrer un montant de mise valide.");
        return;
    }
    const amount = betAmount + gameData.last_bet - currentPlayer.current_bet;

    if (currentPlayer.money < amount) {
        alert("Vous n'avez pas assez d'argent pour cette mise.");
        return;
    }

    const response = await SqlRequest('raise', { game_id: gameData.id, player_id: gameData.current_player_id, amount: amount, current_bet: currentPlayer.current_bet });
    if (response.success) {
        gameData.last_bet = currentPlayer.current_bet + amount;
        gameData.pot += amount;
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du raise :", response.error);
    }
}

async function playerFollow() {
    if (currentPlayer.current_bet >= gameData.last_bet) {
        changePlayer();
        return;
    }

    let delta_amount = gameData.last_bet - currentPlayer.current_bet;
    if (currentPlayer.money < delta_amount) {
        delta_amount = currentPlayer.money;
    }

    const response = await SqlRequest('follow', { game_id: gameData.id, player_id: gameData.current_player_id, amount: delta_amount });
    if (response.success) {
        gameData.pot += delta_amount;
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du follow :", response.error);
    }
}

async function playerAllIn() {
    const response = await SqlRequest('all_in', { game_id: gameData.id, player_id: gameData.current_player_id });
    if (response.success) {
        gameData = await getGame(gameData.id);
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du all-in :", response.error);
    }
}

// -----------------------------------------------------



// Fonctions pour les actions administratives
async function endGame() {
    const container = document.querySelector('.table-container');
        
    if (!container) {
        console.error("Conteneur .table-container introuvable");
        return;
    }

    if (document.querySelector('.win-panel')) return;

    const winOverlay = document.createElement('div');
    winOverlay.className = 'win-overlay';
    const winPanel = document.createElement('div');
    winPanel.className = 'win-panel';
    winPanel.innerHTML = `
        <h2>🏆 La partie est terminée ! 🏆<br>Qui a gagné ?</h2>
        <div id="winner-buttons-area"></div>
    `;
    
    winOverlay.appendChild(winPanel);
    container.appendChild(winOverlay);

    const area = document.getElementById('winner-buttons-area');
    const playerElements = document.querySelectorAll('.player-slot');

    playerElements.forEach(slot => {
        const id = slot.getAttribute('data-id');
        const name = slot.querySelector('.player-name').textContent.split(': ')[1];
        
        const btn = document.createElement('button');
        btn.className = 'btn-win';
        btn.innerText = name;
        btn.onclick = () => declareWinner(id);
        area.appendChild(btn);
    });

    document.getElementById('end-game-screen').style.display = 'flex';
    container.classList.add('blur-effect');
}

async function declareWinner(playerId) {
    const container = document.querySelector('.table-container');
    container.classList.remove('blur-effect');

    const response = await SqlRequest('declare_winner', { game_id: gameData.id, player_id: playerId });
    if (response.success) {
        const winPanel = document.querySelector('.win-panel');
        if (winPanel) {
            winPanel.innerHTML = `
                

                <h2>${playersData.find(player => player.id == playerId).name} gagne la partie et remporte ${gameData.last_bet} 🪙 !</h2>
                <button class="btn-back" onclick="window.location.href='index.html'">Retour à l'accueil</button>
                <button class="btn-replay" onclick="StartNewGame()">Rejouer</button>
            `;
        }
    } else {
        console.error("Erreur lors de la déclaration du gagnant :", response.error);
    }
}

async function StartNewGame() {
    window.location.reload();
}

async function addMoney() {
    let amount = parseInt(document.getElementById('money-amount').value);
    if (isNaN(amount)) {
        alert("Veuillez entrer un montant valide.");
        return;
    }

    const response = await SqlRequest('add_money', { player_id: gameData.current_player_id, amount: amount });
    if (response.success) {
        playersData = await getPlayers();
        updateClientInterface();
    } else {
        console.error("Erreur lors de l'ajout d'argent :", response.error);
    }
}

async function deleteGame() {
    const confirmation = confirm("Êtes-vous sûr de vouloir supprimer cette partie ? Cette action est irréversible.");
    if (!confirmation) return;

    const response = await SqlRequest('delete_game', { game_id: gameData.id });
    if (response.success) {
        console.log("Partie supprimée avec succès.", response.success);
        window.location.replace('index.html');
    } else {
        console.error("Erreur lors de la suppression du jeu :", response.error);
    }
}

