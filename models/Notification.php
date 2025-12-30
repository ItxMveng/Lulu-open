<?php
/**
 * Model Notification - Gestion des notifications
 */
require_once __DIR__ . '/../config/db.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Créer une notification
     */
    public function create($utilisateurId, $type, $titre, $contenu, $lien = null) {
        $sql = "INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, url_action) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$utilisateurId, $type, $titre, $contenu, $lien]);
    }
    
    /**
     * Récupérer notifications
     */
    public function getAll($utilisateurId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM notifications WHERE utilisateur_id = ? 
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marquer comme lu
     */
    public function markAsRead($notificationId, $utilisateurId) {
        $sql = "UPDATE notifications SET lu = 1 WHERE id = ? AND utilisateur_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId, $utilisateurId]);
    }
    
    /**
     * Marquer toutes comme lues
     */
    public function markAllAsRead($utilisateurId) {
        $sql = "UPDATE notifications SET lu = 1 WHERE utilisateur_id = ? AND lu = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$utilisateurId]);
    }
    
    /**
     * Compter non lues
     */
    public function countUnread($utilisateurId) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lu = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Supprimer notification
     */
    public function delete($notificationId, $utilisateurId) {
        $sql = "DELETE FROM notifications WHERE id = ? AND utilisateur_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationId, $utilisateurId]);
    }
}
