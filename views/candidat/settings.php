<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/sidebar.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

global $database;
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['mot_de_passe'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $database->query("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?", 
                    [$hashed_password, $_SESSION['user_id']]);
                flashMessage('Mot de passe modifié avec succès !', 'success');
            } else {
                flashMessage('Les nouveaux mots de passe ne correspondent pas.', 'error');
            }
        } else {
            flashMessage('Mot de passe actuel incorrect.', 'error');
        }
    }
    
    if ($action === 'update_notifications') {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        
        $database->query("UPDATE utilisateurs SET 
            email_notifications = ?, sms_notifications = ?, newsletter = ? 
            WHERE id = ?", 
            [$email_notifications, $sms_notifications, $newsletter, $_SESSION['user_id']]);
        
        flashMessage('Préférences de notification mises à jour !', 'success');
    }
    
    if ($action === 'delete_account') {
        $password_confirm = $_POST['password_confirm'];
        
        if (password_verify($password_confirm, $user['mot_de_passe'])) {
            // Supprimer toutes les données associées
            $database->query("DELETE FROM candidatures WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
            $database->query("DELETE FROM analyses_cv WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
            $database->query("DELETE FROM messages WHERE expediteur_id = ? OR destinataire_id = ?", 
                [$_SESSION['user_id'], $_SESSION['user_id']]);
            $database->query("DELETE FROM cvs WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
            $database->query("DELETE FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
            
            session_destroy();
            header('Location: ../../index.php?message=account_deleted');
            exit;
        } else {
            flashMessage('Mot de passe incorrect. Suppression annulée.', 'error');
        }
    }
}

// Recharger les données utilisateur
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .settings-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 2rem;
        }
        .settings-header {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 1.5rem;
            background: #fff5f5;
        }
        .switch-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .switch-container:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php renderSidebar($_SESSION['user_type'], 'settings.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">⚙️ Paramètres</h1>
                    <p class="text-muted mb-0">Gérez votre compte et vos préférences</p>
                </div>
            </div>

            <div class="row">
                <!-- Sécurité -->
                <div class="col-lg-6">
                    <div class="settings-card">
                        <div class="settings-header">
                            <h5 class="mb-0">🔒 Sécurité</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="new_password" 
                                           minlength="8" required>
                                    <div class="form-text">Minimum 8 caractères</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           minlength="8" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-check me-2"></i>Modifier le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="col-lg-6">
                    <div class="settings-card">
                        <div class="settings-header">
                            <h5 class="mb-0">🔔 Notifications</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="switch-container">
                                    <div>
                                        <strong>Notifications par email</strong>
                                        <div class="text-muted small">Recevoir les notifications importantes par email</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" 
                                               <?= ($user['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                
                                <div class="switch-container">
                                    <div>
                                        <strong>Notifications SMS</strong>
                                        <div class="text-muted small">Recevoir les alertes urgentes par SMS</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications" 
                                               <?= ($user['sms_notifications'] ?? 0) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                
                                <div class="switch-container">
                                    <div>
                                        <strong>Newsletter</strong>
                                        <div class="text-muted small">Recevoir notre newsletter hebdomadaire</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="newsletter" 
                                               <?= ($user['newsletter'] ?? 1) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="bi bi-bell me-2"></i>Sauvegarder les préférences
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Informations du compte -->
                <div class="col-lg-6">
                    <div class="settings-card">
                        <div class="settings-header">
                            <h5 class="mb-0">👤 Informations du compte</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-muted">Nom complet</label>
                                    <div class="fw-bold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted">Email</label>
                                    <div class="fw-bold"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted">Type de compte</label>
                                    <div class="fw-bold"><?= ucfirst($user['type_utilisateur']) ?></div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted">Statut</label>
                                    <span class="badge bg-<?= $user['statut'] === 'actif' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($user['statut']) ?>
                                    </span>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted">Membre depuis</label>
                                    <div class="fw-bold"><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="profile/edit.php" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil me-2"></i>Modifier le profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zone de danger -->
                <div class="col-12">
                    <div class="danger-zone">
                        <h5 class="text-danger mb-3">⚠️ Zone de danger</h5>
                        <p class="text-muted mb-3">
                            Les actions suivantes sont irréversibles. Assurez-vous de bien comprendre les conséquences.
                        </p>
                        
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="bi bi-trash me-2"></i>Supprimer mon compte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de suppression de compte -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">⚠️ Supprimer le compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>Attention !</strong> Cette action est irréversible.
                    </div>
                    
                    <p>En supprimant votre compte, vous perdrez :</p>
                    <ul>
                        <li>Toutes vos candidatures</li>
                        <li>Votre profil et CV</li>
                        <li>Vos messages et conversations</li>
                        <li>Vos analyses IA</li>
                        <li>Votre historique d'abonnement</li>
                    </ul>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_account">
                        <div class="mb-3">
                            <label class="form-label">Confirmez avec votre mot de passe :</label>
                            <input type="password" class="form-control" name="password_confirm" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label" for="confirmDelete">
                                Je comprends que cette action est irréversible
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>