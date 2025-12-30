<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    if (isset($_SESSION['type_utilisateur']) && $_SESSION['type_utilisateur'] === 'client') {
        header('Location: views/client/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Debug
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST reçu dans login.php: ' . print_r($_POST, true));
}

// Le traitement est géré par auth-handler.php

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateToken();
}

$title = 'Connexion - LULU-OPEN';
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="container-fluid">
            <div class="row min-vh-100">
                <!-- Left Side - Form -->
                <div class="col-lg-6 d-flex align-items-center justify-content-center">
                    <div class="auth-form-container">
                        <div class="text-center mb-4">
                            <h1 class="auth-logo">
                                <span class="text-primary">LULU</span><span class="text-dark">-OPEN</span>
                            </h1>
                            <p class="text-muted">Connectez-vous à votre compte</p>
                        </div>

                        <?php if ($flashMessage = getFlashMessage()): ?>
                            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                                <?= htmlspecialchars($flashMessage['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="auth-handler.php" class="auth-form">
                            <input type="hidden" name="action" value="login">
                            <?php echo csrf_field(); ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                Se connecter
                            </button>
                            
                            <div class="text-center">
                                <a href="reset-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-3">Pas encore de compte ?</p>
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="views/auth/register.php?type=client" class="card-link text-decoration-none">
                                        <div class="card h-100 border-primary">
                                            <div class="card-body text-center p-3">
                                                <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                                                <h6 class="card-title mt-2 mb-1">Client</h6>
                                                <p class="card-text small text-muted">Trouvez des services</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="col-6">
                                    <a href="views/auth/register.php?type=professionnel" class="card-link text-decoration-none">
                                        <div class="card h-100 border-success">
                                            <div class="card-body text-center p-3">
                                                <i class="bi bi-briefcase text-success" style="font-size: 2rem;"></i>
                                                <h6 class="card-title mt-2 mb-1">Professionnel</h6>
                                                <p class="card-text small text-muted">Offrez vos services</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Visual -->
                <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center auth-visual">
                    <div class="auth-visual-content">
                        <h2 class="text-white mb-4">Rejoignez notre communauté</h2>
                        <p class="text-white-50 mb-4">
                            Connectez-vous avec des milliers de professionnels et trouvez les meilleures opportunités.
                        </p>
                        
                        <div class="auth-stats">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h3 class="text-primary">2,500+</h3>
                                    <p class="text-white-50">Prestataires</p>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-primary">1,200+</h3>
                                    <p class="text-white-50">CV Actifs</p>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-primary">98%</h3>
                                    <p class="text-white-50">Satisfaction</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
            --light-gray: #f8f9fa;
            --medium-gray: #6c757d;
            --border-radius: 8px;
            --transition: all 0.3s ease;
            --font-family: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-family);
            background: var(--light-gray);
        }

        .auth-visual {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
        }

        .auth-form-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }

        .auth-logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-form .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #E9ECEF;
            transition: var(--transition);
        }

        .auth-form .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.25);
        }

        .card-link:hover .card {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }
    </style>
</body>
</html>