<?php
/**
 * Model Favorite - Gestion des favoris CLIENT
 */
require_once __DIR__ . '/../config/db.php';

class Favorite {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Ajouter un profil aux favoris
     */
    public function add($utilisateurId, $cibleType, $cibleId) {
        if (!in_array($cibleType, ['prestataire', 'candidat'])) {
            return "Type invalide";
        }
        
        if ($this->exists($utilisateurId, $cibleType, $cibleId)) {
            return "Déjà en favoris";
        }
        
        $sql = "INSERT INTO favoris (utilisateur_id, type_cible, cible_id) VALUES (?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$utilisateurId, $cibleType, $cibleId]);
            $this->createNotification($cibleId, $cibleType);
            return true;
        } catch (PDOException $e) {
            return "Erreur: " . $e->getMessage();
        }
    }
    
    /**
     * Retirer un profil des favoris
     */
    public function remove($utilisateurId, $cibleType, $cibleId) {
        $sql = "DELETE FROM favoris WHERE utilisateur_id = ? AND type_cible = ? AND cible_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$utilisateurId, $cibleType, $cibleId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Vérifier si profil est en favoris
     */
    public function exists($utilisateurId, $cibleType, $cibleId) {
        $sql = "SELECT id FROM favoris WHERE utilisateur_id = ? AND type_cible = ? AND cible_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId, $cibleType, $cibleId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer tous les favoris
     */
    public function getAll($utilisateurId, $filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.id, f.cible_id, f.type_cible, f.created_at AS date_ajout,
                CASE 
                    WHEN f.type_cible = 'prestataire' THEN CONCAT(up.prenom, ' ', up.nom)
                    WHEN f.type_cible = 'candidat' THEN CONCAT(uc.prenom, ' ', uc.nom)
                END AS nom,
                CASE 
                    WHEN f.type_cible = 'prestataire' THEN up.photo_profil
                    WHEN f.type_cible = 'candidat' THEN uc.photo_profil
                END AS photo,
                CASE 
                    WHEN f.type_cible = 'prestataire' THEN pp.titre_professionnel
                    WHEN f.type_cible = 'candidat' THEN cv.titre_poste_recherche
                END AS titre
                FROM favoris f
                LEFT JOIN profils_prestataires pp ON f.type_cible = 'prestataire' AND f.cible_id = pp.utilisateur_id
                LEFT JOIN utilisateurs up ON f.type_cible = 'prestataire' AND f.cible_id = up.id
                LEFT JOIN cvs cv ON f.type_cible = 'candidat' AND f.cible_id = cv.utilisateur_id
                LEFT JOIN utilisateurs uc ON f.type_cible = 'candidat' AND f.cible_id = uc.id
                WHERE f.utilisateur_id = ?";
        
        $params = [$utilisateurId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND f.type_cible = ?";
            $params[] = $filters['type'];
        }
        
        $sql .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter les favoris
     */
    public function count($utilisateurId, $filters = []) {
        $sql = "SELECT COUNT(*) FROM favoris WHERE utilisateur_id = ?";
        $params = [$utilisateurId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND type_cible = ?";
            $params[] = $filters['type'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Créer notification
     */
    private function createNotification($cibleId, $cibleType) {
        if ($cibleType === 'prestataire') {
            $sql = "SELECT utilisateur_id FROM profils_prestataires WHERE id = ?";
        } else {
            $sql = "SELECT utilisateur_id FROM cvs WHERE id = ?";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cibleId]);
        $userId = $stmt->fetchColumn();
        
        if ($userId) {
            require_once __DIR__ . '/Notification.php';
            $notif = new Notification();
            $notif->create($userId, 'favori', 'Nouveau favori', 'Votre profil a été ajouté aux favoris', null);
        }
    }
}
