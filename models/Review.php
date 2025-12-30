<?php
require_once 'BaseModel.php';

class Review extends BaseModel {
    
    protected $table = 'avis_notes';
    
    public function create($data) {
        $requiredFields = ['donneur_id', 'receveur_id', 'type_profil', 'note'];
        $this->validateRequired($requiredFields, $data);
        
        $this->validateNumeric($data['note'], 1, 5);
        $this->validateEnum($data['type_profil'], ['prestataire', 'candidat']);
        
        // Vérification que l'utilisateur ne se note pas lui-même
        if ($data['donneur_id'] == $data['receveur_id']) {
            throw new Exception('Vous ne pouvez pas vous noter vous-même');
        }
        
        // Vérification qu'il n'y a pas déjà un avis
        if ($this->hasExistingReview($data['donneur_id'], $data['receveur_id'])) {
            throw new Exception('Vous avez déjà noté ce profil');
        }
        
        $insertData = [
            'donneur_id' => $data['donneur_id'],
            'receveur_id' => $data['receveur_id'],
            'type_profil' => $data['type_profil'],
            'note' => $data['note'],
            'commentaire' => $data['commentaire'] ?? null,
            'modere' => false
        ];
        
        $reviewId = $this->db->insert($this->table, $insertData);
        
        // Mise à jour de la note moyenne
        $this->updateAverageRating($data['receveur_id'], $data['type_profil']);
        
        return $reviewId;
    }
    
    public function getProfileReviews($profileId, $limit = 10) {
        $sql = "SELECT a.*, u.nom, u.prenom, u.photo_profil
                FROM {$this->table} a
                JOIN utilisateurs u ON a.donneur_id = u.id
                WHERE a.receveur_id = :profile_id AND a.modere = 0
                ORDER BY a.date_avis DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['profile_id' => $profileId, 'limit' => $limit]);
    }
    
    public function getReviewStats($profileId) {
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(note) as average_rating,
                    COUNT(CASE WHEN note = 5 THEN 1 END) as five_stars,
                    COUNT(CASE WHEN note = 4 THEN 1 END) as four_stars,
                    COUNT(CASE WHEN note = 3 THEN 1 END) as three_stars,
                    COUNT(CASE WHEN note = 2 THEN 1 END) as two_stars,
                    COUNT(CASE WHEN note = 1 THEN 1 END) as one_star
                FROM {$this->table}
                WHERE receveur_id = :profile_id AND modere = 0";
        
        return $this->db->fetch($sql, ['profile_id' => $profileId]);
    }
    
    public function canUserReview($donneurId, $receveurId) {
        // Vérifier que l'utilisateur est connecté et différent du receveur
        if (!$donneurId || $donneurId == $receveurId) {
            return false;
        }
        
        // Vérifier qu'il n'y a pas déjà un avis
        return !$this->hasExistingReview($donneurId, $receveurId);
    }
    
    public function moderate($reviewId, $approved = true) {
        $review = $this->getById($reviewId);
        if (!$review) {
            throw new Exception('Avis non trouvé');
        }
        
        if ($approved) {
            return $this->db->update($this->table, ['modere' => true], 'id = :id', ['id' => $reviewId]);
        } else {
            // Suppression de l'avis non approuvé
            $result = $this->db->delete($this->table, 'id = :id', ['id' => $reviewId]);
            
            // Recalcul de la note moyenne
            $this->updateAverageRating($review['receveur_id'], $review['type_profil']);
            
            return $result;
        }
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getPendingReviews() {
        $sql = "SELECT a.*, u1.nom as donneur_nom, u1.prenom as donneur_prenom,
                       u2.nom as receveur_nom, u2.prenom as receveur_prenom
                FROM {$this->table} a
                JOIN utilisateurs u1 ON a.donneur_id = u1.id
                JOIN utilisateurs u2 ON a.receveur_id = u2.id
                WHERE a.modere = 0
                ORDER BY a.date_avis DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    private function hasExistingReview($donneurId, $receveurId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE donneur_id = :donneur_id AND receveur_id = :receveur_id";
        
        $result = $this->db->fetch($sql, [
            'donneur_id' => $donneurId,
            'receveur_id' => $receveurId
        ]);
        
        return $result['count'] > 0;
    }
    
    private function updateAverageRating($profileId, $typeProfile) {
        $stats = $this->db->fetch(
            "SELECT AVG(note) as moyenne, COUNT(*) as total
             FROM {$this->table} 
             WHERE receveur_id = :profile_id AND modere = 1",
            ['profile_id' => $profileId]
        );
        
        $moyenne = round($stats['moyenne'] ?? 0, 2);
        $total = $stats['total'] ?? 0;
        
        if ($typeProfile === 'prestataire') {
            $this->db->update(
                'profils_prestataires',
                ['note_moyenne' => $moyenne, 'nombre_avis' => $total],
                'utilisateur_id = :id',
                ['id' => $profileId]
            );
        } elseif ($typeProfile === 'candidat') {
            $this->db->update(
                'cvs',
                ['note_moyenne' => $moyenne, 'nombre_avis' => $total],
                'utilisateur_id = :id',
                ['id' => $profileId]
            );
        }
    }
}
?>