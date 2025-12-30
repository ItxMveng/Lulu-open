<?php
require_once '../../config/config.php';
requireRole('admin');

$userId = $_GET['id'] ?? null;
if (!$userId) {
    flashMessage('ID utilisateur manquant', 'error');
    redirect('users.php');
}

global $database;

$user = $database->fetch("
    SELECT u.*, l.ville, l.region, l.code_postal
    FROM utilisateurs u
    LEFT JOIN localisations l ON u.localisation_id = l.id
    WHERE u.id = :id
", ['id' => $userId]);

if (!$user) {
    flashMessage('Utilisateur non trouvé', 'error');
    redirect('users.php');
}

// Récupération des détails selon le type
$profileDetails = null;
if ($user['type_utilisateur'] === 'prestataire') {
    $profileDetails = $database->fetch("
        SELECT pp.*, c.nom as categorie_nom
        FROM profils_prestataires pp
        LEFT JOIN categories_services c ON pp.categorie_id = c.id
        WHERE pp.utilisateur_id = :id
    ", ['id' => $userId]);
} elseif ($user['type_utilisateur'] === 'candidat') {
    $profileDetails = $database->fetch("
        SELECT cv.*, c.nom as categorie_nom
        FROM cvs cv
        LEFT JOIN categories_services c ON cv.categorie_id = c.id
        WHERE cv.utilisateur_id = :id
    ", ['id' => $userId]);
}

// Abonnements
$subscriptions = $database->fetchAll("
    SELECT * FROM abonnements 
    WHERE utilisateur_id = :id 
    ORDER BY created_at DESC
", ['id' => $userId]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail Utilisateur - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Administration</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="categories/index.php" class="nav-link">
                <i class="icon">📁</i> Catégories
            </a>
            <a href="users.php" class="nav-link active">
                <i class="icon">👥</i> Utilisateurs
            </a>
            <a href="subscriptions-unified.php" class="nav-link">
                <i class="icon">💳</i> Abonnements
            </a>
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="content-header d-flex justify-content-between align-items-center">
            <div>
                <h1>Détail Utilisateur</h1>
                <p class="text-muted"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
            </div>
            <a href="users.php" class="btn btn-secondary">← Retour</a>
        </div>

        <div class="row g-4">
            <!-- Informations générales -->
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="card-header">
                        <h5>Informations générales</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['telephone'] ?? 'Non renseigné') ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-<?= getUserTypeBadge($user['type_utilisateur']) ?>">
                                        <?= ucfirst($user['type_utilisateur']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-<?= getStatusBadge($user['statut']) ?>">
                                        <?= ucfirst($user['statut']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Localisation</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($user['ville'] ?? 'Non définie') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($profileDetails): ?>
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h5>Détails du profil <?= $user['type_utilisateur'] ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user['type_utilisateur'] === 'prestataire'): ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Titre professionnel</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($profileDetails['titre_professionnel'] ?? '') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($profileDetails['categorie_nom'] ?? '') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tarif horaire</label>
                                    <p class="form-control-plaintext"><?= formatPrice($profileDetails['tarif_horaire'] ?? 0) ?></p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($profileDetails['description_services'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php elseif ($user['type_utilisateur'] === 'candidat'): ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Poste recherché</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($profileDetails['titre_poste_recherche'] ?? '') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($profileDetails['categorie_nom'] ?? '') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Salaire souhaité</label>
                                    <p class="form-control-plaintext"><?= formatPrice($profileDetails['salaire_souhaite'] ?? 0) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions et abonnements -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <div class="card-header">
                        <h5>Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($user['statut'] === 'actif'): ?>
                                <button class="btn btn-warning" onclick="suspendUser(<?= $user['id'] ?>)">
                                    🚫 Suspendre
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success" onclick="activateUser(<?= $user['id'] ?>)">
                                    ✅ Activer
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-primary" onclick="editUser(<?= $user['id'] ?>)">
                                ✏️ Modifier
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($subscriptions)): ?>
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h5>Abonnements</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($subscriptions as $sub): ?>
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= ucfirst($sub['type_abonnement']) ?></strong><br>
                                        <small class="text-muted"><?= formatPrice($sub['prix']) ?></small>
                                    </div>
                                    <span class="badge bg-<?= getSubscriptionStatusBadge($sub['statut']) ?>">
                                        <?= ucfirst($sub['statut']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function suspendUser(id) {
            if (confirm('Êtes-vous sûr de vouloir suspendre cet utilisateur ?')) {
                updateUserStatus(id, 'suspendu');
            }
        }

        function activateUser(id) {
            if (confirm('Êtes-vous sûr de vouloir activer cet utilisateur ?')) {
                updateUserStatus(id, 'actif');
            }
        }

        function editUser(id) {
            alert('Fonctionnalité d\'édition à implémenter');
        }

        function updateUserStatus(id, status) {
            fetch('../../api/admin-users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_status',
                    user_id: id,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
        }
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
    </style>
</body>
</html>

<?php
function getUserTypeBadge($type) {
    switch ($type) {
        case 'admin': return 'danger';
        case 'prestataire': return 'primary';
        case 'candidat': return 'info';
        default: return 'secondary';
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'actif': return 'success';
        case 'suspendu': return 'danger';
        default: return 'warning';
    }
}

function getSubscriptionStatusBadge($status) {
    switch ($status) {
        case 'actif': return 'success';
        case 'expire': return 'warning';
        case 'suspendu': return 'danger';
        default: return 'secondary';
    }
}
?>