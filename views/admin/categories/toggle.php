<?php
require_once '../../../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = $_POST['id'] ?? null;

if (!$id) {
    flashMessage('ID manquant', 'error');
    redirect('index.php');
}

global $database;

try {
    $category = $database->fetch("SELECT * FROM categories_services WHERE id = :id", ['id' => $id]);
    
    if (!$category) {
        throw new Exception('Catégorie non trouvée');
    }
    
    $newStatus = $category['actif'] ? 0 : 1;
    
    $database->update(
        'categories_services',
        ['actif' => $newStatus],
        'id = :id',
        ['id' => $id]
    );
    
    $action = $newStatus ? 'activée' : 'désactivée';
    flashMessage("Catégorie {$action} avec succès", 'success');
    
} catch (Exception $e) {
    flashMessage($e->getMessage(), 'error');
}

redirect('index.php');
?>