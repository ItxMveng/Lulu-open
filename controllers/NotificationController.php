<?php
/**
 * Controller Notification - Gestion des notifications CLIENT
 */
require_once __DIR__ . '/../models/Notification.php';

class NotificationController {
    
    /**
     * Récupérer notifications (API)
     */
    public function getAll() {
        header('Content-Type: application/json');
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $notifModel = new Notification();
        $notifications = $notifModel->getAll($_SESSION['user_id']);
        
        echo json_encode(['notifications' => $notifications]);
    }
    
    /**
     * Marquer comme lu (API)
     */
    public function markAsRead() {
        header('Content-Type: application/json');
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $notificationId = $data['notification_id'] ?? null;
        
        $notifModel = new Notification();
        $result = $notifModel->markAsRead($notificationId, $_SESSION['user_id']);
        
        echo json_encode(['status' => $result ? 'success' : 'error']);
    }
    
    /**
     * Compter non lues (API)
     */
    public function countUnread() {
        header('Content-Type: application/json');
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['count' => 0]);
            exit;
        }
        
        $notifModel = new Notification();
        $count = $notifModel->countUnread($_SESSION['user_id']);
        
        echo json_encode(['count' => $count]);
    }
}
