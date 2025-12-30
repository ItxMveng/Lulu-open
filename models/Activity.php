<?php
/**
 * Model Activity - Gestion de l'historique d'activité CLIENT
 */
require_once __DIR__ . '/../config/db.php';

class Activity {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Enregistrer une consultation de profil
     */
    public function logConsultation($utilisateurId, $profilType, $profilId) {
        $sql = "INSERT INTO historique_consultations (utilisateur_id, cible_type, cible_id) 
                VALUES (?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$utilisateurId, $profilType, $profilId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Compter consultations 7 derniers jours
     */
    public function countLast7Days($utilisateurId) {
        $sql = "SELECT COUNT(*) FROM historique_consultations 
                WHERE utilisateur_id = ? AND date_consultation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Récupérer historique avec filtres
     */
    public function getHistory($utilisateurId, $filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT h.*, 
                CASE 
                    WHEN h.cible_type = 'prestataire' THEN pp.titre_professionnel
                    ELSE CONCAT(u.prenom, ' ', u.nom)
                END AS nom,
                CASE 
                    WHEN h.cible_type = 'prestataire' THEN u2.photo_profil
                    ELSE u.photo_profil
                END AS photo,
                pp.titre_professionnel AS titre
                FROM historique_consultations h
                LEFT JOIN profils_prestataires pp ON h.cible_type = 'prestataire' AND h.cible_id = pp.id
                LEFT JOIN utilisateurs u2 ON pp.utilisateur_id = u2.id
                LEFT JOIN cvs cv ON h.cible_type = 'candidat' AND h.cible_id = cv.id
                LEFT JOIN utilisateurs u ON cv.utilisateur_id = u.id
                WHERE h.utilisateur_id = ?";
        
        $params = [$utilisateurId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND h.cible_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['period'])) {
            $sql .= " AND h.date_consultation >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $filters['period'];
        }
        
        $sql .= " ORDER BY h.date_consultation DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter historique avec filtres
     */
    public function countHistory($utilisateurId, $filters = []) {
        $sql = "SELECT COUNT(*) FROM historique_consultations WHERE utilisateur_id = ?";
        $params = [$utilisateurId];
        
        if (!empty($filters['type'])) {
            $sql .= " AND cible_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['period'])) {
            $sql .= " AND date_consultation >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $filters['period'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
}
