<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Traitement des actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_plan') {
        $plan = $_POST['plan'];
        $price = floatval($_POST['price']);
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Mise à jour dans le fichier de configuration
        $configFile = __DIR__ . '/../../config/stripe.php';
        $content = file_get_contents($configFile);
        
        // Remplacer les valeurs dans PLANS_CONFIG
        $pattern = "/'$plan' => \[\s*'price' => [0-9.]+,\s*'stripe_price_id' => '[^']*',\s*'period_months' => [0-9]+,\s*'name' => '[^']*',\s*'description' => '[^']*',\s*'savings' => [0-9]+\s*\]/";
        $replacement = "'$plan' => [\n        'price' => $price,\n        'stripe_price_id' => '" . PLANS_CONFIG[$plan]['stripe_price_id'] . "',\n        'period_months' => " . PLANS_CONFIG[$plan]['period_months'] . ",\n        'name' => '$name',\n        'description' => '$description',\n        'savings' => " . PLANS_CONFIG[$plan]['savings'] . "\n    ]";
        
        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($configFile, $content);
        
        $_SESSION['flash_success'] = 'Plan mis à jour avec succès';
        header('Location: plans.php');
        exit;
    }
}

$page_title = "Gestion Plans Stripe - Admin LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
    <style>
    .admin-content {
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    @media (max-width: 991.98px) {
        .admin-content {
            margin-left: 0;
            padding-top: calc(60px + 2rem);
        }
    }
    
    .plan-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: none;
        transition: all 0.3s ease;
    }
    
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .plan-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .badge-monthly { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .badge-quarterly { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .badge-yearly { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= url('views/admin/dashboard.php') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Plans Stripe</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-card-list me-2"></i>Gestion des Plans Stripe
                </h1>
                <p class="text-muted">Configuration des tarifs et abonnements</p>
            </div>
        </div>
        
        <!-- Messages flash -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        
        <!-- Plans Stripe -->
        <div class="row">
            <?php foreach (PLANS_CONFIG as $planKey => $planConfig): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="plan-card">
                        <div class="text-center mb-3">
                            <span class="plan-badge badge-<?= $planKey ?>">
                                <?= $planConfig['name'] ?>
                            </span>
                        </div>
                        
                        <div class="text-center mb-3">
                            <h2 class="mb-0"><?= number_format($planConfig['price'], 2) ?>€</h2>
                            <small class="text-muted"><?= $planConfig['description'] ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Détails :</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="bi bi-calendar3 text-primary"></i> Durée: <?= $planConfig['period_months'] ?> mois</li>
                                <li><i class="bi bi-percent text-success"></i> Économies: <?= $planConfig['savings'] ?>%</li>
                                <li><i class="bi bi-credit-card text-info"></i> ID Stripe: <code><?= $planConfig['stripe_price_id'] ?></code></li>
                            </ul>
                        </div>
                        
                        <div class="d-grid">
                            <button class="btn btn-primary" onclick="editPlan('<?= $planKey ?>')">
                                <i class="bi bi-pencil"></i> Modifier
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Fonctionnalités -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gift text-warning"></i> Fonctionnalités Gratuites
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach (FREE_FEATURES as $feature): ?>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success"></i> <?= $feature ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-star-fill text-warning"></i> Fonctionnalités Premium
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach (PREMIUM_FEATURES as $feature): ?>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success"></i> <?= $feature ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Édition Plan -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Modifier le Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_plan">
                        <input type="hidden" name="plan" id="editPlanKey">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom du plan</label>
                            <input type="text" class="form-control" name="name" id="editPlanName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prix (€)</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="editPlanPrice" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="editPlanDescription" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Note :</strong> Les modifications seront appliquées immédiatement sur le site.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const plansConfig = <?= json_encode(PLANS_CONFIG) ?>;
        
        function editPlan(planKey) {
            const plan = plansConfig[planKey];
            
            document.getElementById('editPlanKey').value = planKey;
            document.getElementById('editPlanName').value = plan.name;
            document.getElementById('editPlanPrice').value = plan.price;
            document.getElementById('editPlanDescription').value = plan.description;
            
            new bootstrap.Modal(document.getElementById('editPlanModal')).show();
        }
    </script>
</body>
</html>
