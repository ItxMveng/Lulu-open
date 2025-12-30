<?php
require_once '../../../config/config.php';
requireRole('admin');

// Récupération des catégories
global $database;
$categories = $database->fetchAll("
    SELECT c.*, 
           COUNT(DISTINCT pp.id) as nb_prestataires,
           COUNT(DISTINCT cv.id) as nb_cvs
    FROM categories_services c
    LEFT JOIN profils_prestataires pp ON c.id = pp.categorie_id
    LEFT JOIN cvs cv ON c.id = cv.categorie_id
    GROUP BY c.id
    ORDER BY c.nom ASC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
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
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header d-flex justify-content-between align-items-center">
            <div>
                <h1>Gestion des Catégories</h1>
                <p class="text-muted">Gérez les catégories de services et métiers</p>
            </div>
            <a href="form.php" class="btn btn-primary">
                <i class="me-2">➕</i> Nouvelle Catégorie
            </a>
        </div>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Liste des Catégories</h5>
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="Rechercher..." id="searchInput">
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Icône</th>
                                <th>Couleur</th>
                                <th>Profils</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="display-4">📁</i>
                                            <p class="mt-2">Aucune catégorie trouvée</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr data-category-id="<?= $category['id'] ?>">
                                        <td><?= $category['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="category-icon me-2" style="background-color: <?= $category['couleur'] ?>">
                                                    <?= $category['icone'] ?? '📁' ?>
                                                </div>
                                                <strong><?= htmlspecialchars($category['nom']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                  title="<?= htmlspecialchars($category['description'] ?? '') ?>">
                                                <?= htmlspecialchars($category['description'] ?? 'Aucune description') ?>
                                            </span>
                                        </td>
                                        <td><?= $category['icone'] ?? '📁' ?></td>
                                        <td>
                                            <span class="color-badge" style="background-color: <?= $category['couleur'] ?>">
                                                <?= $category['couleur'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info" data-bs-toggle="tooltip" 
                                                  title="Cliquez pour voir les détails">
                                                <span class="profile-count" data-category-id="<?= $category['id'] ?>">-</span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $category['actif'] ? 'success' : 'secondary' ?>">
                                                <?= $category['actif'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="form.php?id=<?= $category['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   data-bs-toggle="tooltip" title="Modifier">
                                                    ✏️
                                                </a>
                                                
                                                <form method="POST" action="toggle.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                    <button type="submit" 
                                                            class="btn btn-outline-<?= $category['actif'] ? 'warning' : 'success' ?>"
                                                            data-bs-toggle="tooltip" 
                                                            title="<?= $category['actif'] ? 'Désactiver' : 'Activer' ?>">
                                                        <?= $category['actif'] ? '⏸️' : '▶️' ?>
                                                    </button>
                                                </form>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger delete-btn" 
                                                        data-category-id="<?= $category['id'] ?>"
                                                        data-category-name="<?= htmlspecialchars($category['nom']) ?>"
                                                        data-bs-toggle="tooltip" title="Supprimer">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="categoryName"></strong> ?</p>
                    <div class="alert alert-warning">
                        <small>⚠️ Cette action est irréversible et supprimera tous les profils associés.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" action="delete.php" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Delete modal
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const categoryId = this.dataset.categoryId;
                const categoryName = this.dataset.categoryName;
                
                document.getElementById('categoryName').textContent = categoryName;
                document.getElementById('deleteId').value = categoryId;
                
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Load profile counts
        async function loadProfileCounts() {
            const counters = document.querySelectorAll('.profile-count');
            
            for (const counter of counters) {
                const categoryId = counter.dataset.categoryId;
                try {
                    const response = await fetch(`stats.php?id=${categoryId}`);
                    const data = await response.json();
                    
                    const total = (data.nb_prestataires || 0) + (data.nb_cvs || 0);
                    counter.textContent = total;
                } catch (error) {
                    counter.textContent = '0';
                }
            }
        }

        // Load counts on page load
        document.addEventListener('DOMContentLoaded', loadProfileCounts);
    </script>

    <style>
        :root {
            --primary-color: #0099FF;
            --primary-dark: #000033;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --border-radius-lg: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 153, 255, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            --font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
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
        
        .search-box {
            width: 250px;
        }
        
        .category-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        
        .color-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-size: 0.75rem;
            color: white;
            text-align: center;
            line-height: 18px;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }
    </style>
</body>
</html>