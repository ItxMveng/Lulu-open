<?php
/**
 * Model Admin - Gestion des actions admin
 */
class Admin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getStatisticsSummary($periode = '30j'): array {
        $pdo = Database::getInstance()->getConnection();
        $dateFilter = $this->getDateFilter($periode);

        $summary = [];
        $summary['users'] = [
            'new' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE date_inscription {$dateFilter}")->fetchColumn(),
            'total' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
            'growth_rate' => $this->calculateGrowthRate('utilisateurs', 'date_inscription', $periode),
        ];
        $summary['revenue'] = [
            'amount' => $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'valide' AND date_paiement {$dateFilter}")->fetchColumn(),
            'prev_amount' => $this->getPreviousPeriodRevenue($periode),
            'growth_rate' => $this->calculateGrowthRate('paiements', 'date_paiement', $periode, 'montant'),
            'avg_per_user' => $summary['revenue']['amount'] / $summary['users']['total'],
        ];
        $summary['subscriptions'] = [
            'new' => $pdo->query("SELECT COUNT(*) FROM abonnements WHERE date_debut {$dateFilter}")->fetchColumn(),
            'churned' => $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'annule' AND date_fin {$dateFilter}")->fetchColumn(),
            'active' => $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'")->fetchColumn(),
            'churn_rate' => $this->calculateChurnRate($periode),
        ];
        $summary['validations'] = [
            'processed' => $pdo->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'valide' AND date_validation {$dateFilter}")->fetchColumn(),
            'pending' => $pdo->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'")->fetchColumn(),
            'avg_time_hours' => $this->calculateAverageValidationTime(),
        ];

        return $summary;
    }

    private function calculateGrowthRate($table, $dateColumn, $periode, $sumColumn = null) {
        $pdo = Database::getInstance()->getConnection();
        $currentPeriodFilter = $this->getDateFilter($periode);
        $previousPeriodFilter = $this->getPreviousPeriodFilter($periode);

        $currentValue = $pdo->query("SELECT COALESCE(SUM({$sumColumn}), COUNT(*)) FROM {$table} WHERE {$dateColumn} {$currentPeriodFilter}")->fetchColumn();
        $previousValue = $pdo->query("SELECT COALESCE(SUM({$sumColumn}), COUNT(*)) FROM {$table} WHERE {$dateColumn} {$previousPeriodFilter}")->fetchColumn();

        return $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0;
    }

    private function getPreviousPeriodFilter($periode) {
        switch ($periode) {
            case 'today':
                return "BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND CURDATE()";
            case '7j':
                return "BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case '30j':
            default:
                return "BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
    }

    private function calculateChurnRate($periode) {
        $pdo = Database::getInstance()->getConnection();
        $dateFilter = $this->getDateFilter($periode);

        $churned = $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'annule' AND date_fin {$dateFilter}")->fetchColumn();
        $active = $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'")->fetchColumn();

        return $active > 0 ? ($churned / $active) * 100 : 0;
    }

    private function calculateAverageValidationTime() {
        $pdo = Database::getInstance()->getConnection();
        $validations = $pdo->query("SELECT date_creation, date_validation FROM demandes_activation WHERE statut = 'valide'")->fetchAll(PDO::FETCH_ASSOC);

        $totalTime = 0;
        foreach ($validations as $validation) {
            $creationTime = strtotime($validation['date_creation']);
            $validationTime = strtotime($validation['date_validation']);
            $totalTime += ($validationTime - $creationTime) / 3600; // Convert to hours
        }

        return count($validations) > 0 ? $totalTime / count($validations) : 0;
    }

    private function getDateFilter($periode) {
        switch ($periode) {
            case 'today':
                return ">= CURDATE()";
            case '7j':
                return ">= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case '30j':
            default:
                return ">= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
    }

    private function getPreviousPeriodRevenue($periode) {
        $pdo = Database::getInstance()->getConnection();
        $previousPeriodFilter = $this->getPreviousPeriodFilter($periode);
        return $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'valide' AND date_paiement {$previousPeriodFilter}")->fetchColumn();
    }

    public function logAction($admin_id, $action, $cible_type = null, $cible_id = null, $details = []) {
        return $this->db->insert('logs_admin', [
            'admin_id' => $admin_id,
            'action' => $action,
            'cible_type' => $cible_type,
            'cible_id' => $cible_id,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public function getLogs($limit = 50, $admin_id = null) {
        $sql = "SELECT l.*, u.nom, u.prenom
                FROM logs_admin l
                JOIN utilisateurs u ON l.admin_id = u.id";
        $params = [];

        if ($admin_id) {
            $sql .= " WHERE l.admin_id = ?";
            $params[] = $admin_id;
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }
    
    public function getStats() {
        $stats = [];
        
        // Total utilisateurs
        $stats['total_users'] = $this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs");
        
        // Utilisateurs par type
        $stats['prestataires'] = $this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'prestataire'");
        $stats['candidats'] = $this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'candidat'");
        $stats['clients'] = $this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'client'");
        
        // Abonnements actifs
        $stats['abonnements_actifs'] = $this->db->fetchColumn("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'");
        
        // Revenus du mois
        $stats['revenus_mois'] = $this->db->fetchColumn(
            "SELECT COALESCE(SUM(montant), 0) FROM paiements 
             WHERE statut = 'valide' AND MONTH(date_paiement) = MONTH(CURRENT_DATE())"
        );
        
        // Demandes en attente
        $stats['demandes_attente'] = $this->db->fetchColumn("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'");
        
        return $stats;
    }
    
    public function getDashboardStats() {
        $pdo = Database::getInstance()->getConnection();
        
        $stats = [];
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
        $stats['new_users_today'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = CURDATE()")->fetchColumn();
        $stats['revenue_month'] = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'valide' AND MONTH(date_paiement) = MONTH(CURDATE())")->fetchColumn();
        $stats['revenue_last_month'] = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'valide' AND MONTH(date_paiement) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
        $stats['revenue_growth'] = $stats['revenue_last_month'] > 0 ? round((($stats['revenue_month'] - $stats['revenue_last_month']) / $stats['revenue_last_month']) * 100, 1) : 0;
        $stats['active_subscriptions'] = $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif'")->fetchColumn();
        $stats['expiring_soon'] = $pdo->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'actif' AND date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['pending_validations'] = $pdo->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'")->fetchColumn();
        $stats['prestataires'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'prestataire'")->fetchColumn();
        $stats['candidats'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'candidat'")->fetchColumn();
        $stats['clients'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'client'")->fetchColumn();
        $stats['prestataire_candidat'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'prestataire_candidat'")->fetchColumn();

        $stats['user_distribution'] = [
            'prestataires' => $stats['prestataires'],
            'candidats' => $stats['candidats'],
            'clients' => $stats['clients'],
            'prestataire_candidat' => $stats['prestataire_candidat'],
        ];

        return $stats;
    }
    
    public function getRecentUsers($limit = 5) {
        return $this->db->fetchAll(
            "SELECT id, prenom, nom, email, type_utilisateur, statut, photo_profil, date_inscription as date_creation
             FROM utilisateurs ORDER BY date_inscription DESC LIMIT ?",
            [$limit]
        );
    }
    
    public function getRecentPayments($limit = 5) {
        return $this->db->fetchAll(
            "SELECT p.*, CONCAT(u.prenom, ' ', u.nom) as user_name
             FROM paiements p
             JOIN utilisateurs u ON p.utilisateur_id = u.id
             ORDER BY p.date_paiement DESC LIMIT ?",
            [$limit]
        );
    }
}
?>
