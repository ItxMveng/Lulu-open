<?php
$profile = $profile ?? [];
$cvDocuments = $cvDocuments ?? [];
$listVal = static function ($json): string {
    $arr = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
    return implode(', ', array_map('strval', $arr));
};
$multiVal = static function ($json): string {
    $arr = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
    return implode(PHP_EOL, array_map('strval', $arr));
};
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Mon profil</h1>
        <p class="text-secondary mb-0">Complétez votre profil et vos CV pour être visible des entreprises.</p>
    </div>

    <div class="row g-4">
        <!-- Colonne latérale : photo + CV -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="rounded-circle mb-3" style="width:96px;height:96px;object-fit:cover;" src="<?= e(url('/' . ltrim((string) $profile['photo_path'], '/'))) ?>" alt="Photo">
                    <?php else: ?>
                        <span class="avatar-circle mx-auto mb-3" style="width:96px;height:96px;font-size:2rem;"><i class="bi bi-person"></i></span>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/profile/photo')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input class="form-control form-control-sm mb-2" type="file" name="photo" accept="image/png,image/jpeg" required>
                        <button class="btn btn-outline-primary btn-sm w-100" type="submit">Changer la photo</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><i class="bi bi-file-earmark-pdf me-1"></i>Mes CV</h2>
                    <?php if (empty($cvDocuments)): ?>
                        <p class="text-secondary small">Aucun CV importé.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($cvDocuments as $cv): ?>
                                <div class="d-flex align-items-center gap-2 p-2 rounded border">
                                    <i class="bi bi-file-earmark-pdf text-danger"></i>
                                    <a class="small text-truncate flex-grow-1" href="<?= e(url('/' . ltrim((string) $cv['file_path'], '/'))) ?>" target="_blank" rel="noopener"><?= e((string) $cv['file_name']) ?></a>
                                    <?php if ((int) ($cv['is_primary'] ?? 0) === 1): ?>
                                        <span class="badge badge-soft-success">Principal</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/profile/cv/' . (int) $cv['id'] . '/primary')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-link p-0 small" type="submit">Définir principal</button></form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('/profile/cv/' . (int) $cv['id'] . '/delete')) ?>" onsubmit="return confirm('Supprimer ce CV ?');"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0" type="submit" aria-label="Supprimer"><i class="bi bi-trash"></i></button></form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/profile/cv')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input class="form-control form-control-sm mb-2" type="file" name="cv" accept=".pdf,.doc,.docx,image/jpeg,image/png" required>
                        <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="bi bi-upload me-1"></i>Importer un CV</button>
                        <div class="form-text">PDF, Word ou image — 8 Mo max.</div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="col-lg-8">
            <form method="post" action="<?= e(url('/profile/update')) ?>" class="card shadow-sm">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom complet</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) ($profile['display_name'] ?? auth_user()['name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location">Localisation</label>
                            <input class="form-control" type="text" id="location" name="location" value="<?= e((string) ($profile['location'] ?? '')) ?>" placeholder="Ville, télétravail…">
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" for="bio">Présentation</label>
                                <button class="btn btn-sm btn-accent" type="button" id="enhanceBio"><i class="bi bi-stars me-1"></i>Améliorer avec l'IA</button>
                            </div>
                            <textarea class="form-control" id="bio" name="bio" rows="5" placeholder="Décrivez votre parcours, vos atouts… (ou laissez l'IA rédiger à partir de vos compétences)"><?= e((string) ($profile['bio'] ?? '')) ?></textarea>
                            <div id="enhanceStatus" class="form-text"></div>
                        </div>
<?php
$decArr = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
$selCats = $decArr($profile['categories'] ?? null);
$selSkills = $decArr($profile['skills'] ?? null);
$selLangs = $decArr($profile['languages'] ?? null);
$extraSkills = array_values(array_diff($selSkills, $skillsList ?? []));
?>
                        <div class="col-12" id="categoriesWrap" data-max="<?= (int) ($maxCategories ?? 1) ?>">
                            <label class="form-label">Domaines <span class="text-danger">*</span> <span class="text-secondary fw-normal small">(au moins 1)</span></label>
                            <?php View::partial('components/chip-select', ['name' => 'categories', 'options' => $categoriesList ?? [], 'selected' => $selCats]); ?>
                            <div class="form-text" id="categoriesHint">
                                <?php if ((int) ($maxCategories ?? 1) <= 1): ?>
                                    Votre plan gratuit permet <strong>1 domaine</strong>. <a href="<?= e(url('/pricing')) ?>">Passez à un plan supérieur</a> pour en choisir plusieurs.
                                <?php else: ?>
                                    Vous pouvez sélectionner jusqu'à <strong><?= (int) $maxCategories ?> domaines</strong>.
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Compétences</label>
                            <?php View::partial('components/chip-select', ['name' => 'skills', 'options' => $skillsList ?? [], 'selected' => $selSkills]); ?>
                            <input class="form-control form-control-sm mt-2" name="skills_extra" placeholder="Autres compétences (séparées par des virgules)" value="<?= e(implode(', ', $extraSkills)) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Langues</label>
                            <?php View::partial('components/chip-select', ['name' => 'languages', 'options' => $languagesList ?? [], 'selected' => $selLangs]); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="hourly_rate">Tarif horaire (€)</label>
                            <input class="form-control" type="number" step="0.01" id="hourly_rate" name="hourly_rate" value="<?= e((string) ($profile['hourly_rate'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="availability">Disponibilité</label>
                            <input class="form-control" type="text" id="availability" name="availability" value="<?= e((string) ($profile['availability'] ?? '')) ?>" placeholder="Immédiate…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="portfolio">Portfolio (une entrée par ligne)</label>
                            <textarea class="form-control" id="portfolio" name="portfolio" rows="3" placeholder="https://mon-site.com&#10;Projet X — description"><?= e($multiVal($profile['portfolio'] ?? null)) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="certifications">Certifications (une par ligne)</label>
                            <textarea class="form-control" id="certifications" name="certifications" rows="3"><?= e($multiVal($profile['certifications'] ?? null)) ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= !isset($profile['is_visible']) || (int) $profile['is_visible'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_visible">Rendre mon profil visible dans la recherche</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-check-lg me-1"></i>Enregistrer mon profil</button>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
document.getElementById('enhanceBio')?.addEventListener('click', async function () {
    const btn = this, orig = btn.innerHTML, bio = document.getElementById('bio'), status = document.getElementById('enhanceStatus');
    const skills = Array.from(document.querySelectorAll('input[name="skills[]"]:checked')).map(i => i.value)
        .concat((document.querySelector('input[name="skills_extra"]')?.value || '').split(',')).filter(Boolean).join(', ');
    const previous = bio.value;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Amélioration…';
    status.innerHTML = '';
    try {
        const res = await fetch('<?= e(url('/profile/enhance')) ?>', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ _csrf_token: '<?= e(csrf_token()) ?>', bio: previous, skills }) });
        const d = await res.json();
        if (d.bio) { bio.value = d.bio; status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Présentation améliorée. <a href="#" id="undoBio">Annuler</a></span>';
            document.getElementById('undoBio').addEventListener('click', (e) => { e.preventDefault(); bio.value = previous; status.innerHTML = ''; }); }
        else status.innerHTML = '<span class="text-danger">'+(d.error||'Échec.')+'</span>';
    } catch (e) { status.innerHTML = '<span class="text-danger">Erreur réseau.</span>'; }
    finally { btn.disabled = false; btn.innerHTML = orig; }
});

// Limite du nombre de domaines selon le plan
(function () {
    const wrap = document.getElementById('categoriesWrap'); if (!wrap) return;
    const max = parseInt(wrap.getAttribute('data-max') || '1');
    const boxes = wrap.querySelectorAll('input[name="categories[]"]');
    const apply = () => {
        const checked = wrap.querySelectorAll('input[name="categories[]"]:checked').length;
        boxes.forEach(b => { if (!b.checked) b.disabled = checked >= max; });
    };
    boxes.forEach(b => b.addEventListener('change', apply));
    apply();
})();
</script>
