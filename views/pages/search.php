<?php
$filters = $filters ?? [];
$profiles = $profiles ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$offers = $offers ?? [];
$activeTab = $activeTab ?? 'profils';
$savedSearches = $savedSearches ?? [];
?>
<section class="py-4">
    <h1 class="h3 mb-4">Rechercher un talent ou une offre</h1>

    <form method="get" action="<?= e(url('/search')) ?>" class="card shadow-sm mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label small">Mots-clés</label>
                    <input class="form-control" type="text" name="q" placeholder="Métier, compétence…" value="<?= e((string) ($filters['q'] ?? '')) ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label small">Localisation</label>
                    <input class="form-control" type="text" name="location" placeholder="Ville ou télétravail" value="<?= e((string) ($filters['location'] ?? '')) ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label small">Trier par</label>
                    <select class="form-select" name="sort">
                        <option value="pertinence">Pertinence</option>
                        <option value="recent" <?= ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' ?>>Plus récents</option>
                        <option value="vues" <?= ($filters['sort'] ?? '') === 'vues' ? 'selected' : '' ?>>Plus vus</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Rechercher</button>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-3 border-top">
                <input class="form-control form-control-sm" style="max-width: 180px;" type="text" name="category" placeholder="Catégorie" value="<?= e((string) ($filters['category'] ?? '')) ?>">
                <input class="form-control form-control-sm" style="max-width: 130px;" type="number" step="0.01" name="rate_min" placeholder="Tarif min €" value="<?= e((string) ($filters['rate_min'] ?? '')) ?>">
                <input class="form-control form-control-sm" style="max-width: 130px;" type="number" step="0.01" name="rate_max" placeholder="Tarif max €" value="<?= e((string) ($filters['rate_max'] ?? '')) ?>">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="available" name="available" <?= !empty($filters['available']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="available">Disponible uniquement</label>
                </div>
                <?php if (is_auth()): ?>
                    <button class="btn btn-outline-secondary btn-sm ms-auto" type="button" id="saveSearchButton"><i class="bi bi-bookmark-plus me-1"></i>Sauvegarder</button>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if (is_auth() && $savedSearches): ?>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <span class="text-secondary small">Recherches sauvegardées :</span>
            <?php foreach ($savedSearches as $saved): ?>
                <span class="badge badge-soft-primary"><i class="bi bi-bookmark me-1"></i><?= e((string) $saved['name']) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'profils' ? 'active' : '' ?>" href="<?= e(url('/search/profils?' . http_build_query(array_merge($filters, ['tab' => 'profils'])))) ?>"><i class="bi bi-person me-1"></i>Talents & prestations</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'offres' ? 'active' : '' ?>" href="<?= e(url('/search/offres?' . http_build_query(array_merge($filters, ['tab' => 'offres'])))) ?>"><i class="bi bi-briefcase me-1"></i>Offres d'emploi</a></li>
    </ul>

    <?php if ($activeTab === 'offres'): ?>
        <?php if (empty($offers)): ?>
            <?php View::partial('components/empty-state', ['icon' => 'bi-briefcase', 'title' => 'Aucune offre trouvée', 'text' => 'Essayez d\'élargir vos critères de recherche.']); ?>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($offers as $offer): ?>
                    <div class="col-md-6 col-lg-4"><?php View::partial('components/offer-card', ['offer' => $offer]); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php $items = $profiles['items'] ?? []; ?>
        <?php if (empty($items)): ?>
            <?php View::partial('components/empty-state', ['icon' => 'bi-person-x', 'title' => 'Aucun profil trouvé', 'text' => 'Modifiez vos filtres pour découvrir plus de talents.']); ?>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-secondary small"><?= e((string) ($profiles['total'] ?? 0)) ?> résultat(s)</span>
                <span class="text-secondary small">Page <?= e((string) ($profiles['page'] ?? 1)) ?> / <?= e((string) ($profiles['pages'] ?? 1)) ?></span>
            </div>
            <div class="row g-4">
                <?php foreach ($items as $profile): ?>
                    <div class="col-md-6 col-lg-4"><?php View::partial('components/profile-card', ['profile' => $profile]); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if (is_auth()): ?>
<script>
document.getElementById('saveSearchButton')?.addEventListener('click', async () => {
    const name = window.prompt('Nom de cette recherche');
    if (!name) return;
    const params = new URLSearchParams(window.location.search);
    const response = await fetch('<?= e(url('/api/saved-searches')) ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, filters: Object.fromEntries(params.entries()), alert_enabled: false })
    });
    if (response.ok) { window.location.reload(); }
});
</script>
<?php endif; ?>
