// --- CONFIGURATION & UTILITAIRES ---

/**
 * Fonction universelle pour parler à ton RequestHandler
 * @param {string} action - L'action à exécuter (ex: 'searchTMDB')
 * @param {object} params - Les données à envoyer
 */
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

document.getElementById('ratingZone').classList.add('hidden');

// --- RECHERCHE ---

/**
 * Lance la recherche TMDB et affiche les résultats sous forme de cartes
 */
async function performSearch() {
    const query = document.getElementById('searchInput').value;
    if (!query) return;

    const grid = document.getElementById('resultsGrid');
    grid.innerHTML = '<p class="col-span-full text-center py-10 opacity-50">Recherche de pépites en cours...</p>';

    const data = await apiRequest('searchTMDB', { query: query });
    
    grid.innerHTML = ''; // On vide

    if (data.results && data.results.length > 0) {
        data.results.forEach(item => {
            // On ignore les résultats sans image ou qui ne sont pas film/série
            if (!item.poster_path || (item.media_type !== 'movie' && item.media_type !== 'tv')) return;

            const title = item.title || item.name;
            const poster = `https://image.tmdb.org/t/p/w500${item.poster_path}`;
            const type = item.media_type === 'tv' ? 'serie' : 'film';
            const year = (item.release_date || item.first_air_date || "").substring(0, 4);

            const card = document.createElement('div');
            card.className = 'bg-slate-800 rounded-xl overflow-hidden border border-slate-700 hover:scale-105 transition-transform cursor-pointer group';
            card.innerHTML = `
                <div class="relative overflow-hidden" onclick="showDetails(${item.id}, '${item.media_type}')">
                    <img src="${poster}" class="w-full h-auto">
                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="bg-white text-black px-4 py-2 rounded-full font-bold text-xs uppercase tracking-wider">Voir la fiche</span>
                    </div>
                </div>
                <div class="p-3">
                    <h3 class="font-bold text-sm truncate">${title}</h3>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[10px] text-gray-400 uppercase">${type}</span>
                        <span class="text-[10px] text-blue-400 font-bold">${year}</span>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    } else {
        grid.innerHTML = '<p class="col-span-full text-center py-10 text-gray-500">Aucun résultat trouvé pour cette recherche.</p>';
    }
}

// --- FICHE DÉTAILLÉE (MODAL) ---

/**
 * Récupère les détails complets d'un film et ouvre la modal
 */
async function showDetails(id, type) {
    // TMDB utilise 'movie' ou 'tv', on s'assure d'avoir le bon type
    const mediaType = (type === 'serie') ? 'tv' : type;
    
    const movie = await apiRequest('getMovieDetails', { id: id, type: mediaType });
    if (!movie) return;

    const modal = document.getElementById('movieModal');

    // 1. Textes de base
    document.getElementById('modalTitle').innerText = movie.title || movie.name;
    const year = (movie.release_date || movie.first_air_date || "").substring(0, 4);
    const duration = movie.runtime ? `${movie.runtime} min` : (movie.number_of_seasons ? `${movie.number_of_seasons} Saison(s)` : "");
    const genres = movie.genres.map(g => g.name).join(', ');
    
    document.getElementById('modalMeta').innerText = `${year} • ${genres} ${duration ? '• ' + duration : ''}`;
    document.getElementById('modalOverview').innerText = movie.overview || "Aucun synopsis disponible pour le moment.";
    document.getElementById('modalVote').innerText = movie.vote_average ? movie.vote_average.toFixed(1) : "N/A";

    // 2. Images (Bannière)
    const bannerUrl = movie.backdrop_path ? `https://image.tmdb.org/t/p/original${movie.backdrop_path}` : '';
    document.getElementById('modalBanner').style.backgroundImage = `url(${bannerUrl})`;

    // 3. Réalisateur
    const director = movie.credits.crew.find(person => person.job === 'Director');
    document.getElementById('modalDirector').innerText = director ? director.name : "Non renseigné";

    // 4. Casting (Les 8 premiers)
    const castContainer = document.getElementById('modalCast');
    castContainer.innerHTML = movie.credits.cast.slice(0, 8).map(actor => `
        <div class="min-w-[110px] text-center flex-shrink-0">
            <img src="${actor.profile_path ? 'https://image.tmdb.org/t/p/w185' + actor.profile_path : 'https://via.placeholder.com/185x278?text=No+Image'}" 
                 class="w-20 h-20 object-cover rounded-full mx-auto mb-2 border-2 border-slate-700 shadow-lg">
            <p class="text-[10px] font-bold leading-tight text-white">${actor.name}</p>
            <p class="text-[9px] text-gray-500 italic">${actor.character}</p>
        </div>
    `).join('');

    // 5. Bouton Ajouter (on passe les infos pour la BDD)
    const simplifiedType = (mediaType === 'tv') ? 'serie' : 'film';
    const btn = document.getElementById('modalMainBtn');
    btn.innerText = "+ AJOUTER À LA LISTE";
    btn.className = "w-full bg-blue-600 hover:bg-blue-500 text-white py-4 rounded-xl font-black shadow-lg transition-all active:scale-95";
    btn.onclick = () => addMovie(movie.id, movie.title || movie.name, movie.poster_path, simplifiedType);

    // Cache les étoiles en mode recherche
    document.getElementById('ratingZone').classList.add('hidden');

    // Affichage
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

/**
 * Ferme la modal
 */
function closeModal() {
    document.getElementById('movieModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// --- ACTIONS BDD ---

/**
 * Ajoute un film dans la base de données MySQL
 */
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

// Permettre la recherche avec la touche "Entrée"
document.getElementById('searchInput').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

async function LogOut() {
    const res = await fetch('../RequestHandler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    });
    const data = await res.json();
    if (data.success) {
        window.location.href = '../index.php';
    }
}