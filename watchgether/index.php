<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>WatchGether - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">

    <div class="bg-slate-800 p-8 rounded-2xl shadow-xl w-full max-w-md border border-white/5">
        <h1 class="text-3xl font-black text-center mb-8 text-blue-400 italic">WATCHGETHER</h1>
        
        <div id="authForm">
            <input type="text" id="pseudo" placeholder="Pseudo" class="w-full p-4 mb-4 bg-slate-900 rounded-lg border border-slate-700 focus:border-blue-500 outline-none">
            <input type="password" id="password" placeholder="Mot de passe" class="w-full p-4 mb-6 bg-slate-900 rounded-lg border border-slate-700 focus:border-blue-500 outline-none">
            
            <button onclick="handleAuth('login')" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-xl font-bold mb-4 transition">Se connecter</button>
            <button onclick="handleAuth('register')" class="w-full bg-slate-700 hover:bg-slate-600 py-4 rounded-xl font-bold transition">Créer un compte</button>
        </div>
    </div>

    <script>
        async function handleAuth(action) {
            const pseudo = document.getElementById('pseudo').value;
            const password = document.getElementById('password').value;

            const response = await fetch('RequestHandler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, params: { pseudo, password } })
            });
            const data = await response.json();

            if (data.success) {
                if (action === 'register') alert("Compte créé ! Connecte-toi maintenant.");
                else window.location.href = 'search/index.php';
            } else {
                alert(data.error);
            }
        }
    </script>
</body>
</html>