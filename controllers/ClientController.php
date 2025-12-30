<?php
/**
 * Controller Client - Gestion du dashboard et profil CLIENT
 */
require_once __DIR__ . '/../models/Favorite.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/SavedSearch.php';
require_once __DIR__ . '/../models/Activity.php';

class ClientController {
    
    /**
     * Afficher dashboard client
     */
    public function dashboard() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'client') {
            header('Location: /lulu/login.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        $favoriteModel = new Favorite();
        $messageModel = new Message();
        $notifModel = new Notification();
        
        $stats = [
            'favoris' => $favoriteModel->count($userId),
            'messages_non_lus' => $messageModel->countUnread($userId),
            'notifications_non_lues' => $notifModel->countUnread($userId)
        ];
        
        $notifications = $notifModel->getAll($userId, 1, 5);
        
        require_once __DIR__ . '/../views/client/dashboard.php';
    }
    

    
    
    /**
     * Récupérer statistiques CLIENT
     */
    public function getStats($userId) {
        $favoriteModel = new Favorite();
        $messageModel = new Message();
        $notifModel = new Notification();
        $activityModel = new Activity();
        
        return [
            'favoris' => $favoriteModel->count($userId),
            'messages_non_lus' => $messageModel->countUnread($userId),
            'notifications_non_lues' => $notifModel->countUnread($userId),
            'consultations_7j' => $activityModel->countLast7Days($userId)
        ];
    }
    
    /**
     * Récupérer notifications récentes
     */
    public function getRecentNotifications($userId, $limit = 5) {
        $notifModel = new Notification();
        return $notifModel->getAll($userId, 1, $limit);
    }
}
?>