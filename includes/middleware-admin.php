<?php
/**
 * Middleware Admin - Protection accès pages admin
 */

function require_admin() {
    // Vérifier session existe
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
        header('Location: /lulu/login.php');
        exit;
    }
    
    // Vérifier type utilisateur est admin
    if (!isset($_SESSION['type_utilisateur']) || $_SESSION['type_utilisateur'] !== 'admin') {
        $_SESSION['error'] = "Accès non autorisé. Cette section est réservée aux administrateurs.";
        header('Location: /lulu/views/' . $_SESSION['type_utilisateur'] . '/dashboard.php');
        exit;
    }
}

function log_admin_action($action, $cible_type = null, $cible_id = null, $details = []) {
    if (!isset($_SESSION['user_id'])) return false;
    
    require_once __DIR__ . '/../models/Admin.php';
    $adminModel = new Admin();
    return $adminModel->logAction($_SESSION['user_id'], $action, $cible_type, $cible_id, $details);
}
?>
