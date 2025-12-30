<?php
/**
 * API Envoi Message - LULU-OPEN
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Message.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$destinataireId = $_POST['destinataire_id'] ?? null;
$message = trim($_POST['message'] ?? '');
$sujet = $_POST['sujet'] ?? 'Message';

if (!$destinataireId || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Destinataire ou message manquant']);
    exit;
}

try {
    $messageModel = new Message();
    $messageId = $messageModel->send($_SESSION['user_id'], $destinataireId, $sujet, $message);
    
    if ($messageId) {
        echo json_encode([
            'success' => true,
            'message' => 'Message envoyé',
            'message_id' => $messageId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
