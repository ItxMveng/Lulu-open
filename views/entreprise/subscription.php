<?php
$subscription = $subscription ?? null;
$status = (string) ($subscription['status'] ?? 'inactive');
$statusMap = ['active' => ['Actif', 'badge-soft-success'], 'inactive' => ['Inactif', 'text-bg-secondary'], 'cancelled' => ['Annulé', 'status-rejetee']];
[$statusLabel, $statusClass] = $statusMap[$status] ?? [$status, 'text-bg-secondary'];
?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h3 mb-4">Mon abonnement</h1>
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <p class="text-secondary small mb-1">Plan actuel</p>
                            <h2 class="h4 mb-0"><?= e((string) ($subscription['plan_name'] ?? 'Aucun plan actif')) ?></h2>
                        </div>
                        <span class="badge <?= e($statusClass) ?> status-badge"><?= e($statusLabel) ?></span>
                    </div>
                    <p class="text-secondary">Les plans <strong>Pro</strong> et <strong>Business</strong> débloquent les modules IA, les offres illimitées et une meilleure visibilité.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= e(url('/pricing')) ?>"><i class="bi bi-arrow-up-circle me-1"></i>Voir les plans</a>
                        <a class="btn btn-outline-secondary" href="<?= e(url('/abonnement/portail')) ?>">Gérer le paiement</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
