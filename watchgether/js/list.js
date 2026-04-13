let allMovies = []; // Stockage local de la liste entière

async function loadList() {
    const response = await fetch('RequestHandler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getMyList' })
    });
    const data = await response.json();
    
    if (data.success) {
        allMovies = data.movies;
        renderList();
    }
}

function renderList() {
    const grid = document.getElementById('myListGrid');
    const sort = document.getElementById('sortOrder').value;
    const type = document.getElementById('filterType').value;
    const status = document.getElementById('filterStatus').value;

    // 1. Filtrage
    let filtered = allMovies.filter(m => {
        const matchType = (type === 'all' || m.type === type);
        const matchStatus = (status === 'all' || m.vu == status);
        return matchType && matchStatus;
    });

    // 2. Tri
    filtered.sort((a, b) => {
        if (sort === 'titre_asc') return a.titre.localeCompare(b.titre);
        if (sort === 'date_ajout_asc') return new Date(a.date_ajout) - new Date(b.date_ajout);
        if (sort === 'date_ajout_desc') return new Date(b.date_ajout) - new Date(a.date_ajout);
    });

    // 3. Affichage
    grid.innerHTML = '';
    document.getElementById('movieCount').innerText = filtered.length;

    filtered.forEach(m => {
        const card = document.createElement('div');
        card.className = `relative group bg-slate-800 rounded-xl overflow-hidden border ${m.vu == 1 ? 'border-green-500/50' : 'border-slate-700'}`;
        card.innerHTML = `
            <img src="https://image.tmdb.org/t/p/w500${m.affiche_path}" class="w-full h-auto ${m.vu == 1 ? 'opacity-40 grayscale' : ''}">
            <div class="p-3">
                <h3 class="font-bold text-sm truncate">${m.titre}</h3>
                <div class="flex gap-2 mt-2">
                    <button onclick="toggleVu(${m.id})" class="flex-grow py-1 rounded text-[10px] font-bold ${m.vu == 1 ? 'bg-green-600' : 'bg-slate-700 hover:bg-slate-600'}">
                        ${m.vu == 1 ? 'VU' : 'À VOIR'}
                    </button>
                    <button onclick="deleteMovie(${m.id})" class="bg-red-900/50 hover:bg-red-600 px-2 py-1 rounded text-[10px]">
                        🗑️
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Actions rapides
async function toggleVu(id) {
    const res = await fetch('RequestHandler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggleViewed', params: { movie_id: id } })
    });
    const data = await res.json();
    if (data.success) {
        // On met à jour localement pour ne pas recharger toute l'API
        const movie = allMovies.find(m => m.id == id);
        movie.vu = movie.vu == 1 ? 0 : 1;
        renderList();
    }
}

async function deleteMovie(id) {
    if(!confirm("Supprimer ce film de ta liste ?")) return;
    const res = await fetch('RequestHandler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteMovie', params: { movie_id: id } })
    });
    const data = await res.json();
    if (data.success) {
        allMovies = allMovies.filter(m => m.id != id);
        renderList();
    }
}

loadList();