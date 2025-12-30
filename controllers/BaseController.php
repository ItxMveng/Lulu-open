<?php
/**
 * Contrôleur de base - LULU-OPEN
 */

class BaseController {
    
    protected function render($view, $data = []) {
        // Extraction des variables pour la vue
        extract($data);
        
        // Récupération du message flash s'il existe
        $flashMessage = getFlashMessage();
        
        // Chemin vers le fichier de vue
        $viewPath = BASE_PATH . '/views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new Exception("Vue non trouvée: $view");
        }
    }
    
    protected function renderView($view, $data = []) {
        extract($data);
        $flashMessage = getFlashMessage();
        
        ob_start();
        $viewPath = BASE_PATH . '/views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new Exception("Vue non trouvée: $view");
        }
        
        return ob_get_clean();
    }
    
    protected function renderLayout($layout, $content, $data = []) {
        $data['content'] = $content;
        $this->render("layouts/$layout", $data);
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function validateRequired($fields, $data) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "Le champ $field est requis";
            }
        }
        
        return $errors;
    }
    
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^[\+]?[1-9][\d]{8,14}$/', $phone);
    }
    
    protected function handleFileUpload($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
        try {
            return uploadFile($file, $directory, $allowedTypes);
        } catch (Exception $e) {
            throw new Exception("Erreur upload: " . $e->getMessage());
        }
    }
    
    protected function paginate($query, $params, $page = 1, $perPage = 20) {
        global $database;
        
        $offset = ($page - 1) * $perPage;
        
        // Requête pour compter le total
        $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $query);
        $total = $database->fetch($countQuery, $params)['total'];
        
        // Requête avec limite
        $query .= " LIMIT $offset, $perPage";
        $results = $database->fetchAll($query, $params);
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    protected function requireAuth() {
        if (!isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Authentification requise'], 401);
            } else {
                flashMessage('Vous devez être connecté pour accéder à cette page', 'warning');
                redirect('login.php');
            }
        }
    }
    
    protected function requireRole($role) {
        $this->requireAuth();
        
        if (!hasRole($role)) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Accès refusé'], 403);
            } else {
                flashMessage('Vous n\'avez pas les droits pour accéder à cette page', 'error');
                redirect('index.php');
            }
        }
    }
    
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    protected function isPost() {
        return $this->getRequestMethod() === 'POST';
    }
    
    protected function isGet() {
        return $this->getRequestMethod() === 'GET';
    }
    
    protected function getInput($key, $default = null) {
        if ($this->isPost()) {
            return sanitize($_POST[$key] ?? $default);
        }
        return sanitize($_GET[$key] ?? $default);
    }
    
    protected function getAllInput() {
        if ($this->isPost()) {
            return sanitize($_POST);
        }
        return sanitize($_GET);
    }
    
    protected function validateCSRF() {
        if ($this->isPost()) {
            $token = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';
            
            if (!hash_equals($sessionToken, $token)) {
                throw new Exception('Token CSRF invalide');
            }
        }
    }
    
    protected function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    protected function logActivity($action, $details = null) {
        global $database;
        
        try {
            $data = [
                'utilisateur_id' => $_SESSION['user_id'] ?? null,
                'action' => $action,
                'details' => $details ? json_encode($details) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $database->insert('logs_activite', $data);
        } catch (Exception $e) {
            error_log("Erreur log activité: " . $e->getMessage());
        }
    }
    
    protected function createNotification($userId, $type, $title, $message, $actionUrl = null) {
        global $database;
        
        try {
            $data = [
                'utilisateur_id' => $userId,
                'type' => $type,
                'titre' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'lu' => false
            ];
            
            return $database->insert('notifications', $data);
        } catch (Exception $e) {
            error_log("Erreur création notification: " . $e->getMessage());
            return false;
        }
    }
    
    protected function getUnreadNotifications($userId) {
        global $database;
        
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE utilisateur_id = ? AND lu = 0 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            
            return $database->fetchAll($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Erreur récupération notifications: " . $e->getMessage());
            return [];
        }
    }
    
    protected function markNotificationAsRead($notificationId, $userId) {
        global $database;
        
        try {
            return $database->update(
                'notifications',
                ['lu' => true],
                'id = ? AND utilisateur_id = ?',
                [$notificationId, $userId]
            );
        } catch (Exception $e) {
            error_log("Erreur marquage notification: " . $e->getMessage());
            return false;
        }
    }
}
?>