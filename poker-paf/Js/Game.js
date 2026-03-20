

// Fonction et variables essentiel

const activePlayerLabel = document.getElementById('active-player-name');
let gameData = null;
let currentPlayer = null;
let playersData = [];

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

// Fonctions pour démarrer la page
// Remplace ton window.onload par ceci :
window.onload = async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const gId = urlParams.get('game_id');

    // On récupère les données initiales
    gameData = await getGame(gId);
    playersData = await getPlayers();
    
    document.getElementById('title_page').textContent = gameData.name + " - Poker PAF";
    updateClientInterface();

    // --- LANCEMENT DU TEMPS RÉEL (SSE) ---
    startRealTimeSync(gId);
}

function startRealTimeSync(gameId) {
    const evtSource = new EventSource(`stream.php?game_id=${gameId}`);

    evtSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        // On met à jour nos variables globales avec les données fraîches du serveur
        gameData = data.game;
        playersData = data.players;

        console.log("🔄 Table synchronisée");
        
        // On rafraîchit l'affichage sans recharger la page
        updateClientInterface();
    };

    evtSource.onerror = function() {
        console.log("⚠️ Connexion perdue, tentative de reconnexion...");
    };
}

// Dans game.js
let lastDataHash = "";

async function updateClientInterface() {
    if (!gameData) return;

    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id'));
    const actionPanel = document.querySelector('.action-panel');

    // --- 1. GESTION DES PANNEAUX DE VICTOIRE (SYNCHRO SSE) ---
    console.log("DEBUG STATUS SSE:", gameData.status);    
    if (gameData.status === 'deciding') {
        // L'admin est en train de choisir : on affiche "Attente"
        showWaitingForWinner();
    } 
    else if (gameData.status === 'finished' && gameData.winner_id) {
        // Un gagnant a été validé en BDD
        const winner = playersData.find(p => Number(p.id) === Number(gameData.winner_id));
        const winnerName = winner ? winner.name : "Un joueur";
        
        // showVictoryScreen s'occupe maintenant de supprimer l'ancien panneau 
        // avant d'afficher le nouveau avec les confettis.
        showVictoryScreen(winnerName, gameData.pot);
    } 
    else if (gameData.status === 'playing') {
        // Si l'admin a relancé la partie (StartNewGame), on nettoie les panneaux
        // pour ceux qui n'auraient pas cliqué sur "OK"
        closeVictoryScreen(); 
    }

    // --- 2. GESTION DU HASH POUR L'INTERFACE DE TABLE ---

    const currentHash = `${gameData.pot}-${gameData.current_player_id}-${gameData.last_bet}-${gameData.is_locked}-${gameData.status}`;
    if (currentHash === lastDataHash) return;
    lastDataHash = currentHash;
    
    setupPlayers();
    getCurrentPlayer();
    
    // --- 3. GESTION DES BOUTONS ET DU TOUR ---

    const isLocked = Number(gameData.is_locked) === 1;
    const isMyTurn = Number(gameData.current_player_id) === viewerId;
    const turnInfo = document.querySelector('.turn-info');
    const inputs = document.querySelectorAll('.action-buttons button, .raise-group input, .raise-group button');

    // Bordure de tour
    if (!isLocked && isMyTurn && gameData.status === 'playing') {
        actionPanel.classList.add('my-turn-border');
    } else {
        actionPanel.classList.remove('my-turn-border');
    }

    if (isLocked) {
        if (turnInfo) turnInfo.innerHTML = `<span style="color: #f1c40f; font-weight: bold;">🔒 Jeu verrouillé. En attente de l'Admin...</span>`;
        inputs.forEach(el => el.disabled = true);
    } else if (!isMyTurn) {
        if (turnInfo && currentPlayer) {
            turnInfo.innerHTML = `Attente de <strong style="color: #e74c3c;">${currentPlayer.name}</strong>...`;
        }
        inputs.forEach(el => el.disabled = true);
    } else {
        if (turnInfo) turnInfo.innerHTML = `<span style="color: #2ecc71; font-weight: bold;">✅ C'est à vous de jouer !</span>`;
        inputs.forEach(el => el.disabled = false);
    }

    // Mise à jour du label du haut
    if (currentPlayer && activePlayerLabel) {
        activePlayerLabel.textContent = `${currentPlayer.name} (${currentPlayer.money} 🪙)`;
    }
}

// Fonction pour envoyer l'ordre au serveur
async function toggleGameLock(checkbox) {
    const status = checkbox.checked ? 0 : 1;
    
    // 1. On change d'abord le statut de verrouillage
    const response = await SqlRequest('toggle_lock', { 
        game_id: gameData.id, 
        status: status 
    });

    if (response && response.success) {
        // 2. Si on vient de DÉVERROUILLER (status 0), on reset le joueur actif
        if (status === 0) {
            console.log("🔓 Déverrouillage : Réinitialisation au joueur après le Dealer...");
            await resetToPostDealerPlayer();
        }
    } else {
        alert("Erreur lors du changement de statut");
        checkbox.checked = !checkbox.checked;
    }
}

async function resetToPostDealerPlayer() {
    // On cherche l'index du dealer dans playersData
    const dealerIndex = playersData.findIndex(p => Number(p.is_dealer) === 1);
    
    // Le premier joueur à parler est (dealer + 1), mais on doit gérer la boucle du tableau
    // et sauter les joueurs qui se sont couchés (is_folded)
    let nextIndex = (dealerIndex + 1) % playersData.length;
    
    // Sécurité : on cherche le prochain qui n'est pas couché
    let attempts = 0;
    while (playersData[nextIndex].is_folded && attempts < playersData.length) {
        nextIndex = (nextIndex + 1) % playersData.length;
        attempts++;
    }

    const firstPlayerId = playersData[nextIndex].id;

    // On envoie l'ordre au serveur de mettre ce joueur en actif
    await SqlRequest('set_current_player', { 
        game_id: gameData.id, 
        player_id: firstPlayerId 
    });
}

async function setupPlayers() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id')); // On récupère qui regarde la page

    const PokerTable = document.getElementById('table');
    PokerTable.innerHTML = ''; 
    let newHtml = `
    <div class="pot-area">
        <div class="total-pot">${gameData.pot}</div>
        <div id="Mise" class="current-bet-display">Mise: ${gameData.last_bet}</div>
    </div>
    `;

    playersData.forEach((player, index) => {
        const isDealer = Number(player.is_dealer) === 1;
        const isMe = Number(player.id) === viewerId; // Est-ce moi ?
        const isActive = Number(gameData.current_player_id) === Number(player.id);

        newHtml += `
            <div class="player-slot slot-${index} 
                ${player.is_folded ? ' blur-effect' : ''} 
                ${player.money <= 0 ? ' All-in-Blur' : ''} 
                ${isMe ? 'is-me' : ''}" 
                data-id="${player.id}">
                
                <div class="player-info ${isActive ? ' active' : ''}">
                    ${isDealer ? '<div class="dealer-badge">D</div>' : ''}
                    
                    <span class="player-name">${isMe ? 'VOUS' : 'J' + (index + 1)} : ${player.name}</span>
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

function getCurrentPlayer() {
    if (!playersData || !gameData) return null;
    
    // On cherche le joueur dans le tableau par son ID
    currentPlayer = playersData.find(player => Number(player.id) === Number(gameData.current_player_id));
    
    return currentPlayer;
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
    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id'));

    // Sécurité : si l'ID de l'URL n'est pas celui du joueur actif, on stoppe tout
    if (Number(gameData.current_player_id) !== viewerId) {
        alert("Ce n'est pas votre tour !");
        return;
    }
    const response = await SqlRequest('fold', { player_id: gameData.current_player_id });
    if (response.success) {
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du fold :", response.error);
    }
}

async function playerRaise() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id'));

    // Sécurité : si l'ID de l'URL n'est pas celui du joueur actif, on stoppe tout
    if (Number(gameData.current_player_id) !== viewerId) {
        alert("Ce n'est pas votre tour !");
        return;
    }
    const betInput = document.getElementById('raise-amount');
    const betValue = parseInt(betInput.value);

    // Validation basique du champ de saisie
    if (isNaN(betValue) || betValue <= 0) {
        alert("Veuillez saisir un montant valide.");
        return;
    }

    // Envoi de la requête de relance au serveur
    const response = await SqlRequest('raise', { 
        game_id: gameData.id, 
        player_id: gameData.current_player_id, 
        bet_input: betValue 
    });

    if (response && response.success) {
        betInput.value = ''; // Réinitialisation du champ
        await changePlayer(); // Passage au joueur suivant
    } else {
        // Affichage de l'erreur retournée par le PHP (ex: "Fonds insuffisants")
        alert("Erreur : " + (response ? response.error : "Serveur injoignable"));
    }
}

async function playerFollow() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id'));

    // Sécurité : si l'ID de l'URL n'est pas celui du joueur actif, on stoppe tout
    if (Number(gameData.current_player_id) !== viewerId) {
        alert("Ce n'est pas votre tour !");
        return;
    }
    // Si le joueur a déjà mis la somme requise
    if (Number(currentPlayer.current_bet) >= Number(gameData.last_bet)) {
        // AU LIEU DE changePlayer(), on vérifie si le tour est fini
        const activePlayers = playersData.filter(p => Number(p.is_folded) === 0);
        const allMatched = activePlayers.every(p => Number(p.current_bet) === Number(gameData.last_bet));

        if (allMatched) {
            await SqlRequest('toggle_lock', { game_id: gameData.id, status: 1 });
            return; // On s'arrête là, le SSE fera le reste
        }
        
        await changePlayer();
        return;
    }
    // 1. On force la récupération du joueur actuel s'il est manquant
    if (!currentPlayer) {
        await getCurrentPlayer();
    }

    // 2. Si après ça il est toujours introuvable, on arrête pour éviter l'erreur
    if (!currentPlayer) {
        console.error("Erreur : Impossible de trouver les données du joueur actif.");
        return;
    }

    // 3. Logique de suivi
    if (Number(currentPlayer.current_bet) >= Number(gameData.last_bet)) {
        changePlayer();
        return;
    }

    // Calcul du montant à ajouter
    let delta_amount = Math.max(0, Number(gameData.last_bet) - Number(currentPlayer.current_bet));
        
    if (Number(currentPlayer.money) < delta_amount) {
        delta_amount = Number(currentPlayer.money);
    }

    const response = await SqlRequest('follow', { 
        game_id: gameData.id, 
        player_id: gameData.current_player_id, 
        amount: delta_amount 
    });

    if (response && response.success) {
        // --- LOGIQUE DE VÉRIFICATION DU TOUR ---
        // On récupère les données fraîches pour savoir si c'était le dernier à suivre
        const refreshedPlayers = await getPlayers();
        
        // On vérifie si tout le monde a mis la même somme (et n'est pas couché)
        const activePlayers = refreshedPlayers.filter(p => Number(p.is_folded) === 0);
        const allMatched = activePlayers.every(p => Number(p.current_bet) === Number(gameData.last_bet));

        if (allMatched) {
            // On demande au serveur de verrouiller la partie (si tu as l'action prévue)
            await SqlRequest('toggle_lock', { game_id: gameData.id, status: 1 });
            console.log("Tour terminé, table verrouillée.");
        } else {
            // Sinon on passe juste au suivant
            await changePlayer();
        }
    }
}

async function playerAllIn() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewerId = Number(urlParams.get('player_id'));

    // Sécurité : si l'ID de l'URL n'est pas celui du joueur actif, on stoppe tout
    if (Number(gameData.current_player_id) !== viewerId) {
        alert("Ce n'est pas votre tour !");
        return;
    }
    const response = await SqlRequest('all_in', { game_id: gameData.id, player_id: gameData.current_player_id });
    if (response.success) {
        gameData = await getGame(gameData.id);
        playersData = await getPlayers();
        changePlayer();
    } else {
        console.error("Erreur lors du all-in :", response.error);
    }
}

// Étape 1 : Afficher l'attente quand l'admin termine la partie
function showWaitingForWinner() {
    if (document.getElementById('player-modal')) return;

    const overlay = document.createElement('div');
    overlay.id = 'player-modal';
    overlay.className = 'player-win-overlay';
    
    overlay.innerHTML = `
        <div class="player-win-content">
            <h2>FIN DE PARTIE</h2>
            <p class="waiting-text">Détermination du gagnant en cours...</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

let victoryDisplayedFor = null; // Variable globale

function showVictoryScreen(winnerName, amount) {
    // 1. On cherche s'il y a déjà un panneau ouvert (celui d'attente par exemple)
    const existingModal = document.getElementById('player-modal');
    if (existingModal) {
        existingModal.remove(); // On le supprime proprement
    }

    // 2. On crée le nouveau panneau de victoire
    const modal = document.createElement('div');
    modal.id = 'player-modal';
    modal.className = 'player-win-overlay';

    modal.innerHTML = `
        <div class="player-win-content">
            <h2 class="victory-title">VICTOIRE DE ${winnerName}</h2>
            <span class="win-amount">Il remporte ${amount} 🪙</span>
            <button class="btn-ok" onclick="closeVictoryScreen()">OK</button>
        </div>
    `;

    document.body.appendChild(modal);
    
    // On lance les confettis
    if (typeof startConfetti === "function") startConfetti();
}

// Et dans ton closeVictoryScreen
function closeVictoryScreen() {
    const modal = document.getElementById('player-modal');
    if (modal) modal.remove();
    victoryDisplayedFor = null; // Reset pour la prochaine partie
}


function startConfetti() {
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed; width: 10px; height: 10px; 
            background: ${['#d4af37','#ffffff','#2ecc71'][Math.floor(Math.random()*3)]};
            top: -10px; left: ${Math.random() * 100}vw;
            z-index: 10001; pointer-events: none;
            border-radius: 50%;
            animation: fall ${2 + Math.random() * 3}s linear forwards;
        `;
        document.body.appendChild(confetti);
        setTimeout(() => confetti.remove(), 5000);
    }
}