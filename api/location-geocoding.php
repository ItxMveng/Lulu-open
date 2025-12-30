<?php
require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// 1. Recherche locale d'abord (plus rapide)
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            CONCAT(v.nom, ', ', p.nom) as suggestion,
            v.nom as ville,
            p.nom as pays,
            p.code_iso,
            'local' as source
        FROM villes v 
        JOIN pays p ON v.pays_id = p.id
        WHERE v.nom LIKE :query 
           OR p.nom LIKE :query
        ORDER BY v.population DESC
        LIMIT 5
    ");
    
    $stmt->execute(['query' => '%' . $query . '%']);
    $localResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $localResults = [];
}

// 2. Si pas assez de résultats locaux, utiliser API gratuite
$allResults = $localResults;

if (count($localResults) < 5) {
    // Nominatim OpenStreetMap (gratuit, pas de clé API requise)
    $nominatimUrl = "https://nominatim.openstreetmap.org/search?q=" . urlencode($query) . 
                   "&format=json&addressdetails=1&limit=5&accept-language=fr";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: LULU-OPEN/1.0',
            'timeout' => 3
        ]
    ]);
    
    $response = @file_get_contents($nominatimUrl, false, $context);
    
    if ($response) {
        $nominatimData = json_decode($response, true);
        
        foreach ($nominatimData as $item) {
            if (isset($item['address'])) {
                $ville = $item['address']['city'] ?? 
                        $item['address']['town'] ?? 
                        $item['address']['village'] ?? 
                        $item['display_name'];
                        
                $pays = $item['address']['country'] ?? '';
                
                if ($ville && $pays) {
                    $allResults[] = [
                        'suggestion' => $ville . ', ' . $pays,
                        'ville' => $ville,
                        'pays' => $pays,
                        'code_iso' => $item['address']['country_code'] ?? '',
                        'source' => 'nominatim'
                    ];
                }
            }
        }
    }
}

// Supprimer les doublons
$uniqueResults = [];
$seen = [];

foreach ($allResults as $result) {
    $key = strtolower($result['suggestion']);
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $uniqueResults[] = $result;
    }
}

echo json_encode(array_slice($uniqueResults, 0, 10));
?>