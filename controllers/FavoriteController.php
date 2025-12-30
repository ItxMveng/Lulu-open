<?php
/**
 * Controller Favorite - Gestion des favoris CLIENT
 */
require_once __DIR__ . '/../models/Favorite.php';

class FavoriteController {
    
    /**
     * Ajouter aux favoris (API)
     */
    public function add() {
        header('Content-Type: application/json');
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $cibleType = $data['cible_type'] ?? null;
        $cibleId = $data['cible_id'] ?? null;
        
        if (!$cibleType || !$cibleId) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit;
        }
        
        $favoriteModel = new Favorite();
        $result = $favoriteModel->add($_SESSION['user_id'], $cibleType, $cibleId);
        
        if ($result === true) {
            echo json_encode(['status' => 'success', 'message' => 'Ajouté aux favoris']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $result]);
        }
    }
    
    /**
     * Retirer des favoris (API)
     */
    public function remove() {
        header('Content-Type: application/json');
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $cibleType = $_GET['cible_type'] ?? null;
        $cibleId = $_GET['cible_id'] ?? null;
        
        $favoriteModel = new Favorite();
        $result = $favoriteModel->remove($_SESSION['user_id'], $cibleType, $cibleId);
        
        echo json_encode(['status' => $result ? 'success' : 'error']);
    }
    
    /**
     * Afficher liste des favoris
     */
    public function index() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /lulu/login.php');
            exit;
        }
        
        $favoriteModel = new Favorite();
        $favoris = $favoriteModel->getAll($_SESSION['user_id']);
        
        require_once __DIR__ . '/../views/client/favoris.php';
    }
}
