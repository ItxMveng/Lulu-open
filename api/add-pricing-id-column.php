<?php
/**
 * Script de migration pour ajouter la colonne pricing_id à subscription_requests
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    global $database;
    
    // Vérifier si la colonne existe déjà
    $checkQuery = "SHOW COLUMNS FROM subscription_requests LIKE 'pricing_id'";
    $result = $database->query($checkQuery);
    
    if (empty($result)) {
        // Ajouter la colonne pricing_id
        $alterQuery = "ALTER TABLE subscription_requests 
                       ADD COLUMN pricing_id INT NULL AFTER user_id,
                       ADD INDEX idx_pricing (pricing_id)";
        
        $database->query($alterQuery);
        
        echo json_encode([
            'success' => true,
            'message' => 'Colonne pricing_id ajoutée avec succès à subscription_requests'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'La colonne pricing_id existe déjà'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
