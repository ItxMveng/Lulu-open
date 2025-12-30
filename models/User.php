<?php

class User {

    private $db;

    public function __construct($database = null) {
        global $database;
        $this->db = $database ?? $database;
    }

    public function findByEmail($email) {
        return $this->db->fetch("SELECT * FROM utilisateurs WHERE email = ?", [$email]);
    }

    public function findById($id) {
        return $this->db->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$id]);
    }

    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) {
            throw new Exception("Email ou mot de passe incorrect");
        }

        if (!password_verify($password, $user['mot_de_passe'])) {
            throw new Exception("Email ou mot de passe incorrect");
        }

        return $user;
    }

    public function getRecentProfiles($limit = 6) {
        $sql = "
            SELECT u.id, u.nom, u.prenom, u.photo_profil, u.type_utilisateur, l.ville,
                   COALESCE(pp.titre_professionnel, cv.titre_poste_recherche) as titre,
                   cs.nom as categorie_nom
            FROM utilisateurs u
            LEFT JOIN localisations l ON u.localisation_id = l.id
            LEFT JOIN profils_prestataires pp ON u.id = pp.utilisateur_id
            LEFT JOIN cvs cv ON u.id = cv.utilisateur_id
            LEFT JOIN categories_services cs ON cs.id = COALESCE(pp.categorie_id, cv.categorie_id)
            WHERE u.statut = 'actif'
              AND u.type_utilisateur IN ('prestataire', 'candidat', 'prestataire_candidat')
              AND (pp.id IS NOT NULL OR cv.id IS NOT NULL)
            ORDER BY u.date_inscription DESC
            LIMIT ?
        ";

        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Vérifie si l'utilisateur a un abonnement payant actif
     *
     * @param int $userId ID de l'utilisateur
     * @return bool True si l'utilisateur a un abonnement payant actif
     */
    public function hasActivePaidSubscription($userId) {
        try {
            $sql = "
                SELECT COUNT(*) as count
                FROM abonnements a
                JOIN plans_abonnement p ON a.plan_id = p.id
                WHERE a.utilisateur_id = ?
                  AND a.statut = 'actif'
                  AND p.prix_mensuel > 0
                  AND a.date_fin >= CURDATE()
            ";
            $result = $this->db->fetch($sql, [$userId]);
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Erreur dans hasActivePaidSubscription: " . $e->getMessage());
            return false;
        }
    }
}
?>