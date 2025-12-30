<?php
require_once '../../config/config.php';
require_once '../../models/Subscription.php';
requireLogin();

if ($_SESSION['user_type'] !== 'prestataire_candidat') {
    redirect('../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];

$subscription = new Subscription($database);
$user = $database->fetch("SELECT u.*, l.pays FROM utilisateurs u 
                          LEFT JOIN localisations l ON u.localisation_id = l.id 
                          WHERE u.id = ?", [$userId]);

$currency = $user['devise'] ?? 'EUR';
$role = $user['type_utilisateur'];

$pricings = $subscription->getLocalizedPricings($role, $currency);
$subscriptionStatus = $subscription->getUserSubscriptionStatus($userId);

$currencySymbols = [
    'EUR' => '€', 'USD' => '$', 'CHF' => 'CHF', 'MAD' => 'DH',
    'XOF' => 'CFA', 'XAF' => 'FCFA', 'CAD' => 'CAD', 'GBP' => '£'
];
$symbol = $currencySymbols[$currency] ?? $currency;

$paymentMethods = [
    'Virement' => [
        'name' => 'Virement Bancaire',
        'details' => "IBAN: FR76 1234 5678 9012 3456 7890 123\nBIC: BNPAFRPPXXX\nBénéficiaire: LULU-OPEN SARL"
    ],
    'PayPal' => [
        'name' => 'PayPal',
        'details' => "Email PayPal: payments@lulu-open.com"
    ],
    'Mobile Money' => [
        'name' => 'Mobile Money',
        'details' => "Orange Money: +225 07 XX XX XX XX\nMTN Money: +237 6XX XX XX XX"
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Abonnement - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Dual</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="messages/inbox.php" class="nav-link">
                <i class="bi bi-chat-dots"></i> Messages
            </a>
            <a href="abonnement.php" class="nav-link active">
                <i class="bi bi-credit-card"></i> Abonnement
            </a>
            <a href="settings.php" class="nav-link">
                <i class="bi bi-gear"></i> Paramètres
            </a>
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <h1 class="mb-4">🎯 Mon Abonnement</h1>

        <?php if (isset($subscriptionStatus['subscription_status']) && $subscriptionStatus['subscription_status'] === 'Actif'): ?>
            <?php
            $daysLeft = ceil((strtotime($subscriptionStatus['subscription_end_date']) - time()) / 86400);
            $isExpiringSoon = $daysLeft <= 7;
            ?>
            <div class="alert <?= $isExpiringSoon ? 'alert-danger' : 'alert-success' ?> mb-4">
                <?php if ($isExpiringSoon): ?>
                    ⚠️ URGENT: Votre abonnement expire dans <?= $daysLeft ?> jours (<?= date('d/m/Y', strtotime($subscriptionStatus['subscription_end_date'])) ?>). Renouvelez maintenant !
                <?php else: ?>
                    ✅ Votre abonnement est ACTIF jusqu'au <?= date('d/m/Y', strtotime($subscriptionStatus['subscription_end_date'])) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <?php foreach ($pricings as $index => $pricing): ?>
                <div class="col-md-4">
                    <div class="card pricing-card <?= $index === 1 ? 'popular' : '' ?>">
                        <?php if ($index === 1): ?>
                            <div class="popular-badge">POPULAIRE</div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h3 class="mb-3"><?= $pricing['duration_months'] ?> mois</h3>
                            <div class="price mb-4">
                                <?= number_format($pricing['price'], 2) ?><span class="currency"><?= $symbol ?></span>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Profil visible 24/7</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Messagerie illimitée</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Support prioritaire</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Badge vérifié</li>
                                <?php if ($pricing['duration_months'] >= 6): ?>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Mise en avant hebdomadaire</li>
                                <?php endif; ?>
                                <?php if ($pricing['duration_months'] === 12): ?>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Statistiques avancées</li>
                                    <li class="mb-2"><i class="bi bi-star-fill text-warning"></i> 2 mois OFFERTS</li>
                                <?php endif; ?>
                            </ul>
                            <button class="btn btn-primary w-100" onclick="openModal(<?= $pricing['duration_months'] ?>, <?= $pricing['price'] ?>, '<?= $currency ?>', <?= $pricing['id'] ?>)">
                                Souscrire
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="subscriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finaliser votre abonnement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="subscriptionForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="duration_months" id="duration_months">
                        <input type="hidden" name="amount_paid" id="amount_paid">
                        <input type="hidden" name="pricing_id" id="pricing_id">
                        <input type="hidden" name="currency" value="<?= $currency ?>">

                        <div class="mb-3">
                            <label class="form-label">Durée sélectionnée</label>
                            <input type="text" class="form-control" id="display_duration" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Montant à payer</label>
                            <input type="text" class="form-control" id="display_amount" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Moyen de paiement *</label>
                            <select name="payment_method" id="payment_method" class="form-select" required onchange="showPaymentDetails()">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($paymentMethods as $key => $method): ?>
                                    <option value="<?= $key ?>"><?= $method['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="payment_details_container" style="display:none;">
                            <div class="alert alert-info">
                                <pre id="payment_details" style="margin:0; white-space:pre-wrap;"></pre>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preuve de paiement (JPG, PNG, PDF - Max 5MB) *</label>
                            <input type="file" name="proof_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Soumettre ma demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generic Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="messageModalBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const paymentMethods = <?= json_encode($paymentMethods) ?>;
        let modal;

        document.addEventListener('DOMContentLoaded', function() {
            modal = new bootstrap.Modal(document.getElementById('subscriptionModal'));
            messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        });

        function openModal(duration, price, currency, pricingId) {
            document.getElementById('duration_months').value = duration;
            document.getElementById('amount_paid').value = price;
            document.getElementById('pricing_id').value = pricingId;
            document.getElementById('display_duration').value = duration + ' mois';
            document.getElementById('display_amount').value = price + ' ' + currency;
            modal.show();
        }

        function showPaymentDetails() {
            const method = document.getElementById('payment_method').value;
            const container = document.getElementById('payment_details_container');
            const details = document.getElementById('payment_details');
            
            if (method && paymentMethods[method]) {
                details.textContent = paymentMethods[method].details;
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMessageModal(title, message) {
            document.getElementById('messageModalLabel').textContent = title;
            document.getElementById('messageModalBody').textContent = message;
            messageModal.show();
        }

        document.getElementById('subscriptionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const response = await fetch('../../controllers/SubscriptionController.php?action=submitRequest', {
            method: 'POST',
            body: formData
        });

        const text = await response.text(); // <-- Récupère tout avant JSON
        console.log("RAW RESPONSE:", text); // <-- Important pour voir l'erreur

        const result = JSON.parse(text); // transforme en JSON

        if (result.success) {
            showMessageModal('Succès', result.message);
            location.reload();
        } else { // Si le serveur renvoie success: false
            showMessageModal('Erreur', result.message); // Affiche directement le message d'erreur du serveur
        }
    } catch (error) {
        showMessageModal('Erreur Système', 'Une erreur inattendue est survenue. Veuillez vérifier la console pour plus de détails.');
    }
});

    </script>

    <style>
        body { background: #f8f9fa; }
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #000033, #0099FF);
            color: white;
            padding: 2rem 0;
        }
        .sidebar-header { padding: 0 2rem; margin-bottom: 2rem; }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .pricing-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s;
            position: relative;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,153,255,0.15);
        }
        .pricing-card.popular {
            border-color: #0099FF;
        }
        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #0099FF;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .price {
            font-size: 3rem;
            font-weight: bold;
            color: #0099FF;
        }
        .currency {
            font-size: 1.5rem;
        }
    </style>
</body>
</html>
