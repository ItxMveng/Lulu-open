<?php
/**
 * Webhook Stripe - LULU-OPEN
 * Endpoint pour recevoir les notifications de paiement
 */

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Headers de sécurité
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://api.stripe.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Stripe-Signature');

try {
    require_once '../config/config.php';
    require_once '../includes/StripeGateway.php';
    
    // Vérifier que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Récupérer le payload et la signature
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($sigHeader)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing payload or signature']);
        exit;
    }
    
    // Log du webhook reçu
    error_log("🔔 Webhook Stripe reçu - Signature: " . substr($sigHeader, 0, 20) . "...");
    error_log("📦 Payload size: " . strlen($payload) . " bytes");
    
    // Traiter le webhook
    $result = StripeGateway::handleWebhook($payload, $sigHeader);
    
    if ($result) {
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        error_log("✅ Webhook traité avec succès");
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Webhook processing failed']);
        error_log("❌ Échec du traitement webhook");
    }
    
} catch (Exception $e) {
    error_log("❌ Erreur webhook critique: " . $e->getMessage());
    error_log("📍 Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>