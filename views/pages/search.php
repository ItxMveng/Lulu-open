<?php
$filters = $filters ?? [];
$profiles = $profiles ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$offers = $offers ?? [];
$activeTab = $activeTab ?? 'profils';
$savedSearches = $savedSearches ?? [];
?>
<section class="py-4">
    <h1 class="mb-4">Recherche</h1>
    <form method="get" action="<?= e(url('/search')) ?>" class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-lg-4"><input class="form-control" type="text" name="q" placeholder="Mots-clés" value="<?= e((string) ($filters['q'] ?? '')) ?>"></div>
                <div class="col-lg-2"><input class="form-control" type="text" name="location" placeholder="Lieu" value="<?= e((string) ($filters['location'] ?? '')) ?>"></div>
                <div class="col-lg-2"><input class="form-control" type="text" name="category" placeholder="Catégorie" value="<?= e((string) ($filters['category'] ?? '')) ?>"></div>
                <div class="col-lg-2"><input class="form-control" type="number" step="0.01" name="rate_min" placeholder="Tarif min" value="<?= e((string) ($filters['rate_min'] ?? '')) ?>"></div>
                <div class="col-lg-2"><button class="btn btn-primary w-100" type="submit">Rechercher</button></div>
                <div class="col-lg-3"><select class="form-select" name="type"><option value="">Tous les profils</option><option value="services" <?= ($filters['type'] ?? '') === 'services' ? 'selected' : '' ?>>Services</option><option value="recrutement" <?= ($filters['type'] ?? '') === 'recrutement' ? 'selected' : '' ?>>Recrutement</option><option value="mixte" <?= ($filters['type'] ?? '') === 'mixte' ? 'selected' : '' ?>>Mixte</option></select></div>
                <div class="col-lg-3"><select class="form-select" name="sort"><option value="pertinence">Pertinence</option><option value="recent" <?= ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' ?>>Plus récents</option><option value="vues" <?= ($filters['sort'] ?? '') === 'vues' ? 'selected' : '' ?>>Plus vus</option></select></div>
                <div class="col-lg-2"><input class="form-control" type="number" name="rate_max" placeholder="Tarif max" value="<?= e((string) ($filters['rate_max'] ?? '')) ?>"></div>
                <div class="col-lg-2 form-check mt-2 ms-2"><input class="form-check-input" type="checkbox" id="available" name="available" <?= !empty($filters['available']) ? 'checked' : '' ?>><label class="form-check-label" for="available">Disponible</label></div>
                <?php if (is_auth()): ?>
                    <div class="col-lg-2 text-end"><button class="btn btn-outline-secondary w-100" type="button" id="saveSearchButton">Sauvegarder</button></div>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if (is_auth() && $savedSearches): ?>
        <div class="alert alert-light border d-flex flex-wrap gap-2 align-items-center">
            <strong>Recherches sauvegardées :</strong>
            <?php foreach ($savedSearches as $saved): ?>
                <span class="badge text-bg-light border"><?= e((string) $saved['name']) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'profils' ? 'active' : '' ?>" href="<?= e(url('/search/profils?' . http_build_query(array_merge($filters, ['tab' => 'profils'])))) ?>">Prestations</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'offres' ? 'active' : '' ?>" href="<?= e(url('/search/offres?' . http_build_query(array_merge($filters, ['tab' => 'offres'])))) ?>">Offres d'emploi</a></li>
    </ul>

    <?php if ($activeTab === 'offres'): ?>
        <div class="row g-4">
            <?php foreach ($offers as $offer): ?>
                <div class="col-lg-4"><?php View::partial('components/offer-card', ['offer' => $offer]); ?></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach (($profiles['items'] ?? []) as $profile): ?>
                <div class="col-lg-4"><?php View::partial('components/profile-card', ['profile' => $profile]); ?></div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <span class="text-secondary">Résultats: <?= e((string) ($profiles['total'] ?? 0)) ?></span>
            <span class="text-secondary">Page <?= e((string) ($profiles['page'] ?? 1)) ?> / <?= e((string) ($profiles['pages'] ?? 1)) ?></span>
        </div>
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
    if (response.ok) {
        window.location.reload();
    }
});
</script>
<?php endif; ?>