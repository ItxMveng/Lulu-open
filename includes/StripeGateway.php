<?php
/**
 * Gateway Stripe - LULU-OPEN
 * Gestion complète des paiements automatisés
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/stripe.php';

class StripeGateway {
    
    private static function initStripe() {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);
    }
    
    /**
     * Créer une session de paiement Stripe Checkout
     */
    public static function createCheckoutSession($userId, $plan) {
        global $database;
        
        try {
            self::initStripe();
            
            // Validation du plan
            if (!isValidStripePlan($plan)) {
                throw new Exception("Plan invalide: $plan");
            }
            
            // Récupération utilisateur
            $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }
            
            $planConfig = PLANS_CONFIG[$plan];
            
            // Création de la session Stripe
            $session = \Stripe\Checkout\Session::create([
                'customer_email' => $user['email'],
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $planConfig['stripe_price_id'],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription', // Mode abonnement pour prix récurrents
                'success_url' => STRIPE_SUCCESS_URL,
                'cancel_url' => STRIPE_CANCEL_URL,
                'metadata' => [
                    'user_id' => $userId,
                    'plan' => $plan,
                    'amount' => $planConfig['price'],
                    'period_months' => $planConfig['period_months']
                ],
                'billing_address_collection' => 'required',
                'phone_number_collection' => [
                    'enabled' => true,
                ],
                'custom_text' => [
                    'submit' => [
                        'message' => 'Votre abonnement sera activé automatiquement après paiement.'
                    ]
                ]
            ]);
            
            // Enregistrer la demande d'upgrade en BDD
            $demandeId = $database->query(
                "INSERT INTO demandes_upgrade (utilisateur_id, plan_demande, montant, stripe_session_id, statut, date_demande) 
                 VALUES (?, ?, ?, ?, 'en_attente', NOW())",
                [$userId, $plan, $planConfig['price'], $session->id]
            );
            
            error_log("✅ Stripe Session créée: " . $session->id . " pour user $userId, plan $plan");
            
            return $session->url;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("❌ Erreur Stripe API: " . $e->getMessage());
            throw new Exception("Erreur de paiement: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("❌ Erreur Gateway: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gestionnaire de webhooks Stripe
     */
    public static function handleWebhook($payload, $sigHeader) {
        global $database;
        
        try {
            self::initStripe();
            
            // Vérification de la signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                STRIPE_WEBHOOK_SECRET
            );
            
            error_log("🔔 Webhook Stripe reçu: " . $event->type);
            
            switch ($event->type) {
                case 'checkout.session.completed':
                    return self::handleCheckoutCompleted($event->data->object);
                    
                case 'invoice.payment_succeeded':
                    return self::handlePaymentSucceeded($event->data->object);
                    
                case 'invoice.payment_failed':
                    return self::handlePaymentFailed($event->data->object);
                    
                default:
                    error_log("⚠️ Webhook non géré: " . $event->type);
                    return true;
            }
            
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log("❌ Signature webhook invalide: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("❌ Erreur webhook: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traiter le checkout complété
     */
    private static function handleCheckoutCompleted($session) {
        global $database;
        
        try {
            $userId = $session->metadata->user_id;
            $plan = $session->metadata->plan;
            $amount = $session->metadata->amount;
            
            error_log("💳 Checkout complété: User $userId, Plan $plan, Montant $amount€");
            
            // Mettre à jour la demande d'upgrade
            $database->query(
                "UPDATE demandes_upgrade 
                 SET stripe_payment_intent = ?, statut = 'paye' 
                 WHERE stripe_session_id = ?",
                [$session->subscription ?? $session->id, $session->id]
            );
            
            // Récupérer l'ID de la demande
            $demande = $database->fetch(
                "SELECT id FROM demandes_upgrade WHERE stripe_session_id = ?",
                [$session->id]
            );
            
            if ($demande) {
                // Enregistrer le paiement
                $database->query(
                    "INSERT INTO paiements_stripe 
                     (utilisateur_id, demande_upgrade_id, montant, plan, stripe_session_id, stripe_payment_intent, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'succeeded', NOW())",
                    [$userId, $demande['id'], $amount, $plan, $session->id, $session->subscription ?? $session->id]
                );
                
                // ACTIVATION AUTOMATIQUE DE L'ABONNEMENT
                self::activateSubscriptionAuto($userId, $plan);
                
                // Notification utilisateur
                self::notifyPaymentSuccess($userId, $plan, $amount);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Erreur handleCheckoutCompleted: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activation automatique de l'abonnement
     */
    private static function activateSubscriptionAuto($userId, $plan) {
        global $database;
        
        try {
            $planConfig = PLANS_CONFIG[$plan];
            $months = $planConfig['period_months'];
            
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime("+$months months"));
            
            // Mettre à jour l'utilisateur avec le nouveau statut
            $database->query(
                "UPDATE utilisateurs SET 
                    subscription_status = 'Actif',
                    subscription_start_date = ?,
                    subscription_end_date = ?
                WHERE id = ?",
                [$startDate, $endDate, $userId]
            );
            
            // Mettre à jour la demande d'upgrade
            $database->query(
                "UPDATE demandes_upgrade SET 
                    statut = 'approuve',
                    date_traitement = NOW()
                WHERE utilisateur_id = ? AND plan_demande = ? AND statut = 'paye'",
                [$userId, $plan]
            );
            
            // Notification admin
            self::notifyAdminNewSubscription($userId, $plan, $planConfig['price']);
            
            error_log("✅ Abonnement activé automatiquement: User $userId, Plan $plan jusqu'au $endDate");
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Erreur activation auto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traiter le paiement réussi
     */
    private static function handlePaymentSucceeded($paymentIntent) {
        error_log("✅ Paiement réussi: " . $paymentIntent->id);
        return true;
    }
    
    /**
     * Traiter le paiement échoué
     */
    private static function handlePaymentFailed($paymentIntent) {
        global $database;
        
        try {
            error_log("❌ Paiement échoué: " . $paymentIntent->id);
            
            // Marquer la demande comme échouée
            $database->query(
                "UPDATE demandes_upgrade SET statut = 'refuse', motif_refus = 'Paiement échoué' 
                 WHERE stripe_payment_intent = ?",
                [$paymentIntent->id]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Erreur handlePaymentFailed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notification de succès de paiement
     */
    private static function notifyPaymentSuccess($userId, $plan, $amount) {
        global $database;
        
        try {
            $user = $database->fetch("SELECT prenom, nom, email FROM utilisateurs WHERE id = ?", [$userId]);
            if (!$user) return;
            
            $planConfig = PLANS_CONFIG[$plan];
            $endDate = date('Y-m-d H:i:s', strtotime("+" . $planConfig['period_months'] . " months"));
            
            // Notification interne
            $database->query(
                "INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, date_creation) 
                 VALUES (?, 'systeme', 'Abonnement activé', ?, NOW())",
                [
                    $userId, 
                    "🎉 Votre abonnement " . $planConfig['name'] . " est maintenant actif jusqu'au " . 
                    date('d/m/Y', strtotime($endDate)) . ". Profitez de toutes les fonctionnalités premium !"
                ]
            );
            
            // Email de confirmation
            $subject = "🎉 Votre abonnement LULU-OPEN est activé !";
            $message = "Bonjour {$user['prenom']} {$user['nom']},\n\n";
            $message .= "Félicitations ! Votre paiement de {$amount}€ a été traité avec succès.\n\n";
            $message .= "Votre abonnement {$planConfig['name']} est maintenant actif jusqu'au " . date('d/m/Y', strtotime($endDate)) . ".\n\n";
            $message .= "Vous pouvez maintenant profiter de toutes les fonctionnalités premium :\n";
            foreach (PREMIUM_FEATURES as $feature) {
                $message .= "• $feature\n";
            }
            $message .= "\nCordialement,\nL'équipe LULU-OPEN";
            
            @mail($user['email'], $subject, $message, "From: noreply@lulu-open.com");
            
        } catch (Exception $e) {
            error_log("❌ Erreur notification: " . $e->getMessage());
        }
    }
    
    /**
     * Notification admin nouveau abonnement
     */
    private static function notifyAdminNewSubscription($userId, $plan, $amount) {
        global $database;
        
        try {
            $user = $database->fetch("SELECT prenom, nom, email, type_utilisateur FROM utilisateurs WHERE id = ?", [$userId]);
            if (!$user) return;
            
            $planConfig = PLANS_CONFIG[$plan];
            
            // Notification admin dans la base
            $database->query(
                "INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, date_creation) 
                 VALUES (1, 'paiement', 'Nouveau paiement Stripe', ?, NOW())",
                [
                    "💳 Paiement reçu: {$user['prenom']} {$user['nom']} - Plan {$planConfig['name']} - {$amount}€"
                ]
            );
            
            // Log pour suivi admin
            error_log("📊 ADMIN: Nouveau abonnement - User: {$user['email']}, Plan: $plan, Montant: {$amount}€");
            
        } catch (Exception $e) {
            error_log("❌ Erreur notification admin: " . $e->getMessage());
        }
    }
    
    /**
     * Vérifier le statut d'abonnement
     */
    public static function isSubscribed($userId) {
        global $database;
        
        try {
            $user = $database->fetch(
                "SELECT subscription_status, subscription_end_date FROM utilisateurs WHERE id = ?",
                [$userId]
            );
            
            if (!$user) return false;
            
            return $user['subscription_status'] === 'Actif' && 
                   strtotime($user['subscription_end_date']) > time();
                   
        } catch (Exception $e) {
            error_log("❌ Erreur isSubscribed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les informations d'abonnement
     */
    public static function getSubscriptionInfo($userId) {
        global $database;
        
        try {
            return $database->fetch(
                "SELECT subscription_status, subscription_start_date, 
                        subscription_end_date 
                 FROM utilisateurs WHERE id = ?",
                [$userId]
            );
            
        } catch (Exception $e) {
            error_log("❌ Erreur getSubscriptionInfo: " . $e->getMessage());
            return null;
        }
    }
}
?>