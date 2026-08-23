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

        <form method="post" action="<?= e($action) ?>" class="card shadow-sm">
            <div class="card-body p-4">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="title">Titre de la prestation <span class="text-danger">*</span></label>
                    <input class="form-control" id="title" name="title" required maxlength="160" value="<?= e((string) $val('title')) ?>" placeholder="Ex. Création d'un logo professionnel">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="category">Domaine</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">— Aucun —</option>
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= e((string) $cat) ?>" <?= (string) $val('category') === (string) $cat ? 'selected' : '' ?>><?= e((string) $cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Ce qui est inclus, votre méthode, ce qui vous distingue…"><?= e((string) $val('description')) ?></textarea>
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
