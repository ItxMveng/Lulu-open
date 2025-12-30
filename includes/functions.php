<?php
/**
 * Fonctions utilitaires - LULU-OPEN
 */

// Gestion des messages flash
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

// Alias pour compatibilité
if (!function_exists('flashMessage')) {
    function flashMessage($message, $type = 'info') {
        setFlashMessage($message, $type);
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

// Génération de token CSRF
if (!function_exists('generateToken')) {
    function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validation CSRF
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Génération du champ CSRF pour les formulaires
if (!function_exists('csrf_field')) {
    function csrf_field() {
        $token = generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

// Vérification de connexion
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// Vérification des rôles
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
    }
}

// Redirection
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// Validation email
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Nettoyage des données
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Détection de la langue du navigateur
if (!function_exists('detectBrowserLanguage')) {
    function detectBrowserLanguage() {
        $lang = 'fr'; // Par défaut français
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $lang = substr($langs[0], 0, 2);
        }
        return in_array($lang, ['fr', 'en']) ? $lang : 'fr';
    }
}

// Détection de la devise
if (!function_exists('detectCurrency')) {
    function detectCurrency($countryCode = null) {
        // Mapping simple pays -> devise
        $currencies = [
            'FR' => 'EUR',
            'BE' => 'EUR', 
            'CH' => 'CHF',
            'CA' => 'CAD',
            'US' => 'USD',
            'GB' => 'GBP'
        ];
        
        return $currencies[$countryCode] ?? 'EUR';
    }
}
?>