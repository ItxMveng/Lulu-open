<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/User.php';
require_once '../models/Subscription.php';
require_once 'BaseController.php';

class AuthController extends BaseController {
    
    public function login() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $email = $this->getInput('email');
                $password = $this->getInput('password');
                
                if (empty($email) || empty($password)) {
                    throw new Exception('Email et mot de passe requis');
                }
                
                global $database;
                $userModel = new User($database);
                $user = $userModel->authenticate($email, $password);
                
                // Vérification du statut d'abonnement pour prestataires/candidats
                if (in_array($user['type_utilisateur'], ['prestataire', 'candidat'])) {
                    $subscriptionModel = new Subscription();
                    $activeSubscription = $subscriptionModel->getActiveSubscription($user['id']);
                    
                    if (!$activeSubscription) {
                        $_SESSION['subscription_required'] = true;
                    }
                }
                
                // Création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['type_utilisateur'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                
                $this->logActivity('login', ['user_id' => $user['id']]);
                
                setFlashMessage('Connexion réussie !', 'success');
                
                // Redirection selon le type d'utilisateur
                $redirectUrl = $this->getRedirectUrl($user['type_utilisateur']);
                redirect($redirectUrl);
                
            } catch (Exception $e) {
                setFlashMessage($e->getMessage(), 'error');
            }
        }
        
        $data = [
            'title' => 'Connexion - ' . APP_NAME,
            'csrf_token' => $this->generateCSRF()
        ];
        
        $this->render('auth/login', $data);
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception('Token CSRF invalide');
                }
                
                $data = $_POST;
                $userModel = new User();
                
                // Validation des mots de passe
                if ($data['password'] !== $data['confirm_password']) {
                    throw new Exception('Les mots de passe ne correspondent pas');
                }
                
                // Création ou récupération de la localisation
                $localisationId = null;
                if (!empty($data['ville']) && !empty($data['region'])) {
                    $localisationId = $this->createOrGetLocation($data);
                }
                
                $userData = [
                    'email' => $data['email'],
                    'mot_de_passe' => $data['password'],
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'telephone' => $data['telephone'] ?? null,
                    'type_utilisateur' => $data['type_utilisateur'],
                    'localisation_id' => $localisationId
                ];
                
                $userId = $userModel->create($userData);
                
                // Si c'est un client, redirection directe
                if ($data['type_utilisateur'] === 'client') {
                    setFlashMessage('Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success');
                    header('Location: ../../login.php');
                    exit;
                } else {
                    // Pour prestataire/candidat, redirection vers étape 3
                    $_SESSION['temp_user_id'] = $userId;
                    header('Location: ../auth/register.php?step=3&type=' . $data['type_utilisateur']);
                    exit;
                }
                
            } catch (Exception $e) {
                setFlashMessage($e->getMessage(), 'error');
                header('Location: ../auth/register.php?step=2&type=' . ($data['type_utilisateur'] ?? 'client'));
                exit;
            }
        }
    }
    
    private function createOrGetLocation($data) {
        $pdo = getConnection();
        
        // Vérifier si la localisation existe déjà
        $stmt = $pdo->prepare("SELECT id FROM localisations WHERE ville = ? AND pays = ?");
        $stmt->execute([$data['ville'], $data['pays']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Créer une nouvelle localisation
        $stmt = $pdo->prepare("INSERT INTO localisations (ville, pays, code_iso) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['ville'],
            $data['pays'],
            $data['code_iso'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    public function logout() {
        $this->logActivity('logout', ['user_id' => $_SESSION['user_id'] ?? null]);
        
        session_destroy();
        flashMessage('Déconnexion réussie', 'info');
        redirect('index.php');
    }
    
    public function resetPassword() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $email = $this->getInput('email');
                if (empty($email)) {
                    throw new Exception('Email requis');
                }
                
                $userModel = new User();
                $token = $userModel->resetPassword($email);
                
                // TODO: Envoyer email avec le token
                
                flashMessage('Un email de réinitialisation a été envoyé', 'success');
                redirect('login.php');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        $data = [
            'title' => 'Réinitialisation - ' . APP_NAME,
            'csrf_token' => $this->generateCSRF()
        ];
        
        $this->render('auth/reset_password', $data);
    }
    
    private function getRedirectUrl($userType) {
        switch ($userType) {
            case 'admin':
                return 'views/admin/dashboard.php';
            case 'prestataire':
            case 'prestataire_candidat':
                return 'views/prestataire/dashboard.php';
            case 'candidat':
                return 'views/candidat/dashboard.php';
            default:
                return 'index.php';
        }
    }
}
?>