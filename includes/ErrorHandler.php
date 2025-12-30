<?php
/**
 * Gestionnaire d'erreurs centralisé - LULU-OPEN
 * Sépare les erreurs techniques (logs) des messages utilisateur
 */

class ErrorHandler {
    
    private static $logFile = null;
    
    /**
     * Initialise le gestionnaire d'erreurs
     */
    public static function init() {
        self::$logFile = BASE_PATH . '/logs/errors.log';
        
        // Créer dossier logs si nécessaire
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Configurer selon environnement
        if (defined('APP_ENV') && APP_ENV === 'production') {
            ini_set('display_errors', 0);
            error_reporting(E_ALL);
        }
    }
    
    /**
     * Log une erreur dans le fichier de logs
     * 
     * @param string $message Message d'erreur
     * @param string $level Niveau (ERROR, WARNING, INFO)
     * @param array $context Contexte additionnel
     */
    public static function log($message, $level = 'ERROR', $context = []) {
        if (!self::$logFile) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $user = $_SESSION['user_id'] ?? 'guest';
        
        $logMessage = sprintf(
            "[%s] [%s] [IP:%s] [User:%s] [URI:%s] %s\n",
            $timestamp,
            $level,
            $ip,
            $user,
            $uri,
            $message
        );
        
        // Ajouter contexte si présent
        if (!empty($context)) {
            $logMessage .= "Context: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        error_log($logMessage, 3, self::$logFile);
    }
    
    /**
     * Affiche une erreur user-friendly
     * 
     * @param string $userMessage Message pour l'utilisateur
     * @param string $technicalMessage Message technique (loggé uniquement)
     * @param int $httpCode Code HTTP (défaut: 500)
     */
    public static function display($userMessage, $technicalMessage = '', $httpCode = 500) {
        // Logger l'erreur technique
        if ($technicalMessage) {
            self::log($technicalMessage, 'ERROR');
        }
        
        // Définir code HTTP
        http_response_code($httpCode);
        
        // En développement, afficher détails
        if (defined('APP_ENV') && APP_ENV === 'development' && $technicalMessage) {
            $userMessage .= '<br><small class="text-muted">Détails techniques : ' . htmlspecialchars($technicalMessage) . '</small>';
        }
        
        // Afficher message utilisateur
        echo self::renderErrorPage($userMessage, $httpCode);
        exit;
    }
    
    /**
     * Génère une page d'erreur HTML
     */
    private static function renderErrorPage($message, $code) {
        $title = match($code) {
            404 => 'Page non trouvée',
            403 => 'Accès refusé',
            500 => 'Erreur serveur',
            default => 'Erreur'
        };
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #000033, #0099FF); min-height: 100vh; display: flex; align-items: center; }
        .error-container { background: white; border-radius: 15px; padding: 3rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .error-code { font-size: 6rem; font-weight: 700; color: #0099FF; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="error-container text-center">
                    <div class="error-code">$code</div>
                    <h2 class="mb-3">$title</h2>
                    <p class="text-muted mb-4">$message</p>
                    <a href="/lulu/" class="btn btn-primary">
                        <i class="bi bi-house"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Gère les exceptions non capturées
     */
    public static function handleException($exception) {
        self::log(
            $exception->getMessage(),
            'EXCEPTION',
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
        
        self::display(
            'Une erreur inattendue est survenue. Nos équipes ont été notifiées.',
            $exception->getMessage()
        );
    }
    
    /**
     * Gère les erreurs PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $message = "$errstr in $errfile on line $errline";
        self::log($message, 'PHP_ERROR');
        
        // Ne pas afficher les erreurs mineures en production
        if (defined('APP_ENV') && APP_ENV === 'production') {
            return true;
        }
        
        return false; // Laisser PHP gérer
    }
}

// Initialiser le gestionnaire
ErrorHandler::init();

// Enregistrer les handlers
set_exception_handler([ErrorHandler::class, 'handleException']);
set_error_handler([ErrorHandler::class, 'handleError']);
?>
