<?php /** @var array $services */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Mes prestations</h1>
        <p class="text-secondary mb-0">Décrivez les services que vous proposez. Ils apparaîtront sur votre profil public.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/client/services/nouveau')) ?>"><i class="bi bi-plus-lg me-1"></i>Nouvelle prestation</a>
</div>

<?php if (empty($services)): ?>
    <div class="card">
        <div class="card-body text-center p-5">
            <div class="mb-3" style="font-size:2.5rem;"><i class="bi bi-briefcase text-secondary"></i></div>
            <h2 class="h5">Aucune prestation pour le moment</h2>
            <p class="text-secondary">Ajoutez votre première prestation pour attirer des clients (ex. « Création de logo », « Développement de site vitrine »…).</p>
            <a class="btn btn-primary" href="<?= e(url('/client/services/nouveau')) ?>"><i class="bi bi-plus-lg me-1"></i>Créer une prestation</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($services as $s): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="h5 mb-0"><?= e((string) $s['title']) ?></h2>
                            <?php if ((int) $s['is_active'] === 1): ?>
                                <span class="badge badge-soft-success">Active</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Masquée</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($s['category'])): ?>
                            <div class="mb-2"><span class="badge badge-soft-primary"><?= e((string) $s['category']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($s['description'])): ?>
                            <p class="text-secondary small mb-3"><?= nl2br(e(mb_strimwidth((string) $s['description'], 0, 160, '…'))) ?></p>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-3 text-secondary small mb-3 mt-auto">
                            <span><i class="bi bi-cash-coin me-1"></i><strong class="text-primary"><?= e(Service::formatPrice($s['price'] !== null ? (float) $s['price'] : null, (string) $s['price_type'])) ?></strong></span>
                            <?php if (!empty($s['delivery_days'])): ?>
                                <span><i class="bi bi-clock me-1"></i><?= (int) $s['delivery_days'] ?> j de délai</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/client/services/' . (int) $s['id'] . '/edit')) ?>"><i class="bi bi-pencil me-1"></i>Modifier</a>
                            <form method="post" action="<?= e(url('/client/services/' . (int) $s['id'] . '/delete')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Cette prestation sera définitivement supprimée de votre profil." data-confirm-title="Supprimer la prestation ?" data-confirm-ok="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
