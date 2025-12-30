<?php
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Données de villes françaises et internationales
$cities = [
    ['ville' => 'Paris', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Paris, France'],
    ['ville' => 'Lyon', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Lyon, France'],
    ['ville' => 'Marseille', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Marseille, France'],
    ['ville' => 'Toulouse', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Toulouse, France'],
    ['ville' => 'Nice', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Nice, France'],
    ['ville' => 'Nantes', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Nantes, France'],
    ['ville' => 'Strasbourg', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Strasbourg, France'],
    ['ville' => 'Montpellier', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Montpellier, France'],
    ['ville' => 'Bordeaux', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Bordeaux, France'],
    ['ville' => 'Lille', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Lille, France'],
    ['ville' => 'Rennes', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Rennes, France'],
    ['ville' => 'Reims', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Reims, France'],
    ['ville' => 'Le Havre', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Le Havre, France'],
    ['ville' => 'Saint-Étienne', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Saint-Étienne, France'],
    ['ville' => 'Toulon', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Toulon, France'],
    ['ville' => 'Grenoble', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Grenoble, France'],
    ['ville' => 'Dijon', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Dijon, France'],
    ['ville' => 'Angers', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Angers, France'],
    ['ville' => 'Nîmes', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Nîmes, France'],
    ['ville' => 'Villeurbanne', 'pays' => 'France', 'code_iso' => 'FR', 'suggestion' => 'Villeurbanne, France'],
    
    // Villes internationales
    ['ville' => 'Londres', 'pays' => 'Royaume-Uni', 'code_iso' => 'GB', 'suggestion' => 'Londres, Royaume-Uni'],
    ['ville' => 'Berlin', 'pays' => 'Allemagne', 'code_iso' => 'DE', 'suggestion' => 'Berlin, Allemagne'],
    ['ville' => 'Madrid', 'pays' => 'Espagne', 'code_iso' => 'ES', 'suggestion' => 'Madrid, Espagne'],
    ['ville' => 'Rome', 'pays' => 'Italie', 'code_iso' => 'IT', 'suggestion' => 'Rome, Italie'],
    ['ville' => 'Bruxelles', 'pays' => 'Belgique', 'code_iso' => 'BE', 'suggestion' => 'Bruxelles, Belgique'],
    ['ville' => 'Genève', 'pays' => 'Suisse', 'code_iso' => 'CH', 'suggestion' => 'Genève, Suisse'],
    ['ville' => 'Zurich', 'pays' => 'Suisse', 'code_iso' => 'CH', 'suggestion' => 'Zurich, Suisse'],
    ['ville' => 'Amsterdam', 'pays' => 'Pays-Bas', 'code_iso' => 'NL', 'suggestion' => 'Amsterdam, Pays-Bas'],
    ['ville' => 'Barcelone', 'pays' => 'Espagne', 'code_iso' => 'ES', 'suggestion' => 'Barcelone, Espagne'],
    ['ville' => 'Milan', 'pays' => 'Italie', 'code_iso' => 'IT', 'suggestion' => 'Milan, Italie']
];

// Filtrer les villes selon la requête
$results = [];
$query = strtolower($query);

foreach ($cities as $city) {
    if (strpos(strtolower($city['suggestion']), $query) !== false || 
        strpos(strtolower($city['ville']), $query) !== false) {
        $results[] = $city;
    }
}

// Limiter à 10 résultats
$results = array_slice($results, 0, 10);

echo json_encode($results);
?>