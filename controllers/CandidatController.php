<?php
require_once 'BaseController.php';
require_once 'models/Profile.php';
require_once 'models/Message.php';
require_once 'models/Subscription.php';

class CandidatController extends BaseController {
    
    public function __construct($database) {
        parent::__construct();
        $this->db = $database;
        $this->requireAuth();
        
        if (!in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
            flashMessage('Accès refusé', 'error');
            redirect('/');
        }
    }
    
    public function dashboard() {
        try {
            $profileModel = new Profile($this->db);
            $messageModel = new Message($this->db);
            $subscriptionModel = new Subscription($this->db);
            
            // Profil candidat
            $profile = $profileModel->getCandidat($_SESSION['user_id']);
            
            // Statistiques
            $stats = [
                'candidatures' => $this->getCandidaturesCount(),
                'entretiens' => $this->getEntretiensCount(),
                'messages' => $messageModel->getUnreadCount($_SESSION['user_id']),
                'profile_views' => $this->getProfileViews()
            ];
            
            // Statut abonnement
            $subscription = $subscriptionModel->getActiveSubscription($_SESSION['user_id']);
            
            // Offres recommandées
            $recommendedJobs = $this->getRecommendedJobs();
            
            $data = [
                'title' => 'Dashboard Candidat - LULU-OPEN',
                'profile' => $profile,
                'stats' => $stats,
                'subscription' => $subscription,
                'recommendedJobs' => $recommendedJobs,
                'showSidebar' => true,
                'sidebarType' => 'candidat',
                'bodyClass' => 'candidat-dashboard'
            ];
            
            $content = $this->renderView('candidat/dashboard', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            error_log("Erreur CandidatController::dashboard: " . $e->getMessage());
            flashMessage('Erreur lors du chargement du dashboard', 'error');
            redirect('/');
        }
    }
    
    public function editProfile() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $data = $this->getAllInput();
                $profileModel = new Profile($this->db);
                
                // Gestion de l'upload de CV
                if (isset($_FILES['cv_fichier']) && $_FILES['cv_fichier']['error'] === UPLOAD_ERR_OK) {
                    $cvPath = $this->handleFileUpload($_FILES['cv_fichier'], 'cvs', ALLOWED_DOC_TYPES);
                    $data['cv_fichier'] = $cvPath;
                }
                
                $profileModel->updateCandidat($_SESSION['user_id'], $data);
                
                $this->logActivity('candidat_profile_update', ['fields' => array_keys($data)]);
                
                flashMessage('Profil mis à jour avec succès !', 'success');
                redirect('/candidat/dashboard');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        try {
            $profileModel = new Profile($this->db);
            $profile = $profileModel->getCandidat($_SESSION['user_id']);
            
            $categoryModel = new Category($this->db);
            $categories = $categoryModel->getAll();
            
            $data = [
                'title' => 'Modifier mon CV - LULU-OPEN',
                'profile' => $profile,
                'categories' => $categories,
                'csrf_token' => $this->generateCSRF(),
                'showSidebar' => true,
                'sidebarType' => 'candidat'
            ];
            
            $content = $this->renderView('candidat/profile/edit', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement du profil', 'error');
            redirect('/candidat/dashboard');
        }
    }
    
    public function candidatures() {
        try {
            $candidatures = $this->getCandidatures();
            
            $data = [
                'title' => 'Mes Candidatures - LULU-OPEN',
                'candidatures' => $candidatures,
                'showSidebar' => true,
                'sidebarType' => 'candidat'
            ];
            
            $content = $this->renderView('candidat/candidatures', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement des candidatures', 'error');
            redirect('/candidat/dashboard');
        }
    }
    
    public function searchJobs() {
        try {
            $filters = $this->getAllInput();
            $jobs = $this->searchJobOffers($filters);
            
            $data = [
                'title' => 'Recherche d\'emploi - LULU-OPEN',
                'jobs' => $jobs,
                'filters' => $filters,
                'showSidebar' => true,
                'sidebarType' => 'candidat'
            ];
            
            $content = $this->renderView('candidat/search-jobs', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors de la recherche', 'error');
            redirect('/candidat/dashboard');
        }
    }
    
    private function getCandidaturesCount() {
        // TODO: Implémenter le système de candidatures
        return 0;
    }
    
    private function getEntretiensCount() {
        // TODO: Implémenter le système d'entretiens
        return 0;
    }
    
    private function getProfileViews() {
        $sql = "SELECT COUNT(*) as count FROM logs_activite 
                WHERE action = 'profile_view' 
                AND details LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $result = $this->db->fetch($sql, ['%"profile_id":' . $_SESSION['user_id'] . '%']);
        return $result['count'] ?? 0;
    }
    
    private function getRecommendedJobs() {
        // TODO: Implémenter la recommandation d'emplois basée sur le profil
        return [];
    }
    
    private function getCandidatures() {
        // TODO: Implémenter la récupération des candidatures
        return [];
    }
    
    private function searchJobOffers($filters) {
        // TODO: Implémenter la recherche d'offres d'emploi
        return [];
    }
}
?>