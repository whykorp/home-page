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
            <a href="index.php" class="bg-slate-800 px-4 py-2 rounded-lg hover:bg-slate-700 transition">← Retour à la recherche</a>
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

    <?php include 'modal_template.php'; // Conseil : mets le code de la modal dans un fichier à part pour l'inclure partout ?>

    <script src="js/list.js"></script>
</body>
</html>