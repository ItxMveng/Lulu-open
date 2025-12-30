<?php
require_once '../../../config/config.php';
requireLogin();

if ($_SESSION['user_type'] !== 'prestataire_candidat') {
    redirect('../../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../../../includes/navbar.php'; ?>
    
    <div class="container mt-5 pt-5">
        <div class="alert alert-info">
            <h4><i class="bi bi-info-circle"></i> Gestion des abonnements</h4>
            <p>La gestion des abonnements sera bientôt disponible.</p>
            <a href="../dashboard.php" class="btn btn-primary">Retour au dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
