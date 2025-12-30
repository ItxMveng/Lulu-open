<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    global $database;
    
    $sql = "SELECT ville, region, pays, code_postal, latitude, longitude 
            FROM suggestions_localisation 
            WHERE ville LIKE :query OR region LIKE :query 
            ORDER BY population DESC, ville ASC 
            LIMIT 10";
    
    $results = $database->fetchAll($sql, ['query' => $query . '%']);
    
    $suggestions = [];
    foreach ($results as $result) {
        $suggestions[] = [
            'label' => $result['ville'] . ', ' . $result['region'] . ', ' . $result['pays'],
            'ville' => $result['ville'],
            'region' => $result['region'],
            'pays' => $result['pays'],
            'code_postal' => $result['code_postal'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude']
        ];
    }
    
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche']);
}
?>