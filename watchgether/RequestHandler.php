<?php
$apiKey = '23af653f99d2e7ac884415805e7ca84c';

function searchMovie($query) {
    global $apiKey;
    $url = "https://api.themoviedb.org/3/search/multi?api_key=" . $apiKey . "&language=fr-FR&query=" . urlencode($query);
    
    // On récupère le JSON
    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>