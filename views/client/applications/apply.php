<?php
$offer = $offer ?? [];
$cvDocuments = $cvDocuments ?? [];
$offerId = (int) ($offer['id'] ?? 0);
?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <a class="text-secondary small d-inline-flex align-items-center mb-3" href="<?= e(url('/offres/' . $offerId)) ?>"><i class="bi bi-arrow-left me-1"></i>Retour à l'offre</a>
            <h1 class="h3 mb-1"><i class="bi bi-stars text-primary me-1"></i>Postuler intelligemment</h1>
            <p class="text-secondary mb-4"><?= e((string) ($offer['title'] ?? 'Offre')) ?></p>

            <!-- Adéquation IA -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 mb-1"><span class="badge badge-soft-primary me-2">1</span>Votre adéquation</h2>
                            <p class="text-secondary small mb-0">L'IA évalue votre profil par rapport à cette offre.</p>
                        </div>
                        <button class="btn btn-outline-primary" type="button" id="btnAnalyse"><i class="bi bi-graph-up-arrow me-1"></i>Analyser</button>
                    </div>
                    <div id="analyseResult" class="mt-3 d-none"></div>
                </div>
            </div>

            <form method="post" action="<?= e(url('/applications')) ?>" enctype="multipart/form-data" class="card shadow-sm">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= $offerId ?>">
                    <input type="hidden" name="entreprise_id" value="<?= e((string) ($offer['entreprise_id'] ?? 0)) ?>">
                    <input type="hidden" name="generated_cv" id="generatedCv" disabled>

                    <!-- CV -->
                    <h2 class="h6 mb-3"><span class="badge badge-soft-primary me-2">2</span>Votre CV</h2>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <?php if (!empty($cvDocuments)): ?>
                            <div class="form-check"><input class="form-check-input" type="radio" name="cvmode" id="mode_saved" value="saved" checked><label class="form-check-label" for="mode_saved">CV enregistré</label></div>
                        <?php endif; ?>
                        <div class="form-check"><input class="form-check-input" type="radio" name="cvmode" id="mode_upload" value="upload" <?= empty($cvDocuments) ? 'checked' : '' ?>><label class="form-check-label" for="mode_upload">Importer un PDF</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="cvmode" id="mode_gen" value="gen"><label class="form-check-label" for="mode_gen"><i class="bi bi-stars text-accent"></i> Générer un CV adapté (IA)</label></div>
                    </div>

                    <?php if (!empty($cvDocuments)): ?>
                        <div class="cv-pane" data-mode="saved">
                            <select class="form-select" name="cv_id" id="cvSelect">
                                <?php foreach ($cvDocuments as $cv): ?>
                                    <option value="<?= (int) $cv['id'] ?>" <?= (int) ($cv['is_primary'] ?? 0) === 1 ? 'selected' : '' ?>><?= e((string) $cv['file_name']) ?><?= (int) ($cv['is_primary'] ?? 0) === 1 ? ' (principal)' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="cv-pane <?= empty($cvDocuments) ? '' : 'd-none' ?>" data-mode="upload">
                        <input class="form-control" type="file" name="cv" id="cvFile" accept=".pdf,.doc,.docx,image/jpeg,image/png" <?= empty($cvDocuments) ? '' : 'disabled' ?>>
                        <div class="form-text">PDF, Word ou image — 8 Mo max.</div>
                    </div>
                    <div class="cv-pane d-none" data-mode="gen">
                        <button class="btn btn-accent" type="button" id="btnGenCv"><i class="bi bi-magic me-1"></i>Générer mon CV pour cette offre</button>
                        <div id="genCvResult" class="mt-3"></div>
                    </div>

                    <!-- Lettre -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h2 class="h6 mb-0"><span class="badge badge-soft-primary me-2">3</span>Lettre de motivation</h2>
                            <button class="btn btn-sm btn-accent" type="button" id="btnGenLetter"><i class="bi bi-stars me-1"></i>Générer avec l'IA</button>
                        </div>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="9" placeholder="Rédigez, ou laissez l'IA générer une lettre adaptée à cette offre et à votre profil…"></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-send-check me-1"></i>Envoyer ma candidature</button>
                        <a class="btn btn-outline-secondary" href="<?= e(url('/offres/' . $offerId)) ?>">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    const CSRF = '<?= e(csrf_token()) ?>';
    const OFFER = <?= json_encode((string) ($offer['description'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const chips = (a, cls) => (a && a.length) ? '<div class="d-flex flex-wrap gap-2">'+a.map(x=>`<span class="badge ${cls}">${esc(x)}</span>`).join('')+'</div>' : '';
    const md = (t) => String(t??'').split('\n').map(l => /^#{1,3}\s+/.test(l) ? '<strong>'+esc(l.replace(/^#{1,3}\s+/,''))+'</strong>' : (/^[-*]\s+/.test(l) ? '• '+esc(l.replace(/^[-*]\s+/,'')) : esc(l))).join('<br>').replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
    const cvId = () => ($('cvSelect') && !$('cvSelect').closest('.cv-pane').classList.contains('d-none')) ? $('cvSelect').value : '';

    // Bascule des modes CV
    document.querySelectorAll('[name="cvmode"]').forEach(r => r.addEventListener('change', () => {
        const mode = document.querySelector('[name="cvmode"]:checked').value;
        document.querySelectorAll('.cv-pane').forEach(p => p.classList.toggle('d-none', p.getAttribute('data-mode') !== mode));
        if ($('cvSelect')) $('cvSelect').disabled = mode !== 'saved';
        if ($('cvFile')) $('cvFile').disabled = mode !== 'upload';
        $('generatedCv').disabled = mode !== 'gen';
    }));

    async function post(url, body) { const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }); return r.json(); }
    function busy(btn, on, label) { if (on) { btn.dataset.o = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>'+(label||'…'); } else { btn.disabled = false; btn.innerHTML = btn.dataset.o; } }

    $('btnAnalyse').addEventListener('click', async () => {
        const btn = $('btnAnalyse'), box = $('analyseResult'); busy(btn, true, 'Analyse…'); box.classList.remove('d-none');
        box.innerHTML = '<div class="text-secondary small"><span class="spinner-border spinner-border-sm me-1"></span>L\'IA analyse votre profil…</div>';
        try {
            const d = await post('<?= e(url('/client/ia/analyse')) ?>', { _csrf_token: CSRF, cv_id: cvId(), offer_text: OFFER });
            const s = Math.max(0, Math.min(100, d.match_score||0)); const col = s>=70?'success':(s>=40?'warning':'danger');
            box.innerHTML = `<div class="d-flex align-items-center gap-3"><div class="score-ring score-${col}" style="--v:${s};--sz:64px;"><span>${s}<small>/100</small></span></div>
                <div><div class="fw-semibold mb-1">Adéquation ${s>=70?'forte':(s>=40?'correcte':'à renforcer')}</div>${chips(d.strengths,'badge-soft-success')}</div></div>
                ${d.recommendation?'<p class="small text-secondary mt-2 mb-0"><i class="bi bi-lightbulb me-1"></i>'+esc(d.recommendation)+'</p>':''}`;
        } catch (e) { box.innerHTML = '<div class="text-danger small">Erreur d\'analyse.</div>'; }
        finally { busy(btn, false); }
    });

    $('btnGenCv').addEventListener('click', async () => {
        const btn = $('btnGenCv'), box = $('genCvResult'); busy(btn, true, 'Génération…');
        box.innerHTML = '<div class="text-secondary small"><span class="spinner-border spinner-border-sm me-1"></span>L\'IA rédige votre CV…</div>';
        try {
            const d = await post('<?= e(url('/client/ia/generer-cv')) ?>', { _csrf_token: CSRF, cv_id: cvId(), offer_text: OFFER, target_role: <?= json_encode((string) ($offer['title'] ?? ''), JSON_UNESCAPED_UNICODE) ?> });
            $('generatedCv').value = d.cv || '';
            box.innerHTML = `<div class="lulu-alert lulu-alert-success mb-2"><i class="bi bi-check-circle-fill"></i><div class="small">CV généré — il sera joint à votre candidature (.docx).</div></div>
                <div class="doc-render" style="max-height:320px;">${md(d.cv)}</div>`;
        } catch (e) { box.innerHTML = '<div class="text-danger small">Erreur de génération.</div>'; }
        finally { busy(btn, false); }
    });

    $('btnGenLetter').addEventListener('click', async () => {
        const btn = $('btnGenLetter'); busy(btn, true, 'Génération…');
        try {
            const d = await post('<?= e(url('/client/ia/lettre')) ?>', { _csrf_token: CSRF, cv_id: cvId(), offer_text: OFFER, entreprise: '', tone: 'professionnel' });
            if (d.text) $('cover_letter').value = d.text;
        } catch (e) {}
        finally { busy(btn, false); }
    });
})();
</script>
