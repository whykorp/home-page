function addPiece(nomUtilisateur, quantite) {
    // Créez une requête XMLHttpRequest pour envoyer une demande au serveur
    var xhr = new XMLHttpRequest();
    
    // Spécifiez la méthode HTTP (POST) et l'URL du script PHP qui mettra à jour les pièces
    xhr.open("POST", "update_pieces.php", true);
    
    // Définissez le type de données à envoyer (formulaire)
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
    // Définissez la fonction de rappel pour gérer la réponse du serveur
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Mettez à jour l'affichage du nombre de pièces si nécessaire
            var response = xhr.responseText;
            if (response === "success") {
                var elementPieces = document.getElementById("pieces-" + nomUtilisateur);
                if (elementPieces) {
                    var nombreDePieces = parseInt(elementPieces.textContent);
                    nombreDePieces += quantite;
                    elementPieces.textContent = nombreDePieces;
                }
            }
        }
    };
    
    // Envoyez les données au serveur (nom de l'utilisateur et quantité)
    var data = "nom=" + encodeURIComponent(nomUtilisateur) + "&quantite=" + encodeURIComponent(quantite);
    xhr.send(data);
}
