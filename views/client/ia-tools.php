<?php
$cvDocuments = $cvDocuments ?? [];
$aiConfigured = $aiConfigured ?? false;
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-stars me-2 text-primary"></i>Assistant IA candidat</h1>
        <p class="text-secondary mb-0">Importez une offre, l'IA analyse votre adéquation et génère CV et lettre sur mesure.</p>
    </div>

    <?php if ($aiConfigured): ?>
        <div class="lulu-alert lulu-alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div class="small">IA activée — résultats générés par intelligence artificielle.</div></div>
    <?php else: ?>
        <div class="lulu-alert lulu-alert-warning mb-4"><i class="bi bi-exclamation-triangle-fill"></i><div class="small">IA non configurée : mode local simplifié. Ajoutez une clé Mistral dans <code>.env</code> pour la pleine puissance.</div></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- L'offre -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h6 mb-0"><span class="badge badge-soft-primary me-2">1</span>L'offre visée</h2>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-secondary active" id="tabLink" type="button"><i class="bi bi-link-45deg"></i> Lien</button>
                            <button class="btn btn-outline-secondary" id="tabFile" type="button"><i class="bi bi-file-earmark"></i> Fichier</button>
                        </div>
                    </div>
                    <div id="paneLink" class="input-group input-group-sm mb-2">
                        <input class="form-control" id="srcUrl" type="url" placeholder="https://…/offre">
                        <button class="btn btn-primary" id="importUrl" type="button"><i class="bi bi-download me-1"></i>Importer</button>
                    </div>
                    <div id="paneFile" class="input-group input-group-sm mb-2 d-none">
                        <input class="form-control" id="srcFile" type="file" accept="application/pdf,image/png,image/jpeg,image/webp">
                        <button class="btn btn-primary" id="importFile" type="button"><i class="bi bi-magic me-1"></i>Extraire</button>
                    </div>
                    <div id="importStatus" class="small mb-2"></div>
                    <textarea class="form-control" id="offerText" rows="12" placeholder="Collez ici le texte de l'offre, ou importez-le via un lien ou un fichier ci-dessus."></textarea>
                </div>
            </div>
        </div>

        <!-- Mon CV -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><span class="badge badge-soft-primary me-2">2</span>Mon profil / CV</h2>
                    <?php if (!empty($cvDocuments)): ?>
                        <label class="form-label small" for="cvSelect">Utiliser un CV enregistré</label>
                        <select class="form-select form-select-sm mb-3" id="cvSelect">
                            <option value="">— Coller / utiliser mon profil —</option>
                            <?php foreach ($cvDocuments as $cv): ?>
                                <option value="<?= (int) $cv['id'] ?>"><?= e((string) $cv['file_name']) ?><?= (int) ($cv['is_primary'] ?? 0) === 1 ? ' (principal)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <label class="form-label small" for="cvText">Contenu de votre CV</label>
                    <textarea class="form-control" id="cvText" rows="<?= empty($cvDocuments) ? 12 : 9 ?>" placeholder="Expériences, compétences, formations… (ou laissez vide pour utiliser votre profil enregistré)"></textarea>
                    <?php if (empty($cvDocuments)): ?>
                        <div class="form-text mt-2"><a href="<?= e(url('/client/profile/edit')) ?>">Importez un CV</a> pour aller plus vite la prochaine fois.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex flex-wrap gap-2 my-4">
        <button class="btn btn-primary" data-ai="analyse"><i class="bi bi-graph-up-arrow me-1"></i>Analyser mon adéquation</button>
        <button class="btn btn-accent" data-ai="cv"><i class="bi bi-file-earmark-person me-1"></i>Générer mon CV</button>
        <button class="btn btn-outline-primary" data-ai="lettre"><i class="bi bi-envelope-paper me-1"></i>Générer ma lettre</button>
        <button class="btn btn-outline-secondary" data-ai="infos"><i class="bi bi-info-circle me-1"></i>Infos clés / où postuler</button>
    </div>

    <!-- Résultat (caché tant qu'aucune action) -->
    <div id="aiResultWrap" class="card d-none">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0" id="aiResultTitle">Résultat</h2>
                <button class="btn btn-sm btn-outline-secondary" id="aiResultClose" type="button"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="aiResult"></div>
        </div>
    </div>
</section>

<script>
(function () {
    const CSRF = '<?= e(csrf_token()) ?>';
    const U = { import: '<?= e(url('/client/ia/importer-offre')) ?>', analyse: '<?= e(url('/client/ia/analyse')) ?>', cv: '<?= e(url('/client/ia/generer-cv')) ?>', lettre: '<?= e(url('/client/ia/lettre')) ?>', infos: '<?= e(url('/client/ia/infos-offre')) ?>' };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const chips = (a, cls) => (a && a.length) ? '<div class="d-flex flex-wrap gap-2">'+a.map(x=>`<span class="badge ${cls}">${esc(x)}</span>`).join('')+'</div>' : '<span class="text-secondary small">Aucun</span>';
    // Mini markdown -> HTML (titres, gras, listes)
    const md = (t) => {
        const lines = String(t ?? '').split('\n'); let html = '', inList = false;
        const flush = () => { if (inList) { html += '</ul>'; inList = false; } };
        for (let l of lines) {
            if (/^\s*[-*]\s+/.test(l)) { if (!inList) { html += '<ul class="mb-2">'; inList = true; } html += '<li>'+inline(l.replace(/^\s*[-*]\s+/,''))+'</li>'; continue; }
            flush();
            if (/^#\s+/.test(l)) html += '<h3 class="h5 mb-1">'+inline(l.replace(/^#\s+/,''))+'</h3>';
            else if (/^##\s+/.test(l)) html += '<h4 class="h6 text-secondary text-uppercase mt-3 mb-1" style="letter-spacing:.04em;">'+inline(l.replace(/^##\s+/,''))+'</h4>';
            else if (l.trim()==='') html += '';
            else html += '<p class="mb-2">'+inline(l)+'</p>';
        }
        flush(); return html;
    };
    const inline = (s) => esc(s).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>');
    const download = (name, text) => { const b = new Blob([text], {type:'text/plain'}); const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = name; a.click(); };

    const wrap = $('aiResultWrap'), box = $('aiResult'), title = $('aiResultTitle');
    $('aiResultClose').addEventListener('click', () => wrap.classList.add('d-none'));

    // Onglets source
    const setTab = (link) => { $('tabLink').classList.toggle('active', link); $('tabFile').classList.toggle('active', !link); $('paneLink').classList.toggle('d-none', !link); $('paneFile').classList.toggle('d-none', link); };
    $('tabLink').addEventListener('click', () => setTab(true));
    $('tabFile').addEventListener('click', () => setTab(false));

    async function importOffer(type, btn) {
        const fd = new FormData(); fd.append('_csrf_token', CSRF); fd.append('source_type', type);
        if (type === 'url') { if (!$('srcUrl').value.trim()) return; fd.append('url', $('srcUrl').value); }
        if (type === 'file') { if (!$('srcFile').files[0]) return; fd.append('document', $('srcFile').files[0]); }
        const st = $('importStatus'), orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        st.innerHTML = '<span class="text-secondary"><span class="spinner-border spinner-border-sm me-1"></span>Extraction…</span>';
        try {
            const r = await fetch(U.import, { method:'POST', body: fd }); const d = await r.json();
            if (!r.ok) { st.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>${esc(d.error||'Échec.')}</span>`; return; }
            $('offerText').value = d.text; st.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Offre importée'+(d.refined?' et nettoyée par l\'IA':'')+'.</span>';
        } catch (e) { st.innerHTML = '<span class="text-danger">Erreur réseau.</span>'; }
        finally { btn.disabled = false; btn.innerHTML = orig; }
    }
    $('importUrl').addEventListener('click', (e) => importOffer('url', e.currentTarget));
    $('importFile').addEventListener('click', (e) => importOffer('file', e.currentTarget));

    const badgeAi = (ai) => ai ? '' : '<span class="badge text-bg-secondary ms-2">mode local</span>';
    const render = {
        analyse: (d) => { const s = Math.max(0,Math.min(100,d.match_score||0)); const col = s>=70?'success':(s>=40?'warning':'danger');
            return `<div class="d-flex align-items-center gap-3 mb-3"><div class="score-ring score-${col}" style="--v:${s}"><span>${s}<small>/100</small></span></div>
            <div><div class="fw-semibold">Adéquation ${s>=70?'forte':(s>=40?'correcte':'faible')}${badgeAi(d.ai)}</div><div class="text-secondary small">Estimation IA de votre correspondance avec l'offre.</div></div></div>
            <h3 class="h6 mt-3"><i class="bi bi-hand-thumbs-up text-success me-1"></i>Points forts</h3>${chips(d.strengths,'badge-soft-success')}
            <h3 class="h6 mt-3"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Points à renforcer</h3>${chips(d.gaps,'status-en_attente')}
            <h3 class="h6 mt-3"><i class="bi bi-lightbulb text-primary me-1"></i>Recommandation</h3><p class="mb-0">${esc(d.recommendation)||'—'}</p>`; },
        cv: (d) => docToolbar('cv') + `<div class="doc-render doc-cv" data-raw="${esc(d.cv)}">${md(d.cv)}</div>${badgeAi(d.ai)?'<div class="mt-2">'+badgeAi(d.ai)+'</div>':''}`,
        lettre: (d) => docToolbar('lettre') + `<div class="doc-render doc-letter" data-raw="${esc(d.text)}" style="white-space:pre-wrap;">${esc(d.text)}</div>`,
    };
    const docToolbar = (kind) => `<div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
        <button class="btn btn-sm btn-outline-secondary" data-copy="${kind}"><i class="bi bi-clipboard me-1"></i>Copier</button>
        <button class="btn btn-sm btn-outline-primary" data-doc="${kind}" data-fmt="docx"><i class="bi bi-file-earmark-word me-1"></i>Word</button>
        <button class="btn btn-sm btn-outline-danger" data-doc="${kind}" data-fmt="pdf"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button></div>`;
    const DOC_URL = '<?= e(url('/client/ia/document')) ?>';
    function docContent(kind) { const el = box.querySelector(kind === 'cv' ? '.doc-cv' : '.doc-letter'); return el ? el.getAttribute('data-raw') : ''; }
    async function downloadDoc(kind, fmt) {
        const content = docContent(kind);
        if (fmt === 'pdf') {
            const f = document.createElement('form'); f.method = 'POST'; f.action = DOC_URL; f.target = '_blank';
            f.innerHTML = `<input name="_csrf_token" value="${CSRF}"><input name="type" value="${kind}"><input name="format" value="pdf">`;
            const ta = document.createElement('textarea'); ta.name = 'content'; ta.value = content; f.appendChild(ta);
            document.body.appendChild(f); f.submit(); f.remove(); return;
        }
        const fd = new FormData(); fd.append('_csrf_token', CSRF); fd.append('type', kind); fd.append('format', 'docx'); fd.append('content', content);
        const r = await fetch(DOC_URL, { method: 'POST', body: fd });
        const blob = await r.blob(); const a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = (kind === 'lettre' ? 'lettre-motivation' : 'cv') + '-lulu.docx'; a.click();
    }
    render.infos = (d) => {
        const i = d.info || {};
        const row = (label, val, ic) => val ? `<div class="d-flex gap-2 mb-2"><i class="bi ${ic} text-primary"></i><div><span class="text-secondary small d-block">${label}</span><span class="fw-semibold">${esc(val)}</span></div></div>` : '';
        let apply = '';
        if (i.email_candidature) apply += `<a class="btn btn-sm btn-primary me-2 mb-2" href="mailto:${esc(i.email_candidature)}"><i class="bi bi-envelope me-1"></i>${esc(i.email_candidature)}</a>`;
        if (i.url_candidature) apply += `<a class="btn btn-sm btn-outline-primary mb-2" href="${esc(i.url_candidature)}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Postuler en ligne</a>`;
        return `<div class="row"><div class="col-md-6">${row('Intitulé', i.intitule, 'bi-briefcase')}${row('Entreprise', i.entreprise, 'bi-building')}${row('Lieu', i.lieu, 'bi-geo-alt')}</div>
            <div class="col-md-6">${row('Contrat', i.contrat, 'bi-file-text')}${row('Date limite', i.date_limite, 'bi-calendar-event')}</div></div>
            ${i.resume?'<p class="text-secondary small mt-2">'+esc(i.resume)+'</p>':''}
            ${apply?'<hr><h3 class="h6"><i class="bi bi-send me-1"></i>Pour postuler</h3>'+apply:'<div class="lulu-alert lulu-alert-info mt-2"><i class="bi bi-info-circle-fill"></i><div class="small">Aucun contact de candidature détecté dans l\'offre. S\'il s\'agit d\'une offre de la plateforme, utilisez le bouton « Postuler » sur l\'offre.</div></div>'}`;
    };
    const titles = { analyse: 'Analyse d\'adéquation', cv: 'Votre CV généré', lettre: 'Votre lettre de motivation', infos: 'Infos clés de l\'offre' };

    document.querySelectorAll('[data-ai]').forEach(btn => btn.addEventListener('click', async () => {
        const tool = btn.getAttribute('data-ai');
        if (!$('offerText').value.trim() && tool !== 'cv') { $('importStatus').innerHTML = '<span class="text-danger">Ajoutez d\'abord le texte de l\'offre.</span>'; $('offerText').focus(); return; }
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Génération…';
        wrap.classList.remove('d-none'); title.textContent = titles[tool]; box.innerHTML = '<div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>L\'IA travaille…</div>';
        wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        try {
            const body = { _csrf_token: CSRF, cv_id: $('cvSelect')?.value || '', cv_text: $('cvText').value || '', offer_text: $('offerText').value || '', target_role: '', entreprise: '', tone: 'professionnel' };
            const r = await fetch(U[tool], { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
            const d = await r.json();
            if (!r.ok) { box.innerHTML = `<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>${esc(d.error||'Erreur.')}</div></div>`; return; }
            box.innerHTML = render[tool](d);
            box.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', () => { navigator.clipboard?.writeText(docContent(b.getAttribute('data-copy'))); b.innerHTML='<i class="bi bi-check2 me-1"></i>Copié'; }));
            box.querySelectorAll('[data-doc]').forEach(b => b.addEventListener('click', () => downloadDoc(b.getAttribute('data-doc'), b.getAttribute('data-fmt'))));
        } catch (e) { box.innerHTML = '<div class="lulu-alert lulu-alert-danger"><i class="bi bi-x-circle-fill"></i><div>Une erreur est survenue.</div></div>'; }
        finally { btn.disabled = false; btn.innerHTML = orig; }
    }));
})();
</script>
