<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

$success = '';
$error = '';

// Récupérer les infos utilisateur
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $nom = trim($_POST['nom'] ?? '');
                $prenom = trim($_POST['prenom'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $telephone = trim($_POST['telephone'] ?? '');
                
                if ($nom && $prenom && $email) {
                    $database->query(
                        "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?",
                        [$nom, $prenom, $email, $telephone, $_SESSION['user_id']]
                    );
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['email'] = $email;
                    $success = 'Profil mis à jour avec succès';
                    $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
                } else {
                    $error = 'Tous les champs sont requis';
                }
                break;
                
            case 'update_password':
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                
                if ($new !== $confirm) {
                    $error = 'Les mots de passe ne correspondent pas';
                } elseif (strlen($new) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères';
                } elseif (password_verify($current, $user['mot_de_passe'])) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $database->query("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?", [$hashed, $_SESSION['user_id']]);
                    $success = 'Mot de passe changé avec succès';
                } else {
                    $error = 'Mot de passe actuel incorrect';
                }
                break;
        }
    }
}

// Upload photo de profil
if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['photo_profil']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/lulu/uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['photo_profil']['tmp_name'], $upload_path)) {
            // Supprimer ancienne photo
            if ($user['photo_profil'] && file_exists($upload_dir . basename($user['photo_profil']))) {
                unlink($upload_dir . basename($user['photo_profil']));
            }
            
            $database->query("UPDATE utilisateurs SET photo_profil = ? WHERE id = ?", [$new_filename, $_SESSION['user_id']]);
            $success = 'Photo de profil mise à jour';
            $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
        } else {
            $error = 'Erreur lors de l\'upload';
        }
    } else {
        $error = 'Format de fichier non autorisé';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; margin: 0; padding: 0; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .card-header-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 1rem; border-radius: 15px 15px 0 0; }
        .list-group-item.active { background: #0099FF; border-color: #0099FF; }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; border-radius: 25px; padding: 0.5rem 1.5rem; }
        .profile-photo { width: 150px; height: 150px; object-fit: cover; }
        .avatar-initials { width: 150px; height: 150px; background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; font-size: 3rem; font-weight: 600; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Paramètres</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="list-group">
                    <a href="#profil" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="bi bi-person me-2"></i>Mon Profil
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-bell me-2"></i>Notifications
                    </a>
                    <a href="#confidentialite" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-shield-lock me-2"></i>Confidentialité
                    </a>
                    <a href="#securite" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-key me-2"></i>Sécurité
                    </a>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profil">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-person me-2"></i>Informations Personnelles</h4>
                            </div>
                            <div class="p-4">
                                <!-- Photo de profil -->
                                <div class="text-center mb-4">
                                    <?php 
                                    $photoPath = $user['photo_profil'] ? '/lulu/uploads/profiles/' . basename($user['photo_profil']) : '';
                                    if ($user['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                                    ?>
                                        <img src="<?= $photoPath ?>" class="rounded-circle profile-photo mb-3" alt="Photo">
                                    <?php else: 
                                        $initials = mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1);
                                    ?>
                                        <div class="rounded-circle avatar-initials d-inline-flex align-items-center justify-content-center mb-3">
                                            <?= strtoupper($initials) ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" enctype="multipart/form-data" class="d-inline">
                                        <input type="file" name="photo_profil" id="photoInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('photoInput').click()" style="border-radius: 20px;">
                                            <i class="bi bi-camera"></i> Changer la photo
                                        </button>
                                    </form>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Prénom *</label>
                                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nom *</label>
                                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Téléphone</label>
                                            <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="+33 6 12 34 56 78">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary-custom">
                                                <i class="bi bi-check2"></i> Enregistrer
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="notifications">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-bell me-2"></i>Préférences Notifications</h4>
                            </div>
                            <div class="p-4">
                                <p class="text-muted mb-4">Gérez vos préférences de notifications</p>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifEmail" checked disabled>
                                    <label class="form-check-label" for="notifEmail">
                                        <strong>Recevoir les notifications par email</strong><br>
                                        <small class="text-muted">Notifications importantes sur votre adresse email</small>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifMessages" checked disabled>
                                    <label class="form-check-label" for="notifMessages">
                                        <strong>Notifications nouveaux messages</strong><br>
                                        <small class="text-muted">Soyez alerté lors de nouveaux messages</small>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifFavoris" disabled>
                                    <label class="form-check-label" for="notifFavoris">
                                        <strong>Notifications favoris</strong><br>
                                        <small class="text-muted">Alertes sur vos profils favoris</small>
                                    </label>
                                </div>
                                <div class="alert alert-info mt-4">
                                    <i class="bi bi-info-circle me-2"></i>Les préférences de notifications seront bientôt disponibles
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="confidentialite">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Confidentialité</h4>
                            </div>
                            <div class="p-4">
                                <p class="text-muted mb-4">Gérez vos paramètres de confidentialité</p>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="profilPublic" disabled>
                                    <label class="form-check-label" for="profilPublic">
                                        <strong>Profil visible publiquement</strong><br>
                                        <small class="text-muted">Permettre aux autres utilisateurs de voir votre profil</small>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="showEmail" disabled>
                                    <label class="form-check-label" for="showEmail">
                                        <strong>Afficher mon email</strong><br>
                                        <small class="text-muted">Rendre votre email visible sur votre profil</small>
                                    </label>
                                </div>
                                <div class="alert alert-info mt-4">
                                    <i class="bi bi-info-circle me-2"></i>Les paramètres de confidentialité seront bientôt disponibles
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="securite">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-key me-2"></i>Sécurité</h4>
                            </div>
                            <div class="p-4">
                                <h5 class="mb-3">Changer le mot de passe</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_password">
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe actuel *</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nouveau mot de passe *</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                                        <small class="text-muted">Minimum 6 caractères</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="bi bi-key"></i> Changer le mot de passe
                                    </button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3 text-danger">Zone dangereuse</h5>
                                <p class="text-muted">Actions irréversibles sur votre compte</p>
                                <button class="btn btn-outline-danger" onclick="alert('Fonctionnalité bientôt disponible')">
                                    <i class="bi bi-trash"></i> Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
