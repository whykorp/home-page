<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>WatchGether - Recherche</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-blue-400 text-center">WatchGether</h1>
        
        <div class="flex gap-2 mb-10">
            <input type="text" id="searchInput" placeholder="Chercher un film ou une série..." 
                   class="flex-grow p-4 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none focus:border-blue-500">
            <button onclick="performSearch()" class="bg-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-blue-500 transition">
                Chercher
            </button>
        </div>

        <div id="resultsGrid" class="grid grid-cols-2 md:grid-cols-4 gap-6"></div>
    </div>
    <div id="movieModal" class="fixed inset-0 bg-black/95 z-50 hidden overflow-y-auto">
    <div class="relative w-full max-w-5xl mx-auto min-h-screen bg-slate-900 shadow-2xl border-x border-white/10">
        
        <button onclick="closeModal()" class="absolute top-6 right-6 z-50 bg-black/50 text-white p-2 rounded-full hover:bg-red-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div id="modalBanner" class="w-full h-[300px] md:h-[450px] bg-cover bg-center relative">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/20 to-transparent"></div>
            <div class="absolute bottom-10 left-6 md:left-10 right-6">
                <h2 id="modalTitle" class="text-4xl md:text-6xl font-black text-white drop-shadow-lg"></h2>
                <p id="modalMeta" class="text-lg text-blue-400 font-semibold mt-2"></p>
            </div>
        </div>

        <div class="p-6 md:p-10 grid md:grid-cols-3 gap-10">
            <div class="md:col-span-2">
                <h3 class="text-xl font-bold mb-3 text-white border-b border-white/10 pb-2">Synopsis</h3>
                <p id="modalOverview" class="text-gray-300 leading-relaxed text-lg mb-8"></p>
                
                <h3 class="text-xl font-bold mb-4 text-white border-b border-white/10 pb-2">Casting principal</h3>
                <div id="modalCast" class="flex gap-4 overflow-x-auto pb-6 scrollbar-hide">
                    </div>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-800/80 p-6 rounded-2xl border border-white/5">
                    <div class="mb-4">
                        <span class="text-gray-400 text-xs uppercase tracking-widest block mb-1">Note TMDB</span>
                        <div class="flex items-baseline gap-1">
                            <span id="modalVote" class="text-4xl font-black text-yellow-500"></span>
                            <span class="text-gray-500 font-bold">/ 10</span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <span class="text-gray-400 text-xs uppercase tracking-widest block mb-1">Réalisateur</span>
                        <span id="modalDirector" class="text-xl font-bold text-white"></span>
                    </div>
                    <button id="modalAddBtn" class="w-full bg-green-600 hover:bg-green-500 text-white py-4 rounded-xl font-black text-lg shadow-lg shadow-green-900/20 transition-all active:scale-95">
                        + AJOUTER À LA LISTE
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="js/index.js"></script>
</body>
</html>