
// ################# Ces infos sont a changer en fonction de la BDD ##########################################################

let text = document.getElementById("text");
let money_input = document.getElementById("Money");

let money = { // Se rapelle de l'argent que chaque joueur a au début de la partie
    "Ender": 10000,
    "Sophie": 1000,
    "Léo": 1000,
    "Mélanie": 1000,
    "Jean": 500,
    "Lucie": 1000
};
let blinds = { // Se rapelle de l'argent actuellement mis en jeu par chaque joueur
    "Ender": 20,
    "Sophie": 30,
    "Léo": 20,
    "Mélanie": 20,
    "Jean": 20,
    "Lucie": 20
};
let player_list = ["Ender", "Sophie", "Léo", "Mélanie", "Jean", "Lucie"]; // Liste des joueurs encore en jeu a replir avec la BDD
let current_player = "Ender"; // Joueur actuel a prendre depuis la BDD
let start_blind = 20; // Blinde de départ a prendre depuis la BDD

// ##########################################################################################################################
let current_blind = 0; // Initialistation de la blinde actuelle en variable globale, mis a jour automatiquement


function ChangePlayer(player) { // Uniquement pour les tests, à remplacer par une fonction qui change de joueur dans la Boucle de jeu
    current_player = player;
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

    // Reste à faire avec ton code
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


