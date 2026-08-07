<?php
$cvDocuments = $cvDocuments ?? [];
$aiConfigured = $aiConfigured ?? false;
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-robot me-2"></i>Outils IA</h1>
        <p class="text-secondary mb-0">Analysez et optimisez votre CV, générez une lettre de motivation.</p>
    </div>

    <?php if (!$aiConfigured): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <span class="small mb-0">L'IA n'est pas encore configurée : les résultats utilisent une analyse locale simplifiée. Ajoutez une clé Mistral pour des résultats complets.</span>
        </div>
    <?php endif; ?>

    <!-- Source du CV (partagée) -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <h2 class="h6 mb-3">Votre CV</h2>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small" for="cvSelect">CV enregistré</label>
                    <select class="form-select" id="cvSelect">
                        <option value="">— Coller le texte ci-dessous —</option>
                        <?php foreach ($cvDocuments as $cv): ?>
                            <option value="<?= (int) $cv['id'] ?>"><?= e((string) $cv['file_name']) ?><?= (int) ($cv['is_primary'] ?? 0) === 1 ? ' (principal)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($cvDocuments)): ?>
                        <div class="form-text">Aucun CV enregistré. <a href="<?= e(url('/client/profile/edit')) ?>">En importer un</a> ou collez le texte.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-7">
                    <label class="form-label small" for="cvText">…ou collez le contenu de votre CV</label>
                    <textarea class="form-control" id="cvText" rows="3" placeholder="Expériences, compétences, formations…"></textarea>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills gap-2 mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-analyse" type="button"><i class="bi bi-graph-up me-1"></i>Analyse</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-optim" type="button"><i class="bi bi-magic me-1"></i>Optimisation</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-lettre" type="button"><i class="bi bi-envelope-paper me-1"></i>Lettre de motivation</button></li>
    </ul>

    <div class="tab-content">
        <!-- Analyse -->
        <div class="tab-pane fade show active" id="tab-analyse">
            <div class="card">
                <div class="card-body p-4">
                    <label class="form-label" for="analyseOffer">Texte de l'offre visée</label>
                    <textarea class="form-control mb-3" id="analyseOffer" rows="4" placeholder="Collez la description de l'offre…"></textarea>
                    <button class="btn btn-primary" data-ai="analyse"><i class="bi bi-graph-up me-1"></i>Analyser mon CV</button>
                    <div class="ai-result mt-4" id="result-analyse"></div>
                </div>
            </div>
        </div>

        <!-- Optimisation -->
        <div class="tab-pane fade" id="tab-optim">
            <div class="card">
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label" for="optimRole">Poste visé</label><input class="form-control" id="optimRole" placeholder="Ex: Développeur Flutter"></div>
                        <div class="col-md-8"><label class="form-label" for="optimOffer">Offre (optionnel)</label><input class="form-control" id="optimOffer" placeholder="Mots-clés de l'offre…"></div>
                    </div>
                    <button class="btn btn-primary" data-ai="optim"><i class="bi bi-magic me-1"></i>Optimiser mon CV</button>
                    <div class="ai-result mt-4" id="result-optim"></div>
                </div>
            </div>
        </div>

        <!-- Lettre -->
        <div class="tab-pane fade" id="tab-lettre">
            <div class="card">
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label" for="lettreEntreprise">Entreprise</label><input class="form-control" id="lettreEntreprise" placeholder="Nom de l'entreprise"></div>
                        <div class="col-md-6"><label class="form-label" for="lettreTone">Ton</label><select class="form-select" id="lettreTone"><option value="professionnel">Professionnel</option><option value="enthousiaste">Enthousiaste</option><option value="sobre">Sobre</option></select></div>
                        <div class="col-12"><label class="form-label" for="lettreOffer">Texte de l'offre</label><textarea class="form-control" id="lettreOffer" rows="3" placeholder="Collez l'offre…"></textarea></div>
                    </div>
                    <button class="btn btn-primary" data-ai="lettre"><i class="bi bi-envelope-paper me-1"></i>Générer la lettre</button>
                    <div class="ai-result mt-4" id="result-lettre"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const CSRF = '<?= e(csrf_token()) ?>';
const URLS = {
    analyse: '<?= e(url('/client/ia/analyse')) ?>',
    optim: '<?= e(url('/client/ia/optimiser')) ?>',
    lettre: '<?= e(url('/client/ia/lettre')) ?>',
};
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

function cvSource() {
    return { cv_id: document.getElementById('cvSelect').value || '', cv_text: document.getElementById('cvText').value || '' };
}
async function callAi(tool, extra, resultEl, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Génération…';
    resultEl.innerHTML = '';
    try {
        const res = await fetch(URLS[tool], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _csrf_token: CSRF, ...cvSource(), ...extra }),
        });
        const data = await res.json();
        if (!res.ok) { resultEl.innerHTML = `<div class="alert alert-danger mb-0">${esc(data.error || 'Erreur.')}</div>`; return; }
        renderers[tool](data, resultEl);
    } catch (e) {
        resultEl.innerHTML = '<div class="alert alert-danger mb-0">Une erreur est survenue.</div>';
    } finally {
        btn.disabled = false; btn.innerHTML = original;
    }
}
const list = (arr, cls) => (arr && arr.length) ? '<div class="d-flex flex-wrap gap-2">' + arr.map(x => `<span class="badge ${cls}">${esc(x)}</span>`).join('') + '</div>' : '<span class="text-secondary small">—</span>';
const renderers = {
    analyse: (d, el) => {
        const score = Math.max(0, Math.min(100, d.match_score || 0));
        el.innerHTML = `
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-n" style="font-size:2.2rem;">${score}<span class="fs-6 text-secondary">/100</span></div>
                <div class="progress flex-grow-1" style="height:10px;"><div class="progress-bar bg-success" style="width:${score}%"></div></div>
            </div>
            <h3 class="h6 mt-3">Points forts</h3>${list(d.strengths, 'badge-soft-success')}
            <h3 class="h6 mt-3">Lacunes</h3>${list(d.gaps, 'status-en_attente')}
            <h3 class="h6 mt-3">Recommandation</h3><p class="mb-0">${esc(d.recommendation)}</p>`;
    },
    optim: (d, el) => {
        el.innerHTML = `
            <h3 class="h6">Résumé optimisé</h3><p>${esc(d.summary)}</p>
            <h3 class="h6 mt-3">Compétences à mettre en avant</h3>${list(d.skills, 'badge-soft-primary')}
            <h3 class="h6 mt-3">Réécriture des expériences</h3><ul class="small">${(d.experience_rewrite||[]).map(x=>`<li>${esc(x)}</li>`).join('')}</ul>
            <h3 class="h6 mt-3">Mots-clés à ajouter</h3>${list(d.keywords_to_add, 'text-bg-secondary')}`;
    },
    lettre: (d, el) => {
        el.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2"><h3 class="h6 mb-0">Votre lettre</h3>
            <button class="btn btn-sm btn-outline-secondary" id="copyLetter"><i class="bi bi-clipboard me-1"></i>Copier</button></div>
            <textarea class="form-control" rows="12" id="letterText">${esc(d.text)}</textarea>`;
        el.querySelector('#copyLetter').addEventListener('click', () => {
            const t = el.querySelector('#letterText'); t.select(); document.execCommand('copy');
        });
    },
};
document.querySelectorAll('[data-ai]').forEach(btn => {
    btn.addEventListener('click', () => {
        const tool = btn.getAttribute('data-ai');
        const extra = tool === 'analyse' ? { offer_text: document.getElementById('analyseOffer').value }
            : tool === 'optim' ? { target_role: document.getElementById('optimRole').value, offer_text: document.getElementById('optimOffer').value }
            : { entreprise: document.getElementById('lettreEntreprise').value, tone: document.getElementById('lettreTone').value, offer_text: document.getElementById('lettreOffer').value };
        callAi(tool, extra, document.getElementById('result-' + tool), btn);
    });
});
</script>
