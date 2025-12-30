<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

global $database;
$userId = $_SESSION['user_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $portfolioId = $data['id'] ?? 0;
    
    // Vérifier que le portfolio appartient à l'utilisateur
    $portfolio = $database->fetch(
        "SELECT p.id, p.image, pp.utilisateur_id 
         FROM portfolios p 
         INNER JOIN profils_prestataires pp ON p.prestataire_id = pp.id 
         WHERE p.id = ?",
        [$portfolioId]
    );
    
    if (!$portfolio || $portfolio['utilisateur_id'] != $userId) {
        throw new Exception('Portfolio non trouvé');
    }
    
    // Supprimer l'image
    $imagePath = '../uploads/portfolios/' . $portfolio['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    // Supprimer de la base de données
    $database->delete('portfolios', 'id = ?', [$portfolioId]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
