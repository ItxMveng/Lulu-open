<?php
/**
 * Routeur centralisé - LULU-OPEN
 */

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->initializeRoutes();
    }
    
    private function initializeRoutes() {
        // Route d'accueil
        $this->addRoute('GET', '/', 'HomeController@index');
        $this->addRoute('GET', '/home', 'HomeController@index');
        
        // Routes d'authentification
        $this->addRoute('GET', '/login', 'AuthController@showLogin');
        $this->addRoute('POST', '/login', 'AuthController@login');
        $this->addRoute('GET', '/register', 'AuthController@showRegister');
        $this->addRoute('POST', '/register', 'AuthController@register');
        $this->addRoute('GET', '/logout', 'AuthController@logout');
        
        // Routes publiques
        $this->addRoute('GET', '/search', 'SearchController@index');
        $this->addRoute('GET', '/profile/{id}', 'ProfileController@show');
        
        // Routes Admin
        $this->addRoute('GET', '/admin', 'AdminController@dashboard', ['auth', 'admin']);
        $this->addRoute('GET', '/admin/users', 'AdminUserController@index', ['auth', 'admin']);
        
        // Routes Client
        $this->addRoute('GET', '/client/dashboard', 'ClientController@dashboard', ['auth', 'client']);
        
        // Routes Prestataire
        $this->addRoute('GET', '/prestataire/dashboard', 'PrestataireController@dashboard', ['auth', 'prestataire']);
        
        // Routes Candidat
        $this->addRoute('GET', '/candidat/dashboard', 'CandidatController@dashboard', ['auth', 'candidat']);
        
        // Routes Dual (Prestataire+Candidat)
        $this->addRoute('GET', '/dual/dashboard', 'DualController@dashboard', ['auth', 'dual']);
    }
    
    public function addRoute($method, $path, $handler, $middleware = []) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function dispatch($uri, $method = 'GET') {
        // L'URI est déjà nettoyée dans index.php
        // Pas besoin de la nettoyer à nouveau ici
        
        foreach ($this->routes as $route) {
            // Gestion des routes avec paramètres
            if (strpos($route['path'], '{') !== false) {
                $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
                $pattern = '#^' . $pattern . '$#';
                if (preg_match($pattern, $uri, $matches) && $route['method'] === $method) {
                    array_shift($matches); // Enlever le match complet
                    foreach ($route['middleware'] as $middleware) {
                        $this->applyMiddleware($middleware);
                    }
                    return $this->executeHandler($route['handler'], $matches);
                }
            } else {
                // Routes exactes
                if ($route['method'] === $method && $route['path'] === $uri) {
                    foreach ($route['middleware'] as $middleware) {
                        $this->applyMiddleware($middleware);
                    }
                    return $this->executeHandler($route['handler']);
                }
            }
        }
        
        http_response_code(404);
        echo "Page non trouvée";
    }
    
    private function applyMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                if (!isLoggedIn()) {
                    flashMessage('Connexion requise', 'warning');
                    redirect('/login.php');
                }
                break;
            case 'admin':
                if (!isLoggedIn() || !hasRole('admin')) {
                    flashMessage('Accès administrateur requis', 'error');
                    redirect('/login.php');
                }
                break;
            case 'client':
                if (!isLoggedIn() || !hasRole('client')) {
                    flashMessage('Accès client requis', 'error');
                    redirect('/login.php');
                }
                break;
            case 'prestataire':
                if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['prestataire', 'prestataire_candidat'])) {
                    flashMessage('Accès prestataire requis', 'error');
                    redirect('/login.php');
                }
                // Vérifier abonnement actif
                $this->checkActiveSubscription();
                break;
            case 'candidat':
                if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
                    flashMessage('Accès candidat requis', 'error');
                    redirect('/login.php');
                }
                // Vérifier abonnement actif
                $this->checkActiveSubscription();
                break;
            case 'dual':
                if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestataire_candidat') {
                    flashMessage('Profil dual requis', 'error');
                    redirect('/login.php');
                }
                // Vérifier abonnement actif
                $this->checkActiveSubscription();
                break;
        }
    }
    
    private function checkActiveSubscription() {
        if (in_array($_SESSION['user_type'], ['prestataire', 'candidat', 'prestataire_candidat'])) {
            global $database;
            $activeSubscription = $database->fetch(
                "SELECT * FROM abonnements WHERE utilisateur_id = ? AND statut = 'actif' AND date_fin >= CURDATE()",
                [$_SESSION['user_id']]
            );
            
            if (!$activeSubscription) {
                $_SESSION['subscription_required'] = true;
                // Permettre l'accès mais avec limitation
            }
        }
    }
    
    private function executeHandler($handler, $params = []) {
        list($controllerName, $method) = explode('@', $handler);
        
        $controllerFile = BASE_PATH . '/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            throw new Exception("Contrôleur non trouvé: $controllerName");
        }
        
        require_once $controllerFile;
        
        global $database;
        $controller = new $controllerName($database);
        
        if (!method_exists($controller, $method)) {
            throw new Exception("Méthode non trouvée: $method dans $controllerName");
        }
        
        return call_user_func_array([$controller, $method], $params);
    }
}
?>