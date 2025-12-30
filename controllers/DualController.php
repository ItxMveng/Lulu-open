<?php
require_once 'BaseController.php';
require_once 'models/Profile.php';
require_once 'models/Message.php';
require_once 'models/Subscription.php';

class DualController extends BaseController {
    
    public function __construct($database) {
        parent::__construct();
        $this->db = $database;
        $this->requireAuth();
        $this->requireRole('prestataire_candidat');
    }
    
    public function dashboard() {
        try {
            $profileModel = new Profile($this->db);
            $messageModel = new Message($this->db);
            $subscriptionModel = new Subscription($this->db);
            
            // Profils prestataire et candidat
            $prestataireProfile = $profileModel->getPrestataire($_SESSION['user_id']);
            $candidatProfile = $profileModel->getCandidat($_SESSION['user_id']);
            
            // Statistiques combinées
            $stats = [
                'prestataire' => [
                    'profile_views' => $this->getProfileViews('prestataire'),
                    'messages' => $messageModel->getUnreadCount($_SESSION['user_id']),
                    'rating' => $prestataireProfile['note_moyenne'] ?? 0,
                    'clients' => $this->getClientsCount()
                ],
                'candidat' => [
                    'candidatures' => $this->getCandidaturesCount(),
                    'entretiens' => $this->getEntretiensCount(),
                    'profile_views' => $this->getProfileViews('candidat'),
                    'rating' => $candidatProfile['note_moyenne'] ?? 0
                ]
            ];
            
            // Statut abonnement
            $subscription = $subscriptionModel->getActiveSubscription($_SESSION['user_id']);
            
            // Mode actuel (par défaut prestataire)
            $currentMode = $_SESSION['current_mode'] ?? 'prestataire';
            
            $data = [
                'title' => 'Dashboard Dual - LULU-OPEN',
                'prestataireProfile' => $prestataireProfile,
                'candidatProfile' => $candidatProfile,
                'stats' => $stats,
                'subscription' => $subscription,
                'currentMode' => $currentMode,
                'showSidebar' => true,
                'sidebarType' => 'dual',
                'bodyClass' => 'dual-dashboard'
            ];
            
            $content = $this->renderView('dual/dashboard', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            error_log("Erreur DualController::dashboard: " . $e->getMessage());
            flashMessage('Erreur lors du chargement du dashboard', 'error');
            redirect('/');
        }
    }
    
    public function switchProfile($type) {
        if (!in_array($type, ['prestataire', 'candidat'])) {
            flashMessage('Type de profil invalide', 'error');
            redirect('/dual/dashboard');
        }
        
        $_SESSION['current_mode'] = $type;
        
        $this->logActivity('profile_switch', ['new_mode' => $type]);
        
        flashMessage("Basculé en mode $type", 'success');
        
        // Rediriger vers le dashboard approprié
        if ($type === 'prestataire') {
            redirect('/prestataire/dashboard');
        } else {
            redirect('/candidat/dashboard');
        }
    }
    
    public function analytics() {
        try {
            $analytics = [
                'prestataire' => $this->getPrestataireAnalytics(),
                'candidat' => $this->getCandidatAnalytics(),
                'combined' => $this->getCombinedAnalytics()
            ];
            
            $data = [
                'title' => 'Analyses - LULU-OPEN',
                'analytics' => $analytics,
                'showSidebar' => true,
                'sidebarType' => 'dual'
            ];
            
            $content = $this->renderView('dual/analytics', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement des analyses', 'error');
            redirect('/dual/dashboard');
        }
    }
    
    public function settings() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $settings = $this->getAllInput();
                $this->updateDualSettings($settings);
                
                flashMessage('Paramètres mis à jour avec succès !', 'success');
                redirect('/dual/settings');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        try {
            $currentSettings = $this->getDualSettings();
            
            $data = [
                'title' => 'Paramètres - LULU-OPEN',
                'settings' => $currentSettings,
                'csrf_token' => $this->generateCSRF(),
                'showSidebar' => true,
                'sidebarType' => 'dual'
            ];
            
            $content = $this->renderView('dual/settings', $data);
            $this->renderLayout('main', $content, $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement des paramètres', 'error');
            redirect('/dual/dashboard');
        }
    }
    
    private function getProfileViews($type) {
        $sql = "SELECT COUNT(*) as count FROM logs_activite 
                WHERE action = 'profile_view' 
                AND details LIKE ? 
                AND details LIKE ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $result = $this->db->fetch($sql, [
            '%"profile_id":' . $_SESSION['user_id'] . '%',
            '%"type":"' . $type . '"%'
        ]);
        
        return $result['count'] ?? 0;
    }
    
    private function getClientsCount() {
        // TODO: Implémenter le comptage des clients
        return 0;
    }
    
    private function getCandidaturesCount() {
        // TODO: Implémenter le comptage des candidatures
        return 0;
    }
    
    private function getEntretiensCount() {
        // TODO: Implémenter le comptage des entretiens
        return 0;
    }
    
    private function getPrestataireAnalytics() {
        return [
            'monthly_views' => $this->getMonthlyViews('prestataire'),
            'revenue_trend' => $this->getRevenueTrend(),
            'client_satisfaction' => $this->getClientSatisfaction()
        ];
    }
    
    private function getCandidatAnalytics() {
        return [
            'monthly_views' => $this->getMonthlyViews('candidat'),
            'application_success_rate' => $this->getApplicationSuccessRate(),
            'interview_conversion' => $this->getInterviewConversion()
        ];
    }
    
    private function getCombinedAnalytics() {
        return [
            'total_revenue' => $this->getTotalRevenue(),
            'total_opportunities' => $this->getTotalOpportunities(),
            'overall_rating' => $this->getOverallRating()
        ];
    }
    
    private function getMonthlyViews($type) {
        // TODO: Implémenter les statistiques mensuelles
        return [];
    }
    
    private function getRevenueTrend() {
        // TODO: Implémenter la tendance des revenus
        return [];
    }
    
    private function getClientSatisfaction() {
        // TODO: Implémenter la satisfaction client
        return 0;
    }
    
    private function getApplicationSuccessRate() {
        // TODO: Implémenter le taux de succès des candidatures
        return 0;
    }
    
    private function getInterviewConversion() {
        // TODO: Implémenter la conversion entretiens
        return 0;
    }
    
    private function getTotalRevenue() {
        // TODO: Implémenter le revenu total
        return 0;
    }
    
    private function getTotalOpportunities() {
        // TODO: Implémenter le total des opportunités
        return 0;
    }
    
    private function getOverallRating() {
        // TODO: Implémenter la note globale
        return 0;
    }
    
    private function getDualSettings() {
        // TODO: Implémenter la récupération des paramètres
        return [];
    }
    
    private function updateDualSettings($settings) {
        // TODO: Implémenter la mise à jour des paramètres
    }
}
?>