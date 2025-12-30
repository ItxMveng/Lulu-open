<?php
// Désactiver l'affichage des erreurs pour éviter les sorties HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Debug initial
error_log("=== API admin-subscriptions appelée ===");

// Fonction pour retourner JSON et quitter
function sendJsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

try {
    require_once '../config/config.php';
    require_once '../includes/middleware-admin.php';
    require_admin();
    require_once '../models/Subscription.php';

    header('Content-Type: application/json');

    // Debug input
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);

    $input = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        sendJsonResponse(false, 'Données JSON invalides');
    }

    if (!isset($input['action'])) {
        error_log("Action manquante dans input");
        sendJsonResponse(false, 'Action manquante');
    }

    $action = $input['action'];
    error_log("Action: " . $action);

    $db = Database::getInstance();
    $subscription = new Subscription($db);

    $db->beginTransaction();

    switch ($action) {
        case 'approuver':
            if (empty($input['id'])) {
                throw new Exception("ID abonnement manquant");
            }
            $requestId = (int)$input['id'];
            error_log("Tentative d'activation pour ID: " . $requestId);

            $requestDetails = $subscription->getRequestDetails($requestId);
            error_log("Détails de la demande: " . json_encode($requestDetails));
            if (!$requestDetails) {
                throw new Exception("Demande d'abonnement introuvable");
            }

            if ($subscription->activateSubscription($requestDetails)) {
                $db->commit();
                error_log("Activation réussie");
                sendJsonResponse(true, 'Abonnement activé avec succès');
            } else {
                throw new Exception("Erreur lors de l'activation de l'abonnement");
            }
            break;

        case 'refuser':
            if (empty($input['id']) || empty($input['motif'])) {
                throw new Exception("Paramètres manquants");
            }
            $requestId = (int)$input['id'];
            $motif = trim($input['motif']);
            error_log("Tentative de refus pour ID: " . $requestId . ", motif: " . $motif);

            if ($subscription->rejectRequestStatus($requestId, $motif)) {
                $db->commit();
                error_log("Refus réussi");
                sendJsonResponse(true, 'Demande d\'abonnement refusée');
            } else {
                throw new Exception("Erreur lors du refus de la demande");
            }
            break;

        default:
            throw new Exception("Action inconnue");
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erreur API admin-subscriptions: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    sendJsonResponse(false, $e->getMessage());
}
?>
