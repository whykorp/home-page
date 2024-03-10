<html>
    <head>
        <meta charset="UTF-8">
        <title>Ruty - L'outil multifonction</title>
        <link rel="stylesheet" href="styles/style1.css">
        <link rel="icon" href="img/logo.png">        
    </head>
    <body>
        <header id="header">
            <img src="img/logo.png" alt="Logo Ruty" id="menu-trigger">
            <h1 class="name">Ruty</h1>
        </header>
        <?php include 'menunav.php'; ?>
        <div>
            <h2 class="welcome">Bienvenue dans votre espace Noah !</h2>
            <div id="current_date">
                <p id="p1"></p>
                <script>
                    let date1 = new Date();

                    let dateLocale = date1.toLocaleString('fr-FR',{
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                        });

                    document.getElementById('p1').innerHTML = 'Nous sommes le ' + dateLocale;
                </script>
            </div>
        </div>

        <script src="js/script.js" async></script>
    </body>

</html>