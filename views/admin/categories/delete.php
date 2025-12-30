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
    
    // Vérifier s'il y a des profils associés
    $profileCount = $database->fetch("
        SELECT 
            (SELECT COUNT(*) FROM profils_prestataires WHERE categorie_id = :id) +
            (SELECT COUNT(*) FROM cvs WHERE categorie_id = :id) as total
    ", ['id' => $id])['total'];
    
    if ($profileCount > 0) {
        throw new Exception("Impossible de supprimer cette catégorie car elle contient {$profileCount} profil(s)");
    }
    
    $database->delete('categories_services', 'id = :id', ['id' => $id]);
    
    flashMessage('Catégorie supprimée avec succès', 'success');
    
} catch (Exception $e) {
    flashMessage($e->getMessage(), 'error');
}

redirect('index.php');
?>