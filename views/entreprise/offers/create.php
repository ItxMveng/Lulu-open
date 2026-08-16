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
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0" for="description">Description</label>
                        <button class="btn btn-sm btn-accent" type="button" id="aiDraftBtn"><i class="bi bi-stars me-1"></i>Rédiger avec l'IA</button>
                    </div>
                    <textarea class="form-control" id="description" name="description" rows="8" required></textarea>
                    <div class="form-text">Renseignez au moins le titre et les compétences, puis laissez l'IA proposer une description.</div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Publier l'offre</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('/entreprise/offres')) ?>">Annuler</a>
            </div>
        </div>
    </form>
</section>
<script>
document.getElementById('aiDraftBtn')?.addEventListener('click', async function () {
    const btn = this, orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rédaction…';
    try {
        const res = await fetch('<?= e(url('/entreprise/offres/ia-draft')) ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                _csrf_token: '<?= e(csrf_token()) ?>',
                title: document.getElementById('title').value,
                skills: document.getElementById('skills_required').value,
                contract_type: document.getElementById('contract_type').value,
                sector: document.getElementById('location').value
            })
        });
        const d = await res.json();
        if (d.description) document.getElementById('description').value = d.description;
    } catch (e) {}
    finally { btn.disabled = false; btn.innerHTML = orig; }
});
</script>