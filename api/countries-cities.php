<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

try {
    global $database;
    
    if ($action === 'countries') {
        // Récupérer tous les pays
        $countries = $database->fetchAll("
            SELECT DISTINCT pays, code_iso 
            FROM localisations_monde 
            WHERE pays IS NOT NULL 
            ORDER BY pays
        ");
        echo json_encode($countries);
        
    } elseif ($action === 'cities' && isset($_GET['country'])) {
        // Récupérer les villes d'un pays
        $country = $_GET['country'];
        $cities = $database->fetchAll("
            SELECT ville, region, pays, code_iso 
            FROM localisations_monde 
            WHERE pays = ? AND ville IS NOT NULL 
            ORDER BY ville
        ", [$country]);
        echo json_encode($cities);
        
    } else {
        echo json_encode(['error' => 'Action non valide']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>