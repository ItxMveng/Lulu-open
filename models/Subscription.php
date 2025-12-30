<?php
/**
 * Modèle gérant les opérations liées aux abonnements
 * 
 * Cette version corrige définitivement l'erreur "ArgumentCountError"
 * 
 * @package LULU-OPEN
 * @subpackage Models
 * @version 1.2.0
 * @since 2023-11-19
 */
class Subscription {
    private $db;
    
    /**
     * Constructeur de la classe Subscription
     * 
     * @param PDO|object $db Instance de la base de données (optionnel)
     * @throws InvalidArgumentException Si $db n'est pas fourni
     */
    public function __construct($db = null) {
        global $database;
        $this->db = $db ?? $database;
        
        if ($this->db === null) {
            throw new InvalidArgumentException("Erreur fatale: L'objet de base de données est requis pour instancier Subscription");
        }
    }
    
    /**
     * Récupère les tarifs pour un rôle spécifique
     * 
     * @param string $role Rôle de l'utilisateur
     * @return array Tableau des tarifs
     */
    public function getPricingsForRole($role) {
        try {
            $query = "SELECT * FROM pricings WHERE role = ?";
            $result = $this->db->query($query, array($role));
            
            // Extraction sécurisée des résultats
            if (is_array($result) && !empty($result) && array_key_exists(0, $result)) {
                return $result;
            }
            
            if (is_object($result)) {
                $pricings = array();
                while ($row = $result->fetch_assoc()) {
                    $pricings[] = $row;
                }
                return $pricings;
            }
            
            return array();
        } catch (Exception $e) {
            error_log("Erreur dans getPricingsForRole: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Crée une nouvelle requête d'abonnement
     * 
     * @param array $data Données de la requête
     * @return int|bool ID de la requête créée ou false en cas d'erreur
     */
    public function createRequest($data) {
        try {
            if (empty($data) || !isset($data['user_id'])) {
                error_log("Données invalides ou user_id manquant pour createRequest.");
                return false;
            }

            // Assurer que le statut par défaut est défini s'il n'est pas fourni
            $data['status'] = $data['status'] ?? 'En Attente';

            $columns = array_keys($data);
            $placeholders = array_map(function($key) { return ":$key"; }, $columns);

            $sql = sprintf(
                'INSERT INTO subscription_requests (%s) VALUES (%s)',
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            // Utilisation d'une méthode d'insertion plus directe si elle existe
            // ou exécution de la requête préparée.
            $this->db->query($sql, $data);
            
            // lastInsertId() doit être appelé sur l'objet PDO, pas sur la classe Database
            return $this->db->getConnection()->lastInsertId();

        } catch (Exception $e) {
            error_log("Exception dans createRequest: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }
    

    /**
     * Récupère le statut d'abonnement de l'utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return string Statut de l'abonnement
     */
    public function getUserSubscriptionStatus($userId)
    {
        try {
            // La méthode fetch retourne déjà la première ligne ou null
            $query = "SELECT subscription_status, subscription_end_date FROM utilisateurs WHERE id = ?";
            $result = $this->db->fetch($query, [$userId]);

            if ($result && is_array($result)) {
                // Retourne le tableau complet avec le statut et la date de fin
                return $result;
            }

            // Si aucun résultat, retourne un statut par défaut
            return ['subscription_status' => 'Inactif', 'subscription_end_date' => null];
        } catch (Exception $e) {
            error_log("Erreur dans getUserSubscriptionStatus: " . $e->getMessage());
            // En cas d'erreur, retourne un statut par défaut pour éviter les crashs
            return ['subscription_status' => 'Inactif', 'subscription_end_date' => null];
        }
    }

    /**
     * Récupère les tarifs localisés pour un rôle et une devise
     * 
     * @param string $role Rôle de l'utilisateur
     * @param string $currency Devise de l'utilisateur
     * @return array Tableau des tarifs
     */
    public function getLocalizedPricings($role, $currency) {
        try {
            $query = "SELECT * FROM pricings WHERE role = ? AND currency = ?";
            $result = $this->db->query($query, array($role, $currency));
            
            if (is_array($result)) {
                return $result;
            }
            
            return array();
        } catch (Exception $e) {
            error_log("Erreur dans getLocalizedPricings: " . $e->getMessage());
            // Optionnel: retourner des tarifs par défaut si la requête échoue
            return array();
        }
    }

    /**
     * Récupère toutes les demandes d'abonnement en attente
     *
     * @return array Tableau associatif contenant les demandes en attente
     */
    public function getPendingRequests()
    {
        try {
            // Jointure avec la table utilisateurs et pricings pour récupérer toutes les informations nécessaires
            $sql = "SELECT 
                        sr.*, 
                        u.prenom, 
                        u.nom, 
                        u.email, 
                        u.type_utilisateur,
                        p.duration_months,  -- Ajout de la durée du plan
                        p.price as plan_price, -- Ajout du prix du plan
                        p.currency as plan_currency -- Ajout de la devise du plan
                    FROM subscription_requests sr
                    JOIN utilisateurs u ON sr.user_id = u.id
                    LEFT JOIN pricings p ON sr.pricing_id = p.id -- Jointure avec la table pricings
                    WHERE sr.status = 'En Attente' ORDER BY sr.id DESC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des demandes en attente : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les abonnements avec filtres
     *
     * @param array $filters Filtres à appliquer (status, expiring_soon)
     * @return array Tableau des abonnements
     */
    public function getAllSubscriptions($filters = []) {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "a.statut = :status";
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['expiring_soon'])) {
                $where[] = "a.date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT 
                        a.id, u.prenom, u.nom, u.email, u.type_utilisateur,
                        a.type_abonnement, a.duree_mois, a.prix, u.devise,
                        a.date_debut, a.date_fin, a.statut,
                        DATEDIFF(a.date_fin, CURDATE()) as days_remaining
                    FROM abonnements a
                    JOIN utilisateurs u ON a.utilisateur_id = u.id
                    $whereClause
                    ORDER BY a.date_fin DESC";

            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("Erreur dans getAllSubscriptions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques sur les abonnements
     *
     * @return array Tableau des statistiques
     */
    public function getSubscriptionStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
                        SUM(CASE WHEN statut = 'expire' THEN 1 ELSE 0 END) as expires,
                        SUM(CASE WHEN statut = 'suspendu' THEN 1 ELSE 0 END) as suspendus,
                        SUM(CASE WHEN statut = 'actif' AND date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                        SUM(CASE WHEN statut = 'actif' THEN prix / duree_mois ELSE 0 END) as mrr
                    FROM abonnements";
            return $this->db->fetch($sql);
        } catch (Exception $e) {
            error_log("Erreur dans getSubscriptionStats: " . $e->getMessage());
            return ['total' => 0, 'actifs' => 0, 'expires' => 0, 'suspendus' => 0, 'expiring_soon' => 0, 'mrr' => 0];
        }
    }

    /**
     * Récupère les détails d'une demande d'abonnement spécifique.
     * @param int $requestId
     * @return array|null
     */
    public function getRequestDetails($requestId) {
        $sql = "SELECT
                    sr.*,
                    p.duration_months,
                    p.price,
                    p.currency,
                    COALESCE(p.role, 'Plan Standard') as plan_name
                FROM subscription_requests sr
                LEFT JOIN pricings p ON sr.pricing_id = p.id
                WHERE sr.id = :id";
        return $this->db->fetch($sql, ['id' => $requestId]);
    }

    /**
     * Active un abonnement pour un utilisateur.
     * @param array $requestDetails
     * @return bool
     */
    public function activateSubscription($requestDetails) {
        try {
            $userId = $requestDetails['user_id'];
            $duration = $requestDetails['duration_months'];

            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime("+$duration months"));

            // 1. Mettre à jour la table utilisateurs
            $this->db->update('utilisateurs', [
                'subscription_status' => 'Actif',
                'subscription_start_date' => $startDate,
                'subscription_end_date' => $endDate
            ], 'id = ?', [$userId]);

            // 2. Insérer dans la table abonnements (pour l'historique) - avec gestion d'erreur
            try {
                $this->db->insert('abonnements', [
                    'utilisateur_id' => $userId,
                    'type_abonnement' => 'Standard',
                    'duree_mois' => $duration,
                    'prix' => $requestDetails['price'] ?? 0,
                    'date_debut' => $startDate,
                    'date_fin' => $endDate,
                    'statut' => 'actif'
                ]);
            } catch (Exception $e) {
                // Si la table abonnements n'existe pas ou a des colonnes différentes, continuer
                error_log("Impossible d'insérer dans abonnements: " . $e->getMessage());
            }

            // 3. Mettre à jour le statut de la demande
            $this->db->update('subscription_requests', ['status' => 'Approuvé'], 'id = ?', [$requestDetails['id']]);

            // 4. Envoyer notifications
            $this->notifyUserSubscriptionActivated($userId, $duration, $endDate);

            return true;
        } catch (Exception $e) {
            error_log("Erreur d'activation d'abonnement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejette une demande d'abonnement.
     * @param int $requestId
     * @param string $reason
     * @return bool
     */
    public function rejectRequestStatus($requestId, $reason) {
        try {
            // Vérifier que la demande existe
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                error_log("Demande d'abonnement introuvable: " . $requestId);
                return false;
            }

            // Mettre à jour le statut
            $updateResult = $this->db->update('subscription_requests', ['status' => 'Rejeté', 'admin_notes' => $reason], 'id = ?', [$requestId]);
            if (!$updateResult) {
                error_log("Échec de la mise à jour du statut pour la demande: " . $requestId);
                return false;
            }

            // Notifier l'utilisateur (ne pas bloquer si la notification échoue)
            try {
                $this->notifyUserSubscriptionRejected($request['user_id'], $reason);
            } catch (Exception $e) {
                error_log("Erreur notification rejet (non bloquante): " . $e->getMessage());
            }

            return true;
        } catch (Exception $e) {
            error_log("Erreur rejet abonnement: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Récupère le nombre de demandes d'abonnement en attente
     *
     * @return int Nombre de demandes en attente
     */
    public function getPendingCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM subscription_requests WHERE status = 'En Attente'";
            $result = $this->db->fetch($sql);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Erreur dans getPendingCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notifie l'utilisateur de l'activation de son abonnement
     */
    private function notifyUserSubscriptionActivated($userId, $duration, $endDate) {
        try {
            // Récupérer infos utilisateur
            $user = $this->db->fetch("SELECT prenom, nom, email FROM utilisateurs WHERE id = ?", [$userId]);
            if (!$user) return;

            // Récupérer les détails du plan activé
            $subscription = $this->db->fetch("SELECT p.nom as plan_nom, a.prix FROM abonnements a JOIN plans_abonnement p ON a.plan_id = p.id WHERE a.utilisateur_id = ? AND a.statut = 'actif' ORDER BY a.created_at DESC LIMIT 1", [$userId]);
            $planName = $subscription['plan_nom'] ?? 'Plan Premium';
            $montant = $subscription['prix'] ?? 0;

            // Envoyer email
            $subject = "🎉 Votre abonnement LULU-OPEN est activé !";
            $message = "Bonjour {$user['prenom']} {$user['nom']},\n\n";
            $message .= "Félicitations ! Votre abonnement au plan \"{$planName}\" a été activé avec succès.\n\n";
            $message .= "Détails de votre abonnement :\n";
            $message .= "- Plan : {$planName}\n";
            $message .= "- Montant payé : {$montant}€\n";
            $message .= "- Durée : {$duration} mois\n";
            $message .= "- Date de fin : " . date('d/m/Y', strtotime($endDate)) . "\n\n";
            $message .= "Vous pouvez maintenant profiter de toutes les fonctionnalités premium :\n";
            $message .= "- Profil visible 24/7\n";
            $message .= "- Messagerie illimitée\n";
            $message .= "- Support prioritaire\n";
            $message .= "- Badge vérifié\n\n";
            $message .= "Cordialement,\nL'équipe LULU-OPEN";

            @mail($user['email'], $subject, $message, "From: noreply@lulu-open.com");

            // Envoyer message interne détaillé
            $this->db->insert('messages', [
                'expediteur_id' => 1, // Admin
                'destinataire_id' => $userId,
                'sujet' => "Votre souscription au plan \"{$planName}\" est acceptée",
                'contenu' => "Bonjour {$user['prenom']}, votre demande de souscription au plan \"{$planName}\" a été acceptée et votre paiement de {$montant}€ est validé. Votre abonnement est maintenant actif jusqu'au " . date('d/m/Y', strtotime($endDate)) . ". Merci pour votre confiance !",
                'lu' => 0,
                'date_envoi' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            error_log("Erreur notification activation: " . $e->getMessage());
        }
    }

    /**
     * Notifie l'utilisateur du rejet de sa demande d'abonnement
     */
    private function notifyUserSubscriptionRejected($userId, $reason) {
        try {
            // Récupérer infos utilisateur
            $user = $this->db->fetch("SELECT prenom, nom, email FROM utilisateurs WHERE id = ?", [$userId]);
            if (!$user) {
                error_log("Utilisateur introuvable pour notification rejet: " . $userId);
                return;
            }

            // Récupérer les détails de la demande rejetée
            $request = $this->db->fetch("SELECT COALESCE(p.role, 'Plan Standard') as plan_name FROM subscription_requests sr LEFT JOIN pricings p ON sr.pricing_id = p.id WHERE sr.user_id = ? AND sr.status = 'Rejeté' ORDER BY sr.id DESC LIMIT 1", [$userId]);
            $planName = $request ? $request['plan_name'] : 'Plan demandé';

            // Envoyer email
            $subject = "❌ Demande d'abonnement LULU-OPEN rejetée";
            $message = "Bonjour {$user['prenom']} {$user['nom']},\n\n";
            $message .= "Nous sommes désolés de vous informer que votre demande d'abonnement au plan \"{$planName}\" a été rejetée.\n\n";
            $message .= "Motif du rejet :\n{$reason}\n\n";
            $message .= "Vous pouvez :\n";
            $message .= "- Corriger les informations et soumettre une nouvelle demande\n";
            $message .= "- Nous contacter pour plus de détails\n\n";
            $message .= "Cordialement,\nL'équipe LULU-OPEN";

            @mail($user['email'], $subject, $message, "From: noreply@lulu-open.com");

            // Envoyer message interne avec motif
            $messageData = [
                'expediteur_id' => 1, // Admin
                'destinataire_id' => $userId,
                'sujet' => "Votre demande de souscription a été refusée",
                'contenu' => "Bonjour {$user['prenom']}, votre demande de souscription au plan \"{$planName}\" a été refusée. Motif : {$reason}. Vous pouvez modifier vos informations ou choisir un autre plan, puis refaire une demande.",
                'lu' => 0,
                'date_envoi' => date('Y-m-d H:i:s')
            ];

            $insertResult = $this->db->insert('messages', $messageData);
            if (!$insertResult) {
                error_log("Échec de l'insertion du message de rejet pour l'utilisateur: " . $userId);
            }

        } catch (Exception $e) {
            error_log("Erreur notification rejet: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
        }
    }
}
