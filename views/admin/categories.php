<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

global $database;
$categories = $database->fetchAll("SELECT * FROM categories_services ORDER BY nom ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Catégories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

    <div class="admin-content">
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Catégories</li>
                </ol>
            </div>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-tags-fill me-2"></i>Gestion des Catégories</h1>
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-2"></i>Nouvelle Catégorie
            </button>
        </div>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-custom">
            <div class="card-header-custom">
                <h5 class="mb-0">Liste des Catégories (<?= count($categories) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Icône</th>
                            <th>Nombre d'utilisations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td>
                                <strong>
                                    <a href="#" onclick="showCategoryUsers(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nom']) ?>')" 
                                       class="text-decoration-none text-primary">
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?= htmlspecialchars($cat['description'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($cat['icone']): ?>
                                    <i class="<?= htmlspecialchars($cat['icone']) ?>" style="font-size: 1.5rem; color: #0099FF;"></i>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Compter le nombre total d'utilisateurs uniques liés à cette catégorie
                                $totalUsers = $database->fetchColumn("
                                    SELECT COUNT(DISTINCT user_id) FROM (
                                        SELECT utilisateur_id as user_id FROM profils_prestataires WHERE categorie_id = ?
                                        UNION
                                        SELECT utilisateur_id as user_id FROM cvs WHERE categorie_id = ?
                                    ) as combined_users
                                ", [$cat['id'], $cat['id']]);
                                ?>
                                <?php if ($totalUsers > 0): ?>
                                    <span class="badge bg-info"><?= $totalUsers ?> utilisateurs</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Non utilisée</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary btn-action" onclick="editCategory(<?= $cat['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Catégorie -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="../../api/admin-categories.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icône (classe Bootstrap Icons)</label>
                            <input type="text" class="form-control" name="icone" placeholder="bi bi-briefcase">
                            <small class="text-muted">Ex: bi bi-briefcase, bi bi-code-slash</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Utilisateurs de la Catégorie -->
    <div class="modal fade" id="categoryUsersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Utilisateurs de la catégorie : <span id="categoryName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="categoryUsersContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier Catégorie -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icône (classe Bootstrap Icons)</label>
                            <input type="text" class="form-control" id="edit_icone" name="icone" placeholder="bi bi-briefcase">
                            <small class="text-muted">Ex: bi bi-briefcase, bi bi-code-slash</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afficher message de succès
        <?php if (isset($_GET['success'])): ?>
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            Catégorie créée avec succès!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
        <?php endif; ?>

        function showCategoryUsers(categoryId, categoryName) {
            document.getElementById('categoryName').textContent = categoryName;
            document.getElementById('categoryUsersContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            `;
            
            fetch('../../api/admin-categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_users', id: categoryId})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    if (data.users.length === 0) {
                        html = '<p class="text-muted text-center">Aucun utilisateur dans cette catégorie</p>';
                    } else {
                        html = `
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Type</th>
                                            <th>Email</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        data.users.forEach(user => {
                            const statusClass = user.statut === 'Actif' ? 'success' : 'secondary';
                            let typeClass = 'secondary';
                            let roleText = user.source;
                            
                            if (user.source === 'prestataire') {
                                typeClass = 'primary';
                                roleText = 'Prestataire';
                            } else if (user.source === 'candidat') {
                                typeClass = 'info';
                                roleText = 'Candidat';
                            }
                            
                            html += `
                                <tr>
                                    <td><strong>${user.prenom} ${user.nom}</strong></td>
                                    <td><span class="badge bg-${typeClass}">${roleText}</span></td>
                                    <td>${user.email}</td>
                                    <td><span class="badge bg-${statusClass}">${user.statut}</span></td>
                                </tr>
                            `;
                        });
                        html += '</tbody></table></div>';
                    }
                    document.getElementById('categoryUsersContent').innerHTML = html;
                } else {
                    document.getElementById('categoryUsersContent').innerHTML = 
                        '<p class="text-danger">Erreur: ' + data.message + '</p>';
                }
            })
            .catch(e => {
                document.getElementById('categoryUsersContent').innerHTML = 
                    '<p class="text-danger">Erreur de connexion</p>';
            });
            
            new bootstrap.Modal(document.getElementById('categoryUsersModal')).show();
        }

        function editCategory(id) {
            fetch('../../api/admin-categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get', id: id})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const cat = data.data;
                    document.getElementById('edit_id').value = cat.id;
                    document.getElementById('edit_nom').value = cat.nom;
                    document.getElementById('edit_description').value = cat.description || '';
                    document.getElementById('edit_icone').value = cat.icone || '';
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(e => alert('Erreur de connexion'));
        }

        function deleteCategory(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
                fetch('../../api/admin-categories.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', id: id})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(e => alert('Erreur de connexion'));
            }
        }

        // Gestion du formulaire de modification
        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                action: 'update',
                id: formData.get('id'),
                nom: formData.get('nom'),
                description: formData.get('description'),
                icone: formData.get('icone')
            };
            
            fetch('../../api/admin-categories.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(e => alert('Erreur de connexion'));
        });
    </script>
</body>
</html>
