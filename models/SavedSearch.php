<?php
/**
 * Model SavedSearch - Gestion des recherches sauvegardées CLIENT
 */
require_once __DIR__ . '/../config/db.php';

class SavedSearch {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Créer une recherche sauvegardée
     */
    public function create($utilisateurId, $nom, $criteres, $typeRecherche) {
        $sql = "INSERT INTO recherches_sauvegardees (utilisateur_id, nom, criteres, type) 
                VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$utilisateurId, $nom, json_encode($criteres), $typeRecherche]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Récupérer toutes les recherches sauvegardées
     */
    public function getAll($utilisateurId) {
        $sql = "SELECT * FROM recherches_sauvegardees WHERE utilisateur_id = ? ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Supprimer une recherche
     */
    public function delete($rechercheId, $utilisateurId) {
        $sql = "DELETE FROM recherches_sauvegardees WHERE id = ? AND utilisateur_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$rechercheId, $utilisateurId]);
    }
}
