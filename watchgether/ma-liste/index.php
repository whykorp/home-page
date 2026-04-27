<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); // Redirige vers le login si pas de session
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ma Liste - WatchGether</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold text-blue-400">Ma Liste 🍿</h1>
            <a href="../search/index.php" class="bg-slate-800 px-4 py-2 rounded-lg hover:bg-slate-700 transition">← Retour à la recherche</a>
        </div>

        <div class="flex items-end mb-0 border-b border-slate-700">
            <button onclick="switchTab('my')" id="tab-my" 
                    class="px-8 py-4 bg-slate-800 text-white font-bold text-sm uppercase tracking-widest border-b-4 border-blue-500 transition-all">
                Ma Liste
            </button>
            
            <button onclick="switchTab('common')" id="tab-common" 
                    class="px-8 py-4 bg-slate-700/50 text-gray-400 font-bold text-sm uppercase tracking-widest border-b-4 border-transparent hover:bg-slate-800 hover:text-white transition-all">
                Notre Liste
            </button>
            
            <button onclick="switchTab('partner')" id="tab-partner" 
                    class="px-8 py-4 bg-slate-700/50 text-gray-400 font-bold text-sm uppercase tracking-widest border-b-4 border-transparent hover:bg-slate-800 hover:text-white transition-all">
                Sa Liste
            </button>
        </div>

        <div class="bg-slate-800 p-4 rounded-xl mb-8 flex flex-wrap gap-4 items-center">
            <div class="flex flex-col">
                <label class="text-xs text-gray-400 mb-1 uppercase font-bold">Trier par</label>
                <select id="sortOrder" onchange="renderList()" class="bg-slate-700 border-none rounded p-2 focus:ring-2 focus:ring-blue-500">
                    <option value="date_ajout_desc">Date d'ajout (Récent)</option>
                    <option value="date_ajout_asc">Date d'ajout (Ancien)</option>
                    <option value="titre_asc">Ordre Alphabétique</option>
                </select>
            </div>

            <div class="flex flex-col">
                <label class="text-xs text-gray-400 mb-1 uppercase font-bold">Type</label>
                <select id="filterType" onchange="renderList()" class="bg-slate-700 border-none rounded p-2">
                    <option value="all">Tout voir</option>
                    <option value="film">Films uniquement</option>
                    <option value="serie">Séries uniquement</option>
                </select>
            </div>

            <div class="flex flex-col">
                <label class="text-xs text-gray-400 mb-1 uppercase font-bold">Statut</label>
                <select id="filterStatus" onchange="renderList()" class="bg-slate-700 border-none rounded p-2">
                    <option value="all">Tout</option>
                    <option value="0">À voir</option>
                    <option value="1">Déjà vus</option>
                </select>
            </div>
            
            <div class="ml-auto text-sm text-gray-400">
                <span id="movieCount">0</span> éléments
            </div>
        </div>

        <div id="myListGrid" class="grid grid-cols-2 md:grid-cols-5 gap-6">
            </div>
    </div>

    <?php include '../modal-template.php';?>

    <script src="../js/list.js"></script>
</body>
</html>