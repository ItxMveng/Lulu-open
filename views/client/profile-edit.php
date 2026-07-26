<section class="py-4">
    <h1 class="mb-4">Mon profil client</h1>
    <?php $profile = $profile ?? []; ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Photo</h2>
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="img-fluid rounded mb-3" src="<?= e(url('/' . $profile['photo_path'])) ?>" alt="Photo de profil">
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/profile/photo')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input class="form-control mb-3" type="file" name="photo" accept="image/png,image/jpeg" required>
                        <button class="btn btn-outline-primary w-100" type="submit">Mettre à jour la photo</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <form method="post" action="<?= e(url('/profile/update')) ?>" class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) ($profile['display_name'] ?? auth_user()['name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location">Localisation</label>
                            <input class="form-control" type="text" id="location" name="location" value="<?= e((string) ($profile['location'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bio">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="6"><?= e((string) ($profile['bio'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-12 form-check mt-3 ms-2">
                            <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= !isset($profile['is_visible']) || (int) $profile['is_visible'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">Rendre mon profil public</label>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</section>