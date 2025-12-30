<?php
require_once 'BaseModel.php';

class Profile extends BaseModel {
    
    public function getPrestataire($userId) {
        $sql = "SELECT p.*, c.nom as categorie_nom, c.couleur as categorie_couleur, c.icone as categorie_icone,
                       u.nom, u.prenom, u.email, u.telephone, u.photo_profil, u.date_inscription,
                       l.ville, l.region, l.code_postal
                FROM profils_prestataires p
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                LEFT JOIN categories_services c ON p.categorie_id = c.id
                LEFT JOIN localisations l ON u.localisation_id = l.id
                WHERE p.utilisateur_id = :user_id AND u.statut = 'actif'";
        
        return $this->db->fetch($sql, ['user_id' => $userId]);
    }
    
    public function getCandidat($userId) {
        $sql = "SELECT cv.*, c.nom as categorie_nom, c.couleur as categorie_couleur, c.icone as categorie_icone,
                       u.nom, u.prenom, u.email, u.telephone, u.photo_profil, u.date_inscription,
                       l.ville, l.region, l.code_postal
                FROM cvs cv
                JOIN utilisateurs u ON cv.utilisateur_id = u.id
                LEFT JOIN categories_services c ON cv.categorie_id = c.id
                LEFT JOIN localisations l ON u.localisation_id = l.id
                WHERE cv.utilisateur_id = :user_id AND u.statut = 'actif'";
        
        return $this->db->fetch($sql, ['user_id' => $userId]);
    }
    
    public function searchProfiles($filters = [], $page = 1, $perPage = 12) {
        $where = ["u.statut = 'actif'"];
        $params = [];
        $joins = [];
        
        // Type de profil
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'prestataire') {
                $joins[] = "JOIN profils_prestataires p ON u.id = p.utilisateur_id";
                $where[] = "(u.type_utilisateur = 'prestataire' OR u.type_utilisateur = 'prestataire_candidat')";
            } elseif ($filters['type'] === 'candidat') {
                $joins[] = "JOIN cvs cv ON u.id = cv.utilisateur_id";
                $where[] = "(u.type_utilisateur = 'candidat' OR u.type_utilisateur = 'prestataire_candidat')";
            }
        } else {
            $joins[] = "LEFT JOIN profils_prestataires p ON u.id = p.utilisateur_id AND u.type_utilisateur IN ('prestataire', 'prestataire_candidat')";
            $joins[] = "LEFT JOIN cvs cv ON u.id = cv.utilisateur_id AND u.type_utilisateur IN ('candidat', 'prestataire_candidat')";
            $where[] = "u.type_utilisateur IN ('prestataire', 'candidat', 'prestataire_candidat')";
        }
        
        // Catégories (peut être array)
        if (!empty($filters['categories'])) {
            $categories = $filters['categories'];
            if (is_array($categories) && !empty($categories)) {
                $placeholders = implode(',', array_fill(0, count($categories), '?'));
                $where[] = "(p.categorie_id IN ($placeholders) OR cv.categorie_id IN ($placeholders))";
                $params = array_merge($params, $categories, $categories); // Deux fois pour p et cv
            } elseif (!is_array($categories) && !empty($categories)) {
                $where[] = "(p.categorie_id = ? OR cv.categorie_id = ?)";
                $params[] = $categories;
                $params[] = $categories;
            }
        }
        
        // Localisation
        if (!empty($filters['location'])) {
            $joins[] = "LEFT JOIN localisations l ON u.localisation_id = l.id";
            $where[] = "(l.ville LIKE :location OR l.region LIKE :location)";
            $params['location'] = '%' . $filters['location'] . '%';
        }
        
        // Budget/Tarif
        if (!empty($filters['min_price'])) {
            $where[] = "(p.tarif_horaire >= :min_price OR cv.salaire_souhaite >= :min_price)";
            $params['min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = "(p.tarif_horaire <= :max_price OR cv.salaire_souhaite <= :max_price)";
            $params['max_price'] = $filters['max_price'];
        }
        
        // Note minimum
        if (!empty($filters['rating'])) {
            $where[] = "(p.note_moyenne >= :rating OR cv.note_moyenne >= :rating)";
            $params['rating'] = $filters['rating'];
        }
        
        // Abonnement actif
        if (!empty($filters['active_subscription'])) {
            $joins[] = "JOIN abonnements a ON u.id = a.utilisateur_id AND a.statut = 'actif' AND a.date_fin >= CURDATE()";
        }
        
        // Recherche textuelle
        if (!empty($filters['query'])) {
            $where[] = "(u.nom LIKE :query OR u.prenom LIKE :query OR p.titre_professionnel LIKE :query OR cv.titre_poste_recherche LIKE :query OR p.description_services LIKE :query OR cv.competences LIKE :query)";
            $params['query'] = '%' . $filters['query'] . '%';
        }
        
        $joinClause = implode(' ', array_unique($joins));
        $whereClause = implode(' AND ', $where);
        
        // Tri
        $orderBy = "u.date_inscription DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'rating':
                    $orderBy = "COALESCE(p.note_moyenne, cv.note_moyenne, 0) DESC";
                    break;
                case 'price_asc':
                    $orderBy = "COALESCE(p.tarif_horaire, cv.salaire_souhaite, 0) ASC";
                    break;
                case 'price_desc':
                    $orderBy = "COALESCE(p.tarif_horaire, cv.salaire_souhaite, 0) DESC";
                    break;
            }
        }
        
        $sql = "SELECT DISTINCT u.id, u.nom, u.prenom, u.photo_profil, u.type_utilisateur, u.date_inscription,
                       COALESCE(l.ville, '') as ville, COALESCE(l.region, '') as region,
                       p.titre_professionnel, p.note_moyenne as note_prestataire, p.tarif_horaire,
                       cv.titre_poste_recherche, cv.note_moyenne as note_cv, cv.salaire_souhaite,
                       COALESCE(c.nom, '') as categorie_nom, COALESCE(c.couleur, '#0099FF') as categorie_couleur
                FROM utilisateurs u
                $joinClause
                LEFT JOIN localisations l ON u.localisation_id = l.id
                LEFT JOIN categories_services c ON (p.categorie_id = c.id OR cv.categorie_id = c.id)
                WHERE $whereClause
                ORDER BY $orderBy";
        
        $offset = ($page - 1) * $perPage;
        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        
        return $this->db->fetchAll($sql . " LIMIT :offset, :limit", $params);
    }
    
    public function updatePrestataire($userId, $data) {
        $allowedFields = ['titre_professionnel', 'description_services', 'tarif_horaire', 'tarif_forfait', 
                         'experience_annees', 'diplomes', 'certifications', 'disponibilite', 'rayon_intervention'];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('Aucune donnée à mettre à jour');
        }
        
        return $this->db->update('profils_prestataires', $updateData, 'utilisateur_id = :id', ['id' => $userId]);
    }
    
    public function updateCandidat($userId, $data) {
        $allowedFields = ['titre_poste_recherche', 'niveau_experience', 'salaire_souhaite', 'type_contrat',
                         'competences', 'formations', 'experiences_professionnelles', 'langues', 'mobilite', 'lettre_motivation'];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('Aucune donnée à mettre à jour');
        }
        
        return $this->db->update('cvs', $updateData, 'utilisateur_id = :id', ['id' => $userId]);
    }
    
    public function getProfileStats($userId) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM logs_activite WHERE action = 'profile_view' AND details LIKE :profile_view) as profile_views,
                    (SELECT COUNT(*) FROM messages WHERE destinataire_id = :user_id) as messages_received,
                    (SELECT COUNT(*) FROM avis_notes WHERE receveur_id = :user_id) as total_reviews
                ";
        
        return $this->db->fetch($sql, [
            'profile_view' => '%"profile_id":' . $userId . '%',
            'user_id' => $userId
        ]);
    }
}
?>
