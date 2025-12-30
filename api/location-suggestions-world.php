<?php
require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getConnection();
    
    // Recherche dans les villes et pays
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            CONCAT(v.nom, ', ', p.nom) as suggestion,
            v.nom as ville,
            p.nom as pays,
            p.code_iso,
            'ville' as type
        FROM villes v 
        JOIN pays p ON v.pays_id = p.id
        WHERE v.nom LIKE :query 
           OR p.nom LIKE :query
           OR CONCAT(v.nom, ', ', p.nom) LIKE :query
        ORDER BY 
            CASE 
                WHEN v.nom LIKE :exact_query THEN 1
                WHEN p.nom LIKE :exact_query THEN 2
                WHEN v.nom LIKE :start_query THEN 3
                WHEN p.nom LIKE :start_query THEN 4
                ELSE 5
            END,
            v.population DESC
        LIMIT 10
    ");
    
    $searchQuery = '%' . $query . '%';
    $exactQuery = $query . '%';
    
    $stmt->execute([
        'query' => $searchQuery,
        'exact_query' => $exactQuery,
        'start_query' => $exactQuery
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur de recherche']);
}
?>