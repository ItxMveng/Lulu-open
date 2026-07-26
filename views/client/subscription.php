<section class="py-4">
    <h1 class="mb-4">Mon abonnement client</h1>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="h4"><?= e((string) ($subscription['plan_name'] ?? 'Aucun plan')) ?></h2>
            <p class="text-secondary">Statut: <?= e((string) ($subscription['status'] ?? 'inactive')) ?></p>
            <div class="d-flex gap-3">
                <a class="btn btn-primary" href="<?= e(url('/pricing')) ?>">Changer de plan</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('/abonnement/portail')) ?>">Portail Stripe</a>
            </div>
        </div>
    </div>
</section>