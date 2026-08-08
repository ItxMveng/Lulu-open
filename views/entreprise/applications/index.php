<?php
$applications = $applications ?? [];
$statusOptions = ['en_attente' => 'En attente', 'vue' => 'Vue', 'entretien' => 'Entretien', 'acceptee' => 'Acceptée', 'rejetee' => 'Refusée'];
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Candidatures reçues</h1>
        <p class="text-secondary mb-0"><?= count($applications) ?> candidature(s)</p>
    </div>

    <?php if (empty($applications)): ?>
        <?php View::partial('components/empty-state', [
            'icon' => 'bi-people',
            'title' => 'Aucune candidature pour le moment',
            'text' => 'Publiez des offres pour attirer les talents.',
            'actionUrl' => url('/entreprise/offres/new'),
            'actionLabel' => 'Publier une offre',
        ]); ?>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-surface-2">
                        <tr><th class="ps-4">Offre</th><th>Reçue le</th><th>CV</th><th>Statut</th><th class="text-end pe-4">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= e((string) $application['title']) ?></td>
                            <td class="text-secondary"><?= e(date('d/m/Y', strtotime((string) ($application['created_at'] ?? 'now')))) ?></td>
                            <td>
                                <?php if (!empty($application['cv_path'])): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/' . ltrim((string) $application['cv_path'], '/'))) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>Voir</a>
                                <?php else: ?>
                                    <span class="text-secondary small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php View::partial('components/status-badge', ['status' => (string) $application['status']]); ?></td>
                            <td class="pe-4">
                                <div class="d-flex gap-2 justify-content-end align-items-center">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-analyze="<?= (int) $application['id'] ?>" data-title="<?= e((string) $application['title']) ?>"><i class="bi bi-robot me-1"></i>Analyser</button>
                                    <form method="post" action="<?= e(url('/entreprise/candidatures/' . (int) $application['id'] . '/status')) ?>" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <select class="form-select form-select-sm" style="max-width: 140px;" name="status">
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?= e($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">OK</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Modal d'analyse IA -->
<div class="modal fade" id="analyzeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-robot me-2"></i>Analyse IA — <span id="analyzeTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="analyzeBody">
                <div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>Analyse en cours…</div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= e(csrf_token()) ?>';
const modalEl = document.getElementById('analyzeModal');
const modal = new bootstrap.Modal(modalEl);
const body = document.getElementById('analyzeBody');
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const chips = (arr, cls) => (arr && arr.length) ? '<div class="d-flex flex-wrap gap-2">' + arr.map(x => `<span class="badge ${cls}">${esc(x)}</span>`).join('') + '</div>' : '<span class="text-secondary small">—</span>';

document.querySelectorAll('[data-analyze]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-analyze');
        document.getElementById('analyzeTitle').textContent = btn.getAttribute('data-title') || '';
        body.innerHTML = '<div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>Analyse en cours…</div>';
        modal.show();
        try {
            const res = await fetch('<?= e(url('/entreprise/candidatures')) ?>/' + id + '/analyser', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ _csrf_token: CSRF }),
            });
            const d = await res.json();
            if (!res.ok) { body.innerHTML = `<div class="alert alert-danger mb-0">${esc(d.error || 'Erreur.')}</div>`; return; }
            const score = Math.max(0, Math.min(100, d.match_score || 0));
            body.innerHTML = `
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-n" style="font-size:2rem;">${score}<span class="fs-6 text-secondary">/100</span></div>
                    <div class="progress flex-grow-1" style="height:10px;"><div class="progress-bar bg-success" style="width:${score}%"></div></div>
                </div>
                <h6 class="mt-3">Points forts</h6>${chips(d.strengths, 'badge-soft-success')}
                <h6 class="mt-3">Lacunes</h6>${chips(d.gaps, 'status-en_attente')}
                <h6 class="mt-3">Recommandation</h6><p class="mb-0">${esc(d.recommendation)}</p>`;
        } catch (e) {
            body.innerHTML = '<div class="alert alert-danger mb-0">Une erreur est survenue.</div>';
        }
    });
});
</script>
