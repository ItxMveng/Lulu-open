<?php
require_once '../config/config.php';
require_once '../models/Category.php';

header('Content-Type: application/json');

try {
    $categoryModel = new Category();
    $categories = $categoryModel->getAll();
    
    $result = [];
    foreach ($categories as $category) {
        $result[] = [
            'id' => $category['id'],
            'name' => $category['nom'],
            'icon' => $category['icone'] ?? '📁',
            'color' => $category['couleur'] ?? '#0099FF',
            'count' => $category['nb_prestataires'] ?? 0
        ];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors du chargement des catégories']);
}
?>