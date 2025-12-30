<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../models/Plan.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'admin') {
    header('Location: /lulu/login.php');
    exit;
}

$planModel = new Plan();
$plans = $planModel->getAll(false);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Plans - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

    <div class="admin-content">
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Plans</li>
                </ol>
            </div>
        </nav>
        
        <h1 class="mb-4"><i class="bi bi-card-list"></i> Gestion des Plans d'Abonnement</h1>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Prix Mensuel</th>
                    <th>Prix Annuel</th>
                    <th>Actif</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?= $plan['id'] ?></td>
                    <td><span class="badge" style="background:<?= $plan['couleur_badge'] ?>"><?= htmlspecialchars($plan['nom']) ?></span></td>
                    <td><?= htmlspecialchars($plan['type_utilisateur']) ?></td>
                    <td><?= number_format($plan['prix_mensuel'], 2) ?> €</td>
                    <td><?= number_format($plan['prix_annuel'], 2) ?> €</td>
                    <td><?= $plan['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary">Modifier</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
