<?php
/**
 * Controller Gestion Utilisateurs
 */
class UserManagementController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllUsers($filters = []) {
        $sql = "SELECT u.*, l.ville, l.pays 
                FROM utilisateurs u
                LEFT JOIN localisations l ON u.localisation_id = l.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND u.type_utilisateur = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND u.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT u.*, l.ville, l.pays 
             FROM utilisateurs u
             LEFT JOIN localisations l ON u.localisation_id = l.id
             WHERE u.id = ?",
            [$id]
        );
    }
    
    public function updateUserStatus($user_id, $statut, $raison = null) {
        $data = ['statut' => $statut];
        if ($raison) {
            $data['raison_blocage'] = $raison;
        }
        return $this->db->update('utilisateurs', $data, 'id = ?', [$user_id]);
    }
    
    public function deleteUser($user_id) {
        return $this->db->delete('utilisateurs', 'id = ?', [$user_id]);
    }
}
?>
