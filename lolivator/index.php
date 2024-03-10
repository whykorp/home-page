<html>
<head>
    <title>LoLivator - Beta</title>
    <link rel="icon" href="img/logo.png">
    <link rel="stylesheet" href="styles/style.css">
    <script src="script.js"></script>
</head>
<body>
    <header>
        <img src="img/logo.png">
        <h1 class="Welcome">Bienvenue sur LoLivator [BETA]</h1>
    </header>
    <div class="app">
        <div class="noah">
            <?php include("noah_buttons.php"); ?>
            <button onclick="addPiece('Noah', 1)">Ajouter 1 pièce</button>
        </div>
        <div class="lazare">
            <?php include("lazare_buttons.php"); ?>
            <button onclick="addPiece('Lazare', 1)">Ajouter 1 pièce</button>
        </div>
    </div>
</body>
</html>