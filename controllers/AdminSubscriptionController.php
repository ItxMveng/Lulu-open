<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Subscription.php';

class AdminSubscriptionController {
    private $db;
    private $subscriptionModel;

    /**
     * Constructeur qui initialise la base de données et le modèle.
     */
    public function __construct() {
        // S'assure que la variable globale $database est disponible
        global $database;
        if ($database === null) {
            // Si elle n'est pas disponible, on l'initialise.
            $database = Database::getInstance();
        }
        $this->db = $database;

        // Instanciation CORRECTE du modèle avec la connexion à la DB
        $this->subscriptionModel = new Subscription($this->db);
    }

    /**
     * Gère la logique de l'action demandée (vérifier, rejeter, etc.).
     */
    public function handleRequest() {
        try {
            $this->requireAdmin();

            $action = $_GET['action'] ?? null;

            switch ($action) {
                case 'verifyPayment':
                    $this->verifyPayment();
                    break;
                case 'rejectPayment':
                    $this->rejectPayment();
                    break;
                default:
                    throw new Exception("Action non valide.");
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            redirect('/lulu/views/admin/subscriptions-unified.php');
        }
    }

    /**
     * Vérifie et active un abonnement.
     */
    private function verifyPayment() {
        $requestId = $_POST['request_id'] ?? null;
        if (!$requestId) {
            throw new Exception("ID de la demande manquant.");
        }

        // Récupérer les détails de la demande
        $requestDetails = $this->subscriptionModel->getRequestDetails($requestId);
        if (!$requestDetails) {
            throw new Exception("Demande d'abonnement introuvable.");
        }

        // Activer l'abonnement
        $success = $this->subscriptionModel->activateSubscription($requestDetails);

        if ($success) {
            $_SESSION['success_message'] = "L'abonnement pour la demande #{$requestId} a été activé avec succès.";
        } else {
            $_SESSION['error_message'] = "Erreur technique lors de l'activation de l'abonnement #{$requestId}.";
        }
        redirect('/lulu/views/admin/subscriptions-unified.php');
    }

    /**
     * Rejette une demande d'abonnement.
     */
    private function rejectPayment() {
        $requestId = $_POST['request_id'] ?? null;
        $reason = $_POST['admin_notes'] ?? 'Raison non spécifiée.';

        if (!$requestId) {
            throw new Exception("ID de la demande manquant.");
        }

        // Rejeter la demande dans la base de données
        $success = $this->subscriptionModel->rejectRequestStatus($requestId, $reason);

        if ($success) {
            $_SESSION['success_message'] = "La demande #{$requestId} a été rejetée.";
        } else {
            $_SESSION['error_message'] = "Erreur technique lors du rejet de la demande #{$requestId}.";
        }
        redirect('/lulu/views/admin/subscriptions-unified.php');
    }

    /**
     * Vérifie si l'utilisateur est un administrateur.
     */
    private function requireAdmin() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            exit('Accès refusé. Vous devez être administrateur.');
        }
    }
}

// Point d'entrée du script
try {
    $controller = new AdminSubscriptionController();
    $controller->handleRequest();
} catch (Exception $e) {
    // Gestion des erreurs d'initialisation
    $_SESSION['error_message'] = "Erreur fatale du contrôleur : " . $e->getMessage();
    redirect('/lulu/views/admin/subscriptions-unified.php');
}