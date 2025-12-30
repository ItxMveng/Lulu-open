<?php
require_once 'BaseController.php';
require_once 'models/Profile.php';
require_once 'models/Category.php';
require_once 'models/Review.php';

class SearchController extends BaseController {
    
    public function index() {
        try {
            // Récupération des paramètres de recherche avancée
            $categories = $_GET['categories'] ?? [];
            if (!is_array($categories)) {
                $categories = !empty($categories) ? [$categories] : [];
            }
            $categories = array_filter(array_map('intval', $categories));

            $filters = [
                'type' => $this->getInput('type', ''),
                'query' => $this->getInput('q', ''),
                'location' => $this->getInput('location', ''),
                'categories' => $categories,
                'min_price' => $this->getInput('min_price', ''),
                'max_price' => $this->getInput('max_price', ''),
                'rating' => $this->getInput('rating', ''),
                'active_subscription' => $this->getInput('active_only', false),
                'sort' => $this->getInput('sort', 'recent')
            ];
            
            $page = max(1, (int)$this->getInput('page', 1));
            $perPage = 12;
            
            // Recherche des profils avec filtrage avancé
            $profileModel = new Profile();
            // Forcer le filtrage par abonnement actif pour tous les résultats
            $filters['active_subscription'] = true;
            $results = $profileModel->searchProfiles($filters, $page, $perPage);
            
            // Récupération des catégories et localisations pour les filtres
            $categoryModel = new Category();
            $categories = $categoryModel->getAll();
            $locations = $this->getLocations();
            
            // Statistiques de recherche
            $totalResults = $this->getTotalResults($filters);
            
            $data = [
                'title' => 'Recherche - ' . APP_NAME,
                'results' => $results,
                'categories' => $categories,
                'locations' => $locations,
                'filters' => $filters,
                'total_results' => $totalResults,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalResults / $perPage)
            ];
            
            $this->render('pages/search_results', $data);
            
        } catch (Exception $e) {
            error_log("Erreur SearchController::index: " . $e->getMessage());
            flashMessage('Erreur lors de la recherche', 'error');
            redirect('index.php');
        }
    }
    
    public function profile() {
        try {
            $profileId = $this->getInput('id');

            if (!$profileId) {
                throw new Exception('ID de profil manquant');
            }

            $userModel = new User();
            $profile = $userModel->getById($profileId);

            if (!$profile || $profile['statut'] !== 'actif') {
                throw new Exception('Profil non trouvé ou inactif');
            }

            // Pour prestataire_candidat, récupérer les deux profils
            if ($profile['type_utilisateur'] === 'prestataire_candidat') {
                $profileDetails = [
                    'prestataire' => $this->getProfileDetailsPrestataire($profileId),
                    'candidat' => $this->getProfileDetailsCandidat($profileId)
                ];
                $profileType = 'dual';
            } else {
                $profileDetails = $this->getProfileDetails($profile);
                $profileType = $profile['type_utilisateur'];
            }

            // Récupération du portfolio pour les prestataires
            $portfolio = ($profileType === 'prestataire' || $profileType === 'dual')
                       ? $this->getPortfolio($profileId) : null;

            // Récupération des avis
            $reviews = $this->getProfileReviews($profileId);

            // Incrémentation du compteur de vues (si pas le propriétaire)
            if (!isLoggedIn() || $_SESSION['user_id'] != $profileId) {
                $this->incrementProfileViews($profileId);
            }

            $data = [
                'title' => $profile['prenom'] . ' ' . $profile['nom'] . ' - ' . APP_NAME,
                'profile' => $profile,
                'profile_details' => $profileDetails,
                'profileType' => $profileType,
                'portfolio' => $portfolio,
                'reviews' => $reviews,
                'can_review' => $this->canUserReview($profileId),
                'csrf_token' => $this->generateCSRF()
            ];

            $this->render('pages/profile_detail', $data);

        } catch (Exception $e) {
            flashMessage($e->getMessage(), 'error');
            redirect('search.php');
        }
    }
    
    public function addReview() {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            redirect('search.php');
        }
        
        try {
            $this->validateCSRF();
            
            $profileId = $this->getInput('profile_id');
            $rating = $this->getInput('rating');
            $comment = $this->getInput('comment');
            
            if (!$profileId || !$rating) {
                throw new Exception('Données manquantes');
            }
            
            if (!$this->canUserReview($profileId)) {
                throw new Exception('Vous ne pouvez pas noter ce profil');
            }
            
            // Validation de la note
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Note invalide');
            }
            
            global $database;
            
            // Vérification si l'utilisateur a déjà noté ce profil
            $existingReview = $database->fetch(
                "SELECT id FROM avis_notes WHERE donneur_id = :donneur_id AND receveur_id = :receveur_id",
                ['donneur_id' => $_SESSION['user_id'], 'receveur_id' => $profileId]
            );
            
            if ($existingReview) {
                throw new Exception('Vous avez déjà noté ce profil');
            }
            
            // Récupération du type de profil
            $profile = $database->fetch("SELECT type_utilisateur FROM utilisateurs WHERE id = :id", ['id' => $profileId]);
            $typeProfile = in_array($profile['type_utilisateur'], ['prestataire', 'candidat']) ? $profile['type_utilisateur'] : 'prestataire';
            
            // Insertion de l'avis
            $reviewData = [
                'donneur_id' => $_SESSION['user_id'],
                'receveur_id' => $profileId,
                'type_profil' => $typeProfile,
                'note' => $rating,
                'commentaire' => $comment
            ];
            
            $database->insert('avis_notes', $reviewData);
            
            // Mise à jour de la note moyenne
            $this->updateAverageRating($profileId, $typeProfile);
            
            $this->logActivity('review_add', ['profile_id' => $profileId, 'rating' => $rating]);
            
            flashMessage('Votre avis a été ajouté avec succès !', 'success');
            
        } catch (Exception $e) {
            flashMessage($e->getMessage(), 'error');
        }
        
        redirect("profile.php?id=$profileId");
    }
    
    private function getTotalResults($filters) {
        try {
            $profileModel = new Profile();
            $results = $profileModel->searchProfiles($filters, 1, 1000);
            return count($results);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getLocations() {
        global $database;
        return $database->fetchAll(
            "SELECT DISTINCT ville, region FROM localisations ORDER BY ville ASC"
        );
    }
    
    private function getProfileDetails($profile) {
        global $database;

        if ($profile['type_utilisateur'] === 'prestataire') {
            return $database->fetch(
                "SELECT p.*, c.nom as categorie_nom, c.couleur as categorie_couleur
                 FROM profils_prestataires p
                 LEFT JOIN categories_services c ON p.categorie_id = c.id
                 WHERE p.utilisateur_id = :id",
                ['id' => $profile['id']]
            );
        } elseif ($profile['type_utilisateur'] === 'candidat') {
            return $database->fetch(
                "SELECT cv.*, c.nom as categorie_nom, c.couleur as categorie_couleur
                 FROM cvs cv
                 LEFT JOIN categories_services c ON cv.categorie_id = c.id
                 WHERE cv.utilisateur_id = :id",
                ['id' => $profile['id']]
            );
        }

        return null;
    }

    private function getProfileDetailsPrestataire($userId) {
        global $database;
        return $database->fetch(
            "SELECT p.*, c.nom as categorie_nom, c.couleur as categorie_couleur
             FROM profils_prestataires p
             LEFT JOIN categories_services c ON p.categorie_id = c.id
             WHERE p.utilisateur_id = :id",
            ['id' => $userId]
        );
    }

    private function getProfileDetailsCandidat($userId) {
        global $database;
        return $database->fetch(
            "SELECT cv.*, c.nom as categorie_nom, c.couleur as categorie_couleur
             FROM cvs cv
             LEFT JOIN categories_services c ON cv.categorie_id = c.id
             WHERE cv.utilisateur_id = :id",
            ['id' => $userId]
        );
    }

    private function getPortfolio($userId) {
        global $database;
        return $database->fetchAll(
            "SELECT * FROM portfolios WHERE prestataire_id = :id ORDER BY created_at DESC",
            ['id' => $userId]
        );
    }
    
    private function getProfileReviews($profileId) {
        $reviewModel = new Review();
        return $reviewModel->getProfileReviews($profileId, 10);
    }
    
    private function canUserReview($profileId) {
        if (!isLoggedIn()) {
            return false;
        }
        
        $reviewModel = new Review();
        return $reviewModel->canUserReview($_SESSION['user_id'], $profileId) && 
               $_SESSION['user_type'] === 'client';
    }
    
    private function incrementProfileViews($profileId) {
        global $database;
        
        try {
            // Vérification si une vue a déjà été comptée aujourd'hui pour cette IP
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $today = date('Y-m-d');
            
            $existingView = $database->fetch(
                "SELECT id FROM logs_activite 
                 WHERE action = 'profile_view' 
                 AND details LIKE :profile_id 
                 AND ip_address = :ip 
                 AND DATE(created_at) = :today",
                [
                    'profile_id' => '%"profile_id":' . $profileId . '%',
                    'ip' => $ip,
                    'today' => $today
                ]
            );
            
            if (!$existingView) {
                $this->logActivity('profile_view', ['profile_id' => $profileId]);
            }
            
        } catch (Exception $e) {
            // Log silencieux, ne pas interrompre l'affichage
            error_log("Erreur increment views: " . $e->getMessage());
        }
    }
    
    private function updateAverageRating($profileId, $typeProfile) {
        global $database;
        
        try {
            // Calcul de la nouvelle moyenne
            $stats = $database->fetch(
                "SELECT AVG(note) as moyenne, COUNT(*) as total
                 FROM avis_notes 
                 WHERE receveur_id = :profile_id",
                ['profile_id' => $profileId]
            );
            
            $moyenne = round($stats['moyenne'], 2);
            $total = $stats['total'];
            
            // Mise à jour selon le type de profil
            if ($typeProfile === 'prestataire') {
                $database->update(
                    'profils_prestataires',
                    ['note_moyenne' => $moyenne, 'nombre_avis' => $total],
                    'utilisateur_id = :id',
                    ['id' => $profileId]
                );
            } elseif ($typeProfile === 'candidat') {
                $database->update(
                    'cvs',
                    ['note_moyenne' => $moyenne, 'nombre_avis' => $total],
                    'utilisateur_id = :id',
                    ['id' => $profileId]
                );
            }
            
        } catch (Exception $e) {
            error_log("Erreur update rating: " . $e->getMessage());
        }
    }
}
?>
