<?php
require_once '../config/config.php';
require_once '../includes/middleware.php';
require_once '../models/Message.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_login();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$messageModel = new Message();
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'get_conversation':
            $interlocutorId = (int)($_GET['user_id'] ?? 0);
            if (!$interlocutorId) {
                throw new Exception("ID utilisateur manquant");
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT prenom, nom, photo_profil FROM utilisateurs WHERE id = ?");
            $stmt->execute([$interlocutorId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            $conversation = $messageModel->getConversation($userId, $interlocutorId);
            $messageModel->markAsRead($userId, $interlocutorId);

            echo json_encode([
                'success' => true,
                'conversation' => $conversation,
                'user' => $user
            ]);
            break;

        case 'send_message':
            $destinataireId = (int)($_POST['destinataire_id'] ?? 0);
            $sujet = trim($_POST['sujet'] ?? 'Message');
            $contenu = trim($_POST['contenu'] ?? '');

            if (!$destinataireId || !$sujet || (!$contenu && !isset($_FILES['fichier']))) {
                throw new Exception("Paramètres manquants");
            }

            $fichierJoint = null;
            
            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/messages/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['fichier']['name']);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadPath)) {
                    $fichierJoint = 'uploads/messages/' . $fileName;
                }
            }

            $messageId = $messageModel->send($userId, $destinataireId, $sujet, $contenu, $fichierJoint);

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

        case 'delete_message':
            $messageId = (int)($_POST['message_id'] ?? 0);
            if (!$messageId) {
                throw new Exception("ID message manquant");
            }

            // Utilisateur ne peut supprimer que ses propres messages
            $result = $messageModel->deleteMessage($messageId, $userId, false);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Message supprimé']);
            } else {
                throw new Exception("Impossible de supprimer le message ou vous n'êtes pas l'auteur");
            }
            break;

        case 'delete_conversation':
            $interlocutorId = (int)($_POST['user_id'] ?? 0);
            if (!$interlocutorId) {
                throw new Exception("ID utilisateur manquant");
            }

            $result = $messageModel->deleteConversation($userId, $interlocutorId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Conversation supprimée']);
            } else {
                throw new Exception("Impossible de supprimer la conversation");
            }
            break;

        case 'mark_read':
            $messageId = (int)($_POST['message_id'] ?? 0);
            if (!$messageId) {
                throw new Exception("ID message manquant");
            }

            $result = $messageModel->markMessageAsRead($messageId, $userId);
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("Action inconnue: " . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>