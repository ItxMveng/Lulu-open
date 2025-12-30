<?php
require_once 'BaseController.php';
require_once 'models/Message.php';

class MessageController extends BaseController {
    
    public function __construct() {
        $this->requireAuth();
    }
    
    public function inbox() {
        try {
            $messageModel = new Message();
            $conversations = $messageModel->getConversations($_SESSION['user_id']);
            $unreadCount = $messageModel->getUnreadCount($_SESSION['user_id']);
            
            $data = [
                'title' => 'Messages - ' . APP_NAME,
                'conversations' => $conversations,
                'unread_count' => $unreadCount
            ];
            
            $this->render('prestataire/messages/inbox', $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement des messages', 'error');
            redirect('prestataire/dashboard.php');
        }
    }
    
    public function conversation() {
        try {
            $contactId = $this->getInput('contact_id');
            
            if (!$contactId) {
                throw new Exception('Contact non spécifié');
            }
            
            $messageModel = new Message();
            $messages = $messageModel->getConversation($_SESSION['user_id'], $contactId);
            
            // Récupération des infos du contact
            global $database;
            $contact = $database->fetch(
                "SELECT id, nom, prenom, photo_profil, type_utilisateur FROM utilisateurs WHERE id = :id",
                ['id' => $contactId]
            );
            
            if (!$contact) {
                throw new Exception('Contact non trouvé');
            }
            
            $data = [
                'title' => 'Conversation avec ' . $contact['prenom'] . ' ' . $contact['nom'],
                'messages' => $messages,
                'contact' => $contact,
                'csrf_token' => $this->generateCSRF()
            ];
            
            $this->render('prestataire/messages/conversation', $data);
            
        } catch (Exception $e) {
            flashMessage($e->getMessage(), 'error');
            redirect('prestataire/messages/inbox.php');
        }
    }
    
    public function sendMessage() {
        if (!$this->isPost()) {
            $this->json(['error' => 'Méthode non autorisée'], 405);
        }
        
        try {
            $this->validateCSRF();
            
            $destinataireId = $this->getInput('destinataire_id');
            $sujet = $this->getInput('sujet');
            $contenu = $this->getInput('contenu');
            
            if (!$destinataireId || !$sujet || !$contenu) {
                throw new Exception('Données manquantes');
            }
            
            $messageModel = new Message();
            $messageId = $messageModel->sendMessage([
                'expediteur_id' => $_SESSION['user_id'],
                'destinataire_id' => $destinataireId,
                'sujet' => $sujet,
                'contenu' => $contenu
            ]);
            
            $this->logActivity('message_sent', [
                'message_id' => $messageId,
                'recipient_id' => $destinataireId
            ]);
            
            $this->json([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message envoyé avec succès'
            ]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function getConversation() {
        try {
            $contactId = $this->getInput('contact_id');
            $lastMessageId = $this->getInput('last_message_id', 0);
            
            if (!$contactId) {
                throw new Exception('Contact non spécifié');
            }
            
            $messageModel = new Message();
            $messages = $messageModel->getConversation($_SESSION['user_id'], $contactId);
            
            // Filtrer les nouveaux messages si last_message_id est fourni
            if ($lastMessageId > 0) {
                $messages = array_filter($messages, function($message) use ($lastMessageId) {
                    return $message['id'] > $lastMessageId;
                });
            }
            
            $this->json([
                'success' => true,
                'messages' => array_values($messages),
                'unread_count' => $messageModel->getUnreadCount($_SESSION['user_id'])
            ]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function markAsRead() {
        if (!$this->isPost()) {
            $this->json(['error' => 'Méthode non autorisée'], 405);
        }
        
        try {
            $contactId = $this->getInput('contact_id');
            
            if (!$contactId) {
                throw new Exception('Contact non spécifié');
            }
            
            $messageModel = new Message();
            $messageModel->markAsRead($_SESSION['user_id'], $contactId);
            
            $this->json([
                'success' => true,
                'unread_count' => $messageModel->getUnreadCount($_SESSION['user_id'])
            ]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function deleteMessage() {
        if (!$this->isPost()) {
            $this->json(['error' => 'Méthode non autorisée'], 405);
        }
        
        try {
            $this->validateCSRF();
            
            $messageId = $this->getInput('message_id');
            
            if (!$messageId) {
                throw new Exception('Message non spécifié');
            }
            
            $messageModel = new Message();
            $messageModel->deleteMessage($messageId, $_SESSION['user_id']);
            
            $this->logActivity('message_deleted', ['message_id' => $messageId]);
            
            $this->json(['success' => true, 'message' => 'Message supprimé']);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function search() {
        try {
            $query = $this->getInput('q', '');
            
            if (strlen($query) < 2) {
                throw new Exception('Requête trop courte');
            }
            
            $messageModel = new Message();
            $results = $messageModel->searchMessages($_SESSION['user_id'], $query);
            
            $this->json([
                'success' => true,
                'results' => $results,
                'total' => count($results)
            ]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    public function getStats() {
        try {
            $messageModel = new Message();
            $stats = $messageModel->getMessageStats($_SESSION['user_id']);
            
            $this->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
?>