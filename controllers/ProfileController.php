<?php
require_once 'BaseController.php';

class ProfileController extends BaseController {

    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function show($id = null) {
        if (!$id) {
            http_response_code(404);
            echo "Profil non trouvé";
            return;
        }

        try {
            // Déterminer si l'utilisateur est prestataire ou candidat
            $userCheck = $this->db->fetch("
                SELECT type_utilisateur,
                       (SELECT COUNT(*) FROM profils_prestataires WHERE utilisateur_id = ?) as has_prestataire,
                       (SELECT COUNT(*) FROM cvs WHERE utilisateur_id = ?) as has_candidat
                FROM utilisateurs
                WHERE id = ? AND statut = 'actif'
            ", [$id, $id, $id]);

            if (!$userCheck) {
                http_response_code(404);
                echo "Profil non trouvé";
                return;
            }

            $profileType = null;
            $profile = null;

            // Gérer les profils hybrides (prestataire_candidat)
            if ($userCheck['type_utilisateur'] === 'prestataire_candidat') {
                if ($userCheck['has_prestataire'] > 0) {
                    // Afficher par défaut comme prestataire
                    $profileType = 'prestataire';
                } elseif ($userCheck['has_candidat'] > 0) {
                    $profileType = 'candidat';
                }
            } elseif ($userCheck['type_utilisateur'] === 'prestataire' && $userCheck['has_prestataire'] > 0) {
                $profileType = 'prestataire';
            } elseif ($userCheck['type_utilisateur'] === 'candidat' && $userCheck['has_candidat'] > 0) {
                $profileType = 'candidat';
            }

            if ($profileType === 'prestataire') {
                // Récupérer le profil prestataire
                $profile = $this->db->fetch("
                    SELECT u.*, p.*, c.nom as categorie_nom, c.icone as categorie_icone,
                           AVG(an.note) as note_moyenne,
                           COUNT(an.id) as nombre_avis,
                           'prestataire' as profile_type
                    FROM utilisateurs u
                    JOIN profils_prestataires p ON u.id = p.utilisateur_id
                    LEFT JOIN categories_services c ON p.categorie_id = c.id
                    LEFT JOIN avis_notes an ON u.id = an.receveur_id AND an.type_profil = 'prestataire'
                    WHERE u.id = ? AND u.statut = 'actif'
                    GROUP BY u.id
                ", [$id]);

                // Récupérer les avis des prestataires
                $reviews = $this->db->fetchAll("
                    SELECT an.*, u.prenom, u.nom, CONCAT(u.prenom, ' ', u.nom) as donneur_nom
                    FROM avis_notes an
                    JOIN utilisateurs u ON an.donneur_id = u.id
                    WHERE an.receveur_id = ? AND an.type_profil = 'prestataire'
                    ORDER BY an.date_avis DESC
                    LIMIT 10
                ", [$id]);

                // Récupérer le portfolio
                $portfolio = $this->db->fetchAll("
                    SELECT * FROM portfolios
                    WHERE prestataire_id = ?
                    ORDER BY created_at DESC
                ", [$profile['id'] ?? null]);

            } elseif ($profileType === 'candidat') {
                // Récupérer le profil candidat
                $profile = $this->db->fetch("
                    SELECT u.*, cv.*, c.nom as categorie_nom, c.icone as categorie_icone,
                           AVG(an.note) as note_moyenne,
                           COUNT(an.id) as nombre_avis,
                           'candidat' as profile_type,
                           cv.cv_fichier as cv_fichier
                    FROM utilisateurs u
                    JOIN cvs cv ON u.id = cv.utilisateur_id
                    LEFT JOIN categories_services c ON cv.categorie_id = c.id
                    LEFT JOIN avis_notes an ON u.id = an.receveur_id AND an.type_profil = 'candidat'
                    WHERE u.id = ? AND u.statut = 'actif'
                    GROUP BY u.id
                ", [$id]);

                // Récupérer les avis des candidats
                $reviews = $this->db->fetchAll("
                    SELECT an.*, u.prenom, u.nom, CONCAT(u.prenom, ' ', u.nom) as donneur_nom
                    FROM avis_notes an
                    JOIN utilisateurs u ON an.donneur_id = u.id
                    WHERE an.receveur_id = ? AND an.type_profil = 'candidat'
                    ORDER BY an.date_avis DESC
                    LIMIT 10
                ", [$id]);

                $portfolio = []; // Candidats n'ont pas de portfolio
            }

            // Charger la vue du profil
            if ($profile) {
                require_once 'views/pages/profile_detail.php';
            } else {
                http_response_code(404);
                echo "Profil non trouvé";
            }

        } catch (Exception $e) {
            error_log("Erreur dans ProfileController: " . $e->getMessage());
            http_response_code(500);
            echo "Erreur serveur: " . $e->getMessage();
        }
    }
}
?>
