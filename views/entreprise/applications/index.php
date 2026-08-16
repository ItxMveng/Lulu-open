<?php
$applications = $applications ?? [];
$sort = $sort ?? 'score';
$statusOptions = ['en_attente' => 'En attente', 'vue' => 'Vue', 'entretien' => 'Entretien', 'acceptee' => 'Acceptée', 'rejetee' => 'Refusée'];
$dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
$initials = static function (string $n): string {
    $n = trim($n); $i = $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '?';
    if (preg_match('/\s(\S)/u', $n, $m)) { $i .= mb_strtoupper($m[1]); }
    return $i;
};
$scoreColor = static fn (int $s): string => $s >= 70 ? 'success' : ($s >= 40 ? 'warning' : 'danger');
$offersFilter = [];
foreach ($applications as $a) { $offersFilter[(int) $a['offer_id']] = (string) $a['title']; }
?>
<section class="py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Candidatures reçues</h1>
            <p class="text-secondary mb-0"><?= count($applications) ?> candidature(s) · classées par <?= $sort === 'recent' ? 'date' : 'score IA' ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if (count($offersFilter) > 1): ?>
                <select class="form-select form-select-sm" id="offerFilter" style="min-width:200px;">
                    <option value="">Toutes les offres</option>
                    <?php foreach ($offersFilter as $oid => $ot): ?><option value="<?= (int) $oid ?>"><?= e($ot) ?></option><?php endforeach; ?>
                </select>
            <?php endif; ?>
            <select class="form-select form-select-sm" id="sortSelect" style="min-width:170px;">
                <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Trier : meilleur score</option>
                <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Trier : plus récentes</option>
            </select>
        </div>
    </div>

    <?php if (empty($applications)): ?>
        <?php View::partial('components/empty-state', ['icon' => 'bi-people', 'title' => 'Aucune candidature', 'text' => 'Publiez des offres pour attirer les talents.', 'actionUrl' => url('/entreprise/offres/new'), 'actionLabel' => 'Publier une offre']); ?>
    <?php else: ?>
        <div class="d-flex flex-column gap-3" id="appList">
            <?php foreach ($applications as $app): $skills = $dec($app['candidate_skills'] ?? '[]'); $name = (string) ($app['candidate_name'] ?? 'Candidat'); $score = $app['match_score']; ?>
                <div class="card app-card" data-offer="<?= (int) $app['offer_id'] ?>" data-appid="<?= (int) $app['id'] ?>" data-score="<?= $score !== null ? (int) $score : '' ?>">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div class="d-flex gap-3">
                                <div class="score-slot">
                                    <?php if ($score !== null): ?>
                                        <div class="score-ring score-<?= $scoreColor((int) $score) ?>" style="--v:<?= (int) $score ?>;--sz:60px;"><span><?= (int) $score ?><small>/100</small></span></div>
                                    <?php else: ?>
                                        <div class="score-ring" style="--sz:60px;--ring-c:#CBD5E1;" title="Analyse en cours"><span class="text-secondary"><span class="spinner-border spinner-border-sm"></span></span></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a class="h6 mb-1 d-inline-block text-body" href="<?= e(url('/profile/' . (int) $app['applicant_id'])) ?>" target="_blank"><?= e($name) ?> <i class="bi bi-box-arrow-up-right small text-secondary"></i></a>
                                    <div class="text-secondary small"><i class="bi bi-geo-alt me-1"></i><?= e((string) ($app['candidate_location'] ?? 'Non précisé')) ?><?php if (!empty($app['candidate_rate'])): ?> · <?= e((string) $app['candidate_rate']) ?> €/h<?php endif; ?></div>
                                    <div class="text-secondary small mt-1"><i class="bi bi-briefcase me-1"></i>Postule à : <strong><?= e((string) $app['title']) ?></strong> · <?= e(date('d/m/Y', strtotime((string) ($app['created_at'] ?? 'now')))) ?></div>
                                </div>
                            </div>
                            <?php View::partial('components/status-badge', ['status' => (string) $app['status']]); ?>
                        </div>

                        <?php if (!empty($skills)): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3"><?php foreach (array_slice($skills, 0, 8) as $s): ?><span class="badge badge-soft-primary"><?= e((string) $s) ?></span><?php endforeach; ?></div>
                        <?php endif; ?>

                        <?php if (!empty($app['cover_letter'])): ?>
                            <details class="mt-3"><summary class="small text-primary" style="cursor:pointer;">Lettre de motivation</summary><p class="small text-secondary mt-2 mb-0" style="white-space:pre-wrap;"><?= e((string) $app['cover_letter']) ?></p></details>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-analyze="<?= (int) $app['id'] ?>" data-title="<?= e($name) ?>"><i class="bi bi-robot me-1"></i>Détails IA</button>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/messages/nouveau/' . (int) $app['applicant_id'])) ?>"><i class="bi bi-chat-dots me-1"></i>Contacter</a>
                            <?php if (!empty($app['cv_path'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/' . ltrim((string) $app['cv_path'], '/'))) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-arrow-down me-1"></i>CV</a><?php endif; ?>
                        </div>

                        <!-- Mise à jour du statut (avec entretien) -->
                        <form method="post" action="<?= e(url('/entreprise/candidatures/' . (int) $app['id'] . '/status')) ?>" class="status-form mt-3 pt-3 border-top">
                            <?= csrf_field() ?>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <label class="small text-secondary mb-0">Statut :</label>
                                <select class="form-select form-select-sm status-select" name="status" style="max-width:160px;">
                                    <?php foreach ($statusOptions as $v => $l): ?><option value="<?= e($v) ?>" <?= $app['status'] === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Enregistrer & notifier</button>
                            </div>
                            <div class="interview-fields row g-2 mt-2 d-none">
                                <div class="col-12"><div class="lulu-alert lulu-alert-info py-2"><i class="bi bi-calendar-event"></i><div class="small">Programmez l'entretien : ces informations seront envoyées au candidat par email.</div></div></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" type="datetime-local" name="interview_at" value="<?= !empty($app['interview_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $app['interview_at']))) : '' ?>"></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="interview_location" placeholder="Lieu ou lien visio" value="<?= e((string) ($app['interview_location'] ?? '')) ?>"></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="interview_note" placeholder="Message (optionnel)" value="<?= e((string) ($app['interview_note'] ?? '')) ?>"></div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

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

    document.getElementById('offerFilter')?.addEventListener('change', function () {
        const v = this.value;
        document.querySelectorAll('.app-card').forEach(c => c.classList.toggle('d-none', v && c.getAttribute('data-offer') !== v));
    });
    document.getElementById('sortSelect')?.addEventListener('change', function () { window.location = '?sort=' + this.value; });

    // Champs entretien visibles selon le statut
    document.querySelectorAll('.status-form').forEach(form => {
        const sel = form.querySelector('.status-select'), fields = form.querySelector('.interview-fields');
        const toggle = () => fields.classList.toggle('d-none', !['entretien','acceptee'].includes(sel.value));
        sel.addEventListener('change', toggle); toggle();
    });

    const modalEl = document.getElementById('analyzeModal'), modal = new bootstrap.Modal(modalEl), body = document.getElementById('analyzeBody');
    document.querySelectorAll('[data-analyze]').forEach(btn => btn.addEventListener('click', async () => {
        document.getElementById('analyzeTitle').textContent = btn.getAttribute('data-title') || '';
        body.innerHTML = '<div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>Analyse…</div>';
        modal.show();
        try {
            const r = await fetch('<?= e(url('/entreprise/candidatures')) ?>/' + btn.getAttribute('data-analyze') + '/analyser', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ _csrf_token: CSRF }) });
            const d = await r.json();
            if (!r.ok) { body.innerHTML = `<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>${esc(d.error||'Erreur.')}</div></div>`; return; }
            const s = Math.max(0,Math.min(100,d.match_score||0)), col = s>=70?'success':(s>=40?'warning':'danger');
            body.innerHTML = `<div class="d-flex align-items-center gap-3 mb-3"><div class="score-ring score-${col}" style="--v:${s};--sz:70px;"><span>${s}<small>/100</small></span></div><div class="fw-semibold">Adéquation ${s>=70?'forte':(s>=40?'correcte':'faible')}</div></div>
                <h6 class="mt-2">Points forts</h6>${chips(d.strengths,'badge-soft-success')}
                <h6 class="mt-3">Points de vigilance</h6>${chips(d.gaps,'status-en_attente')}
                <h6 class="mt-3">Recommandation</h6><p class="mb-0 small">${esc(d.recommendation)||'—'}</p>`;
        } catch (e) { body.innerHTML = '<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>Une erreur est survenue.</div></div>'; }
    }));

    // Analyse IA automatique (arrière-plan) pour les candidatures non encore scorées.
    const SORT = '<?= e($sort) ?>';
    const ANALYZE_BASE = '<?= e(url('/entreprise/candidatures')) ?>';
    const ringHtml = (s) => { const col = s>=70?'success':(s>=40?'warning':'danger'); return `<div class="score-ring score-${col}" style="--v:${s};--sz:60px;"><span>${s}<small>/100</small></span></div>`; };
    const resort = () => { const list = document.getElementById('appList'); if (!list || SORT === 'recent') return;
        [...list.children].sort((a,b) => (parseInt(b.getAttribute('data-score')||-1)) - (parseInt(a.getAttribute('data-score')||-1))).forEach(c => list.appendChild(c)); };
    const pending = [...document.querySelectorAll('.app-card')].filter(c => c.getAttribute('data-score') === '');
    (async () => {
        for (const card of pending) {
            try {
                const r = await fetch(ANALYZE_BASE + '/' + card.getAttribute('data-appid') + '/analyser', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ _csrf_token: CSRF }) });
                const d = await r.json();
                if (r.ok && typeof d.match_score !== 'undefined') {
                    const s = Math.max(0, Math.min(100, d.match_score||0));
                    card.setAttribute('data-score', s);
                    const slot = card.querySelector('.score-slot'); if (slot) slot.innerHTML = ringHtml(s);
                }
            } catch (e) {}
        }
        resort();
    })();
});
</script>
