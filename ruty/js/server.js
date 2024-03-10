const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();

// Endpoint pour récupérer la liste des notes dans un dossier
app.get('/api/notes/:folder', (req, res) => {
    const folder = req.params.folder;
    const folderPath = path.join(__dirname, 'notepad/contents', folder);

    fs.readdir(folderPath, (err, files) => {
        if (err) {
            console.error(err);
            res.status(500).json({ error: 'Erreur lors de la lecture des fichiers.' });
        } else {
            const notes = files.map((file) => {
                return { name: file, rtfFile: path.join('notepad/contents', folder, file) };
            });
            res.json(notes);
        }
    });
});

const port = process.env.PORT || 3000;
app.listen(port, () => {
    console.log(`Serveur en cours d'exécution sur le port ${port}`);
});
