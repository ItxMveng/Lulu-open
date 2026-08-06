<section class="py-4">
    <a class="text-secondary small d-inline-flex align-items-center mb-3" href="<?= e(url('/entreprise/offres')) ?>"><i class="bi bi-arrow-left me-1"></i>Retour à mes offres</a>
    <h1 class="h3 mb-1">Publier une offre</h1>
    <p class="text-secondary mb-4">Décrivez le poste pour attirer les bons profils.</p>
    <form method="post" action="<?= e(url('/entreprise/offres')) ?>" class="card shadow-sm">
        <div class="card-body p-4">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="title">Titre</label>
                    <input class="form-control" type="text" id="title" name="title" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="emploi">Emploi</option>
                        <option value="mission">Mission</option>
                        <option value="stage">Stage</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contract_type">Type de contrat</label>
                    <input class="form-control" type="text" id="contract_type" name="contract_type">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Localisation</label>
                    <input class="form-control" type="text" id="location" name="location">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="salary_min">Salaire min</label>
                    <input class="form-control" type="number" step="0.01" id="salary_min" name="salary_min">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="salary_max">Salaire max</label>
                    <input class="form-control" type="number" step="0.01" id="salary_max" name="salary_max">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="skills_required">Compétences requises</label>
                    <textarea class="form-control" id="skills_required" name="skills_required" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="expires_at">Date d'expiration</label>
                    <input class="form-control" type="datetime-local" id="expires_at" name="expires_at">
                </div>
                <div class="col-12 form-check mt-3 ms-2">
                    <input class="form-check-input" type="checkbox" name="remote_ok" id="remote_ok">
                    <label class="form-check-label" for="remote_ok">Télétravail accepté</label>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="8" required></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Publier l'offre</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('/entreprise/offres')) ?>">Annuler</a>
            </div>
        </div>
    </form>
</section>