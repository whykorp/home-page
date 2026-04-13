<?php include 'functions.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>WatchGether - Recherche</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-blue-400 text-center">Rechercher un film ou une série</h1>
        
        <form method="GET" class="flex gap-2 mb-10">
            <input type="text" name="q" placeholder="Titre du film..." 
                   class="flex-grow p-4 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-blue-500 transition">
                Chercher
            </button>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            if (isset($_GET['q']) && !empty($_GET['q'])) {
                $results = searchMovie($_GET['q']);
                
                foreach ($results['results'] as $item) {
                    // On ne prend que les films ou séries qui ont une affiche
                    if (isset($item['poster_path'])) {
                        $title = isset($item['title']) ? $item['title'] : $item['name'];
                        $poster = "https://image.tmdb.org/t/p/w500" . $item['poster_path'];
                        $date = isset($item['release_date']) ? substr($item['release_date'], 0, 4) : (isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : '');
                        
                        echo "
                        <div class='bg-slate-800 rounded-xl overflow-hidden border border-slate-700 hover:scale-105 transition-transform cursor-pointer'>
                            <img src='$poster' alt='$title' class='w-full h-auto'>
                            <div class='p-3'>
                                <h3 class='font-bold text-sm truncate'>$title</h3>
                                <p class='text-xs text-gray-400'>$date</p>
                                <button class='mt-2 w-full bg-green-600 text-xs py-1 rounded hover:bg-green-500'>+ Ajouter</button>
                            </div>
                        </div>";
                    }
                }
            }
            ?>
        </div>
    </div>

</body>
</html>