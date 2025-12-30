<?php
session_start();
require_once '../config/config.php';
require_once '../config/db.php';

// Fonctions nécessaires
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($message, $type) {
        $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('generateToken')) {
    function generateToken() {
        return bin2hex(random_bytes(32));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] === 'register' || $_POST['action'] === 'setup_profile')) {
    
    // Gestion de l'étape 3 - Configuration du profil
    if ($_POST['action'] === 'setup_profile') {
        try {
            if (!isset($_SESSION['temp_user_id'])) {
                throw new Exception('Session expirée, veuillez recommencer l\'inscription');
            }
            
            $userId = $_SESSION['temp_user_id'];
            $type = $_POST['type_utilisateur'];
            
            // Créer le profil prestataire si nécessaire
            if (in_array($type, ['prestataire', 'prestataire_candidat'])) {
                $database->insert('profils_prestataires', [
                    'utilisateur_id' => $userId,
                    'categorie_id' => $_POST['prestataire_categorie_id'],
                    'titre_professionnel' => $_POST['titre_professionnel'],
                    'description_services' => $_POST['description_services'],
                    'tarif_horaire' => $_POST['tarif_horaire'] ?? null,
                    'experience_annees' => $_POST['experience_annees'] ?? 0
                ]);
            }
            
            // Créer le CV candidat si nécessaire
            if (in_array($type, ['candidat', 'prestataire_candidat'])) {
                $database->insert('cvs', [
                    'utilisateur_id' => $userId,
                    'categorie_id' => $_POST['candidat_categorie_id'],
                    'titre_poste_recherche' => $_POST['titre_poste_recherche'],
                    'niveau_experience' => $_POST['niveau_experience'],
                    'salaire_souhaite' => $_POST['salaire_souhaite'] ?? null,
                    'type_contrat' => $_POST['type_contrat'],
                    'competences' => $_POST['competences']
                ]);
            }
            
            // Connecter automatiquement l'utilisateur
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $type;
            
            // Nettoyer la session temporaire
            unset($_SESSION['temp_user_id']);
            
            // Rediriger vers le profil approprié
            if ($type === 'prestataire') {
                header('Location: /lulu/views/prestataire/dashboard.php');
            } elseif ($type === 'candidat') {
                header('Location: /lulu/views/candidat/dashboard.php');
            } else {
                header('Location: /lulu/views/prestataire/dashboard.php'); // prestataire_candidat
            }
            exit;
            
        } catch (Exception $e) {
            setFlashMessage($e->getMessage(), 'error');
            header('Location: views/auth/register.php?step=3&type=' . ($type ?? 'prestataire'));
            exit;
        }
    }
    
    // Gestion de l'étape 2 - Informations personnelles
    try {
        // Validation CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Token CSRF invalide');
        }
        
        $data = $_POST;
        
        // Validation des mots de passe
        if ($data['password'] !== $data['confirm_password']) {
            throw new Exception('Les mots de passe ne correspondent pas');
        }
        
        // Vérifier si l'email existe déjà
        global $database;
        $existingUser = $database->fetch("SELECT id FROM utilisateurs WHERE email = ?", [$data['email']]);
        if ($existingUser) {
            throw new Exception('Cet email est déjà utilisé');
        }
        
        // Créer la localisation si nécessaire
        $localisationId = null;
        if (!empty($data['ville']) && !empty($data['pays'])) {
            $existing = $database->fetch("SELECT id FROM localisations WHERE ville = ? AND pays = ?", [$data['ville'], $data['pays']]);
            
            if ($existing) {
                $localisationId = $existing['id'];
            } else {
                $localisationId = $database->insert('localisations', [
                    'ville' => $data['ville'],
                    'pays' => $data['pays'],
                    'code_iso' => $data['code_iso'] ?? null
                ]);
            }
        }
        
        // Créer l'utilisateur
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $userId = $database->insert('utilisateurs', [
            'email' => $data['email'],
            'mot_de_passe' => $hashedPassword,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'] ?? null,
            'type_utilisateur' => $data['type_utilisateur'],
            'localisation_id' => $localisationId
        ]);
        
        // Si c'est un client, connecter automatiquement
        if ($data['type_utilisateur'] === 'client') {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = 'client';
            header('Location: /lulu/views/pages/home.php');
            exit;
        } else {
            // Pour prestataire/candidat, redirection vers étape 3
            $_SESSION['temp_user_id'] = $userId;
            header('Location: ../views/auth/register.php?step=3&type=' . $data['type_utilisateur']);
            exit;
        }
        
    } catch (Exception $e) {
        setFlashMessage($e->getMessage(), 'error');
        header('Location: ../views/auth/register.php?step=2&type=' . ($data['type_utilisateur'] ?? 'client'));
        exit;
    }
}

// Si pas de POST, rediriger vers l'inscription
header('Location: ../views/auth/register.php');
exit;
?>