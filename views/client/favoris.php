<?php
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /lulu/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
    </style>
</head>
<body>

<div class="container py-5">
    <a href="dashboard.php" class="btn btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Retour au dashboard
    </a>
    
    <h1 class="mb-4">Mes Favoris</h1>
    
    <div class="text-center py-5">
        <div style="font-size: 5rem;">❤️</div>
        <h3 class="text-muted mt-3">Aucun favori pour le moment</h3>
        <p class="text-muted">Ajoutez des prestataires à vos favoris pour les retrouver facilement</p>
        <a href="recherche-prestataire.php" class="btn btn-primary mt-3">
            <i class="bi bi-search"></i> Rechercher des prestataires
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
