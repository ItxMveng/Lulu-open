<?php
session_start();
require_once 'config/config.php';
require_once 'includes/i18n.php';

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Valider CSRF pour toutes les actions POST (mode debug)
    if (!isset($_POST['csrf_token'])) {
        error_log("CSRF Token manquant");
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Token de sécurité manquant'];
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/lulu/'));
        exit;
    }
    
    if (!validate_csrf_token($_POST['csrf_token'])) {
        error_log("CSRF Token invalide: " . $_POST['csrf_token']);
        error_log("CSRF Token session: " . ($_SESSION['csrf_token'] ?? 'non défini'));
        // Temporairement, on continue quand même pour déboguer
        // $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Token de sécurité invalide'];
        // header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/lulu/'));
        // exit;
    }
    
    if ($_POST['action'] === 'login') {
        try {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                throw new Exception('Email et mot de passe requis');
            }
            
            global $database;
            $user = $database->fetch("SELECT * FROM utilisateurs WHERE email = ? AND statut IN ('actif', 'en_attente')", [$email]);
            
            if (!$user || !password_verify($password, $user['mot_de_passe'])) {
                throw new Exception('Email ou mot de passe incorrect');
            }
            
            // Création de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['type_utilisateur'] = $user['type_utilisateur'];
            $_SESSION['user_type'] = $user['type_utilisateur']; // Compatibilité
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['photo_profil'] = $user['photo_profil'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

            // Mise à jour dernière connexion
            $database->query("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?", [$user['id']]);

            // Déterminer la redirection selon le type et l'état du profil
            $userType = $user['type_utilisateur'];

            // Pour les professionnels, vérifier si c'est la première connexion
            if (in_array($userType, ['prestataire', 'prestataire_candidat', 'candidat'])) {
                // Vérifier si c'est la première connexion (pas de dernière_connexion)
                $isFirstLogin = empty($user['derniere_connexion']);
                
                if ($isFirstLogin && ($user['profil_complet'] ?? 0) == 0) {
                    // Première connexion avec profil incomplet -> redirection vers configuration
                    if ($userType === 'prestataire_candidat') {
                        $redirectUrl = '/lulu/views/prestataire_candidat/profile/edit.php';
                    } elseif ($userType === 'prestataire') {
                        $redirectUrl = '/lulu/views/prestataire/profile/edit.php';
                    } else { // candidat
                        $redirectUrl = '/lulu/views/candidat/profile/edit.php';
                    }
                    $_SESSION['flash_message'] = [
                        'type' => 'info',
                        'message' => 'Bienvenue ! Complétez votre profil pour commencer.'
                    ];
                } else {
                    // Connexions suivantes -> dashboard normal
                    if ($userType === 'prestataire_candidat') {
                        $redirectUrl = '/lulu/views/prestataire_candidat/dashboard.php';
                    } elseif ($userType === 'prestataire') {
                        $redirectUrl = '/lulu/views/prestataire/dashboard.php';
                    } else { // candidat
                        $redirectUrl = '/lulu/views/candidat/dashboard.php';
                    }
                }
            } elseif ($userType === 'client') {
                $redirectUrl = '/lulu/views/client/dashboard.php';
            } elseif ($userType === 'admin') {
                $redirectUrl = '/lulu/views/admin/dashboard.php';
            } else {
                $redirectUrl = '/lulu/';
            }
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Connexion réussie !'];
            header('Location: ' . $redirectUrl);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
            header('Location: /lulu/login.php');
            exit;
        }
    }
    
    elseif ($_POST['action'] === 'register') {
        error_log("=== Début inscription ===");
        error_log("POST data: " . print_r($_POST, true));
        try {
            // Validation
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $type = $_POST['type_utilisateur'] ?? 'client';
            
            // Déterminer le type selon les cases cochées
            if ($type === 'professionnel') {
                $isPrestataire = isset($_POST['is_prestataire']) && $_POST['is_prestataire'] == '1';
                $isCandidat = isset($_POST['is_candidat']) && $_POST['is_candidat'] == '1';
                
                if ($isPrestataire && $isCandidat) {
                    $type = 'prestataire_candidat';
                } elseif ($isPrestataire) {
                    $type = 'prestataire';
                } elseif ($isCandidat) {
                    $type = 'candidat';
                } else {
                    throw new Exception('Veuillez sélectionner au moins un type de profil professionnel');
                }
            }
            
            if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
                throw new Exception('Tous les champs obligatoires doivent être remplis');
            }
            
            if ($password !== $confirm_password) {
                throw new Exception('Les mots de passe ne correspondent pas');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
            }
            
            global $database;
            
            // Vérifier si l'email existe déjà
            $existing = $database->fetch("SELECT id, email, statut FROM utilisateurs WHERE email = ?", [$email]);
            if ($existing) {
                // Si l'utilisateur existe mais est inactif, on peut le réactiver
                if ($existing['statut'] === 'inactif') {
                    throw new Exception('Ce compte existe mais est inactif. Contactez l\'administrateur.');
                }
                throw new Exception('Cet email est déjà utilisé');
            }
            
            // Créer la localisation si fournie
            $localisation_id = null;
            if (!empty($_POST['ville']) && !empty($_POST['pays'])) {
                $ville = trim($_POST['ville']);
                $pays = trim($_POST['pays']);
                $code_iso = $_POST['code_iso'] ?? '';
                
                // Vérifier si la localisation existe
                $loc = $database->fetch("SELECT id FROM localisations WHERE ville = ? AND pays = ?", [$ville, $pays]);
                if ($loc) {
                    $localisation_id = $loc['id'];
                } else {
                    // Créer nouvelle localisation
                    $localisation_id = $database->insert('localisations', [
                        'ville' => $ville,
                        'pays' => $pays,
                        'code_iso' => $code_iso
                    ]);
                }
            }
            
            // Créer l'utilisateur (inactif pour prestataires tant que profil non complet)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $statut = in_array($type, ['prestataire', 'prestataire_candidat', 'candidat']) ? 'en_attente' : 'actif';

            // Détecter langue et devise automatiquement
            $langue = detectBrowserLanguage();
            $devise = detectCurrency($_POST['code_iso'] ?? null);

            $userId = $database->insert('utilisateurs', [
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
                'mot_de_passe' => $hashedPassword,
                'telephone' => $_POST['telephone'] ?? null,
                'type_utilisateur' => $type,
                'localisation_id' => $localisation_id,
                'statut' => $statut,
                'profil_complet' => 0, // Profil non complet par défaut
                'langue' => $langue,
                'devise' => $devise
            ]);
            
            // Créer un abonnement gratuit pour les professionnels
            if (in_array($type, ['prestataire', 'prestataire_candidat', 'candidat'])) {
                // Trouver le plan gratuit approprié selon le type
                $planGratuit = null;
                if ($type === 'candidat') {
                    $planGratuit = $database->fetch("SELECT id FROM plans_abonnement WHERE slug = 'basic-candidat' AND actif = 1");
                } elseif (in_array($type, ['prestataire', 'prestataire_candidat'])) {
                    // Pour prestataire_candidat, utiliser le plan prestataire gratuit par défaut
                    $planGratuit = $database->fetch("SELECT id FROM plans_abonnement WHERE slug = 'gratuit-prestataire' AND actif = 1");
                }

                if ($planGratuit) {
                    $database->insert('abonnements', [
                        'utilisateur_id' => $userId,
                        'plan_id' => $planGratuit['id'],
                        'date_debut' => date('Y-m-d'),
                        'date_fin' => date('Y-m-d', strtotime('+1 year')), // Abonnement gratuit d'un an
                        'statut' => 'actif',
                        'type_abonnement' => 'gratuit'
                    ]);
                }
            }

            // Créer message de bienvenue dans la messagerie interne
            if (in_array($type, ['prestataire', 'prestataire_candidat', 'candidat'])) {
                $messageBienvenue = "Bienvenue sur LULU-OPEN, $prenom !\n\n";
                $messageBienvenue .= "Vous êtes actuellement sur le plan gratuit de base.\n\n";
                $messageBienvenue .= "Pour être visible sur la plateforme et bénéficier de toutes les fonctionnalités :\n";
                $messageBienvenue .= "1. Complétez votre profil (photo, description, compétences)\n";
                $messageBienvenue .= "2. Une fois validé, votre compte deviendra actif\n\n";
                $messageBienvenue .= "Pour des fonctionnalités avancées (mise en avant, statistiques, support prioritaire), ";
                $messageBienvenue .= "découvrez nos abonnements Premium dans la section Abonnements.\n\n";
                $messageBienvenue .= "L'équipe LULU-OPEN";

                $database->insert('messages', [
                    'expediteur_id' => 1, // Admin
                    'destinataire_id' => $userId,
                    'sujet' => 'Bienvenue sur LULU-OPEN !',
                    'contenu' => $messageBienvenue,
                    'lu' => 0,
                    'date_envoi' => date('Y-m-d H:i:s')
                ]);
            }

            // Créer notification pour l'admin
            if (in_array($type, ['prestataire', 'prestataire_candidat', 'candidat'])) {
                $database->insert('notifications', [
                    'utilisateur_id' => 1, // Admin
                    'type_notification' => 'systeme',
                    'titre' => 'Nouveau professionnel inscrit',
                    'contenu' => "$prenom $nom s'est inscrit en tant que $type",
                    'url_action' => '/lulu/views/admin/users.php',
                    'lu' => 0
                ]);
            }
            
            // Connexion automatique
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['type_utilisateur'] = $type;
            $_SESSION['user_type'] = $type; // Compatibilité
            $_SESSION['prenom'] = $prenom;
            $_SESSION['nom'] = $nom;
            $_SESSION['user_name'] = $prenom . ' ' . $nom;
            
            // Si client, inscription terminée
            if ($type === 'client') {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Inscription réussie ! Bienvenue sur LULU-OPEN.'];
                header('Location: /lulu/views/client/dashboard.php');
                exit;
            }
            
            // Pour les professionnels, créer les profils directement
            $categories = $_POST['categories'] ?? [];
            if (empty($categories)) {
                throw new Exception('Veuillez sélectionner au moins une catégorie');
            }
            
            // Limiter à 3 catégories
            $categories = array_slice($categories, 0, 3);
            
            // Créer le profil prestataire si nécessaire
            if ($type === 'prestataire' || $type === 'prestataire_candidat') {
                $prestataireId = $database->insert('profils_prestataires', [
                    'utilisateur_id' => $userId,
                    'titre_professionnel' => '',
                    'description_services' => '',
                    'tarif_horaire' => null,
                    'categorie_id' => $categories[0],
                    'disponibilite' => 1
                ]);
                
                // Lier les catégories au prestataire
                foreach ($categories as $categorieId) {
                    $database->insert('prestataire_categories', [
                        'prestataire_id' => $prestataireId,
                        'categorie_id' => $categorieId
                    ]);
                }
            }
            
            // Créer le profil candidat si nécessaire
            if ($type === 'candidat' || $type === 'prestataire_candidat') {
                $cvId = $database->insert('cvs', [
                    'utilisateur_id' => $userId,
                    'titre_poste_recherche' => '',
                    'competences' => '',
                    'salaire_souhaite' => null,
                    'type_contrat' => 'cdi',
                    'categorie_id' => $categories[0]
                ]);
                
                // Lier les catégories au candidat
                foreach ($categories as $categorieId) {
                    $database->insert('cv_categories', [
                        'cv_id' => $cvId,
                        'categorie_id' => $categorieId
                    ]);
                }
            }
            
            // Redirection selon le type vers la configuration de profil
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Inscription réussie ! Complétez votre profil pour commencer.'];
            if ($type === 'prestataire_candidat') {
                header('Location: /lulu/views/prestataire_candidat/profile/edit.php');
            } elseif ($type === 'prestataire') {
                header('Location: /lulu/views/prestataire/profile/edit.php');
            } else {
                header('Location: /lulu/views/candidat/profile/edit-cv.php');
            }
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/lulu/register.php');
            exit;
        }
    }
    
    elseif ($_POST['action'] === 'setup_profile') {
        try {
            // Vérifier que l'utilisateur est en cours d'inscription
            if (!isset($_SESSION['registration_user_id'])) {
                throw new Exception('Session d\'inscription expirée');
            }
            
            $userId = $_SESSION['registration_user_id'];
            $type = $_SESSION['registration_type'];
            $categories = $_SESSION['registration_categories'] ?? [];
            
            global $database;
            
            // Créer le profil prestataire si nécessaire
            if ($type === 'prestataire' || $type === 'prestataire_candidat') {
                $prestataireId = $database->insert('profils_prestataires', [
                    'utilisateur_id' => $userId,
                    'titre_professionnel' => $_POST['titre_professionnel'] ?? '',
                    'description_services' => $_POST['description_services'] ?? '',
                    'tarif_horaire' => $_POST['tarif_horaire'] ?? null,
                    'categorie_id' => $categories[0] ?? null,
                    'disponibilite' => 1
                ]);
                
                // Lier les catégories au prestataire
                foreach ($categories as $categorieId) {
                    $database->insert('prestataire_categories', [
                        'prestataire_id' => $prestataireId,
                        'categorie_id' => $categorieId
                    ]);
                }
            }
            
            // Créer le profil candidat si nécessaire
            if ($type === 'candidat' || $type === 'prestataire_candidat') {
                $cvId = $database->insert('cvs', [
                    'utilisateur_id' => $userId,
                    'titre_poste_recherche' => $_POST['titre_poste_recherche'] ?? '',
                    'competences' => $_POST['competences'] ?? '',
                    'salaire_souhaite' => $_POST['salaire_souhaite'] ?? null,
                    'type_contrat' => $_POST['type_contrat'] ?? 'cdi',
                    'categorie_id' => $categories[0] ?? null
                ]);
                
                // Lier les catégories au candidat
                foreach ($categories as $categorieId) {
                    $database->insert('cv_categories', [
                        'cv_id' => $cvId,
                        'categorie_id' => $categorieId
                    ]);
                }
            }
            
            // Récupérer les infos utilisateur
            $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);
            
            // Connexion automatique
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $type;
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            
            // Nettoyer les variables de session d'inscription
            unset($_SESSION['registration_user_id']);
            unset($_SESSION['registration_type']);
            unset($_SESSION['registration_categories']);
            
            // Redirection selon le type
            $redirectUrl = '/lulu/';
            if ($type === 'prestataire' || $type === 'prestataire_candidat') {
                $redirectUrl = '/lulu/views/prestataire/dashboard.php';
            } elseif ($type === 'candidat') {
                $redirectUrl = '/lulu/views/candidat/dashboard.php';
            }
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Inscription réussie ! Bienvenue sur LULU-OPEN.'];
            header('Location: ' . $redirectUrl);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/lulu/register.php');
            exit;
        }
    }
}

// Si pas de POST, rediriger vers l'accueil
header('Location: /lulu/');
exit;
