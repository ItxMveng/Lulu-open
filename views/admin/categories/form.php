<?php
require_once '../../../config/config.php';
requireRole('admin');

global $database;
$category = null;
$isEdit = false;

if (isset($_GET['id'])) {
    $isEdit = true;
    $category = $database->fetch("SELECT * FROM categories_services WHERE id = :id", ['id' => $_GET['id']]);
    if (!$category) {
        flashMessage('Catégorie non trouvée', 'error');
        redirect('index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icone = trim($_POST['icone'] ?? '');
        $couleur = trim($_POST['couleur'] ?? '#0099FF');
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        if (empty($nom)) {
            throw new Exception('Le nom est requis');
        }
        
        $data = [
            'nom' => $nom,
            'description' => $description,
            'icone' => $icone,
            'couleur' => $couleur,
            'actif' => $actif
        ];
        
        if ($isEdit) {
            $database->update('categories_services', $data, 'id = :id', ['id' => $category['id']]);
            flashMessage('Catégorie modifiée avec succès', 'success');
        } else {
            $database->insert('categories_services', $data);
            flashMessage('Catégorie créée avec succès', 'success');
        }
        
        redirect('index.php');
        
    } catch (Exception $e) {
        flashMessage($e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> une Catégorie - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Administration</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-link">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="index.php" class="nav-link active">
                <i class="icon">📁</i> Catégories
            </a>
            <a href="../users.php" class="nav-link">
                <i class="icon">👥</i> Utilisateurs
            </a>
            <a href="../subscriptions-unified.php" class="nav-link">
                <i class="icon">💳</i> Abonnements
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header">
            <h1><?= $isEdit ? 'Modifier' : 'Ajouter' ?> une Catégorie</h1>
            <p class="text-muted">Gérer les catégories de services</p>
        </div>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="card-header">
                <h5><?= $isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?= htmlspecialchars($category['nom'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="icone" class="form-label">Icône (emoji)</label>
                            <input type="text" class="form-control" id="icone" name="icone" 
                                   value="<?= htmlspecialchars($category['icone'] ?? '') ?>" 
                                   placeholder="💻">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="couleur" class="form-label">Couleur</label>
                            <input type="color" class="form-control form-control-color" id="couleur" name="couleur" 
                                   value="<?= htmlspecialchars($category['couleur'] ?? '#0099FF') ?>">
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="Description de la catégorie..."><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif" 
                                       <?= ($category['actif'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">
                                    Catégorie active
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Modifier' : 'Créer' ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .admin-page {
            background: #f8f9fa;
            font-family: var(--font-family);
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav .icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--light-gray);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            color: var(--primary-dark);
        }
    </style>
</body>
</html>