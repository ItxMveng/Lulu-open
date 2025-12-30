<?php
require_once '../config/config.php';
requireRole('admin');

header('Content-Type: application/json');

global $database;

try {
    // Stats générales
    $totalUsers = $database->fetch("SELECT COUNT(*) as count FROM utilisateurs")['count'];
    $activePrestataires = $database->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE type_utilisateur = 'prestataire' AND statut = 'actif'")['count'];
    $activeCvs = $database->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE type_utilisateur = 'candidat' AND statut = 'actif'")['count'];
    $activeSubscriptions = $database->fetch("SELECT COUNT(*) as count FROM utilisateurs WHERE subscription_status = 'Actif'")['count'];
    
    // Données pour le graphique des inscriptions (6 derniers mois)
    $registrationsData = $database->fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM utilisateurs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    
    $registrationsChart = [
        'labels' => [],
        'values' => []
    ];
    
    foreach ($registrationsData as $data) {
        $registrationsChart['labels'][] = date('M Y', strtotime($data['month'] . '-01'));
        $registrationsChart['values'][] = (int)$data['count'];
    }
    
    // Répartition des utilisateurs
    $usersDistribution = $database->fetchAll("
        SELECT 
            type_utilisateur,
            COUNT(*) as count
        FROM utilisateurs 
        WHERE type_utilisateur IN ('prestataire', 'candidat', 'client')
        GROUP BY type_utilisateur
    ");
    
    $usersChart = [
        'labels' => [],
        'values' => []
    ];
    
    foreach ($usersDistribution as $data) {
        $usersChart['labels'][] = ucfirst($data['type_utilisateur']);
        $usersChart['values'][] = (int)$data['count'];
    }
    
    echo json_encode([
        'total_users' => $totalUsers,
        'active_prestataires' => $activePrestataires,
        'active_cvs' => $activeCvs,
        'active_subscriptions' => $activeSubscriptions,
        'registrations_chart' => $registrationsChart,
        'users_distribution' => $usersChart
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>