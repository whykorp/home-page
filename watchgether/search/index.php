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
    <title>WatchGether - Recherche</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <a href="../ma-liste" class="bg-slate-800 hover:bg-slate-700 text-white px-6 py-3 rounded-xl font-bold border border-white/5 transition-all active:scale-95 shadow-lg">
            🍿 Ma liste
        </a>

        <h1 class="text-5xl md:text-6xl font-black tracking-tighter italic">
            <span class="text-blue-500">WATCH</span><span class="text-white">GETHER</span>
        </h1>

        <button onclick="LogOut()" class="bg-red-600 hover:bg-red-500 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-red-900/20 transition-all active:scale-95">
            👤 Se déconnecter
        </button>
    </div>
    
    <div class="flex gap-2 mb-10">
        <input type="text" id="searchInput" placeholder="Chercher un film ou une série..." 
               class="flex-grow p-4 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none focus:border-blue-500 text-white">
        <button onclick="performSearch()" class="bg-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-blue-500 transition shadow-lg">
            Chercher
        </button>
    </div>
    <div id="resultsGrid" class="grid grid-cols-2 md:grid-cols-4 gap-6"></div>
</div>
<?php include '../modal-template.php';?>
<script src="../js/search.js"></script>
</body>
</html>