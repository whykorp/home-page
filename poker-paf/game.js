const mysql = require('mysql2');
const connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'poker_paf'
  });

let actualPlayerID = null; // Variable globale pour stocker l'ID du joueur actuel
let actualGameID = null; // Variable globale pour stocker l'ID de la partie actuelle
let playerChips = {}; // Objet pour stocker les jetons de chaque joueur
let playerNames = {}; // Objet pour stocker les noms de chaque joueur
let startBlind = 20; // Blinde de départ à prendre depuis la BDD
let blinds = {}; // Objet pour stocker les blinds de chaque joueur
// ############################################################################################################################
let current_blind = 0; // Initialisation de la blinde actuelle en variable globale, mis à jour automatiquement

function changePlayer() { // Uniquement pour les tests, à remplacer par une fonction qui change de joueur dans la Boucle de jeu
    // Récupération du joueur actuel depuis la base de données
    connection.connect(err => {
        if (err) {
            console.error('Erreur de connexion : ' + err.stack);
            return;
        }
        console.log('Connecté à MySQL avec l\'ID ' + connection.threadId);
    });
    const sql = "SELECT current_player_id FROM games WHERE game_id = ?";
    const values = [actualGameID];
    connection.query(sql, values, (error, results) => {
        if (error) {
            console.error('Erreur lors de la récupération du joueur actuel : ' + error.stack);
            return;
        }
        if (results.length > 0) {
            actualPlayerID = results[0].current_player_id;
            console.log('Joueur actuel : ' + actualPlayerID);
        } else {
            console.log('Aucun résultat trouvé pour le joueur actuel.');
        }
    });

    // Changement du joueur actuel dans la base de données (pour les tests)
    // On cherche le numéro d'ID du joueur suivant l'actuel qui a le même game_id
    const sql2 = "SELECT player_id FROM players WHERE game_id = ? AND player_id > ? ORDER BY player_id ASC LIMIT 1";
    const values2 = [actualGameID, actualPlayerID];
    connection.query(sql2, values2, (error, results) => {
        if (error) {
            console.error('Erreur lors de la récupération du joueur suivant : ' + error.stack);
            return;
        }
        if (results.length > 0) {
            const nextPlayerID = results[0].player_id;
            const sqlUpdate = "UPDATE games SET current_player_id = ? WHERE game_id = ?";
            const valuesUpdate = [nextPlayerID, actualGameID];
            connection.query(sqlUpdate, valuesUpdate, (error, results) => {
                if (error) {
                    console.error('Erreur lors de la mise à jour du joueur actuel : ' + error.stack);
                    return;
                }
                console.log('Joueur actuel mis à jour avec succès.');
            });
        } else {
            console.log('Aucun résultat trouvé pour le joueur suivant.');
        }
    });
    console.log('Changement de joueur : ' + player);
}

function UpdateStatus() { // Fonction mettant à jour la blinde actuel en fonction des blinds de chaque joueur
    current_blind = Math.max(...Object.values(blinds));
}

function UpdateLabels() { // Fonction pour mettre à jour les labels
    let pot = Object.values(blinds).reduce((a, b) => a + b, 0);
    let money_labels = {}
    for (key of Object.keys(money)) {
        money_labels[key] = money[key] - blinds[key];
    }
    for (const key of Object.keys(money_labels)) {
        const label = document.getElementById(`label-${key}`);
        if (label) {
            label.innerText = money_labels[key] + " 🪙";
        }
    }
}

function SeCoucher() { // fonction pour se coucher, elle vérifie si le joueur est en jeu et si sa mise actuelle est inférieure a la blinde actuel, si c'est le cas, il se couche et est retiré de la liste des joueurs encore en jeu
    UpdateStatus();

    if (current_blind > blinds[current_player] && player_list.includes(current_player)) { // Si le joueur est en jeu et que sa mise actuelle est inférieure a la blinde actuel, il se couche
        player_list.splice(player_list.indexOf(current_player), 1);
    }

    UpdateLabels();
}

function Suivre() {
    if (player_list.includes(current_player)) { // Si le joueur est en jeu, il suit
        UpdateStatus();
        if (current_blind > blinds[current_player] && money[current_player] >= current_blind) { // Si la blinde actuelle est supérieur a la sienne et qu'il est en capacité de la payer
            blinds[current_player] = current_blind;
        } else {
            Tapis();
        }

        UpdateLabels();
    }
}

function Relancer() {
    if (player_list.includes(current_player)) { // Si le joueur est en jeu
        UpdateStatus();

        if (+money_input.value >= Math.max(...Object.values(money))){
            Tapis();
        } else {
            if (money[current_player] > (current_blind) && +money_input.value <= money[current_player] - current_blind && +money_input.value > 0 && +money_input.value % (start_blind / 2) == 0) { // Si le joueur a assez d'argent pour suivre la blinde actuelle et relancer
                blinds[current_player] = current_blind + +money_input.value;
            }
        }
        UpdateLabels();
    }
}

function Tapis() {
    if (player_list.includes(current_player)) { // Si le joueur est en jeu, il fait tapis
        UpdateStatus();

        if (money[current_player] < Math.max(...Object.values(money))) { // Si le joueur n'est pas le plus riche
            blinds[current_player] = money[current_player];

        } else { // Si le joueur est le plus riche
            let temp_money = {...money};
            temp_money[current_player] = 0;
            let second_most_rich = Math.max(...Object.values(temp_money));


            blinds[current_player] = second_most_rich;

        }
        UpdateLabels();
    }
}



// Fonction pour mettre à jour les jetons (Chips)
function updateChips(playerId, amount, btnElement) {
    // 1. MISE À JOUR VISUELLE IMMÉDIATE (Optimiste)
    const playerCard = btnElement.closest('.player-card');
    const chipsDisplay = playerCard.querySelector('.player-chips');
    
    // On sauvegarde l'ancienne valeur au cas où le serveur plante
    const oldChipsValue = chipsDisplay.innerText;
    let currentChips = parseInt(oldChipsValue);
    let newChips = currentChips + amount;
    
    // On change l'affichage tout de suite
    chipsDisplay.innerHTML = newChips + " 🪙";

    // 2. ENVOI À LA BASE DE DONNÉES
    const formData = new FormData();
    formData.append('player_id', playerId);
    formData.append('amount', amount);

    fetch('update_chips.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // On récupère en texte pour débugger
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (!data.success) {
                throw new Error(data.message);
            }
            console.log("Sync DB OK pour joueur " + playerId);
        } catch (e) {
            // 3. ANNULATION SI ERREUR
            console.error("Erreur serveur, retour à l'ancien solde. Réponse reçue :", text);
            chipsDisplay.innerHTML = oldChipsValue;
            alert("Erreur de synchronisation : " + text);
        }
    })
    .catch(error => {
        chipsDisplay.innerHTML = oldChipsValue;
        console.error('Erreur réseau :', error);
    });
}

let currentStep = 0;
const steps = [
    { text: "Mélangez et distribuez les cartes (2 par joueur)", btn: "C'est fait !" },
    { text: "Au tour de J1 : Posez la Petite Blind", btn: "OK" },
    { text: "Au tour de J2 : Posez la Grosse Blind", btn: "OK" },
    { text: "Place au jeu ! Suivez les tours en bas.", btn: "Masquer le guide" }
];

function nextStep() {
    const guideBox = document.getElementById('game-guide');
    const textZone = document.getElementById('guide-text');
    const btnZone = document.getElementById('guide-btn');

    if (currentStep < steps.length) {
        textZone.innerText = steps[currentStep].text;
        btnZone.innerText = steps[currentStep].btn;
        currentStep++;
    } else {
        // Une fois fini, on cache le guide ou on le réduit
        guideBox.style.display = 'none';
        // Ici, on pourrait activer les boutons d'action (Suivre, Miser...)
        enableActions(true);
    }
}

function enableActions(status) {
    const btns = document.querySelectorAll('.btn-action, .btn-gold, .btn-validate');
    btns.forEach(b => b.disabled = !status);
    if(!status) {
        document.querySelector('.action-panel').style.opacity = "0.5";
    } else {
        document.querySelector('.action-panel').style.opacity = "1";
    }
}

// Au chargement, on bloque les actions tant que le guide n'est pas fini
window.onload = () => enableActions(false);

// Ta fonction deleteGame déjà existante (rappel)
function deleteGame(idPartie) {
    if (confirm("Supprimer la partie ?")) {
        let formData = new FormData();
        formData.append('game_id', idPartie);

        fetch('delete_game.php', { method: 'POST', body: formData })
        .then(() => window.location.href = 'index.php');
    }
}

function playerAction(type) {
    const raiseInput = document.getElementById('raise-amount');
    let amount = (type === 'raise') ? raiseInput.value : 0;
    const gameId = new URLSearchParams(window.location.search).get('game_id');

    // On prépare les données pour le PHP
    let fd = new FormData();
    fd.append('action', type);
    fd.append('amount', amount);
    fd.append('game_id', gameId);

    fetch('play_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // --- ÉTAPE 1 : Gérer le Halo (Tu m'as dit que ça c'est OK) ---
            document.querySelectorAll('.player-info').forEach(p => p.classList.remove('active'));
            
            // On trouve la "carte" du nouveau joueur grâce à son ID
            const nextPlayerSlot = document.querySelector(`[data-id="${data.next_player_id}"]`);
            const nextInfoBox = nextPlayerSlot.querySelector('.player-info');
            nextInfoBox.classList.add('active');

            // --- ÉTAPE 2 : Mettre à jour le NOM en bas (C'est ça qui te manque) ---
            // On récupère le texte du nom à l'intérieur de la carte du nouveau joueur
            const nextNameRaw = nextInfoBox.querySelector('.player-name').innerText; 
            // On nettoie un peu le texte (pour enlever le "J1 :" par exemple)
            const cleanName = nextNameRaw.split(':').pop().trim();
            
            // On l'injecte dans le texte "Au tour de : ..."
            document.getElementById('active-player-name').innerText = cleanName;

            // --- ÉTAPE 3 : Mettre à jour le POT et vider l'input ---
            if(data.new_pot) document.getElementById('main-pot').innerText = data.new_pot;
            if(raiseInput) raiseInput.value = "";
        }
    })
    .catch(err => console.error("Erreur action:", err));
}

function startNewRound() {
    // Récupère l'ID dans l'URL (?game_id=4)
    const urlParams = new URLSearchParams(window.location.search);
    const gId = urlParams.get('game_id');

    if (!gId) return alert("ID de partie manquant dans l'URL !");

    let fd = new FormData();
    fd.append('game_id', gId);

    fetch('next_round.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload(); // On recharge pour voir le nouveau Dealer
        }
    })
    .catch(err => console.error("Erreur bouton :", err));
}

function closeTable() {
    if (confirm("Fermer la table ?")) {
        // Supprimer la partie et les joueurs associés à la partie dans la db 
        const gameId = new URLSearchParams(window.location.search).get('game_id');
        fetch('delete_game.php', {
            method: 'POST',
            body: new URLSearchParams({'game_id': gameId})
        })
        .then(() => window.location.href = 'index.php');   
    }
}