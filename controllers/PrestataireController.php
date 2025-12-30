<?php
require_once 'BaseController.php';
require_once 'models/Profile.php';
require_once 'models/Subscription.php';
require_once 'models/Message.php';

class PrestataireController extends BaseController {
    
    public function __construct() {
        $this->requireAuth();
        
        if (!in_array($_SESSION['user_type'], ['prestataire', 'candidat'])) {
            flashMessage('Accès refusé', 'error');
            redirect('index.php');
        }
    }
    
    public function dashboard() {
        try {
            $profileModel = new Profile();
            $subscriptionModel = new Subscription();
            $messageModel = new Message();
            
            // Récupération du profil
            if ($_SESSION['user_type'] === 'prestataire') {
                $profile = $profileModel->getPrestataire($_SESSION['user_id']);
            } else {
                $profile = $profileModel->getCandidat($_SESSION['user_id']);
            }
            
            // Statistiques du profil
            $stats = $profileModel->getProfileStats($_SESSION['user_id']);
            
            // Statut de l'abonnement
            $subscription = $subscriptionModel->getActiveSubscription($_SESSION['user_id']);
            $daysRemaining = $subscription ? $subscriptionModel->getDaysRemaining($_SESSION['user_id']) : 0;
            
            // Messages non lus
            $unreadMessages = $messageModel->getUnreadCount($_SESSION['user_id']);
            
            $data = [
                'title' => 'Dashboard - ' . APP_NAME,
                'profile' => $profile,
                'stats' => $stats,
                'subscription' => $subscription,
                'days_remaining' => $daysRemaining,
                'unread_messages' => $unreadMessages
            ];
            
            $this->render('prestataire/dashboard', $data);
            
        } catch (Exception $e) {
            error_log("Erreur PrestataireController::dashboard: " . $e->getMessage());
            flashMessage('Erreur lors du chargement du dashboard', 'error');
            redirect('index.php');
        }
    }
    
    public function editProfile() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $data = $this->getAllInput();
                $profileModel = new Profile();
                
                if ($_SESSION['user_type'] === 'prestataire') {
                    $profileModel->updatePrestataire($_SESSION['user_id'], $data);
                } else {
                    $profileModel->updateCandidat($_SESSION['user_id'], $data);
                }
                
                $this->logActivity('profile_update', ['fields' => array_keys($data)]);
                
                flashMessage('Profil mis à jour avec succès !', 'success');
                redirect('prestataire/dashboard.php');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        try {
            $profileModel = new Profile();
            
            if ($_SESSION['user_type'] === 'prestataire') {
                $profile = $profileModel->getPrestataire($_SESSION['user_id']);
            } else {
                $profile = $profileModel->getCandidat($_SESSION['user_id']);
            }
            
            $categoryModel = new Category();
            $categories = $categoryModel->getAll();
            
            $data = [
                'title' => 'Modifier mon profil - ' . APP_NAME,
                'profile' => $profile,
                'categories' => $categories,
                'csrf_token' => $this->generateCSRF()
            ];
            
            $this->render('prestataire/profile/edit', $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement du profil', 'error');
            redirect('prestataire/dashboard.php');
        }
    }
    
    public function getStats() {
        try {
            $profileModel = new Profile();
            $subscriptionModel = new Subscription();
            $messageModel = new Message();
            
            $stats = $profileModel->getProfileStats($_SESSION['user_id']);
            $subscription = $subscriptionModel->getActiveSubscription($_SESSION['user_id']);
            $messageStats = $messageModel->getMessageStats($_SESSION['user_id']);
            
            $data = [
                'profile_views' => $stats['profile_views'] ?? 0,
                'messages_count' => $messageStats['received_messages'] ?? 0,
                'rating_average' => 4.5, // À calculer depuis les avis
                'rating_count' => $stats['total_reviews'] ?? 0,
                'profile_completion' => $this->calculateProfileCompletion(),
                'subscription' => [
                    'active' => $subscription !== false,
                    'days_remaining' => $subscription ? $subscriptionModel->getDaysRemaining($_SESSION['user_id']) : 0
                ]
            ];
            
            $this->json(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function calculateProfileCompletion() {
        // Logique simple de calcul du pourcentage de completion du profil
        $profileModel = new Profile();
        
        if ($_SESSION['user_type'] === 'prestataire') {
            $profile = $profileModel->getPrestataire($_SESSION['user_id']);
            
            $fields = [
                'titre_professionnel', 'description_services', 'tarif_horaire',
                'experience_annees', 'photo_profil'
            ];
        } else {
            $profile = $profileModel->getCandidat($_SESSION['user_id']);
            
            $fields = [
                'titre_poste_recherche', 'competences', 'formations',
                'experiences_professionnelles', 'photo_profil'
            ];
        }
        
        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($profile[$field])) {
                $completed++;
            }
        }
        
        return round(($completed / count($fields)) * 100);
    }
}
?>