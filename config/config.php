<?php
/**
 * Configuration générale - LULU-OPEN
 */

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration générale
define('APP_NAME', 'LULU-OPEN');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/lulu');
define('BASE_URL', '/lulu/');
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Configuration environnement
define('APP_ENV', 'development'); // 'development' ou 'production'
define('DISPLAY_ERRORS', APP_ENV === 'development');

// Configuration de sécurité
define('HASH_ALGO', PASSWORD_DEFAULT);
define('TOKEN_LENGTH', 32);
define('SESSION_LIFETIME', 3600); // 1 heure

// Configuration des uploads
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx']);

// Configuration email
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@lulu-open.com');
define('FROM_NAME', 'LULU-OPEN');

// Configuration paiement
define('SUBSCRIPTION_PRICE_MONTHLY', 29.99);
define('SUBSCRIPTION_PRICE_QUARTERLY', 79.99);
define('SUBSCRIPTION_PRICE_YEARLY', 299.99);

// Chargement de la configuration Stripe
require_once BASE_PATH . '/config/stripe.php';

// Configuration IA / Mistral
define('AI_PROVIDER', 'mistral');
define('AI_API_KEY', 'XYMWAJsj6AbocHzCfQLwrpvjeCjrf38T'); // TODO: Déplacer en variable d'environnement en production LWS
define('AI_API_BASE_URL', 'https://api.mistral.ai/v1/chat/completions');
define('AI_MODEL_NAME', 'mistral-large-latest');
define('AI_DEBUG', APP_ENV === 'development'); // Mode debug IA

// Timezone
date_default_timezone_set('Europe/Paris');

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', DISPLAY_ERRORS ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/config/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Fonctions utilitaires
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generateToken')) {
    function generateToken($length = TOKEN_LENGTH) {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, HASH_ALGO);
    }
}

if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
    }
}

if (!function_exists('requireRole')) {
    function requireRole($role) {
        requireLogin();
        if (!hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Accès refusé');
        }
    }
}

if (!function_exists('url')) {
    function url($path = '', $params = []) {
        $base = BASE_URL;
        $url = $base . ltrim($path, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}

if (!function_exists('is_current_page')) {
    function is_current_page($page) {
        $current = basename($_SERVER['PHP_SELF']);
        return $current === $page;
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $params = []) {
        if (strpos($url, 'http') === 0) {
            header("Location: $url");
        } else {
            header('Location: ' . url($url, $params));
        }
        exit;
    }
}

if (!function_exists('flashMessage')) {
    function flashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
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

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 2, ',', ' ') . ' €';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y') {
        return date($format, strtotime($date));
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Le fichier est trop volumineux');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }
        
        $filename = generateToken() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . $directory . '/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $fullPath = $uploadPath . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception('Erreur lors de la sauvegarde du fichier');
        }
        
        return $directory . '/' . $filename;
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($filePath) {
        $fullPath = UPLOAD_PATH . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = generateToken();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Chargement des composants de sécurité
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/Validator.php';
require_once BASE_PATH . '/includes/ErrorHandler.php';

// Chargement de la base de données
require_once 'db.php';

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>