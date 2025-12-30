<?php
session_start();
require_once '../config/config.php';
requireRole('admin');

require_once '../models/Subscription.php';

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error_message'] = 'ID d\'abonnement manquant';
    header('Location: ../views/admin/subscriptions-unified.php');
    exit;
}

$subscriptionModel = new Subscription();

try {
    switch ($action) {
        case 'suspend':
            $reason = $_GET['reason'] ?? 'Suspension manuelle par administrateur';
            $success = $subscriptionModel->suspendSubscription($id, $reason);
            if ($success) {
                $_SESSION['success_message'] = 'Abonnement suspendu avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suspension';
            }
            break;

        case 'reactivate':
            $notes = $_GET['notes'] ?? 'Réactivation manuelle par administrateur';
            $success = $subscriptionModel->reactivateSubscription($id, $notes);
            if ($success) {
                $_SESSION['success_message'] = 'Abonnement réactivé avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la réactivation (abonnement peut-être expiré)';
            }
            break;

        case 'extend':
            $months = intval($_GET['months'] ?? 0);
            if ($months <= 0 || $months > 24) {
                $_SESSION['error_message'] = 'Durée invalide (1-24 mois)';
                break;
            }
            $notes = "Prolongation de {$months} mois par administrateur";
            $success = $subscriptionModel->extendSubscription($id, $months, $notes);
            if ($success) {
                $_SESSION['success_message'] = "Abonnement prolongé de {$months} mois avec succès";
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la prolongation';
            }
            break;

        default:
            $_SESSION['error_message'] = 'Action non reconnue';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Erreur: ' . $e->getMessage();
}

header('Location: ../views/admin/subscriptions-unified.php?tab=' . ($_GET['tab'] ?? 'active'));
exit;
