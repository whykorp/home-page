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