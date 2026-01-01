<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

echo "<h1>Test Dashboard Admin</h1>";

try {
    require_once __DIR__ . '/../../models/Admin.php';
    echo "<p>✅ Modèle Admin chargé</p>";
    
    $adminModel = new Admin();
    echo "<p>✅ Instance Admin créée</p>";
    
    $stats = $adminModel->getDashboardStats();
    echo "<p>✅ Stats récupérées</p>";
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    $recentUsers = $adminModel->getRecentUsers(3);
    echo "<p>✅ Utilisateurs récents récupérés: " . count($recentUsers) . "</p>";
    
    $recentPayments = $adminModel->getRecentPayments(3);
    echo "<p>✅ Paiements récents récupérés: " . count($recentPayments) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}
?>