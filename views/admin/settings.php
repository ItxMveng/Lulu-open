<?php
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'admin') {
    header('Location: /lulu/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres Système - Admin</title>
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
                    <li class="breadcrumb-item active">Paramètres</li>
                </ol>
            </div>
        </nav>
        
        <h1 class="mb-4"><i class="bi bi-gear-fill me-2"></i>Paramètres Système</h1>
        
        <div class="card">
            <div class="card-body">
                <h5>Configuration Générale</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nom du Site</label>
                        <input type="text" class="form-control" value="LULU-OPEN">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Admin</label>
                        <input type="email" class="form-control" value="admin@lulu-open.com">
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
