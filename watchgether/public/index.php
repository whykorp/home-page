<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WatchGether - Votre cinéma à deux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .bg-gradient-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
    </style>
</head>
<body class="bg-gradient-custom text-white min-h-screen flex flex-col">

    <header class="p-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold tracking-tighter text-blue-400">WATCH<span class="text-white">GETHER</span></h1>
        <a href="#login" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-full font-semibold transition duration-300">
            Connexion
        </a>
    </header>

    <main class="flex-grow flex flex-col items-center justify-center px-4 text-center">
        <div class="max-w-3xl">
            <h2 class="text-5xl md:text-6xl font-extrabold mb-6">
                Fini de chercher pendant des heures.
            </h2>
            <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                Ajoutez vos films, synchronisez vos envies. <br class="hidden md:block">
                La liste commune trouve vos **doublons** instantanément pour des soirées ciné sans prise de tête.
            </p>

            <div class="grid md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white/5 p-6 rounded-2xl border border-white/10">
                    <div class="text-3xl mb-2">🍿</div>
                    <h3 class="font-bold mb-2">Listes Perso</h3>
                    <p class="text-sm text-gray-400">Chacun sa liste de films et séries à voir.</p>
                </div>
                <div class="bg-white/5 p-6 rounded-2xl border border-white/10">
                    <div class="text-3xl mb-2">🤝</div>
                    <h3 class="font-bold mb-2">Match Automatique</h3>
                    <p class="text-sm text-gray-400">Si vous voulez voir le même film, il apparaît ici.</p>
                </div>
                <div class="bg-white/5 p-6 rounded-2xl border border-white/10">
                    <div class="text-3xl mb-2">🎲</div>
                    <h3 class="font-bold mb-2">Mode Aléatoire</h3>
                    <p class="text-sm text-gray-400">Laissez le destin choisir votre programme ce soir.</p>
                </div>
            </div>

            <button class="bg-white text-blue-900 px-8 py-4 rounded-xl font-bold text-lg hover:scale-105 transition-transform">
                Lancer le projet
            </button>
        </div>
    </main>

    <footer class="p-6 text-center text-gray-500 text-sm">
        &copy; 2026 WatchGether - Propulsé par TMDB API
    </footer>

</body>
</html>