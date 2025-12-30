<?php
/**
 * Model Message - Gestion de la messagerie CLIENT
 */
require_once __DIR__ . '/../config/db.php';

class Message {
    private $db;
    protected $table = 'messages';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Envoyer un message
     */
    public function send($expediteurId, $destinataireId, $sujet, $contenu) {
        // Validation stricte ID numérique
        if (!is_numeric($destinataireId) || $destinataireId <= 0) return false;
        
        // Vérifier destinataire existe
        $stmt = $this->db->prepare("SELECT id FROM utilisateurs WHERE id = ?");
        $stmt->execute([$destinataireId]);
        if (!$stmt->fetch()) return false;
        
        if (empty(trim($contenu))) return false;
        if (!$this->checkRateLimit($expediteurId, $destinataireId)) return false;
        
        $sql = "INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$expediteurId, $destinataireId, $sujet, $contenu]);
            $messageId = $this->db->lastInsertId();
            $this->createNotification($destinataireId, $expediteurId, $messageId);
            return $messageId;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Récupérer liste des conversations
     */
    public function getConversations($utilisateurId) {
        $sql = "SELECT 
                CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END AS interlocuteur_id,
                u.prenom, u.nom, u.photo_profil,
                m.date_envoi AS derniere_date
                FROM messages m
                INNER JOIN (
                    SELECT 
                        CASE WHEN expediteur_id = ? THEN destinataire_id ELSE expediteur_id END AS contact_id,
                        MAX(date_envoi) AS max_date
                    FROM messages
                    WHERE expediteur_id = ? OR destinataire_id = ?
                    GROUP BY contact_id
                ) latest ON (m.expediteur_id = ? AND m.destinataire_id = latest.contact_id OR m.destinataire_id = ? AND m.expediteur_id = latest.contact_id) AND m.date_envoi = latest.max_date
                JOIN utilisateurs u ON u.id = CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END
                ORDER BY derniere_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId, $utilisateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer historique conversation
     */
    public function getConversation($utilisateurId, $interlocuteurId, $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT m.*, u.prenom, u.nom, u.photo_profil
                FROM messages m
                JOIN utilisateurs u ON u.id = m.expediteur_id
                WHERE (m.expediteur_id = ? AND m.destinataire_id = ?)
                   OR (m.expediteur_id = ? AND m.destinataire_id = ?)
                ORDER BY m.date_envoi DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId, $interlocuteurId, $interlocuteurId, $utilisateurId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marquer messages comme lus
     */
    public function markAsRead($utilisateurId, $interlocuteurId) {
        $sql = "UPDATE messages SET lu = 1 
                WHERE destinataire_id = ? AND expediteur_id = ? AND lu = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$utilisateurId, $interlocuteurId]);
    }
    
    /**
     * Compter messages non lus
     */
    public function countUnread($utilisateurId) {
        $sql = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND lu = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Vérifier rate limit (anti-spam)
     */
    private function checkRateLimit($expediteurId, $destinataireId) {
        $sql = "SELECT COUNT(*) FROM messages 
                WHERE expediteur_id = ? AND destinataire_id = ? AND DATE(date_envoi) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expediteurId, $destinataireId]);
        return $stmt->fetchColumn() < 10;
    }
    
    /**
     * Créer notification nouveau message
     */
    private function createNotification($destinataireId, $expediteurId, $messageId) {
        require_once __DIR__ . '/Notification.php';
        $stmt = $this->db->prepare("SELECT prenom, nom FROM utilisateurs WHERE id = ?");
        $stmt->execute([$expediteurId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $notif = new Notification();
        $notif->create($destinataireId, 'message', 'Nouveau message', 
            "Vous avez reçu un message de {$user['prenom']} {$user['nom']}", 
            "/lulu/messages.php?id={$expediteurId}");
    }
}
?>