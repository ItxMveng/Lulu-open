<?php
/**
 * Contrôleur de paiement - LULU-OPEN
 * Gestion des abonnements et paiements Stripe
 */

require_once __DIR__ . '/../includes/StripeGateway.php';
require_once __DIR__ . '/../config/stripe.php';

class PaymentController {
    
    /**
     * Afficher le dashboard des paiements
     */
    public static function dashboard() {
        global $database;
        
        if (!isLoggedIn()) {
            header('Location: ../login.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);
        
        // Vérifier si l'utilisateur a un abonnement actif
        $isActive = StripeGateway::isSubscribed($userId);
        $subscriptionInfo = StripeGateway::getSubscriptionInfo($userId);
        
        // Récupérer la dernière demande
        $demande = $database->fetch(
            "SELECT * FROM demandes_upgrade WHERE utilisateur_id = ? ORDER BY date_demande DESC LIMIT 1",
            [$userId]
        );
        
        // Récupérer l'historique des paiements
        $paiements = $database->fetchAll(
            "SELECT * FROM paiements_stripe WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );
        
        return [
            'user' => $user,
            'isActive' => $isActive,
            'subscriptionInfo' => $subscriptionInfo,
            'demande' => $demande,
            'paiements' => $paiements,
            'plans' => PLANS_CONFIG,
            'freeFeatures' => FREE_FEATURES,
            'premiumFeatures' => PREMIUM_FEATURES
        ];
    }
    
    /**
     * Démarrer le processus de paiement Stripe
     */
    public static function startCheckout($plan) {
        try {
            if (!isLoggedIn()) {
                throw new Exception("Vous devez être connecté");
            }
            
            if (!isValidStripePlan($plan)) {
                throw new Exception("Plan invalide");
            }
            
            $userId = $_SESSION['user_id'];
            
            // Vérifier si l'utilisateur n'a pas déjà un abonnement actif
            if (StripeGateway::isSubscribed($userId)) {
                throw new Exception("Vous avez déjà un abonnement actif");
            }
            
            // Créer la session Stripe
            $checkoutUrl = StripeGateway::createCheckoutSession($userId, $plan);
            
            // Rediriger vers Stripe Checkout
            header('Location: ' . $checkoutUrl);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: payments.php');
            exit;
        }
    }
    
    /**
     * Traiter le retour de Stripe (succès)
     */
    public static function handleSuccess($sessionId) {
        global $database;
        
        error_log("🔍 DEBUG: handleSuccess appelé avec sessionId: $sessionId");
        
        try {
            if (!$sessionId) {
                throw new Exception("Session invalide");
            }
            
            // Récupérer les informations de la session depuis Stripe
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            
            error_log("🔍 DEBUG: Session Stripe récupérée, payment_status: " . $session->payment_status);
            error_log("🔍 DEBUG: Metadata: " . json_encode($session->metadata));
            
            if ($session->payment_status === 'paid') {
                $userId = $session->metadata->user_id;
                $plan = $session->metadata->plan;
                $amount = $session->metadata->amount;
                
                error_log("🔍 DEBUG: Activation pour userId: $userId, plan: $plan, amount: $amount");
                
                // Vérifier que l'utilisateur existe
                $user = $database->fetch("SELECT id, email, subscription_status FROM utilisateurs WHERE id = ?", [$userId]);
                if (!$user) {
                    throw new Exception("Utilisateur introuvable: $userId");
                }
                
                error_log("🔍 DEBUG: Utilisateur trouvé: {$user['email']}, status actuel: {$user['subscription_status']}");
                
                // Activer l'abonnement immédiatement
                $planConfig = PLANS_CONFIG[$plan];
                $months = $planConfig['period_months'];
                
                $startDate = date('Y-m-d H:i:s');
                $endDate = date('Y-m-d H:i:s', strtotime("+$months months"));
                
                error_log("🔍 DEBUG: Dates calculées - Début: $startDate, Fin: $endDate");
                
                // Mettre à jour l'utilisateur
                $result = $database->query(
                    "UPDATE utilisateurs SET 
                        subscription_status = 'Actif',
                        subscription_start_date = ?,
                        subscription_end_date = ?
                    WHERE id = ?",
                    [$startDate, $endDate, $userId]
                );
                
                error_log("🔍 DEBUG: Résultat UPDATE: " . ($result ? 'SUCCESS' : 'FAILED'));
                
                // Vérifier la mise à jour
                $updatedUser = $database->fetch("SELECT subscription_status, subscription_start_date, subscription_end_date FROM utilisateurs WHERE id = ?", [$userId]);
                error_log("🔍 DEBUG: Utilisateur après update: " . json_encode($updatedUser));
                
                // Mettre à jour la demande
                $database->query(
                    "UPDATE demandes_upgrade SET statut = 'approuve', date_traitement = NOW() 
                     WHERE stripe_session_id = ?",
                    [$sessionId]
                );
                
                // Enregistrer le paiement
                $database->query(
                    "INSERT INTO paiements_stripe 
                     (utilisateur_id, montant, plan, stripe_session_id, status, created_at) 
                     VALUES (?, ?, ?, ?, 'succeeded', NOW())",
                    [$userId, $amount, $plan, $sessionId]
                );
                
                error_log("✅ DEBUG: Paiement enregistré avec succès");
                
                return [
                    'success' => true,
                    'plan' => $planConfig,
                    'message' => 'Paiement réussi ! Votre abonnement est maintenant actif.',
                    'debug' => [
                        'userId' => $userId,
                        'plan' => $plan,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'updatedUser' => $updatedUser
                    ]
                ];
            }
            
            throw new Exception("Paiement non confirmé");
            
        } catch (Exception $e) {
            error_log("❌ DEBUG: Erreur dans handleSuccess: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Traiter l'annulation de paiement
     */
    public static function handleCancel() {
        return [
            'success' => false,
            'message' => 'Paiement annulé. Vous pouvez réessayer à tout moment.'
        ];
    }
    
    /**
     * Vérifier le statut d'abonnement (API)
     */
    public static function checkSubscriptionStatus() {
        header('Content-Type: application/json');
        
        try {
            if (!isLoggedIn()) {
                throw new Exception("Non authentifié");
            }
            
            $userId = $_SESSION['user_id'];
            $isActive = StripeGateway::isSubscribed($userId);
            $info = StripeGateway::getSubscriptionInfo($userId);
            
            echo json_encode([
                'success' => true,
                'isActive' => $isActive,
                'subscription' => $info
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Annuler un abonnement
     */
    public static function cancelSubscription() {
        global $database;
        
        try {
            if (!isLoggedIn()) {
                throw new Exception("Non authentifié");
            }
            
            $userId = $_SESSION['user_id'];
            
            // Désactiver l'abonnement
            $database->query(
                "UPDATE utilisateurs SET 
                    subscription_status = 'Inactif',
                    subscription_end_date = NOW()
                WHERE id = ?",
                [$userId]
            );
            
            // Notification
            $database->query(
                "INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, date_creation) 
                 VALUES (?, 'systeme', 'Abonnement annulé', 'Votre abonnement a été annulé avec succès.', NOW())",
                [$userId]
            );
            
            $_SESSION['flash_success'] = 'Abonnement annulé avec succès';
            header('Location: payments.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: payments.php');
            exit;
        }
    }
    
    /**
     * Obtenir les statistiques pour l'admin
     */
    public static function getAdminStats() {
        global $database;
        
        try {
            // Revenus du mois
            $revenusMois = $database->fetch(
                "SELECT COALESCE(SUM(montant), 0) as total 
                 FROM paiements_stripe 
                 WHERE status = 'succeeded' AND MONTH(created_at) = MONTH(NOW())"
            )['total'];
            
            // Nouveaux abonnements du mois
            $nouveauxAbo = $database->fetch(
                "SELECT COUNT(*) as total 
                 FROM utilisateurs 
                 WHERE subscription_status = 'Actif' AND MONTH(subscription_start_date) = MONTH(NOW())"
            )['total'];
            
            // Abonnements actifs
            $aboActifs = $database->fetch(
                "SELECT COUNT(*) as total 
                 FROM utilisateurs 
                 WHERE subscription_status = 'Actif' AND subscription_end_date > NOW()"
            )['total'];
            
            // Taux de conversion
            $totalDemandes = $database->fetch(
                "SELECT COUNT(*) as total FROM demandes_upgrade WHERE MONTH(date_demande) = MONTH(NOW())"
            )['total'];
            
            $tauxConversion = $totalDemandes > 0 ? round(($nouveauxAbo / $totalDemandes) * 100, 1) : 0;
            
            return [
                'revenus_mois' => $revenusMois,
                'nouveaux_abo' => $nouveauxAbo,
                'abo_actifs' => $aboActifs,
                'taux_conversion' => $tauxConversion
            ];
            
        } catch (Exception $e) {
            error_log("Erreur getAdminStats: " . $e->getMessage());
            return [
                'revenus_mois' => 0,
                'nouveaux_abo' => 0,
                'abo_actifs' => 0,
                'taux_conversion' => 0
            ];
        }
    }
}
?>