<?php
$applications = $applications ?? [];
$statusOptions = ['en_attente' => 'En attente', 'vue' => 'Vue', 'entretien' => 'Entretien', 'acceptee' => 'Acceptée', 'rejetee' => 'Refusée'];
$dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
$initials = static function (string $n): string {
    $n = trim($n); $i = $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '?';
    if (preg_match('/\s(\S)/u', $n, $m)) { $i .= mb_strtoupper($m[1]); }
    return $i;
};
// Offres distinctes pour le filtre
$offersFilter = [];
foreach ($applications as $a) { $offersFilter[(int) $a['offer_id']] = (string) $a['title']; }
?>
<section class="py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Candidatures reçues</h1>
            <p class="text-secondary mb-0"><?= count($applications) ?> candidature(s)</p>
        </div>
        <?php if (count($offersFilter) > 1): ?>
            <div>
                <label class="form-label small mb-1">Filtrer par offre</label>
                <select class="form-select form-select-sm" id="offerFilter" style="min-width:240px;">
                    <option value="">Toutes les offres</option>
                    <?php foreach ($offersFilter as $oid => $otitle): ?>
                        <option value="<?= (int) $oid ?>"><?= e($otitle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($applications)): ?>
        <?php View::partial('components/empty-state', ['icon' => 'bi-people', 'title' => 'Aucune candidature', 'text' => 'Publiez des offres pour attirer les talents.', 'actionUrl' => url('/entreprise/offres/new'), 'actionLabel' => 'Publier une offre']); ?>
    <?php else: ?>
        <div class="d-flex flex-column gap-3" id="appList">
            <?php foreach ($applications as $app): $skills = $dec($app['candidate_skills'] ?? '[]'); $name = (string) ($app['candidate_name'] ?? 'Candidat'); ?>
                <div class="card app-card" data-offer="<?= (int) $app['offer_id'] ?>">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div class="d-flex gap-3">
                                <span class="avatar-circle"><?= e($initials($name)) ?></span>
                                <div>
                                    <a class="h6 mb-1 d-inline-block text-body" href="<?= e(url('/profile/' . (int) $app['applicant_id'])) ?>" target="_blank"><?= e($name) ?> <i class="bi bi-box-arrow-up-right small text-secondary"></i></a>
                                    <div class="text-secondary small">
                                        <i class="bi bi-geo-alt me-1"></i><?= e((string) ($app['candidate_location'] ?? 'Non précisé')) ?>
                                        <?php if (!empty($app['candidate_rate'])): ?><span class="mx-1">·</span><?= e((string) $app['candidate_rate']) ?> €/h<?php endif; ?>
                                    </div>
                                    <div class="text-secondary small mt-1"><i class="bi bi-briefcase me-1"></i>Postule à : <strong><?= e((string) $app['title']) ?></strong> · <?= e(date('d/m/Y', strtotime((string) ($app['created_at'] ?? 'now')))) ?></div>
                                </div>
                            </div>
                            <?php View::partial('components/status-badge', ['status' => (string) $app['status']]); ?>
                        </div>

                        <?php if (!empty($skills)): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <?php foreach (array_slice($skills, 0, 8) as $s): ?><span class="badge badge-soft-primary"><?= e((string) $s) ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cover_letter'])): ?>
                            <details class="mt-3">
                                <summary class="small text-primary" style="cursor:pointer;">Lettre de motivation</summary>
                                <p class="small text-secondary mt-2 mb-0" style="white-space:pre-wrap;"><?= e((string) $app['cover_letter']) ?></p>
                            </details>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-analyze="<?= (int) $app['id'] ?>" data-title="<?= e($name) ?>"><i class="bi bi-robot me-1"></i>Analyser (IA)</button>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/messages/nouveau/' . (int) $app['applicant_id'])) ?>"><i class="bi bi-chat-dots me-1"></i>Contacter</a>
                            <?php if (!empty($app['cv_path'])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/' . ltrim((string) $app['cv_path'], '/'))) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-arrow-down me-1"></i>CV</a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/entreprise/candidatures/' . (int) $app['id'] . '/status')) ?>" class="d-flex gap-2 ms-auto">
                                <?= csrf_field() ?>
                                <select class="form-select form-select-sm" style="max-width:150px;" name="status">
                                    <?php foreach ($statusOptions as $v => $l): ?><option value="<?= e($v) ?>" <?= $app['status'] === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">OK</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Modal analyse IA -->
<div class="modal fade" id="analyzeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-robot me-2"></i>Analyse — <span id="analyzeTitle"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="analyzeBody"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = '<?= e(csrf_token()) ?>';
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const chips = (a, cls) => (a && a.length) ? '<div class="d-flex flex-wrap gap-2">'+a.map(x=>`<span class="badge ${cls}">${esc(x)}</span>`).join('')+'</div>' : '<span class="text-secondary small">—</span>';
    const filter = document.getElementById('offerFilter');
    filter?.addEventListener('change', () => {
        const v = filter.value;
        document.querySelectorAll('.app-card').forEach(c => c.classList.toggle('d-none', v && c.getAttribute('data-offer') !== v));
    });
    const modalEl = document.getElementById('analyzeModal');
    const modal = new bootstrap.Modal(modalEl); const body = document.getElementById('analyzeBody');
    document.querySelectorAll('[data-analyze]').forEach(btn => btn.addEventListener('click', async () => {
        document.getElementById('analyzeTitle').textContent = btn.getAttribute('data-title') || '';
        body.innerHTML = '<div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>Analyse en cours…</div>';
        modal.show();
        try {
            const r = await fetch('<?= e(url('/entreprise/candidatures')) ?>/' + btn.getAttribute('data-analyze') + '/analyser', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ _csrf_token: CSRF }) });
            const d = await r.json();
            if (!r.ok) { body.innerHTML = `<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>${esc(d.error||'Erreur.')}</div></div>`; return; }
            const s = Math.max(0,Math.min(100,d.match_score||0)); const col = s>=70?'success':(s>=40?'warning':'danger');
            body.innerHTML = `<div class="d-flex align-items-center gap-3 mb-3"><div class="score-ring score-${col}" style="--v:${s};--sz:70px;"><span>${s}<small>/100</small></span></div><div class="fw-semibold">Adéquation ${s>=70?'forte':(s>=40?'correcte':'faible')}</div></div>
                <h6 class="mt-2">Points forts</h6>${chips(d.strengths,'badge-soft-success')}
                <h6 class="mt-3">Points de vigilance</h6>${chips(d.gaps,'status-en_attente')}
                <h6 class="mt-3">Recommandation</h6><p class="mb-0 small">${esc(d.recommendation)||'—'}</p>`;
        } catch (e) { body.innerHTML = '<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>Une erreur est survenue.</div></div>'; }
    }));
});
</script>
