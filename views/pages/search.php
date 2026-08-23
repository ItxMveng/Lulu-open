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
                <div class="col-lg-3">
                    <label class="form-label small"><?= t('Mots-clés') ?></label>
                    <input class="form-control" type="text" name="q" placeholder="<?= e(t('Métier, compétence…')) ?>" value="<?= e((string) ($filters['q'] ?? '')) ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label small"><i class="bi bi-flag me-1"></i><?= t('Pays') ?></label>
                    <select class="form-select" name="country">
                        <option value=""><?= t('Tous les pays') ?></option>
                        <?php foreach (($countriesList ?? []) as $pays): ?>
                            <option value="<?= e($pays) ?>" <?= ($filters['country'] ?? '') === $pays ? 'selected' : '' ?>><?= e($pays) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small"><?= t('Ville') ?></label>
                    <input class="form-control" type="text" name="location" placeholder="<?= e(t('Ville…')) ?>" value="<?= e((string) ($filters['location'] ?? '')) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label small"><?= t('Trier par') ?></label>
                    <select class="form-select" name="sort">
                        <option value="pertinence"><?= t('Pertinence') ?></option>
                        <option value="recent" <?= ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' ?>><?= t('Plus récents') ?></option>
                        <option value="vues" <?= ($filters['sort'] ?? '') === 'vues' ? 'selected' : '' ?>><?= t('Plus vus') ?></option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i><?= t('Rechercher') ?></button>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-3 border-top">
                <select class="form-select form-select-sm" style="max-width: 200px;" name="category">
                    <option value=""><?= t('Toutes les catégories') ?></option>
                    <?php foreach (($categoriesList ?? []) as $cat): ?>
                        <option value="<?= e((string) $cat['name']) ?>" <?= ($filters['category'] ?? '') === $cat['name'] ? 'selected' : '' ?>><?= e(t((string) $cat['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
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
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="text-secondary small"><?= t('Recherches sauvegardées :') ?></span>
            <?php foreach ($savedSearches as $saved): ?>
                <span class="badge badge-soft-primary"><i class="bi bi-bookmark me-1"></i><?= e((string) $saved['name']) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Alerte d'emploi : convertir (visiteur -> inscription, connecté -> alerte) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 p-3 rounded-3" style="background: var(--lulu-surface-2);">
        <div class="small mb-0"><i class="bi bi-bell-fill me-1 text-primary"></i><?= t('Recevez par email les nouvelles offres qui correspondent à cette recherche.') ?></div>
        <div class="d-flex align-items-center gap-2">
            <span id="alertStatus" class="small text-success"></span>
            <?php if (is_auth()): ?>
                <button class="btn btn-sm btn-primary" id="createAlertBtn" data-filters='<?= e(json_encode($filters ?? [], JSON_UNESCAPED_UNICODE)) ?>'><i class="bi bi-bell me-1"></i><?= t('Créer une alerte') ?></button>
            <?php else: ?>
                <a class="btn btn-sm btn-primary" href="<?= e(url('/register')) ?>"><i class="bi bi-bell me-1"></i><?= t('Créer une alerte') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'profils' ? 'active' : '' ?>" href="<?= e(url('/search/profils?' . http_build_query(array_merge($filters, ['tab' => 'profils'])))) ?>"><i class="bi bi-person me-1"></i><?= t('Talents & prestations') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= $activeTab === 'offres' ? 'active' : '' ?>" href="<?= e(url('/search/offres?' . http_build_query(array_merge($filters, ['tab' => 'offres'])))) ?>"><i class="bi bi-briefcase me-1"></i><?= t('Offres d\'emploi') ?></a></li>
    </ul>

    <?php if ($activeTab === 'offres'): ?>
        <?php if (empty($offers)): ?>
            <div class="card text-center p-5">
                <div class="mb-3" style="font-size:2.4rem;"><i class="bi bi-search text-secondary"></i></div>
                <h3 class="h5"><?= t('Aucune offre pour cette recherche') ?></h3>
                <p class="text-secondary mb-4 mx-auto" style="max-width:48ch;"><?= t('Élargissez vos critères ou explorez les domaines. Vous pouvez aussi créer une alerte pour être prévenu dès qu\'une offre correspond.') ?></p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a class="btn btn-outline-primary" href="<?= e(url('/search?tab=offres')) ?>"><?= t('Élargir la recherche') ?></a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/categories')) ?>"><?= t('Explorer les domaines') ?></a>
                </div>
            </div>
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
            <div class="card text-center p-5">
                <div class="mb-3" style="font-size:2.4rem;"><i class="bi bi-people text-secondary"></i></div>
                <h3 class="h5"><?= t('Aucun talent pour cette recherche') ?></h3>
                <p class="text-secondary mb-4 mx-auto" style="max-width:48ch;"><?= t('Modifiez vos filtres ou explorez les domaines pour découvrir plus de profils.') ?></p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a class="btn btn-outline-primary" href="<?= e(url('/search?tab=profils')) ?>"><?= t('Élargir la recherche') ?></a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/categories')) ?>"><?= t('Explorer les domaines') ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-secondary small"><?= e((string) ($profiles['total'] ?? 0)) ?> <?= t('résultat(s)') ?></span>
                <span class="text-secondary small"><?= t('Page') ?> <?= e((string) ($profiles['page'] ?? 1)) ?> / <?= e((string) ($profiles['pages'] ?? 1)) ?></span>
            </div>
            <div class="row g-4">
                <?php foreach ($items as $profile): ?>
                    <div class="col-md-6 col-lg-4"><?php View::partial('components/profile-card', ['profile' => $profile]); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
document.getElementById('createAlertBtn')?.addEventListener('click', async function () {
    var btn = this, status = document.getElementById('alertStatus');
    var filters = {};
    try { filters = JSON.parse(btn.getAttribute('data-filters') || '{}'); } catch (e) {}
    var name = ((filters.q || '') + (filters.location ? ' · ' + filters.location : '')).trim() || <?= json_encode(t('Alerte emploi')) ?>;
    var orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    try {
        var res = await fetch('<?= e(url('/api/saved-searches')) ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, filters: filters, alert_enabled: true, _csrf_token: <?= json_encode(csrf_token()) ?> })
        });
        var d = await res.json();
        if (res.ok && d.success) {
            status.className = 'small text-success';
            status.textContent = <?= json_encode(t('Alerte créée — vous serez prévenu par email.')) ?>;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>' + <?= json_encode(t('Alerte créée')) ?>;
        } else {
            status.className = 'small text-danger'; status.textContent = d.error || 'Erreur'; btn.disabled = false; btn.innerHTML = orig;
        }
    } catch (e) {
        status.className = 'small text-danger'; status.textContent = <?= json_encode(t('Erreur réseau.')) ?>; btn.disabled = false; btn.innerHTML = orig;
    }
});
</script>

<?php if (is_auth()): ?>
<script>
document.getElementById('saveSearchButton')?.addEventListener('click', async () => {
    const name = window.prompt('Nom de cette recherche');
    if (!name) return;
    const params = new URLSearchParams(window.location.search);
    const response = await fetch('<?= e(url('/api/saved-searches')) ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, filters: Object.fromEntries(params.entries()), alert_enabled: false, _csrf_token: <?= json_encode(csrf_token()) ?> })
    });
    if (response.ok) { window.location.reload(); }
});
</script>
<?php endif; ?>
