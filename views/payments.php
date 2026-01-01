<?php
session_start();
require_once '../config/config.php';
require_once '../controllers/PaymentController.php';

// Traitement des actions
$action = $_GET['action'] ?? '';
$result = null;

// Détecter le retour de Stripe
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $action = 'success';
}

switch ($action) {
    case 'checkout':
        $plan = $_GET['plan'] ?? '';
        PaymentController::startCheckout($plan);
        break;
        
    case 'success':
        $sessionId = $_GET['success'] ?? '';
        $result = PaymentController::handleSuccess($sessionId);
        break;
        
    case 'cancel':
        $result = PaymentController::handleCancel();
        break;
        
    case 'cancel_subscription':
        PaymentController::cancelSubscription();
        break;
        
    case 'check_status':
        PaymentController::checkSubscriptionStatus();
        break;
        
    default:
        $result = PaymentController::dashboard();
        break;
}

// Si c'est une action de retour, afficher le résultat
if (in_array($action, ['success', 'cancel'])) {
    $pageTitle = $action === 'success' ? 'Paiement réussi' : 'Paiement annulé';
    // Forcer l'affichage de la page de résultat
    $showResult = true;
} else {
    $pageTitle = 'Abonnement Premium';
    $data = $result;
    $showResult = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .plan-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .plan-header {
            padding: 2rem;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .plan-header.monthly { background: var(--primary-gradient); }
        .plan-header.quarterly { background: var(--success-gradient); }
        .plan-header.yearly { background: var(--warning-gradient); }
        
        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #ff6b6b;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            transform: rotate(10deg);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: rotate(10deg) scale(1); }
            50% { transform: rotate(10deg) scale(1.05); }
        }
        
        .price-display {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .savings-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .feature-list {
            padding: 2rem;
            list-style: none;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: #28a745;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .current-plan {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-active { background: #d1edff; color: #0c5460; }
        .status-free { background: #f8d7da; color: #721c24; }
        
        .btn-upgrade {
            background: var(--primary-gradient);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .result-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            max-width: 600px;
            margin: 2rem auto;
        }
        
        .result-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .success-icon { color: #28a745; }
        .error-icon { color: #dc3545; }
        
        .payment-history {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .security-badge {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <?php if (isset($showResult) && $showResult && isset($result)): ?>
            <!-- Page de résultat -->
            <div class="result-card" data-aos="zoom-in">
                <?php if ($result['success']): ?>
                    <i class="bi bi-check-circle-fill result-icon success-icon"></i>
                    <h2 class="text-success mb-3">Paiement réussi !</h2>
                    <p class="lead"><?= htmlspecialchars($result['message']) ?></p>
                    <?php if (isset($result['plan'])): ?>
                        <div class="alert alert-success mt-3">
                            <strong>Plan activé :</strong> <?= htmlspecialchars($result['plan']['name']) ?><br>
                            <strong>Montant :</strong> <?= number_format($result['plan']['price'], 2) ?>€<br>
                            <strong>Durée :</strong> <?= $result['plan']['period_months'] ?> mois
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <i class="bi bi-x-circle-fill result-icon error-icon"></i>
                    <h2 class="text-danger mb-3">Paiement annulé</h2>
                    <p class="lead"><?= htmlspecialchars($result['message']) ?></p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="payments.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Retour aux abonnements
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Dashboard des paiements -->
            
            <!-- Messages flash -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" data-aos="fade-down">
                    <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" data-aos="fade-down">
                    <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
            
            <!-- Message de succès pour les résultats -->
            <?php if (isset($result) && in_array($action, ['success', 'cancel']) && $result['success']): ?>
                <div class="alert alert-success alert-dismissible fade show" data-aos="fade-down">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($result['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- En-tête -->
            <div class="text-center mb-5" data-aos="fade-down">
                <h1 class="display-4 fw-bold mb-3">💎 Abonnement Premium</h1>
                <p class="lead text-muted">Débloquez toutes les fonctionnalités et boostez votre carrière</p>
            </div>
            
            <!-- Statut actuel -->
            <div class="current-plan" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2">🎆 Abonnement Actuel</h3>
                        <?php if ($data['isActive']): ?>
                            <h4 class="mb-1">Plan Premium <?= ucfirst($data['subscriptionInfo']['subscription_plan'] ?? '') ?></h4>
                            <p class="mb-2 opacity-75">
                                Actif jusqu'au <?= date('d/m/Y', strtotime($data['subscriptionInfo']['subscription_end_date'])) ?>
                            </p>
                            <span class="status-badge status-active">Actif</span>
                        <?php else: ?>
                            <h4 class="mb-1">Plan Gratuit</h4>
                            <p class="mb-2 opacity-75">Accès limité aux fonctionnalités de base</p>
                            <span class="status-badge status-free">Gratuit</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="bi bi-shield-check" style="font-size: 4rem; opacity: 0.7;"></i>
                        <?php if ($data['isActive']): ?>
                            <div class="mt-2">
                                <a href="?action=cancel_subscription" class="btn btn-outline-light btn-sm" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                                    <i class="bi bi-x-circle me-2"></i>Annuler l'abonnement
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Plans disponibles -->
            <?php if (!$data['isActive']): ?>
                <div class="row mb-5">
                    <div class="col-12 text-center mb-4">
                        <h3>🚀 Choisissez votre plan</h3>
                        <p class="text-muted">Paiement sécurisé par Stripe • Activation immédiate</p>
                    </div>
                    
                    <?php foreach ($data['plans'] as $planKey => $plan): ?>
                        <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="<?= array_search($planKey, array_keys($data['plans'])) * 100 ?>">
                            <div class="plan-card h-100">
                                <?php if ($planKey === 'quarterly'): ?>
                                    <div class="popular-badge">POPULAIRE</div>
                                <?php endif; ?>
                                
                                <div class="plan-header <?= $planKey ?>">
                                    <h4><?= $plan['name'] ?></h4>
                                    <div class="price-display">
                                        <?= number_format($plan['price'], 0) ?>€
                                    </div>
                                    <div><?= $plan['description'] ?></div>
                                    <?php if ($plan['savings'] > 0): ?>
                                        <div class="savings-badge">
                                            Économisez <?= $plan['savings'] ?>%
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="feature-list">
                                    <?php foreach ($data['premiumFeatures'] as $feature): ?>
                                        <li>
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($feature) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center p-3">
                                    <a href="?action=checkout&plan=<?= $planKey ?>" class="btn-upgrade w-100">
                                        <i class="bi bi-credit-card me-2"></i>Choisir ce plan
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Badges de sécurité -->
                <div class="security-badges" data-aos="fade-up">
                    <div class="security-badge">
                        <i class="bi bi-shield-check me-2"></i>Paiement sécurisé SSL
                    </div>
                    <div class="security-badge">
                        <i class="bi bi-credit-card me-2"></i>Stripe certifié PCI DSS
                    </div>
                    <div class="security-badge">
                        <i class="bi bi-arrow-clockwise me-2"></i>Annulation à tout moment
                    </div>
                    <div class="security-badge">
                        <i class="bi bi-headset me-2"></i>Support 24/7
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Historique des paiements -->
            <?php if (!empty($data['paiements'])): ?>
                <div class="payment-history" data-aos="fade-up">
                    <h4 class="mb-3">📋 Historique des paiements</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Plan</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['paiements'] as $paiement): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($paiement['created_at'])) ?></td>
                                        <td><?= ucfirst($paiement['plan']) ?></td>
                                        <td><?= number_format($paiement['montant'], 2) ?>€</td>
                                        <td>
                                            <?php
                                            $badgeClass = match($paiement['status']) {
                                                'succeeded' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= ucfirst($paiement['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Auto-refresh du statut d'abonnement
        if (window.location.search.includes('success=')) {
            setTimeout(() => {
                fetch('payments.php?action=check_status')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.isActive) {
                            console.log('Abonnement confirmé actif');
                        }
                    })
                    .catch(console.error);
            }, 2000);
        }
    </script>
</body>
</html>