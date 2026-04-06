

// Fonction et variables essentiel

const activePlayerLabel = document.getElementById('active-player-name');
const activePlayerDelta = document.getElementById('active-player-delta-blind');
let gameData = null;
let currentPlayer = null;
let playersData = [];

let isGameLockToggled = false; // Variable pour éviter les boucles infinies lors du toggle

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

// Fonction pour ouvrir le menu administrateur
function toggleAdminMenu() {
    const menu = document.getElementById('admin-menu');
    menu.classList.toggle('active');
}

// Fonctions pour démarrer la page
window.onload = async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const gId = urlParams.get('game_id');
    const result = await SqlRequest('is_admin');
        console.log("Vérification des droits administrateur :", result);
        if (!result.is_admin) {
            alert("Vous n'avez pas les droits pour accéder à cette page.");
            window.location.href = 'index.html';
        }
    gameData = await getGame();
    playersData = await getPlayers();

    document.getElementById('title_page').textContent = "Vue Administrateur - " + gameData.name + " - PokerPaf";
    updateClientInterface();

    startRealTimeSync(gId);
}

async function updateClientInterface() {
    setupPlayers();
    getCurrentPlayer();
    refreshAdminPanel();

    activePlayerLabel.textContent = `${currentPlayer.name} (${currentPlayer.money} 🪙)`;
    activePlayerDelta.textContent = `${gameData.last_bet - currentPlayer.current_bet}`;
}

async function refreshAdminPanel() {
    const adminPanel = document.getElementById('admin-lock-control');
    if (!adminPanel) return;

    let statsItem = document.getElementById('stat-item');
    if (statsItem) {
        statsItem.innerHTML = `
        MISE ACTUELLE: <strong id="current-bet">${gameData.last_bet}</strong><br>
        POT ACTUEL <strong id="current-pot">${gameData.pot}</strong>
        `;
    }

    const lockStatus = Number(gameData.is_locked);

    let isLocked = false;
    if (lockStatus === 0) {
        isLocked = false;
    } else if (lockStatus === 1) {
        isLocked = true;
    } else if (lockStatus === 2) {
        isLocked = false; // On considère que le statut "jeu fin" est aussi un état verrouillé pour les actions classiques
        isGameLockToggled = true; // On indique que le toggle a été déclenché pour éviter les boucles infinies
    }

    let lockSwitch = document.getElementById('lock-switch');

    // On ne crée le HTML que s'il n'existe pas encore
    if (!lockSwitch) {
        adminPanel.innerHTML = `
            <div class="admin-control-group">
                <span>Autoriser le jeu :</span>
                <label class="switch">
                    <input type="checkbox" id="lock-switch" ${!isLocked ? 'checked' : ''} onchange="toggleGameLock(this)">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="admin-control-group">
                <span>Autoriser le jeu fin :</span>
                <label class="switch">
                    <input type="checkbox" id="lock-switch_toggle" ${isGameLockToggled ? 'checked' : ''} onchange="toggleGameLockToggle(this)">
                    <span class="slider"></span>
                </label>
            </div>
        `;
    } else {
        lockSwitch.checked = !isLocked;
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

async function toggleGameLockToggle(checkbox) {
    const status = checkbox.checked ? 0 : 1;

    isGameLockToggled = status === 1 ? false : true;

    await SqlRequest('toggle_lock', {
        game_id: gameData.id,
        status: isGameLockToggled ? 2 : 1 // 2 pour "jeu fin", sinon on remet le statut de verrouillage
    });
}

async function resetToPostDealerPlayer() {
    // On cherche l'index du dealer dans playersData
    const dealerIndex = playersData.findIndex(p => Number(p.is_dealer) === 1);
    
    // Le premier joueur à parler est (dealer + 1), mais on doit gérer la boucle du tableau
    // et sauter les joueurs qui se sont couchés (is_folded)
    let nextIndex = (dealerIndex + 1) % playersData.length;
    
    console.log("Index du dealer :", nextIndex, playersData[nextIndex].money <= 0);
    // Sécurité : on cherche le prochain qui n'est pas couché et qui a de l'argent
    let attempts = 0;
    while ((playersData[nextIndex].is_folded || playersData[nextIndex].money <= 0) && attempts < playersData.length) {
        console.log("Index du next :", nextIndex);
        nextIndex = (nextIndex + 1) % playersData.length;
        attempts++;
    }

    const firstPlayerId = playersData[nextIndex].id;

    playerLoopChange(firstPlayerId); // On met à jour le joueur de boucle pour éviter les blocages

    // On envoie l'ordre au serveur de mettre ce joueur en actif
    await SqlRequest('set_current_player', { 
        game_id: gameData.id, 
        player_id: firstPlayerId 
    });
}

async function playerLoopChange(playerId) {
    // Fonction pour changer le joueur de la boucle
    console.log("Mise à jour du joueur de boucle côté client :", playerId);
    const response = await SqlRequest('set_player_loop', { game_id: gameData.id, player_id: playerId });
    if (response.success) {
        console.log("Joueur de boucle mis à jour côté serveur.", response.player_id);
    } else {
        console.error("Erreur lors de la mise à jour du joueur de boucle :", response.error);
    }
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
        // On s'assure de comparer avec le nombre 1 car la BDD renvoie souvent des strings ou des entiers
        const isDealer = Number(player.is_dealer) === 1;
    
        newHtml += `
            <div class="player-slot slot-${index}${player.is_folded ? ' blur-effect' : ''}${player.money <= 0 ? ' All-in-Blur' : ''}" onclick="changePlayer(${player.id})" data-id="${player.id}">
                <div class="player-info${gameData.current_player_id == player.id ? ' active' : ''}">
                    
                    ${isDealer ? '<div class="dealer-badge">D</div>' : ''}
                    
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
    await SqlRequest('update_game_status', { 
        game_id: gameData.id, 
        status: 'deciding' 
    });
    // 1. On vérifie si le panel existe déjà pour éviter les doublons
    if (document.querySelector('.win-overlay')) return;

    // 2. Création de l'overlay (on l'appelle win-overlay pour le CSS)
    const winOverlay = document.createElement('div');
    winOverlay.className = 'win-overlay';
    
    // 3. Création du panel blanc/bleu
    const winPanel = document.createElement('div');
    winPanel.className = 'win-panel';
    winPanel.innerHTML = `
        <h2>🏆 La partie est terminée ! 🏆<br>Qui a gagné ?</h2>
        <div id="winner-buttons-area"></div>
        <button class="btn-spaction" onclick="this.parentElement.parentElement.remove()">Annuler</button>
    `;
    
    winOverlay.appendChild(winPanel);
    
    // IMPORTANCE : On l'attache au BODY pour qu'il soit au-dessus de TOUT (même l'admin)
    document.body.appendChild(winOverlay);

    const area = document.getElementById('winner-buttons-area');
    const playerElements = document.querySelectorAll('.player-slot');

    playerElements.forEach(slot => {
        const id = slot.getAttribute('data-id');
        // On récupère le nom proprement
        const nameElement = slot.querySelector('.player-name');
        const name = nameElement ? nameElement.textContent.replace('VOUS', '').replace(':', '').trim() : "Joueur " + id;
        
        const btn = document.createElement('button');
        btn.className = 'btn-win';
        btn.innerText = name;
        btn.onclick = () => declareWinner(id);
        area.appendChild(btn);
    });

    // On supprime la ligne qui cherchait 'end-game-screen' car winOverlay fait déjà le job
}
async function declareWinner(playerId) {
    console.log("Début de la procédure de victoire...");

    // 1. Première requête : On définit le gagnant
    const resWinner = await SqlRequest('set_winner', { 
        game_id: gameData.id, 
        player_id: playerId 
    });

    if (resWinner && resWinner.success) {
        console.log("✅ Winner ID mis à jour en BDD");

        // 2. Deuxième requête : On passe le statut à 'finished'
        // C'est cette requête qui va déclencher l'écran de victoire chez les joueurs via le SSE
        const resStatus = await SqlRequest('update_game_status', { 
            game_id: gameData.id, 
            status: 'finished' 
        });

        if (resStatus && resStatus.success) {
            console.log("✅ Statut passé à 'finished'");
            
            // On met a jour les valeurs dans BDD
            const result = await SqlRequest('declare_winner', { 
                game_id: gameData.id, 
                player_id: playerId 
            });

            if (result && result.success){
                // Mise à jour de l'interface Admin
                showAdminWinPanel(playerId, result.pot);

                const SetupBlinds = await SqlRequest('setup_blinds', { 
                    game_id: gameData.id,
                    dealer_id: result.next_dealer_id
                });

                if (SetupBlinds && SetupBlinds.success) {
                    console.log("✅ Blinds réinitialisées pour la prochaine partie");

                    logs('money_player_modification', SetupBlinds.dealer_id, [SetupBlinds.blind_amount]);
                    logs('money_player_modification', SetupBlinds.postdealer_id, [SetupBlinds.small_blind]);
                } else {
                    console.error("Erreur lors de la réinitialisation des blinds :", SetupBlinds ? SetupBlinds.error : "Pas de réponse");
                }
            } else {
                console.log("Dommage tu y étais presque")
            }
        }
    } else {
        alert("Erreur lors de la mise à jour du gagnant.");
    }
}

// Fonction isolée pour l'affichage du panel admin (plus propre)
function showAdminWinPanel(playerId, pot) {
    const container = document.querySelector('.table-container');
    if (container) container.classList.remove('blur-effect');

    const winPanel = document.querySelector('.win-panel');
    if (winPanel) {
        const winner = playersData.find(p => p.id == playerId);
        winPanel.innerHTML = `
            <h2>🏆 Victoire de ${winner ? winner.name : 'Joueur'}</h2>
            <p>Le pot de ${pot} 🪙 lui a été attribué.</p>
            <button class="btn-spaction" onclick="StartNewGame()">Nouvelle Manche</button>
        `;
    }
}
async function StartNewGame() {
    // 1. On force le verrouillage (status: 1) avant de relancer
    try {
        const response = await SqlRequest('toggle_lock', { 
            game_id: gameData.id, 
            status: 1  // 1 pour verrouillé
        });

        if (response.success) {
            console.log("Partie verrouillée, relance en cours...");
            // 2. On recharge la page pour démarrer la nouvelle main
            const response = await SqlRequest('update_game_status', { 
                game_id: gameData.id,
                status: 'playing'
            });
            if(response.success){
                window.location.reload();
            } else {
                console.error("Erreur de changement de status :", response.error);
            }
        } else {
            console.error("Erreur de verrouillage :", response.error);
            // Optionnel : on recharge quand même ou on affiche une alerte
            window.location.reload();
        }
    } catch (error) {
        console.error("Erreur réseau :", error);
        window.location.reload();
    }
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





// --- Gestions des logs ---


async function logs(action, player_id, params = []) {

    const strParams = params.join(';'); // Convertit le tableau de paramètres en une string lisible

    const response = await SqlRequest('log_submit', {
        game_id: gameData.id,
        action: action,
        player_id: player_id,
        params: strParams // On convertit le tableau de paramètres en string pour l'enregistrer proprement
    });
    console.log("Log envoyé :", { action, player_id, strParams });
    if (response && !response.success) {
        console.error("Erreur lors de l'enregistrement du log :", response.error);
    }
}

