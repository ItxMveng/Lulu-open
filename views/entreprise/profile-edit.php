<?php $profile = $profile ?? []; ?>
<section class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Mon profil entreprise</h1>
        <a class="btn btn-outline-primary" href="<?= e(url('/entreprise/offres')) ?>">Gérer mes offres</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5">Photo</h2>
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="img-fluid rounded mb-3" src="<?= e(url('/' . $profile['photo_path'])) ?>" alt="Photo d'entreprise">
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/profile/photo')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input class="form-control mb-3" type="file" name="photo" accept="image/png,image/jpeg" required>
                        <button class="btn btn-outline-primary w-100" type="submit">Mettre à jour la photo</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?php View::partial('entreprise/profile-mode', ['profile' => $profile]); ?>
                    <hr>
                    <form method="post" action="<?= e(url('/profile/cv')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <label class="form-label" for="cv">CV PDF principal</label>
                        <input class="form-control mb-3" type="file" id="cv" name="cv" accept="application/pdf" required>
                        <button class="btn btn-outline-secondary w-100" type="submit">Importer un CV</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <form id="entreprise-profile-form" method="post" action="<?= e(url('/profile/update')) ?>" class="card shadow-sm border-0">
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
                            <label class="form-label" for="bio">Description</label>
                            <textarea class="form-control" id="bio" name="bio" rows="5"><?= e((string) ($profile['bio'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="categories">Catégories</label>
                            <textarea class="form-control" id="categories" name="categories" rows="3"><?= e(implode(', ', json_decode((string) ($profile['categories'] ?? '[]'), true) ?: [])) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="skills">Compétences</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3"><?= e(implode(', ', json_decode((string) ($profile['skills'] ?? '[]'), true) ?: [])) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="languages">Langues</label>
                            <textarea class="form-control" id="languages" name="languages" rows="3"><?= e(implode(', ', json_decode((string) ($profile['languages'] ?? '[]'), true) ?: [])) ?></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="hourly_rate">Tarif horaire</label>
                            <input class="form-control" type="number" step="0.01" id="hourly_rate" name="hourly_rate" value="<?= e((string) ($profile['hourly_rate'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="availability">Disponibilité</label>
                            <input class="form-control" type="text" id="availability" name="availability" value="<?= e((string) ($profile['availability'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="portfolio">Portfolio</label>
                            <textarea class="form-control" id="portfolio" name="portfolio" rows="3"><?= e(implode(PHP_EOL, json_decode((string) ($profile['portfolio'] ?? '[]'), true) ?: [])) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="certifications">Certifications</label>
                            <textarea class="form-control" id="certifications" name="certifications" rows="3"><?= e(implode(PHP_EOL, json_decode((string) ($profile['certifications'] ?? '[]'), true) ?: [])) ?></textarea>
                        </div>
                        <div class="col-12 form-check mt-3 ms-2">
                            <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= !isset($profile['is_visible']) || (int) $profile['is_visible'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">Rendre mon profil public</label>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Enregistrer le profil</button>
                </div>
            </form>
        </div>
    </div>
</section>