<?php
$cvDocuments = $cvDocuments ?? [];
$aiConfigured = $aiConfigured ?? false;
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-stars me-2"></i>Assistant IA candidat</h1>
        <p class="text-secondary mb-0">Importez une offre (lien, fichier ou image), l'IA analyse et génère CV et lettre.</p>
    </div>

    <?php if (!$aiConfigured): ?>
        <div class="lulu-alert lulu-alert-warning mb-4">
            <i class="bi bi-exclamation-triangle"></i>
            <div>L'IA n'est pas encore configurée : extraction d'image et résultats avancés indisponibles. Une analyse locale simplifiée est utilisée en attendant.</div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Étape 1 : l'offre -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3"><span class="step-num">1</span><h2 class="h5 mb-0">L'offre visée</h2></div>

                    <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#src-url" type="button"><i class="bi bi-link-45deg"></i> Lien</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#src-file" type="button"><i class="bi bi-file-earmark-arrow-up"></i> Fichier / Image</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#src-text" type="button"><i class="bi bi-fonts"></i> Texte</button></li>
                    </ul>
                    <div class="tab-content mb-3">
                        <div class="tab-pane fade show active" id="src-url">
                            <div class="input-group">
                                <input class="form-control" id="srcUrl" type="url" placeholder="https://…/offre-emploi">
                                <button class="btn btn-primary" id="importUrl" type="button"><i class="bi bi-download"></i></button>
                            </div>
                            <div class="form-text">Collez le lien d'une offre, l'IA récupère le contenu.</div>
                        </div>
                        <div class="tab-pane fade" id="src-file">
                            <div class="input-group">
                                <input class="form-control" id="srcFile" type="file" accept="application/pdf,image/png,image/jpeg,image/webp">
                                <button class="btn btn-primary" id="importFile" type="button"><i class="bi bi-magic"></i></button>
                            </div>
                            <div class="form-text">PDF ou photo d'une offre — lecture automatique (OCR IA pour les images).</div>
                        </div>
                        <div class="tab-pane fade" id="src-text">
                            <p class="text-secondary small mb-0">Collez directement le texte dans le champ ci-dessous.</p>
                        </div>
                    </div>

                    <label class="form-label small" for="offerText">Contenu de l'offre</label>
                    <textarea class="form-control" id="offerText" rows="10" placeholder="Le texte de l'offre apparaîtra ici après import…"></textarea>
                    <div id="importStatus" class="small mt-2"></div>
                </div>
            </div>
        </div>

        <!-- Étape 2 : mon CV -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3"><span class="step-num">2</span><h2 class="h5 mb-0">Mon profil / CV</h2></div>
                    <label class="form-label small" for="cvSelect">CV enregistré</label>
                    <select class="form-select mb-3" id="cvSelect">
                        <option value="">— Utiliser mon profil / coller ci-dessous —</option>
                        <?php foreach ($cvDocuments as $cv): ?>
                            <option value="<?= (int) $cv['id'] ?>"><?= e((string) $cv['file_name']) ?><?= (int) ($cv['is_primary'] ?? 0) === 1 ? ' (principal)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label small" for="cvText">…ou contenu du CV</label>
                    <textarea class="form-control" id="cvText" rows="8" placeholder="Expériences, compétences…"></textarea>
                    <?php if (empty($cvDocuments)): ?>
                        <div class="form-text mt-2">Astuce : <a href="<?= e(url('/client/profile/edit')) ?>">importez un CV</a> pour ne plus avoir à le coller.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Étape 3 : actions IA -->
    <div class="card mt-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3"><span class="step-num">3</span><h2 class="h5 mb-0">Générer avec l'IA</h2></div>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <button class="btn btn-primary" data-ai="analyse"><i class="bi bi-graph-up me-1"></i>Analyser la compatibilité</button>
                <button class="btn btn-accent" data-ai="cv"><i class="bi bi-file-earmark-person me-1"></i>Générer mon CV optimisé</button>
                <button class="btn btn-outline-primary" data-ai="lettre"><i class="bi bi-envelope-paper me-1"></i>Générer ma lettre</button>
            </div>
            <div id="aiResult" class="ai-result mt-3"></div>
        </div>
    </div>
</section>

<script>
const CSRF = '<?= e(csrf_token()) ?>';
const U = {
    import: '<?= e(url('/client/ia/importer-offre')) ?>',
    analyse: '<?= e(url('/client/ia/analyse')) ?>',
    cv: '<?= e(url('/client/ia/generer-cv')) ?>',
    lettre: '<?= e(url('/client/ia/lettre')) ?>',
};
const $ = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const chips = (a, cls) => (a && a.length) ? '<div class="d-flex flex-wrap gap-2">'+a.map(x=>`<span class="badge ${cls}">${esc(x)}</span>`).join('')+'</div>' : '<span class="text-secondary small">—</span>';
const src = () => ({ cv_id: $('cvSelect').value || '', cv_text: $('cvText').value || '', offer_text: $('offerText').value || '' });

async function importOffer(type, btn) {
    const fd = new FormData();
    fd.append('_csrf_token', CSRF);
    fd.append('source_type', type);
    if (type === 'url') fd.append('url', $('srcUrl').value);
    if (type === 'file') { if (!$('srcFile').files[0]) return; fd.append('document', $('srcFile').files[0]); }
    const status = $('importStatus');
    const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    status.innerHTML = '<span class="text-secondary"><span class="spinner-border spinner-border-sm me-1"></span>Extraction en cours…</span>';
    try {
        const res = await fetch(U.import, { method: 'POST', body: fd });
        const d = await res.json();
        if (!res.ok) { status.innerHTML = `<span class="text-danger">${esc(d.error||'Échec.')}</span>`; return; }
        $('offerText').value = d.text; status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Offre importée.</span>';
    } catch(e) { status.innerHTML = '<span class="text-danger">Erreur réseau.</span>'; }
    finally { btn.disabled = false; btn.innerHTML = orig; }
}
$('importUrl').addEventListener('click', (e) => importOffer('url', e.currentTarget));
$('importFile').addEventListener('click', (e) => importOffer('file', e.currentTarget));

const render = {
    analyse: (d) => { const s = Math.max(0,Math.min(100,d.match_score||0)); return `
        <div class="d-flex align-items-center gap-3 mb-3"><div class="stat-n" style="font-size:2.2rem;">${s}<span class="fs-6 text-secondary">/100</span></div>
        <div class="progress flex-grow-1" style="height:10px;"><div class="progress-bar bg-success" style="width:${s}%"></div></div></div>
        <h3 class="h6 mt-3">Points forts</h3>${chips(d.strengths,'badge-soft-success')}
        <h3 class="h6 mt-3">Lacunes</h3>${chips(d.gaps,'status-en_attente')}
        <h3 class="h6 mt-3">Recommandation</h3><p class="mb-0">${esc(d.recommendation)}</p>`; },
    cv: (d) => `
        <h3 class="h6">Résumé professionnel</h3><p>${esc(d.summary)}</p>
        <h3 class="h6 mt-3">Compétences clés</h3>${chips(d.skills,'badge-soft-primary')}
        <h3 class="h6 mt-3">Expériences reformulées</h3><ul class="small">${(d.experience_rewrite||[]).map(x=>`<li>${esc(x)}</li>`).join('')}</ul>
        <h3 class="h6 mt-3">Mots-clés à intégrer</h3>${chips(d.keywords_to_add,'text-bg-secondary')}`,
    lettre: (d) => `
        <div class="d-flex justify-content-between align-items-center mb-2"><h3 class="h6 mb-0">Votre lettre</h3>
        <button class="btn btn-sm btn-outline-secondary" id="copyLetter"><i class="bi bi-clipboard me-1"></i>Copier</button></div>
        <textarea class="form-control" rows="12" id="letterText">${esc(d.text)}</textarea>`,
};

document.querySelectorAll('[data-ai]').forEach(btn => btn.addEventListener('click', async () => {
    const tool = btn.getAttribute('data-ai');
    const box = $('aiResult');
    const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Génération…';
    box.innerHTML = '';
    try {
        const extra = tool === 'lettre' ? { entreprise: '', tone: 'professionnel' } : { target_role: '' };
        const res = await fetch(U[tool], { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ _csrf_token: CSRF, ...src(), ...extra }) });
        const d = await res.json();
        if (!res.ok) { box.innerHTML = `<div class="lulu-alert lulu-alert-danger">${esc(d.error||'Erreur.')}</div>`; return; }
        box.innerHTML = `<div class="card bg-surface-2 border-0"><div class="card-body">${render[tool](d)}</div></div>`;
        const copy = $('copyLetter'); if (copy) copy.addEventListener('click', () => { const t = $('letterText'); t.select(); document.execCommand('copy'); });
    } catch(e) { box.innerHTML = '<div class="lulu-alert lulu-alert-danger">Une erreur est survenue.</div>'; }
    finally { btn.disabled = false; btn.innerHTML = orig; }
}));
</script>
