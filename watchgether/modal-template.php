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
                <div id="modalCast" class="flex gap-4 overflow-x-auto pb-6 scrollbar-hide"></div>
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

                    <div id="ratingZone" class="mb-6 hidden">
                        <span class="text-gray-400 text-xs uppercase tracking-widest block mb-2">Ta note</span>
                        <div class="flex gap-2 text-2xl" id="starContainer">
                            <span onclick="setRating(1)" class="cursor-pointer hover:text-yellow-400 transition text-gray-600">★</span>
                            <span onclick="setRating(2)" class="cursor-pointer hover:text-yellow-400 transition text-gray-600">★</span>
                            <span onclick="setRating(3)" class="cursor-pointer hover:text-yellow-400 transition text-gray-600">★</span>
                            <span onclick="setRating(4)" class="cursor-pointer hover:text-yellow-400 transition text-gray-600">★</span>
                            <span onclick="setRating(5)" class="cursor-pointer hover:text-yellow-400 transition text-gray-600">★</span>
                        </div>
                    </div>

                    <button id="modalMainBtn" class="w-full py-4 rounded-xl font-black text-lg shadow-lg transition-all active:scale-95 text-white bg-blue-600 hover:bg-blue-500">
                        ACTION
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>