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
    <?php include '../modal_template.php';?>
    <script src="js/index.js"></script>
</body>
</html>