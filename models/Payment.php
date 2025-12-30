<?php
require_once 'BaseModel.php';

class Payment extends BaseModel {
    
    protected $table = 'paiements';
    
    public function create($data) {
        $requiredFields = ['utilisateur_id', 'abonnement_id', 'montant', 'methode_paiement'];
        $this->validateRequired($requiredFields, $data);
        
        $allowedMethods = ['carte', 'paypal', 'virement', 'autre'];
        $this->validateEnum($data['methode_paiement'], $allowedMethods);
        
        $this->validateNumeric($data['montant'], 0.01);
        
        $insertData = [
            'utilisateur_id' => $data['utilisateur_id'],
            'abonnement_id' => $data['abonnement_id'],
            'montant' => $data['montant'],
            'methode_paiement' => $data['methode_paiement'],
            'statut_paiement' => $data['statut_paiement'] ?? 'en_attente',
            'transaction_id' => $data['transaction_id'] ?? null
        ];
        
        return $this->db->insert($this->table, $insertData);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getUserPayments($userId) {
        $sql = "SELECT p.*, a.type_abonnement, a.date_debut, a.date_fin
                FROM {$this->table} p
                JOIN abonnements a ON p.abonnement_id = a.id
                WHERE p.utilisateur_id = :user_id
                ORDER BY p.date_paiement DESC";
        
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }
    
    public function updateStatus($paymentId, $status) {
        $allowedStatuses = ['en_attente', 'valide', 'echec', 'rembourse'];
        $this->validateEnum($status, $allowedStatuses);
        
        return $this->db->update($this->table, 
            ['statut_paiement' => $status], 
            'id = :id', 
            ['id' => $paymentId]
        );
    }
    
    public function getByTransactionId($transactionId) {
        $sql = "SELECT * FROM {$this->table} WHERE transaction_id = :transaction_id";
        return $this->db->fetch($sql, ['transaction_id' => $transactionId]);
    }
    
    public function getStats($startDate = null, $endDate = null) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($startDate) {
            $whereClause .= " AND DATE(date_paiement) >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND DATE(date_paiement) <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_paiements,
                    COUNT(CASE WHEN statut_paiement = 'valide' THEN 1 END) as paiements_valides,
                    COUNT(CASE WHEN statut_paiement = 'echec' THEN 1 END) as paiements_echecs,
                    SUM(CASE WHEN statut_paiement = 'valide' THEN montant ELSE 0 END) as revenus_total,
                    AVG(CASE WHEN statut_paiement = 'valide' THEN montant END) as montant_moyen
                FROM {$this->table} 
                $whereClause";
        
        return $this->db->fetch($sql, $params);
    }
    
    public function getMonthlyRevenue($year = null) {
        $year = $year ?: date('Y');
        
        $sql = "SELECT 
                    MONTH(date_paiement) as mois,
                    SUM(CASE WHEN statut_paiement = 'valide' THEN montant ELSE 0 END) as revenus
                FROM {$this->table}
                WHERE YEAR(date_paiement) = :year
                GROUP BY MONTH(date_paiement)
                ORDER BY mois";
        
        return $this->db->fetchAll($sql, ['year' => $year]);
    }
    
    public function getPaymentsByMethod() {
        $sql = "SELECT 
                    methode_paiement,
                    COUNT(*) as nombre,
                    SUM(CASE WHEN statut_paiement = 'valide' THEN montant ELSE 0 END) as revenus
                FROM {$this->table}
                WHERE statut_paiement = 'valide'
                GROUP BY methode_paiement
                ORDER BY revenus DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    public function getPendingPayments() {
        $sql = "SELECT p.*, u.nom, u.prenom, u.email, a.type_abonnement
                FROM {$this->table} p
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                JOIN abonnements a ON p.abonnement_id = a.id
                WHERE p.statut_paiement = 'en_attente'
                ORDER BY p.date_paiement DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    public function refund($paymentId, $reason = null) {
        $payment = $this->getById($paymentId);
        
        if (!$payment) {
            throw new Exception('Paiement non trouvé');
        }
        
        if ($payment['statut_paiement'] !== 'valide') {
            throw new Exception('Seuls les paiements validés peuvent être remboursés');
        }
        
        // Mise à jour du statut
        $this->updateStatus($paymentId, 'rembourse');
        
        // Log du remboursement
        global $database;
        $database->insert('logs_activite', [
            'utilisateur_id' => $payment['utilisateur_id'],
            'action' => 'payment_refund',
            'details' => json_encode([
                'payment_id' => $paymentId,
                'amount' => $payment['montant'],
                'reason' => $reason
            ])
        ]);
        
        return true;
    }
    
    public function generateInvoice($paymentId) {
        $payment = $this->getById($paymentId);
        
        if (!$payment || $payment['statut_paiement'] !== 'valide') {
            throw new Exception('Paiement non trouvé ou non validé');
        }
        
        // Récupération des détails complets
        $sql = "SELECT p.*, u.nom, u.prenom, u.email, a.type_abonnement, a.date_debut, a.date_fin
                FROM {$this->table} p
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                JOIN abonnements a ON p.abonnement_id = a.id
                WHERE p.id = :id";
        
        $invoiceData = $this->db->fetch($sql, ['id' => $paymentId]);
        
        // Génération du numéro de facture
        $invoiceData['invoice_number'] = 'INV-' . date('Y') . '-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT);
        
        return $invoiceData;
    }
}
?>