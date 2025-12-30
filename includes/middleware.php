<?php
/**
 * Middleware - Vérifications d'authentification et d'autorisation
 */

// Inclure les fonctions si pas déjà chargées
if (!function_exists('url')) {
    require_once __DIR__ . '/../includes/functions.php';
}

/**
 * Vérifier que l'utilisateur est connecté
 */
function require_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: /lulu/login.php');
        exit;
    }
}

/**
 * Vérifier que l'utilisateur est un CLIENT
 */
function require_client() {
    require_login();
    
    if (!isset($_SESSION['type_utilisateur']) || $_SESSION['type_utilisateur'] !== 'client') {
        header('Location: /lulu/');
        exit;
    }
}

/**
 * Vérifier que l'utilisateur est un PRESTATAIRE
 */
function require_prestataire() {
    require_login();
    
    if (!isset($_SESSION['type_utilisateur']) || $_SESSION['type_utilisateur'] !== 'prestataire') {
        header('Location: /lulu/');
        exit;
    }
}

/**
 * Vérifier que l'utilisateur est un CANDIDAT
 */
function require_candidat() {
    require_login();
    
    if (!isset($_SESSION['type_utilisateur']) || $_SESSION['type_utilisateur'] !== 'candidat') {
        header('Location: /lulu/');
        exit;
    }
}

/**
 * Vérifier que l'utilisateur est un ADMIN
 */
function require_admin() {
    require_login();
    
    if (!isset($_SESSION['type_utilisateur']) || $_SESSION['type_utilisateur'] !== 'admin') {
        header('Location: /lulu/');
        exit;
    }
}
?>
