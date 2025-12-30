<?php
// LULU-OPEN - Page d'accueil
require_once 'config/config.php';

// Déterminer quelle page afficher
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);
$uri = str_replace('/lulu', '', $uri);
$uri = rtrim($uri, '/');

if (empty($uri)) {
    $uri = '/';
}

try {
    // Router simple
    switch ($uri) {
        case '/':
        case '/home':
        case '/index':
            // Redirection automatique si CLIENT connecté
            if (isset($_SESSION['user_id']) && isset($_SESSION['type_utilisateur']) && $_SESSION['type_utilisateur'] === 'client') {
                header('Location: /lulu/views/client/dashboard.php');
                exit;
            }
            
            // Page d'accueil
            require_once 'controllers/HomeController.php';
            global $database;
            $controller = new HomeController($database);
            $controller->index();
            break;
            
        case '/login':
            if (file_exists('login.php')) {
                require_once 'login.php';
            } else {
                require_once 'controllers/AuthController.php';
                global $database;
                $controller = new AuthController($database);
                $controller->showLogin();
            }
            break;
            
        case '/register':
            if (file_exists('register.php')) {
                require_once 'register.php';
            } else {
                require_once 'controllers/AuthController.php';
                global $database;
                $controller = new AuthController($database);
                $controller->showRegister();
            }
            break;
            
        case '/search':
        case '/services':
        case '/emplois':
            if (file_exists('search.php')) {
                require_once 'search.php';
            } else {
                require_once 'controllers/SearchController.php';
                global $database;
                $controller = new SearchController($database);
                $controller->index();
            }
            break;
            
        case '/messages':
            if (!isLoggedIn()) {
                flashMessage('Vous devez être connecté pour accéder aux messages', 'warning');
                header('Location: /lulu/login');
                exit;
            }
            require_once 'controllers/MessageController.php';
            global $database;
            $controller = new MessageController($database);
            $controller->index();
            break;
            
        case '/logout':
            if (file_exists('logout.php')) {
                require_once 'logout.php';
            } else {
                session_destroy();
                header('Location: /lulu/');
                exit;
            }
            break;
            
        // Pages statiques
        case '/about':
        case '/a-propos':
            require_once 'controllers/PageController.php';
            $controller = new PageController();
            $controller->about();
            break;
            
        case '/contact':
            require_once 'controllers/PageController.php';
            $controller = new PageController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->handleContactForm();
            } else {
                $controller->contact();
            }
            break;
            
        case '/cgu':
        case '/terms':
            require_once 'controllers/PageController.php';
            $controller = new PageController();
            $controller->cgu();
            break;
            
        case '/privacy':
        case '/politique-confidentialite':
            require_once 'controllers/PageController.php';
            $controller = new PageController();
            $controller->privacy();
            break;
            
        case '/legal':
        case '/mentions-legales':
            require_once 'controllers/PageController.php';
            $controller = new PageController();
            $controller->legal();
            break;

        case '/api/favorites':
            header('Content-Type: application/json');
            require_once 'config/config.php';
            require_once 'includes/functions.php';

            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentification requise']);
                exit;
            }

            $action = $_GET['action'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'];

            if ($method === 'POST' && $action === 'add') {
                $data = json_decode(file_get_contents('php://input'), true);
                $cible_id = $data['cible_id'] ?? null;
                $type_cible = $data['type_cible'] ?? null;

                if (!$cible_id || !$type_cible) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Données manquantes']);
                    exit;
                }

                // Vérifier que le profil existe
                global $database;
                $table = $type_cible === 'prestataire' ? 'profils_prestataires' : 'cvs';
                $profil = $database->fetch("SELECT id FROM $table WHERE utilisateur_id = ?", [$cible_id]);

                if (!$profil) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Profil non trouvé']);
                    exit;
                }

                // Vérifier si pas déjà en favoris
                $existing = $database->fetch("SELECT id FROM favoris WHERE utilisateur_id = ? AND cible_id = ?", [
                    $_SESSION['user_id'], $cible_id
                ]);

                if ($existing) {
                    echo json_encode(['status' => 'already_added']);
                    exit;
                }

                // Ajouter aux favoris
                $database->insert('favoris', [
                    'utilisateur_id' => $_SESSION['user_id'],
                    'cible_id' => $cible_id,
                    'type_cible' => $type_cible
                ]);

                echo json_encode(['status' => 'added']);

            } elseif ($method === 'DELETE' && $action === 'remove') {
                $cible_id = $_GET['cible_id'] ?? null;

                if (!$cible_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'cible_id manquant']);
                    exit;
                }

                global $database;
                $database->delete('favoris', 'utilisateur_id = ? AND cible_id = ?', [
                    $_SESSION['user_id'], $cible_id
                ]);

                echo json_encode(['status' => 'removed']);

            } elseif ($method === 'GET' && $action === 'check') {
                $cible_id = $_GET['cible_id'] ?? null;

                if (!$cible_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'cible_id manquant']);
                    exit;
                }

                global $database;
                $favori = $database->fetch("SELECT id FROM favoris WHERE utilisateur_id = ? AND cible_id = ?", [
                    $_SESSION['user_id'], $cible_id
                ]);

                echo json_encode(['is_favorite' => (bool)$favori]);

            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
            }
            exit;
            
        default:
            // Gestion des routes avec paramètres
            if (preg_match('/^\/profile\/(\d+)$/', $uri, $matches)) {
                // Route /profile/{id}
                require_once 'controllers/ProfileController.php';
                global $database;
                $controller = new ProfileController($database);
                $controller->show($matches[1]);
            } else {
                // Page 404 personnalisée
                error_log("URI non reconnue: " . $uri);
                http_response_code(404);
                require_once 'views/errors/404.php';
            }
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur index.php: " . $e->getMessage());
    
    // Page d'erreur simple
    echo "<!DOCTYPE html><html><head><title>Erreur - LULU-OPEN</title></head><body>";
    echo "<h1>Une erreur est survenue</h1>";
    echo "<p>Nous nous excusons pour la gêne occasionnée.</p>";
    echo "<p><a href='/lulu/'>Retour à l'accueil</a></p>";
    echo "</body></html>";
}
?>
