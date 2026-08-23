<?php
/** @var array|null $service */
/** @var array $categoriesList */
$isEdit = $service !== null;
$action = $isEdit ? url('/client/services/' . (int) $service['id']) : url('/client/services');
// Valeur : ancien input prioritaire (repli après erreur), sinon la prestation, sinon défaut.
$val = static function (string $key, $default = '') use ($service) {
    $old = old($key, null);
    if ($old !== null && $old !== '') { return $old; }
    return $service[$key] ?? $default;
};
$curType = (string) $val('price_type', 'from');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-4">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/client/services')) ?>"><i class="bi bi-arrow-left"></i></a>
            <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier la prestation' : 'Nouvelle prestation' ?></h1>
        </div>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card shadow-sm">
            <div class="card-body p-4">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="title">Titre de la prestation <span class="text-danger">*</span></label>
                    <input class="form-control" id="title" name="title" required maxlength="160" value="<?= e((string) $val('title')) ?>" placeholder="Ex. Création d'un logo professionnel">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="category">Domaine</label>
                    <?php if (empty($categoriesList)): ?>
                        <div class="alert alert-warning py-2 small mb-0">
                            Vous n'avez pas encore de domaine sur votre profil. <a href="<?= e(url('/client/profile/edit')) ?>">Ajoutez-en un</a> pour pouvoir le rattacher à vos prestations.
                        </div>
                    <?php else: ?>
                        <?php
                        $curCat = (string) $val('category');
                        $opts = $categoriesList;
                        if ($curCat !== '' && !in_array($curCat, $opts, true)) { $opts[] = $curCat; } // conserve un domaine retiré du profil
                        ?>
                        <select class="form-select" id="category" name="category">
                            <option value="">— Aucun —</option>
                            <?php foreach ($opts as $cat): ?>
                                <option value="<?= e((string) $cat) ?>" <?= $curCat === (string) $cat ? 'selected' : '' ?>><?= e((string) $cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Seuls les domaines liés à votre profil sont proposés.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0" for="description">Description</label>
                        <button class="btn btn-sm btn-accent" type="button" id="aiDescribe"><i class="bi bi-stars me-1"></i>Rédiger avec l'IA</button>
                    </div>
                    <textarea class="form-control" id="description" name="description" rows="6" placeholder="Ce qui est inclus, votre méthode, ce qui vous distingue…"><?= e((string) $val('description')) ?></textarea>
                    <div id="aiStatus" class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="image">Visuel de la prestation <span class="text-secondary fw-normal small">(optionnel)</span></label>
                    <?php $curImg = (string) ($service['image_path'] ?? ''); ?>
                    <?php if ($curImg !== ''): ?>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="<?= e(url('/' . ltrim($curImg, '/'))) ?>" alt="Visuel" style="width:120px;height:80px;object-fit:cover;border-radius:8px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label small" for="remove_image">Retirer ce visuel</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">L'image est automatiquement redimensionnée et compressée (léger). JPG, PNG, WebP ou GIF.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="price">Prix (€)</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="price" name="price" value="<?= e((string) $val('price')) ?>" placeholder="Laisser vide = sur devis">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="price_type">Type de tarif</label>
                        <select class="form-select" id="price_type" name="price_type">
                            <?php foreach (Service::priceTypes() as $slug => $label): ?>
                                <option value="<?= e($slug) ?>" <?= $curType === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="delivery_days">Délai (jours)</label>
                        <input class="form-control" type="number" min="0" id="delivery_days" name="delivery_days" value="<?= e((string) $val('delivery_days')) ?>" placeholder="Ex. 7">
                    </div>
                </div>

                <div class="form-check form-switch mt-4">
                    <?php $active = $isEdit ? ((int) ($service['is_active'] ?? 1) === 1) : true; ?>
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Prestation active (visible sur mon profil)</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer la prestation' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/client/services')) ?>">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('aiDescribe')?.addEventListener('click', async function () {
    const btn = this, orig = btn.innerHTML;
    const desc = document.getElementById('description');
    const status = document.getElementById('aiStatus');
    const title = (document.getElementById('title')?.value || '').trim();
    const category = document.getElementById('category')?.value || '';
    if (title === '') { status.innerHTML = '<span class="text-danger">Indiquez d\'abord un titre.</span>'; return; }
    const previous = desc.value;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rédaction…';
    status.innerHTML = '';
    try {
        const res = await fetch('<?= e(url('/client/services/ia/description')) ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _csrf_token: '<?= e(csrf_token()) ?>', title, category, description: previous })
        });
        const d = await res.json();
        if (d.description) {
            desc.value = d.description;
            status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Description générée. <a href="#" id="undoAi">Annuler</a></span>';
            document.getElementById('undoAi').addEventListener('click', (e) => { e.preventDefault(); desc.value = previous; status.innerHTML = ''; });
        } else {
            status.innerHTML = '<span class="text-danger">' + (d.error || 'Échec.') + '</span>';
        }
    } catch (e) {
        status.innerHTML = '<span class="text-danger">Erreur réseau.</span>';
    } finally {
        btn.disabled = false; btn.innerHTML = orig;
    }
});
</script>
