<?php
require_once '../../../config/config.php';
requireLogin();
requireRole('prestataire');

// Récupération des catégories
try {
    global $database;
    $categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");
    
    // Vérifier si l'utilisateur a déjà un CV
    $existingCv = $database->fetch("SELECT id FROM cvs WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
    if ($existingCv) {
        flashMessage('Vous avez déjà un CV. Vous pouvez le modifier depuis votre profil.', 'info');
        redirect('../dashboard.php');
    }
} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter mon CV - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-link">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="edit.php" class="nav-link active">
                <i class="icon">✏️</i> Mon Profil
            </a>
            <a href="../messages/inbox.php" class="nav-link">
                <i class="icon">💬</i> Messages
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header">
            <h1><i class="bi bi-file-person text-success"></i> Ajouter mon CV</h1>
            <p class="text-muted">Complétez votre profil en ajoutant vos informations de candidat</p>
        </div>

        <?php include '../../components/flash_message.php'; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="bi bi-plus-circle text-success"></i> Créer mon profil candidat</h5>
                        <p class="text-muted mb-0">En ajoutant un CV, vous devenez visible pour les recruteurs</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../../controllers/ProfileController.php">
                            <input type="hidden" name="action" value="add_cv">
                            <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                            
                            <div class="mb-4">
                                <label class="form-label">Domaine de recherche *</label>
                                <select class="form-select" name="candidat_categorie_id" required>
                                    <option value="">Choisir un domaine</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>">
                                            <?= htmlspecialchars($category['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Poste recherché *</label>
                                <input type="text" class="form-control" name="titre_poste_recherche" 
                                       placeholder="Ex: Développeur Full Stack" required>
                                <div class="form-text">Le type de poste que vous recherchez</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Niveau d'expérience *</label>
                                    <select class="form-select" name="niveau_experience" required>
                                        <option value="">Choisir</option>
                                        <option value="debutant">Débutant</option>
                                        <option value="junior">Junior (1-3 ans)</option>
                                        <option value="confirme">Confirmé (3-7 ans)</option>
                                        <option value="senior">Senior (7+ ans)</option>
                                        <option value="expert">Expert (10+ ans)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Salaire souhaité (€/an)</label>
                                    <input type="number" class="form-control" name="salaire_souhaite" 
                                           min="0" placeholder="45000">
                                    <div class="form-text">Optionnel - Salaire brut annuel</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Type de contrat recherché *</label>
                                <select class="form-select" name="type_contrat" required>
                                    <option value="">Choisir</option>
                                    <option value="cdi">CDI</option>
                                    <option value="cdd">CDD</option>
                                    <option value="freelance">Freelance</option>
                                    <option value="stage">Stage</option>
                                    <option value="alternance">Alternance</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Compétences principales *</label>
                                <textarea class="form-control" name="competences" rows="4" 
                                          placeholder="Ex: PHP, JavaScript, MySQL, HTML, CSS, React, Node.js..." required></textarea>
                                <div class="form-text">Séparez vos compétences par des virgules</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Formations</label>
                                <textarea class="form-control" name="formations" rows="3" 
                                          placeholder="Ex: Master en Informatique - Université Paris (2020)"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Expériences professionnelles</label>
                                <textarea class="form-control" name="experiences_professionnelles" rows="4" 
                                          placeholder="Décrivez vos expériences précédentes..."></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Information :</strong> En ajoutant ce CV, votre type de compte deviendra "Prestataire + Candidat". 
                                Vous pourrez proposer vos services ET être visible pour les recruteurs.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Ajouter mon CV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .admin-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #28A745, #20c997);
            color: white;
            padding: 1.5rem;
        }
        
        .card-header h5 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #000033;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #28A745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28A745, #20c997);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
        }
    </style>
</body>
</html>