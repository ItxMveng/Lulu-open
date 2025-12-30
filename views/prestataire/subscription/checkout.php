<?php
require_once '../../../config/config.php';
requireLogin();

if (!in_array($_SESSION['user_type'], ['prestataire', 'candidat'])) {
    redirect('../../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];

// Récupération de l'abonnement actuel
$currentSubscription = $database->fetch("
    SELECT * FROM abonnements 
    WHERE utilisateur_id = :user_id AND statut = 'actif'
    ORDER BY created_at DESC LIMIT 1
", ['user_id' => $userId]);

$subscriptionPlans = [
    'mensuel' => ['price' => 29.99, 'duration' => '1 mois', 'features' => ['Profil visible', 'Messages illimités', 'Support email']],
    'trimestriel' => ['price' => 79.99, 'duration' => '3 mois', 'features' => ['Profil visible', 'Messages illimités', 'Support prioritaire', 'Badge "Pro"']],
    'annuel' => ['price' => 299.99, 'duration' => '12 mois', 'features' => ['Profil visible', 'Messages illimités', 'Support prioritaire', 'Badge "Pro"', 'Statistiques avancées']]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer l'abonnement - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <a href="../profile/edit.php" class="nav-link">
                <i class="icon">✏️</i> Mon Profil
            </a>
            <a href="../messages/inbox.php" class="nav-link">
                <i class="icon">💬</i> Messages
            </a>
            
            <a href="checkout.php" class="nav-link active">
                <i class="icon">💳</i> Abonnement
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header">
            <h1>Gérer l'abonnement</h1>
            <p class="text-muted">Choisissez votre plan d'abonnement</p>
        </div>

        <?php if ($currentSubscription): ?>
            <div class="admin-card mb-4">
                <div class="card-header">
                    <h5>Abonnement actuel</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6>Plan <?= ucfirst($currentSubscription['type_abonnement']) ?></h6>
                            <p class="text-muted mb-0">
                                Expire le <?= date('d/m/Y', strtotime($currentSubscription['date_fin'])) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success fs-6"><?= formatPrice($currentSubscription['prix']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($subscriptionPlans as $planType => $plan): ?>
                <div class="col-lg-4">
                    <div class="admin-card h-100 <?= $currentSubscription && $currentSubscription['type_abonnement'] === $planType ? 'border-primary' : '' ?>">
                        <div class="card-header text-center">
                            <h5><?= ucfirst($planType) ?></h5>
                            <div class="price-display">
                                <span class="price"><?= formatPrice($plan['price']) ?></span>
                                <small class="text-muted">/ <?= $plan['duration'] ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li class="mb-2">
                                        <i class="text-success">✓</i> <?= $feature ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($currentSubscription && $currentSubscription['type_abonnement'] === $planType): ?>
                                <button class="btn btn-outline-primary w-100" disabled>
                                    Plan actuel
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary w-100" onclick="selectPlan('<?= $planType ?>')">
                                    Choisir ce plan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($currentSubscription): ?>
            <div class="admin-card mt-4">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning" onclick="pauseSubscription()">
                            Suspendre l'abonnement
                        </button>
                        <button class="btn btn-danger" onclick="cancelSubscription()">
                            Annuler l'abonnement
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPlan(planType) {
            if (confirm(`Voulez-vous souscrire au plan ${planType} ?`)) {
                // Simulation de paiement réussi
                alert('Paiement simulé réussi ! Votre abonnement a été activé.');
                location.reload();
            }
        }

        function pauseSubscription() {
            if (confirm('Êtes-vous sûr de vouloir suspendre votre abonnement ?')) {
                alert('Abonnement suspendu. Vous pouvez le réactiver à tout moment.');
            }
        }

        function cancelSubscription() {
            if (confirm('Êtes-vous sûr de vouloir annuler définitivement votre abonnement ?')) {
                alert('Abonnement annulé. Vos données seront conservées pendant 30 jours.');
            }
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
        
        .price-display .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .border-primary {
            border: 2px solid var(--primary-color) !important;
        }
    </style>
</body>
</html>