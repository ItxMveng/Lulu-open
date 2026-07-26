<div class="card shadow-sm h-100 border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="h5 mb-1"><?= e((string) ($offer['title'] ?? 'Offre')) ?></h3>
                <p class="text-secondary small mb-2"><?= e((string) ($offer['location'] ?? 'Localisation non précisée')) ?></p>
            </div>
            <span class="badge text-bg-secondary"><?= e((string) ($offer['type'] ?? 'emploi')) ?></span>
        </div>
        <p class="mb-3"><?= e(mb_strimwidth((string) ($offer['description'] ?? ''), 0, 180, '...')) ?></p>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/offres/' . $offer['id'])) ?>">Voir l'offre</a>
    </div>
</div>