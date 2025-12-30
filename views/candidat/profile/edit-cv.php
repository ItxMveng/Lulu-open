<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Vérifier l'authentification
if (!isLoggedIn()) {
    flashMessage('Vous devez être connecté pour accéder à cette page', 'warning');
    header('Location: ../../../login.php');
    exit;
}

// Vérifier le type d'utilisateur
if (!in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    flashMessage('Accès refusé', 'error');
    header('Location: ../../../index.php');
    exit;
}

global $database;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titre_poste_recherche = trim($_POST['titre_poste_recherche'] ?? '');
        $competences = trim($_POST['competences'] ?? '');
        $salaire_souhaite = $_POST['salaire_souhaite'] ?? null;
        $niveau_experience = $_POST['niveau_experience'] ?? 'debutant';
        $type_contrat = $_POST['type_contrat'] ?? 'cdi';
        
        // Mise à jour du CV
        $database->query("
            UPDATE cvs SET 
                titre_poste_recherche = ?,
                competences = ?,
                salaire_souhaite = ?,
                niveau_experience = ?,
                type_contrat = ?
            WHERE utilisateur_id = ?
        ", [
            $titre_poste_recherche,
            $competences,
            $salaire_souhaite,
            $niveau_experience,
            $type_contrat,
            $_SESSION['user_id']
        ]);
        
        flashMessage('Profil mis à jour avec succès !', 'success');
        header('Location: ../dashboard.php');
        exit;
        
    } catch (Exception $e) {
        flashMessage('Erreur lors de la mise à jour : ' . $e->getMessage(), 'error');
    }
}

// Récupérer les données actuelles
$profile = $database->fetch("
    SELECT cv.*, u.nom, u.prenom, u.email, u.photo_profil, u.telephone
    FROM cvs cv
    JOIN utilisateurs u ON cv.utilisateur_id = u.id
    WHERE cv.utilisateur_id = ?
", [$_SESSION['user_id']]);

// Récupérer les catégories
$categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");

$title = 'Modifier mon CV - LULU-OPEN';
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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            color: #000033 !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../../index.php">
                <span class="text-primary">LULU</span><span class="text-dark">-OPEN</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">
                    <i class="bi bi-arrow-left"></i> Retour au dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Modifier mon CV</h4>
                        <p class="text-muted mb-0">Mettez à jour vos informations professionnelles</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="titre_poste_recherche" class="form-label">Poste recherché *</label>
                                    <input type="text" class="form-control" id="titre_poste_recherche" name="titre_poste_recherche" 
                                           value="<?= htmlspecialchars($profile['titre_poste_recherche'] ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="niveau_experience" class="form-label">Niveau d'expérience</label>
                                    <select class="form-select" id="niveau_experience" name="niveau_experience">
                                        <option value="debutant" <?= ($profile['niveau_experience'] ?? '') === 'debutant' ? 'selected' : '' ?>>Débutant</option>
                                        <option value="junior" <?= ($profile['niveau_experience'] ?? '') === 'junior' ? 'selected' : '' ?>>Junior (1-3 ans)</option>
                                        <option value="confirme" <?= ($profile['niveau_experience'] ?? '') === 'confirme' ? 'selected' : '' ?>>Confirmé (3-7 ans)</option>
                                        <option value="senior" <?= ($profile['niveau_experience'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior (7+ ans)</option>
                                        <option value="expert" <?= ($profile['niveau_experience'] ?? '') === 'expert' ? 'selected' : '' ?>>Expert (10+ ans)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="type_contrat" class="form-label">Type de contrat</label>
                                    <select class="form-select" id="type_contrat" name="type_contrat">
                                        <option value="cdi" <?= ($profile['type_contrat'] ?? '') === 'cdi' ? 'selected' : '' ?>>CDI</option>
                                        <option value="cdd" <?= ($profile['type_contrat'] ?? '') === 'cdd' ? 'selected' : '' ?>>CDD</option>
                                        <option value="freelance" <?= ($profile['type_contrat'] ?? '') === 'freelance' ? 'selected' : '' ?>>Freelance</option>
                                        <option value="stage" <?= ($profile['type_contrat'] ?? '') === 'stage' ? 'selected' : '' ?>>Stage</option>
                                        <option value="alternance" <?= ($profile['type_contrat'] ?? '') === 'alternance' ? 'selected' : '' ?>>Alternance</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="salaire_souhaite" class="form-label">Salaire souhaité (€)</label>
                                    <input type="number" class="form-control" id="salaire_souhaite" name="salaire_souhaite" 
                                           value="<?= $profile['salaire_souhaite'] ?? '' ?>" min="0" step="100">
                                    <div class="form-text">Laissez vide si négociable</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="competences" class="form-label">Compétences *</label>
                                    <textarea class="form-control" id="competences" name="competences" rows="4" required 
                                              placeholder="Décrivez vos compétences principales..."><?= htmlspecialchars($profile['competences'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="../dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>