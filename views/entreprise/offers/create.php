<section class="py-4">
    <h1 class="mb-4">Créer une offre</h1>
    <form method="post" action="<?= e(url('/entreprise/offres')) ?>" class="card shadow-sm border-0">
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
            <button class="btn btn-primary mt-4" type="submit">Publier l'offre</button>
        </div>
    </form>
</section>