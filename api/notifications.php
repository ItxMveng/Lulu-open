<?php
/**
 * API Notifications - LULU-OPEN
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Notification.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$notifModel = new Notification();
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if (!$action) {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
}

try {
    switch ($action) {
        case 'count':
            $count = $notifModel->countUnread($_SESSION['user_id']);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'mark_read':
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = $data['notification_id'] ?? null;
            
            if ($notificationId) {
                $result = $notifModel->markAsRead($notificationId, $_SESSION['user_id']);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
            }
            break;
            
        case 'delete':
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = $data['notification_id'] ?? null;
            
            if ($notificationId) {
                $result = $notifModel->delete($notificationId, $_SESSION['user_id']);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
