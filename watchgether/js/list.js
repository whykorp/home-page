let allMovies = []; // Stockage local de la liste entière

window.onload = () => {
    loadList();
    document.getElementById('sortOrder').addEventListener('change', renderList);
    document.getElementById('filterType').addEventListener('change', renderList);
    document.getElementById('filterStatus').addEventListener('change', renderList);
}

async function apiRequest(action, params = {}) {
    try {
        const response = await fetch('../RequestHandler.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify({ action, params })
        });
        return await response.json();
    } catch (error) {
        console.error("Erreur API:", error);
        return { success: false, error: "Erreur de connexion au serveur" };
    }
}

async function addMovie(tmdbId, title, posterPath, type) {
    const result = await apiRequest('addMovie', {
        tmdb_id: tmdbId,
        titre: title,
        affiche_path: posterPath,
        type: type
    });

    if (result.success) {
        alert(`"${title}" a été ajouté à votre liste ! 🍿`);
        closeModal();
    } else {
        alert('Erreur lors de l\'ajout. Vérifie si le film n\'est pas déjà présent.');
    }
}

async function loadList() {
    const response = await fetch('../RequestHandler.php', {
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
        // On ajoute 'flex flex-col' pour que le contenu s'empile proprement
        card.className = `relative group bg-slate-800 rounded-xl overflow-hidden border ${m.vu == 1 ? 'border-green-500/50' : 'border-slate-700'} flex flex-col h-full`;

        if (currentView === 'partner') {
            card.innerHTML = `
                <div class="relative aspect-[2/3] overflow-hidden cursor-pointer" onclick="showDetails(${m.tmdb_id}, '${m.type}', ${m.id}, ${m.vu})">
                    <img src="https://image.tmdb.org/t/p/w500${m.affiche_path}" 
                        class="w-full h-full object-cover transition-transform group-hover:scale-110 ${m.vu == 1 ? 'opacity-40 grayscale' : ''}">
                </div>
                
                <div class="p-3 flex flex-col justify-between flex-grow bg-slate-800">
                    <h3 class="font-bold text-sm text-white truncate mb-2" title="${m.titre}">${m.titre}</h3>
                    
                    <div class="flex gap-2 mt-auto">
                        <button onclick="addMovie(${m.tmdb_id}, '${m.titre}', '${m.affiche_path}', '${m.type}')" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-4 rounded-xl font-black shadow-lg transition-all active:scale-95">
                            + AJOUTER À LA LISTE
                        </button>
                    </div>
                </div>
            `;

        } else if (currentView === 'common') {

            card.innerHTML = `
                <div class="relative aspect-[2/3] overflow-hidden cursor-pointer" onclick="showDetails(${m.tmdb_id}, '${m.type}', ${m.id}, ${m.vu})">
                    <img src="https://image.tmdb.org/t/p/w500${m.affiche_path}" 
                        class="w-full h-full object-cover transition-transform group-hover:scale-110 ${m.vu == 1 ? 'opacity-40 grayscale' : ''}">
                </div>
                
                <div class="p-3 flex flex-col justify-between flex-grow bg-slate-800">
                    <h3 class="font-bold text-sm text-white truncate mb-2" title="${m.titre}">${m.titre}</h3>
                    
                    <div class="flex gap-2 mt-auto">
                        <button onclick="toggleVu(${m.id})" class="flex-grow py-2 rounded text-[10px] font-black tracking-wider transition-colors ${m.vu == 1 ? 'bg-green-600 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600'}">
                            ${m.vu == 1 ? 'VU' : 'À VOIR'}
                        </button>
                    </div>
                </div>
            `;
        } else {
            card.innerHTML = `
                <div class="relative aspect-[2/3] overflow-hidden cursor-pointer" onclick="showDetails(${m.tmdb_id}, '${m.type}', ${m.id}, ${m.vu})">
                    <img src="https://image.tmdb.org/t/p/w500${m.affiche_path}" 
                        class="w-full h-full object-cover transition-transform group-hover:scale-110 ${m.vu == 1 ? 'opacity-40 grayscale' : ''}">
                </div>
                
                <div class="p-3 flex flex-col justify-between flex-grow bg-slate-800">
                    <h3 class="font-bold text-sm text-white truncate mb-2" title="${m.titre}">${m.titre}</h3>
                    
                    <div class="flex gap-2 mt-auto">
                        <button onclick="showDetails(${m.tmdb_id}, '${m.type}', ${m.id}, ${m.vu})" class="flex-grow py-2 rounded text-[10px] font-black tracking-wider transition-colors bg-slate-700 text-gray-300 hover:bg-slate-600">
                            Voir plus
                        </button>
                        <button onclick="deleteMovie(${m.id})" class="bg-red-900/30 hover:bg-red-600 text-red-500 hover:text-white px-3 py-2 rounded transition-colors text-[10px]">
                        🗑️
                        </button>
                    </div>
                </div>
            `;
        }

        grid.appendChild(card);
    });
}

// Actions rapides
async function toggleVu(id) {
    const res = await fetch('../RequestHandler.php', {
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
    const res = await fetch('../RequestHandler.php', {
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

let currentView = 'my'; // 'my', 'common', ou 'partner'

async function switchTab(view) {
    currentView = view;
    
    // Mise à jour visuelle des boutons
    const tabs = ['my', 'common', 'partner'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === view) {
            btn.classList.replace('bg-slate-700/50', 'bg-slate-800');
            btn.classList.replace('text-gray-400', 'text-white');
            btn.classList.replace('border-transparent', 'border-blue-500');
        } else {
            btn.classList.replace('bg-slate-800', 'bg-slate-700/50');
            btn.classList.replace('text-white', 'text-gray-400');
            btn.classList.replace('border-blue-500', 'border-transparent');
        }
    });

    // On recharge les données selon la vue
    let action = 'getMyList';
    if (view === 'common') action = 'getCommonList';
    if (view === 'partner') action = 'getPartnerList'; // Il faudra créer cette action dans RequestHandler

    const response = await apiRequest(action);
    if (response.success) {
        allMovies = response.movies || response.common_movies;
        renderList();
    }
}

async function showDetails(tmdbId, type, localId = null, isVu = 0) {
    // 1. On récupère les détails complets via TMDB (comme dans search.js)
    const mediaType = (type === 'serie') ? 'tv' : 'movie';
    
    // On appelle ton RequestHandler pour avoir les détails + acteurs
    const movie = await apiRequest('getMovieDetails', { id: tmdbId, type: mediaType });
    if (!movie) return;

    // 2. On remplit la modal avec les infos fraîches
    document.getElementById('modalTitle').innerText = movie.title || movie.name;
    
    const year = (movie.release_date || movie.first_air_date || "").substring(0, 4);
    const duration = movie.runtime ? `${movie.runtime} min` : (movie.number_of_seasons ? `${movie.number_of_seasons} Saison(s)` : "");
    const genres = movie.genres.map(g => g.name).join(', ');
    document.getElementById('modalMeta').innerText = `${year} • ${genres} ${duration ? '• ' + duration : ''}`;
    
    document.getElementById('modalOverview').innerText = movie.overview || "Aucun synopsis disponible.";
    document.getElementById('modalVote').innerText = movie.vote_average ? movie.vote_average.toFixed(1) : "N/A";
    
    // Image de bannière
    const bannerUrl = movie.backdrop_path ? `https://image.tmdb.org/t/p/original${movie.backdrop_path}` : '';
    document.getElementById('modalBanner').style.backgroundImage = `url(${bannerUrl})`;

    // Réalisateur
    const director = movie.credits.crew.find(person => person.job === 'Director');
    document.getElementById('modalDirector').innerText = director ? director.name : "Non renseigné";

    // Casting
    const castContainer = document.getElementById('modalCast');
    castContainer.innerHTML = movie.credits.cast.slice(0, 8).map(actor => `
        <div class="min-w-[110px] text-center flex-shrink-0">
            <img src="${actor.profile_path ? 'https://image.tmdb.org/t/p/w185' + actor.profile_path : 'https://via.placeholder.com/185x278?text=No+Image'}" 
                 class="w-20 h-20 object-cover rounded-full mx-auto mb-2 border-2 border-slate-700 shadow-lg">
            <p class="text-[10px] font-bold text-white">${actor.name}</p>
        </div>
    `).join('');

    // 3. LOGIQUE DU BOUTON (Vu / Pas Vu)
    const btn = document.getElementById('modalMainBtn');
    const ratingZone = document.getElementById('ratingZone');

    if (localId) {
        ratingZone.classList.remove('hidden'); // On montre les étoiles
        
        if (isVu == 1) {
            btn.innerText = "NE PLUS MARQUER COMME VU";
            btn.className = "w-full bg-slate-700 hover:bg-slate-600 text-white py-4 rounded-xl font-black transition-all";
        } else {
            btn.innerText = "MARQUER COMME VU";
            btn.className = "w-full bg-green-600 hover:bg-green-500 text-white py-4 rounded-xl font-black transition-all";
        }

        btn.onclick = async () => {
            await toggleVu(localId); // Ta fonction qui change le statut en BDD
            closeModal();
        };
    }

    // 4. LOGIQYUE DES ÉTOILES
        // On récupère la note actuelle pour ce film (si elle existe)
    apiRequest('getStarsRating', { movie_id: localId }).then(res => {
        if (res.success) {
            const currentRating = res.rating || 0;
            setRating(currentRating); // On affiche la note actuelle
        }
    });

    // Affichage final
    document.getElementById('movieModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Fonction pour les étoiles
function setRating(note) {
    const currentMovieId = document.getElementById('modalMainBtn').onclick.toString().match(/toggleVu\((\d+)\)/)[1]; // Extraction de l'ID du film
    const stars = document.querySelectorAll('#starContainer span');
    stars.forEach((star, index) => {
        if (index < note) {
            star.classList.replace('text-gray-600', 'text-yellow-400');
        } else {
            star.classList.replace('text-yellow-400', 'text-gray-600');
        }
    });
    apiRequest('setStarsRating', { movie_id: currentMovieId, rating: note }); // currentMovieId doit être défini lors de l'ouverture de la modal
}

function closeModal() {
    document.getElementById('movieModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

loadList();