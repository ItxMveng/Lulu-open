<?php
/**
 * Système de protection CSRF - LULU-OPEN
 * Protection contre les attaques Cross-Site Request Forgery
 */

// Durée de validité du token CSRF (2 heures)
define('CSRF_TOKEN_LIFETIME', 7200);

/**
 * Génère un nouveau token CSRF cryptographiquement sécurisé
 * 
 * @return string Token CSRF en hexadécimal (64 caractères)
 */
function generate_csrf_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Récupère ou crée le token CSRF de la session
 * Régénère automatiquement si expiré
 * 
 * @return string Token CSRF actuel
 */
function get_csrf_token() {
    // Vérifier si token existe et n'est pas expiré
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        $elapsed = time() - $_SESSION['csrf_token_time'];
        
        // Si token expiré, le régénérer
        if ($elapsed > CSRF_TOKEN_LIFETIME) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
        }
    }
    
    // Créer nouveau token si nécessaire
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_csrf_token();
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Valide un token CSRF avec protection contre timing attacks
 * 
 * @param string $token Token à valider
 * @return bool True si valide, False sinon
 */
function validate_csrf_token($token) {
    // Vérifier que le token existe en session
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Vérifier expiration
    $elapsed = time() - $_SESSION['csrf_token_time'];
    if ($elapsed > CSRF_TOKEN_LIFETIME) {
        return false;
    }
    
    // Comparaison sécurisée contre timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Génère le champ HTML hidden pour les formulaires
 * 
 * @return string HTML du champ input hidden
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Génère le meta tag pour AJAX (optionnel)
 * 
 * @return string HTML du meta tag
 */
function csrf_meta() {
    $token = get_csrf_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Vérifie le token CSRF depuis la requête POST
 * Affiche une erreur et arrête l'exécution si invalide
 * 
 * @param bool $die Arrêter l'exécution si invalide (défaut: true)
 * @return bool True si valide
 */
function verify_csrf_or_die($die = true) {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($token)) {
        if ($die) {
            http_response_code(403);
            die('Erreur de sécurité : Token CSRF invalide ou expiré. Veuillez réessayer.');
        }
        return false;
    }
    
    return true;
}
?>
