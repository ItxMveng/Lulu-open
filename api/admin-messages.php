<?php
require_once '../config/config.php';
require_once '../includes/middleware-admin.php';
require_admin();
require_once '../models/Message.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($input['action'] ?? '');

$messageModel = new Message();
$adminId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'get_conversation':
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) {
                throw new Exception("ID utilisateur manquant");
            }

            // Récupérer les infos utilisateur
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT prenom, nom, photo_profil FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            // Récupérer la conversation
            $conversation = $messageModel->getConversation($adminId, $userId);

            // Marquer comme lu
            $messageModel->markAsRead($adminId, $userId);

            echo json_encode([
                'success' => true,
                'conversation' => $conversation,
                'user' => $user
            ]);
            break;

        case 'send_message':
            $destinataireId = (int)($input['destinataire_id'] ?? 0);
            $sujet = trim($input['sujet'] ?? '');
            $contenu = trim($input['contenu'] ?? '');

            if (!$destinataireId || !$sujet || !$contenu) {
                throw new Exception("Paramètres manquants");
            }

            $messageId = $messageModel->send($adminId, $destinataireId, $sujet, $contenu);

            if ($messageId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Message envoyé avec succès',
                    'message_id' => $messageId
                ]);
            } else {
                throw new Exception("Erreur lors de l'envoi du message");
            }
            break;

        default:
            throw new Exception("Action inconnue");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>