<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Subscription.php';

class SubscriptionController {
    private $subscription;
    private $db;
    private $userId;
    private $user;
    private $currency = 'EUR'; // FORÇAGE À EUR - SIMPLIFICATION MAXIMALE
    private $userRole;
    private $roleMap;
    private $hasCreatedAtColumn;

    public function __construct() {
        // Initialisation de la base de données
        $this->db = Database::getInstance();
        
        // Instanciation CORRECTE de Subscription avec l'objet DB
        $this->subscription = new Subscription($this->db);
        
        $this->checkCreatedAtColumn();
        $this->initializeRoleMap();
        $this->initializeUser();
    }

    private function checkCreatedAtColumn() {
        try {
            $result = $this->db->query("SHOW COLUMNS FROM subscription_requests LIKE 'created_at'");
            $this->hasCreatedAtColumn = !empty($this->extractUserData($result));
        } catch (Exception $e) {
            $this->hasCreatedAtColumn = false;
        }
    }

    private function initializeRoleMap() {
        $this->roleMap = array(
            'prestataire' => 'Prestataire',
            'candidat' => 'Candidat',
            'prestataire_candidat' => 'Prestataire_Candidat',
            'client' => 'Candidat',
            'admin' => null
        );
    }

    private function initializeUser() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Utilisateur non authentifié");
        }
        
        $this->userId = $_SESSION['user_id'];
        $this->user = $this->getSafeUserData();
        
        if (empty($this->user)) {
            throw new Exception("Utilisateur introuvable");
        }
        
        $this->validateUserTypeField();
        $this->userRole = $this->formatRoleForPricing(isset($this->user['type_utilisateur']) ? $this->user['type_utilisateur'] : '');
        
        // FORÇAGE ABSOLU À EUR - PAS DE COMPLEXITÉ INUTILE
        $this->currency = 'EUR';
    }

    private function getSafeUserData() {
        try {
            $queryResult = $this->db->query(
                "SELECT id, email, nom, prenom, telephone, type_utilisateur, 
                        statut, photo_profil, devise, subscription_status, 
                        subscription_start_date, subscription_end_date, last_notification_date
                 FROM utilisateurs 
                 WHERE id = ?", 
                array($this->userId)
            );
            
            $result = $this->extractUserData($queryResult);
            
            if (empty($result)) {
                throw new Exception("Utilisateur introuvable");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erreur dans getSafeUserData: " . $e->getMessage());
            throw new Exception("Erreur système: Impossible de récupérer les données utilisateur");
        }
    }

    private function extractUserData($queryResult) {
        if (is_array($queryResult) && array_key_exists('id', $queryResult)) {
            return $queryResult;
        }
        
        if (is_array($queryResult) && !empty($queryResult) && is_array($queryResult)) {
            return $queryResult;
        }
        
        if (is_object($queryResult) && method_exists($queryResult, 'fetch_assoc')) {
            $row = $queryResult->fetch_assoc();
            return $row ? $row : array();
        }
        
        if (is_object($queryResult) && method_exists($queryResult, 'fetch')) {
            $row = $queryResult->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : array();
        }
        
        return array();
    }

    private function validateUserTypeField() {
        if (!isset($this->user['type_utilisateur'])) {
            $this->user['type_utilisateur'] = $this->deduceUserTypeFromData();
        }
        
        $validRoles = array('admin', 'prestataire', 'candidat', 'client', 'prestataire_candidat');
        if (!in_array($this->user['type_utilisateur'], $validRoles)) {
            $this->user['type_utilisateur'] = $this->deduceUserTypeFromData();
        }
    }

    private function deduceUserTypeFromData() {
        try {
            $isAdmin = $this->db->query(
                "SELECT COUNT(*) as count 
                 FROM utilisateurs 
                 WHERE id = ? AND type_utilisateur = 'admin'", 
                array($this->userId)
            );
            
            $isAdminCount = $this->extractCountValue($isAdmin);
            
            if ($isAdminCount > 0) {
                return 'admin';
            }
            
            $hasOffers = $this->db->query(
                "SELECT COUNT(*) as count FROM profils_prestataires WHERE utilisateur_id = ?", 
                array($this->userId)
            );
            
            $hasCV = $this->db->query(
                "SELECT COUNT(*) as count FROM cvs WHERE utilisateur_id = ?", 
                array($this->userId)
            );
            
            $hasOffersCount = $this->extractCountValue($hasOffers);
            $hasCVCount = $this->extractCountValue($hasCV);
            
            if ($hasOffersCount > 0 && $hasCVCount > 0) {
                return 'prestataire_candidat';
            } else if ($hasOffersCount > 0) {
                return 'prestataire';
            } else if ($hasCVCount > 0) {
                return 'candidat';
            } else {
                return 'client';
            }
        } catch (Exception $e) {
            error_log("Erreur dans deduceUserTypeFromData: " . $e->getMessage());
            return 'client';
        }
    }

    private function extractCountValue($result) {
        if (is_array($result)) {
            if (array_key_exists('count', $result)) {
                return (int)$result['count'];
            }
            if (!empty($result) && is_array($result) && array_key_exists('count', $result)) {
                return (int)$result['count'];
            }
        }
        
        if (is_object($result)) {
            if (method_exists($result, 'fetch_assoc')) {
                $row = $result->fetch_assoc();
                return $row && array_key_exists('count', $row) ? (int)$row['count'] : 0;
            }
            if (method_exists($result, 'fetch')) {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                return $row && array_key_exists('count', $row) ? (int)$row['count'] : 0;
            }
        }
        
        return 0;
    }

    private function formatRoleForPricing($userRole) {
        if (empty($userRole) || !array_key_exists($userRole, $this->roleMap)) {
            return null;
        }
        
        return $this->roleMap[$userRole];
    }

    public function pricing() {
        if (!$this->userRole) {
            $this->handleError("Votre compte n'a pas besoin d'abonnement");
            return;
        }
        
        // Récupération SÉCURISÉE des tarifs - FORÇAGE À EUR
        $pricings = $this->getPricingsForRoleSafe($this->userRole);
        
        if (empty($pricings)) {
            $this->handleError("Aucun plan d'abonnement n'est disponible");
            return;
        }
        
        $subscriptionStatus = $this->subscription->getUserSubscriptionStatus($this->userId);

        // IMPORTANT: NE PAS INSTANCIER Subscription DANS LA VUE
        // La vue doit utiliser $this->subscription qui est déjà initialisé
        require_once __DIR__ . '/../views/subscription/pricing.php';
    }
    
    /**
     * Méthode SÉCURISÉE pour récupérer les tarifs
     */
    private function getPricingsForRoleSafe($role) {
        try {
            // FORÇAGE ABSOLU À EUR - PAS DE COMPLEXITÉ INUTILE
            $currency = 'EUR';
            
            // Requête préparée correcte
            $query = "SELECT * FROM pricings WHERE role = ? AND currency = ?";
            $result = $this->db->query($query, array($role, $currency));
            
            // Extraction des données avec gestion d'erreur
            $pricings = $this->extractPricingsData($result);
            
            // Vérification et correction des données
            foreach ($pricings as &$pricing) {
                $pricing = $this->ensurePricingDataComplete($pricing);
            }
            
            return $pricings;
        } catch (Exception $e) {
            error_log("Erreur dans getPricingsForRoleSafe: " . $e->getMessage());
            // Création de plans par défaut en cas d'erreur
            return $this->getDefaultPricings($role);
        }
    }
    
    /**
     * Extraction sécurisée des données de tarification
     */
    private function extractPricingsData($result) {
        if (is_array($result) && !empty($result) && array_key_exists(0, $result)) {
            return $result;
        }
        
        if (is_object($result)) {
            $pricings = array();
            while ($row = $result->fetch_assoc()) {
                $pricings[] = $row;
            }
            return $pricings;
        }
        
        return array();
    }
    
    /**
     * S'assure que les données de tarification sont complètes
     */
    private function ensurePricingDataComplete($pricing) {
        // Vérification et correction des champs manquants
        if (!isset($pricing['price']) || $pricing['price'] === null) {
            $pricing['price'] = 0;
        }
        
        if (!isset($pricing['currency']) || $pricing['currency'] === null) {
            $pricing['currency'] = 'EUR';
        }
        
        if (!isset($pricing['duration_months']) || $pricing['duration_months'] === null) {
            $pricing['duration_months'] = 1;
        }
        
        if (!isset($pricing['features']) || $pricing['features'] === null) {
            $pricing['features'] = 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié';
        }
        
        return $pricing;
    }
    
    /**
     * Retourne des plans par défaut en cas d'erreur
     */
    private function getDefaultPricings($role) {
        return array(
            array(
                'id' => 1,
                'role' => $role,
                'duration_months' => 3,
                'price' => 29.99,
                'currency' => 'EUR',
                'features' => 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié'
            ),
            array(
                'id' => 2,
                'role' => $role,
                'duration_months' => 6,
                'price' => 49.99,
                'currency' => 'EUR',
                'features' => 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié, Mise en avant hebdomadaire'
            ),
            array(
                'id' => 3,
                'role' => $role,
                'duration_months' => 12,
                'price' => 89.99,
                'currency' => 'EUR',
                'features' => 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié, Mise en avant hebdomadaire, Statistiques avancées'
            )
        );
    }

    public function submitRequest() {
        header('Content-Type: application/json');
        
        try {
            $this->validateRequestMethod();
            $this->validateSession();
            
            if (!$this->userRole) {
                throw new Exception("Votre compte n'a pas besoin d'abonnement");
            }
            
            $pricingId = $this->validatePricingId();
            $paymentMethod = $this->validatePaymentMethod();
            $proofDocumentPath = $this->handleProofDocumentUpload();
            
            // Validation SÉCURISÉE avec gestion d'erreur
            $pricingDetails = $this->validatePricingDetailsSafe($pricingId);
            
            $currentSubscriptionStatus = $this->getCurrentSubscriptionStatus();
            
            if ($currentSubscriptionStatus === 'En Attente') {
                throw new Exception("Vous avez déjà une demande d'abonnement en cours de traitement. Veuillez patienter 24h maximum pour la vérification.");
            }
            
            // Création de la requête avec gestion d'erreur détaillée
            $requestId = $this->createSubscriptionRequest($pricingId, $paymentMethod, $proofDocumentPath);
            
            if (!$requestId) {
                throw new Exception("Erreur système: Impossible de créer la requête (ID non retourné)");
            }
            
            $this->notifyUserAndAdmin($requestId);
            
            echo json_encode(array(
                'success' => true, 
                'message' => 'Votre demande d\'abonnement a été soumise avec succès. ' .
                            'Elle est actuellement en attente de vérification par notre équipe. ' .
                            'Aucun paiement n\'a été prélevé. ' .
                            'Vous recevrez une notification dès que votre abonnement sera activé.'
            ));
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * Validation SÉCURISÉE des détails du plan tarifaire
     */
    private function validatePricingDetailsSafe($pricingId) {
        try {
            // FORÇAGE ABSOLU À EUR - PAS DE COMPLEXITÉ INUTILE
            $this->currency = 'EUR';
            
            // Requête préparée correcte
            $query = "SELECT * FROM pricings WHERE id = ?";
            $result = $this->db->query($query, array($pricingId));
            
            $pricingData = $this->extractUserData($result);
            
            // Si trouvé, retourner les données avec gestion des champs manquants
            if (!empty($pricingData)) {
                return $this->ensurePricingDataComplete($pricingData);
            }
            
            // Si non trouvé, créer un plan par défaut
            return array(
                'id' => $pricingId,
                'role' => $this->userRole,
                'duration_months' => 1,
                'price' => 0,
                'currency' => 'EUR',
                'features' => 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié'
            );
        } catch (Exception $e) {
            error_log("Erreur dans validatePricingDetailsSafe: " . $e->getMessage());
            // En cas d'erreur, retourner un plan par défaut
            return array(
                'id' => $pricingId,
                'role' => $this->userRole,
                'duration_months' => 1,
                'price' => 0,
                'currency' => 'EUR',
                'features' => 'Profil visible 24/7, Messagerie illimitée, Support prioritaire, Badge vérifié'
            );
        }
    }

    private function getCurrentSubscriptionStatus() {
        try {
            $pendingRequestResult = $this->db->query(
                "SELECT id, status FROM subscription_requests 
                 WHERE user_id = ? AND status = 'En Attente' 
                 ORDER BY id DESC LIMIT 1",
                array($this->userId)
            );
            
            $pendingRequest = $this->extractUserData($pendingRequestResult);
            
            if (!empty($pendingRequest)) {
                return 'En Attente';
            }
            
            return isset($this->user['subscription_status']) ? $this->user['subscription_status'] : 'Inactif';
        } catch (Exception $e) {
            error_log("Erreur dans getCurrentSubscriptionStatus: " . $e->getMessage());
            return 'Inactif';
        }
    }

    private function validateRequestMethod() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Méthode non autorisée');
        }
    }

    private function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Non authentifié');
        }
    }

    private function validatePricingId() {
        $pricingId = isset($_POST['pricing_id']) ? $_POST['pricing_id'] : '';
        
        if (empty($pricingId)) {
            throw new Exception('Plan d\'abonnement non sélectionné');
        }
        
        return $pricingId;
    }

    private function validatePaymentMethod() {
        $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
        
        if (empty($paymentMethod)) {
            throw new Exception('Moyen de paiement requis');
        }
        
        return $paymentMethod;
    }

    private function handleProofDocumentUpload() {
        if (!isset($_FILES['proof_document']) || $_FILES['proof_document']['error'] !== UPLOAD_ERR_OK) {
            $this->handleUploadError(isset($_FILES['proof_document']['error']) ? $_FILES['proof_document']['error'] : UPLOAD_ERR_NO_FILE);
        }

        $this->validateUploadDirectory();
        
        $fileExtension = $this->validateFileExtension($_FILES['proof_document']['name']);
        $this->validateFileSize($_FILES['proof_document']['size']);
        
        return $this->moveUploadedFile($_FILES['proof_document']['tmp_name'], $fileExtension);
    }

    private function handleUploadError($errorCode) {
        $errorMessages = array(
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (dépasse upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (dépasse MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'Upload partiel, veuillez réessayer',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture sur le disque',
            UPLOAD_ERR_EXTENSION => 'Upload stoppé par extension PHP'
        );
        
        throw new Exception(isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Erreur inconnue lors de l\'upload');
    }

    private function validateUploadDirectory() {
        $uploadDir = ROOT_PATH . '/uploads/proofs/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Erreur serveur: Impossible de créer le dossier d\'upload');
            }
        }

        if (!is_writable($uploadDir)) {
            throw new Exception('Erreur serveur: Permissions insuffisantes');
        }
    }

    private function validateFileExtension($fileName) {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'pdf');
        
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            throw new Exception('Format de fichier non autorisé (jpg, jpeg, png, pdf uniquement)');
        }
        
        return $fileExtension;
    }

    private function validateFileSize($fileSize) {
        if ($fileSize > 5 * 1024 * 1024) {
            throw new Exception('Fichier trop volumineux (max 5MB)');
        }
    }

    private function moveUploadedFile($tmpName, $fileExtension) {
        $uploadDir = ROOT_PATH . '/uploads/proofs/';
        $fileName = 'proof_' . $this->userId . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($tmpName, $filePath)) {
            throw new Exception('Erreur serveur: Impossible de sauvegarder le fichier');
        }
        
        return 'uploads/proofs/' . $fileName;
    }

    private function createSubscriptionRequest($pricingId, $paymentMethod, $proofDocumentPath) {
        try {
            // Si c'est un plan dynamique, extraire l'ID original
            $originalPricingId = $pricingId;
            if (strpos($pricingId, '_dynamic_') !== false) {
                $pattern = '/^(\d+)_dynamic_(\w+)$/';
                if (preg_match($pattern, $pricingId, $matches)) {
                    $originalPricingId = $matches[1];
                }
            }
            
            // Récupération des données manquantes depuis le formulaire
            $durationMonths = $_POST['duration_months'] ?? null;
            $amountPaid = $_POST['amount_paid'] ?? null;
            $currency = $_POST['currency'] ?? null;

            $requestData = array(
                'user_id' => $this->userId,
                'pricing_id' => $originalPricingId,
                'payment_method' => $paymentMethod,
                'proof_document_path' => $proofDocumentPath,
                'status' => 'En Attente',
                'duration_months' => $durationMonths, // Ajout
                'amount_paid' => $amountPaid,         // Ajout
                'currency' => $currency              // Ajout
            );

            if ($this->hasCreatedAtColumn) {
                $requestData['created_at'] = date('Y-m-d H:i:s');
            }

            error_log("Données de la requête avant création: " . print_r($requestData, true));
            
            $requestId = $this->subscription->createRequest($requestData);
            
            if (!$requestId) {
                error_log("Échec de création de la requête d'abonnement pour l'utilisateur ID: {$this->userId}");
                error_log("Données de la requête: " . print_r($requestData, true));
                return false;
            }
            
            return $requestId;
        } catch (Exception $e) {
            error_log("Erreur dans createSubscriptionRequest: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    private function notifyUserAndAdmin($requestId) {
        try {
            $this->sendUserConfirmation();
            $this->sendUserInternalMessage($requestId);
            $this->sendAdminUrgentNotification($requestId);
        } catch (Exception $e) {
            error_log("Erreur dans notifyUserAndAdmin: " . $e->getMessage());
            // Ne pas bloquer la requête si l'email échoue
        }
    }

    private function sendUserConfirmation() {
        try {
            $subject = "✅ Demande d'abonnement reçue - LULU-OPEN";
            $message = "Bonjour " . (isset($this->user['prenom']) ? $this->user['prenom'] : '') . " " . (isset($this->user['nom']) ? $this->user['nom'] : '') . ",\n\n";
            $message .= "Votre demande d'abonnement a bien été reçue et est actuellement en attente de vérification.\n\n";
            $message .= "Important : Aucun paiement n'a été prélevé sur votre compte.\n";
            $message .= "Notre équipe vérifiera votre document de preuve dans les 24 heures maximum.\n\n";
            $message .= "Vous recevrez une notification dès que votre abonnement sera activé.\n\n";
            $message .= "Cordialement,\nL'équipe LULU-OPEN";

            // Utilisation de @ pour supprimer l'avertissement si mail() échoue,
            // l'erreur est capturée par le try-catch.
            // Pour la production, envisagez une bibliothèque comme PHPMailer.
            @mail(isset($this->user['email']) ? $this->user['email'] : 'noreply@lulu-open.com', $subject, $message, "From: noreply@lulu-open.com");
        } catch (Exception $e) {
            error_log("Erreur dans sendUserConfirmation: " . $e->getMessage());
        }
    }

    private function sendUserInternalMessage($requestId) {
        try {
            // Récupérer les détails du plan demandé
            $requestDetails = $this->subscription->getRequestDetails($requestId);
            $planName = $requestDetails['plan_name'] ?? 'Plan demandé';

            // Créer le message interne
            $this->db->insert('messages', [
                'expediteur_id' => 1, // Admin
                'destinataire_id' => $this->userId,
                'sujet' => 'Votre demande de souscription a bien été envoyée',
                'contenu' => "Bonjour {$this->user['prenom']},\n\nNous avons bien reçu votre demande de souscription au plan \"{$planName}\". Elle sera traitée rapidement par notre équipe.\n\nVous serez notifié dès qu'elle sera acceptée ou refusée.\n\nCordialement,\nL'équipe LULU-OPEN",
                'lu' => 0,
                'date_envoi' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Erreur dans sendUserInternalMessage: " . $e->getMessage());
        }
    }

    private function sendAdminUrgentNotification($requestId) {
        try {
            $adminEmail = "admin@lulu-open.com";
            $subject = "🚨 URGENT - Nouvelle demande d'abonnement #$requestId";
            $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . "/lulu/admin/verify-subscription.php?id=$requestId";
            
            $message = "NOUVELLE DEMANDE D'ABONNEMENT À VÉRIFIER\n\n";
            $message .= "Utilisateur: " . (isset($this->user['prenom']) ? $this->user['prenom'] : '') . " " . (isset($this->user['nom']) ? $this->user['nom'] : '') . " (" . (isset($this->user['email']) ? $this->user['email'] : '') . ")\n";
            $message .= "Demande ID: #$requestId\n\n";
            $message .= "Lien de vérification: $verifyLink\n\n";
            $message .= "Action requise dans les 24 heures.";

            // Utilisation de @ pour supprimer l'avertissement si mail() échoue,
            // l'erreur est capturée par le try-catch.
            // Pour la production, envisagez une bibliothèque comme PHPMailer.
            @mail($adminEmail, $subject, $message, "From: system@lulu-open.com\r\nPriority: urgent");

        } catch (Exception $e) {
            error_log("Erreur dans sendAdminUrgentNotification: " . $e->getMessage());
        }
    }

    private function handleError($message) {
        error_log("Erreur SubscriptionController: $message");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => $message));
        exit;
    }

    public function handleRequest($action) {
        try {
            switch ($action) {
                case 'submitRequest':
                    $this->submitRequest();
                    break;
                case 'pricing':
                default:
                    $this->pricing();
                    break;
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
}

try {
    $controller = new SubscriptionController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'pricing';

    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->pricing();
    }
} catch (Exception $e) {
    error_log("ERREUR GLOBALE SubscriptionController: " . $e->getMessage());
    error_log("TRACE: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    exit;
}