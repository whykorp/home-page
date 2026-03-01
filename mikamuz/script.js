document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const resultsList = document.getElementById('results');

    // Charger le fichier CSV
    fetch('Liste karaoké 2026.csv')
        .then(response => response.text())
        .then(data => {
            const lines = data.split('\n');
            const items = lines.map(line => line.trim());

// Fonction de recherche
function search(query) {
    resultsList.innerHTML = '';
    const filteredItems = items.filter(item => 
        item.toLowerCase().includes(query.toLowerCase())
    );
    filteredItems.forEach(item => {
        let displayedItem = item.replace('.mp4', '').replace(';;;', '');
        const li = document.createElement('li');
        li.textContent = displayedItem;
        resultsList.appendChild(li);
    });
}


            // Écouter les changements de la barre de recherche
            searchInput.addEventListener('input', function () {
                search(searchInput.value);
            });
        })
        .catch(error => console.error('Erreur de chargement du fichier :', error));
});