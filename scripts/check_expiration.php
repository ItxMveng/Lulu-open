<?php
/**
 * Script Cron - Vérification des expirations d'abonnement
 * À exécuter quotidiennement via Cron Job ou Planificateur de tâches Windows
 * 
 * Windows: schtasks /create /tn "LULU-CheckExpiration" /tr "php c:\wamp64\www\lulu\scripts\check_expiration.php" /sc daily /st 02:00
 * Linux: 0 2 * * * /usr/bin/php /path/to/lulu/scripts/check_expiration.php
 */

require_once __DIR__ . '/../models/Subscription.php';

$logFile = __DIR__ . '/expiration_log.txt';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("=== Début de la vérification des expirations ===");
    
    $subscription = new Subscription();
    $notifiedCount = $subscription->checkExpirationAndNotify();
    
    logMessage("Utilisateurs notifiés: $notifiedCount");
    logMessage("=== Fin de la vérification ===");
    
} catch (Exception $e) {
    logMessage("ERREUR: " . $e->getMessage());
    exit(1);
}

exit(0);
