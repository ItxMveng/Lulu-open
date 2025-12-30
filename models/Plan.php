<?php
/**
 * Model Plan - Gestion des plans d'abonnement
 */
class Plan {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($actif_only = true) {
        $sql = "SELECT * FROM plans_abonnement";
        if ($actif_only) {
            $sql .= " WHERE actif = 1";
        }
        $sql .= " ORDER BY type_utilisateur, ordre_affichage";
        return $this->db->fetchAll($sql);
    }
    
    public function getById($id) {
        return $this->db->fetch("SELECT * FROM plans_abonnement WHERE id = ?", [$id]);
    }
    
    public function getByType($type) {
        return $this->db->fetchAll(
            "SELECT * FROM plans_abonnement WHERE type_utilisateur = ? AND actif = 1 ORDER BY ordre_affichage",
            [$type]
        );
    }
    
    public function create($data) {
        return $this->db->insert('plans_abonnement', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('plans_abonnement', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete('plans_abonnement', 'id = ?', [$id]);
    }
    
    public function toggleActif($id) {
        $plan = $this->getById($id);
        if ($plan) {
            $new_status = $plan['actif'] ? 0 : 1;
            return $this->update($id, ['actif' => $new_status]);
        }
        return false;
    }
}
?>
