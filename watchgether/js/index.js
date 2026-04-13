// Fonction principale pour parler au RequestHandler
async function apiRequest(action, params = {}) {
    const response = await fetch('RequestHandler.php', {
        method: 'POST',
        headers: { 'Content-Type:': 'application/json' },
        body: JSON.stringify({ action, params })
    });
    return await response.json();
}

// Action de recherche
async function performSearch() {
    const query = document.getElementById('searchInput').value;
    if (!query) return;

    const grid = document.getElementById('resultsGrid');
    grid.innerHTML = '<p class="col-span-full text-center">Recherche en cours...</p>';

    const data = await apiRequest('searchTMDB', { query: query });
    
    grid.innerHTML = ''; // On vide le message de chargement

    if (data.results) {
        data.results.forEach(item => {
            if (!item.poster_path) return;

            const title = item.title || item.name;
            const poster = `https://image.tmdb.org/t/p/w500${item.poster_path}`;
            const type = item.media_type === 'tv' ? 'serie' : 'film';

            const card = document.createElement('div');
            card.className = 'bg-slate-800 rounded-xl overflow-hidden border border-slate-700 hover:scale-105 transition-transform';
            card.innerHTML = `
                <img src="${poster}" class="w-full h-auto">
                <div class="p-3">
                    <h3 class="font-bold text-sm truncate">${title}</h3>
                    <button onclick="addMovie(${item.id}, '${title.replace(/'/g, "\\'")}', '${item.poster_path}', '${type}')" 
                            class="mt-2 w-full bg-green-600 text-xs py-2 rounded font-bold hover:bg-green-500">
                        + Ajouter
                    </button>
                </div>
            `;
            grid.appendChild(card);
        });
    }
}

// Action d'ajout
async function addMovie(tmdbId, title, posterPath, type) {
    const result = await apiRequest('addMovie', {
        tmdb_id: tmdbId,
        titre: title,
        affiche_path: posterPath,
        type: type
    });

    if (result.success) {
        alert('Ajouté à la liste !');
    } else {
        alert('Erreur lors de l\'ajout.');
    }
}