<?php
$profile = $profile ?? [];
$listVal = static function ($json): string {
    $arr = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
    return implode(', ', array_map('strval', $arr));
};
?>
<section class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Profil entreprise</h1>
            <p class="text-secondary mb-0">Présentez votre entreprise aux talents.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(url('/entreprise/offres')) ?>"><i class="bi bi-megaphone me-1"></i>Mes offres</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="rounded mb-3" style="width:120px;height:120px;object-fit:cover;" src="<?= e(url('/' . ltrim((string) $profile['photo_path'], '/'))) ?>" alt="Logo">
                    <?php else: ?>
                        <span class="avatar-circle mx-auto mb-3" style="width:96px;height:96px;font-size:2rem;border-radius:14px;"><i class="bi bi-building"></i></span>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/profile/photo')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input class="form-control form-control-sm mb-2" type="file" name="photo" accept="image/png,image/jpeg" required>
                        <button class="btn btn-outline-primary btn-sm w-100" type="submit">Changer le logo</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-4">
                    <?php View::partial('entreprise/profile-mode', ['profile' => $profile]); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form method="post" action="<?= e(url('/profile/update')) ?>" class="card shadow-sm">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom de l'entreprise</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) ($profile['display_name'] ?? auth_user()['name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location">Localisation</label>
                            <input class="form-control" type="text" id="location" name="location" value="<?= e((string) ($profile['location'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bio">Description de l'entreprise</label>
                            <textarea class="form-control" id="bio" name="bio" rows="6" placeholder="Votre activité, votre culture, ce que vous recherchez…"><?= e((string) ($profile['bio'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="categories">Secteurs d'activité</label>
                            <textarea class="form-control" id="categories" name="categories" rows="2" placeholder="Tech, Marketing… (virgules)"><?= e($listVal($profile['categories'] ?? null)) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="languages">Langues de travail</label>
                            <textarea class="form-control" id="languages" name="languages" rows="2" placeholder="Français, Anglais… (virgules)"><?= e($listVal($profile['languages'] ?? null)) ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= !isset($profile['is_visible']) || (int) $profile['is_visible'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_visible">Rendre le profil entreprise visible</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</section>
