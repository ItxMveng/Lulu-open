<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisissez votre abonnement - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .pricing-header { max-width: 700px; margin: 3rem auto; text-align: center; }
        .card-pricing { border: none; border-radius: 1rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); transition: all .3s; }
        .card-pricing:hover { transform: translateY(-5px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.2); }
        .card-pricing .card-header { background: transparent; border-bottom: none; padding: 2rem 1.5rem; }
        .card-pricing .card-body { padding: 2rem 1.5rem; }
        .price { font-size: 3rem; font-weight: 700; }
        .price .currency { font-size: 1.5rem; vertical-align: super; }
        .price .period { font-size: 1rem; color: #6c757d; }
        .btn-select-plan { font-size: 1.1rem; font-weight: 600; padding: .8rem 1.5rem; border-radius: 50rem; }
        .modal-content { border-radius: 1rem; }
        .payment-instructions { background-color: #e9ecef; border-radius: .5rem; padding: 1rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="pricing-header">
        <h1 class="display-4">Nos Plans d'Abonnement</h1>
        <p class="lead text-muted">Choisissez le plan qui correspond à vos besoins pour débloquer toutes les fonctionnalités et booster votre visibilité.</p>
    </div>

    <?php if (empty($pricings)): ?>
        <div class="alert alert-warning text-center">Aucun plan d'abonnement n'est disponible pour votre profil et votre devise (<?= htmlspecialchars($currency) ?>) pour le moment.</div>
    <?php else: ?>
        <div class="row justify-content-center">
            <?php foreach ($pricings as $plan): ?>
                <div class="col-lg-4 mb-4">
                    <div class="card card-pricing h-100">
                        <div class="card-header">
                            <h4 class="fw-normal"><?= htmlspecialchars($plan['nom']) ?></h4>
                            <h1 class="price">
                                <span class="currency"><?= htmlspecialchars($plan['devise']) ?></span>
                                <?= htmlspecialchars(number_format($plan['prix'], 2)) ?>
                                <span class="period">/ <?= htmlspecialchars($plan['duree_mois']) ?> mois</span>
                            </h1>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <ul class="list-unstyled mb-4">
                                <?php foreach (explode("\n", $plan['description']) as $feature): ?>
                                    <li class="mb-2"><i class="bi bi-check text-success"></i> <?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button class="btn btn-primary btn-select-plan mt-auto" onclick="selectPlan(<?= $plan['id'] ?>, '<?= $plan['nom'] ?>', '<?= number_format($plan['prix'], 2) . ' ' . $plan['devise'] ?>')">
                                Choisir ce plan
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de soumission -->
<div class="modal fade" id="submissionModal" tabindex="-1" aria-labelledby="submissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="submissionModalLabel">Finaliser votre abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="alert-container"></div>
                <form id="subscriptionForm" enctype="multipart/form-data">
                    <input type="hidden" name="pricing_id" id="pricing_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="display_plan" class="form-label">Plan choisi</label>
                            <input type="text" class="form-control" id="display_plan" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="display_amount" class="form-label">Montant à payer</label>
                            <input type="text" class="form-control" id="display_amount" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Méthode de paiement</label>
                        <select name="payment_method" id="payment_method" class="form-select" required onchange="showPaymentDetails()">
                            <option value="" selected disabled>-- Choisissez une méthode --</option>
                            <option value="virement_bancaire">Virement Bancaire</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>

                    <div id="paymentDetails" class="d-none">
                        <div id="virement_bancaire_details" class="payment-instructions d-none">
                            <h6>Instructions pour le Virement Bancaire</h6>
                            <p>Veuillez effectuer le virement aux coordonnées suivantes :</p>
                            <ul>
                                <li><strong>Banque :</strong> Banque Internationale</li>
                                <li><strong>IBAN :</strong> FR76 3000 4000 0500 0012 3456 789</li>
                                <li><strong>BIC/SWIFT :</strong> BNPAFRPPXXX</li>
                                <li><strong>Bénéficiaire :</strong> LULU-OPEN SAS</li>
                                <li><strong>Référence obligatoire :</strong> ABONNEMENT-<?= $_SESSION['user_id'] ?? '' ?></li>
                            </ul>
                        </div>
                        <div id="mobile_money_details" class="payment-instructions d-none">
                            <h6>Instructions pour Mobile Money</h6>
                            <p>Veuillez envoyer le paiement au numéro suivant :</p>
                            <ul>
                                <li><strong>Opérateur :</strong> Orange Money / Wave</li>
                                <li><strong>Numéro :</strong> +221 77 123 45 67</li>
                                <li><strong>Nom :</strong> LULU-OPEN</li>
                                <li><strong>Référence obligatoire :</strong> ABONNEMENT-<?= $_SESSION['user_id'] ?? '' ?></li>
                            </ul>
                        </div>
                        <div id="paypal_details" class="payment-instructions d-none">
                            <h6>Instructions pour PayPal</h6>
                            <p>Veuillez envoyer le paiement à l'adresse suivante :</p>
                            <ul>
                                <li><strong>Email PayPal :</strong> paypal@lulu-open.com</li>
                                <li><strong>Note obligatoire :</strong> ABONNEMENT-<?= $_SESSION['user_id'] ?? '' ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="proof_document" class="form-label">Preuve de paiement (Capture d'écran, reçu, etc.)</label>
                        <input type="file" name="proof_document" id="proof_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <div class="form-text">Taille maximale : 5MB. Formats autorisés : JPG, PNG, PDF.</div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Soumettre ma demande
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let submissionModal;

    document.addEventListener('DOMContentLoaded', function() {
        submissionModal = new bootstrap.Modal(document.getElementById('submissionModal'));
    });

    function selectPlan(planId, planName, planAmount) {
        document.getElementById('pricing_id').value = planId;
        document.getElementById('display_plan').value = planName;
        document.getElementById('display_amount').value = planAmount;
        submissionModal.show();
    }

    function showPaymentDetails() {
        const paymentMethod = document.getElementById('payment_method').value;
        const allDetails = document.querySelectorAll('.payment-instructions');
        allDetails.forEach(el => el.classList.add('d-none'));

        if (paymentMethod) {
            document.getElementById('paymentDetails').classList.remove('d-none');
            const detailEl = document.getElementById(paymentMethod + '_details');
            if (detailEl) {
                detailEl.classList.remove('d-none');
            }
        } else {
            document.getElementById('paymentDetails').classList.add('d-none');
        }
    }

    document.getElementById('subscriptionForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('btn-spinner');
        const alertContainer = document.getElementById('alert-container');

        alertContainer.innerHTML = '';
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await fetch('/lulu/subscription.php?action=submitRequest', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                showAlert(result.message, 'success');
                form.reset();
                setTimeout(() => {
                    submissionModal.hide();
                    // Optionnel: rediriger ou rafraîchir
                    // window.location.reload(); 
                }, 3000);
            } else {
                showAlert(result.message || 'Une erreur inconnue est survenue.', 'danger');
            }

        } catch (error) {
            console.error('Submission error:', error);
            showAlert('Erreur de communication avec le serveur. Veuillez réessayer.', 'danger');
        } finally {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        alertContainer.append(wrapper);
    }
</script>

</body>
</html>