<?php
/**
 * Modèle Category - LULU-OPEN
 */

require_once 'BaseModel.php';

class Category extends BaseModel {
    
    protected $table = 'categories_services';
    
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY nom ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND actif = 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getPopularCategories($limit = 8) {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT p.id) as nb_prestataires,
                       COUNT(DISTINCT cv.id) as nb_cvs,
                       (COUNT(DISTINCT p.id) + COUNT(DISTINCT cv.id)) as total_profils
                FROM {$this->table} c
                LEFT JOIN profils_prestataires p ON c.id = p.categorie_id
                LEFT JOIN utilisateurs up ON p.utilisateur_id = up.id AND up.statut = 'actif'
                LEFT JOIN cvs cv ON c.id = cv.categorie_id  
                LEFT JOIN utilisateurs ucv ON cv.utilisateur_id = ucv.id AND ucv.statut = 'actif'
                WHERE c.actif = 1
                GROUP BY c.id
                ORDER BY total_profils DESC, c.nom ASC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    public function searchCategories($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE actif = 1 AND (nom LIKE :query OR description LIKE :query)
                ORDER BY nom ASC";
        
        $searchTerm = '%' . $query . '%';
        return $this->db->fetchAll($sql, ['query' => $searchTerm]);
    }
    
    public function create($data) {
        $requiredFields = ['nom'];
        $this->validateRequired($requiredFields, $data);
        
        // Vérification de l'unicité du nom
        if ($this->existsByName($data['nom'])) {
            throw new Exception('Une catégorie avec ce nom existe déjà');
        }
        
        $insertData = [
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'icone' => $data['icone'] ?? 'folder',
            'couleur' => $data['couleur'] ?? '#0099FF',
            'actif' => $data['actif'] ?? 1
        ];
        
        return $this->db->insert($this->table, $insertData);
    }
    
    public function update($id, $data) {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception('Catégorie non trouvée');
        }
        
        // Vérification de l'unicité du nom (sauf pour la catégorie actuelle)
        if (isset($data['nom']) && $this->existsByName($data['nom'], $id)) {
            throw new Exception('Une catégorie avec ce nom existe déjà');
        }
        
        $updateData = [];
        $allowedFields = ['nom', 'description', 'icone', 'couleur', 'actif'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('Aucune donnée à mettre à jour');
        }
        
        return $this->db->update($this->table, $updateData, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        // Vérification des dépendances
        if ($this->hasProfiles($id)) {
            throw new Exception('Impossible de supprimer cette catégorie car elle contient des profils');
        }
        
        return $this->db->delete($this->table, 'id = :id', ['id' => $id]);
    }
    
    public function toggleStatus($id) {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception('Catégorie non trouvée');
        }
        
        $newStatus = $category['actif'] ? 0 : 1;
        return $this->update($id, ['actif' => $newStatus]);
    }
    
    private function existsByName($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nom = :nom";
        $params = ['nom' => $name];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    private function hasProfiles($categoryId) {
        // Vérification des prestataires
        $prestatairesSql = "SELECT COUNT(*) as count FROM profils_prestataires WHERE categorie_id = :id";
        $prestatairesCount = $this->db->fetch($prestatairesSql, ['id' => $categoryId])['count'];
        
        // Vérification des CVs
        $cvsSql = "SELECT COUNT(*) as count FROM cvs WHERE categorie_id = :id";
        $cvsCount = $this->db->fetch($cvsSql, ['id' => $categoryId])['count'];
        
        return ($prestatairesCount + $cvsCount) > 0;
    }
    
    public function getCategoryStats($categoryId) {
        $sql = "SELECT 
                    c.*,
                    COUNT(DISTINCT p.id) as nb_prestataires,
                    COUNT(DISTINCT cv.id) as nb_cvs,
                    AVG(CASE WHEN p.id IS NOT NULL THEN p.note_moyenne END) as note_moyenne_prestataires,
                    AVG(CASE WHEN cv.id IS NOT NULL THEN cv.note_moyenne END) as note_moyenne_cvs
                FROM {$this->table} c
                LEFT JOIN profils_prestataires p ON c.id = p.categorie_id
                LEFT JOIN utilisateurs up ON p.utilisateur_id = up.id AND up.statut = 'actif'
                LEFT JOIN cvs cv ON c.id = cv.categorie_id
                LEFT JOIN utilisateurs ucv ON cv.utilisateur_id = ucv.id AND ucv.statut = 'actif'
                WHERE c.id = :id
                GROUP BY c.id";
        
        return $this->db->fetch($sql, ['id' => $categoryId]);
    }
    
    public function getTopCategories($limit = 5) {
        return $this->getPopularCategories($limit);
    }
}
?>