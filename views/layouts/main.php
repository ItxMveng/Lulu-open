<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%231B3A5B'/%3E%3Ctext x='50%25' y='53%25' font-family='Arial,sans-serif' font-size='38' font-weight='bold' fill='%23F97316' text-anchor='middle' dominant-baseline='central'%3EL%3C/text%3E%3C/svg%3E">
    <?php
    $metaTitle = t((string) ($pageTitle ?? APP_NAME)) . ' | ' . APP_NAME;
    // Description localisée : t() traduit si une entrée EN existe, sinon garde le FR.
    $metaDescription = t($metaDescription ?? 'LULU-OPEN, la marketplace africaine de l\'emploi et des talents : trouvez un job, un freelance ou un candidat, postulez en un clic et boostez votre CV avec l\'IA.');
    $metaKeywords = $metaKeywords ?? 'emploi Afrique, recrutement, offres d\'emploi, freelance, talents, CV, lettre de motivation IA, candidature, jobs, marketplace talents';
    $canonical = APP_URL . request_path();
    $ogImage = APP_URL . '/og-image.png';
    // Zones privées : non indexables (elles sont aussi bloquées dans robots.txt).
    $noindex = (bool) preg_match('#^/(client|entreprise|admin|messages|abonnement|favorites|applications|setup)(/|$)#', request_path());
    ?>
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
    <meta name="theme-color" content="#0369A1">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="<?= Lang::current() === 'en' ? 'en_US' : 'fr_FR' ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => APP_NAME,
        'url' => APP_URL,
        'description' => $metaDescription,
        'potentialAction' => ['@type' => 'SearchAction', 'target' => APP_URL . '/search?q={query}', 'query-input' => 'required name=query'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => APP_NAME,
        'url' => APP_URL,
        'logo' => $ogImage,
        'description' => $metaDescription,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="d-flex flex-column min-vh-100">
<a href="#main" class="skip-link"><?= t('Aller au contenu') ?></a>
<?php View::partial('components/navbar'); ?>
<main id="main" class="flex-grow-1<?= empty($fullWidth) ? ' py-4' : '' ?>">
    <?php if (empty($fullWidth)): ?>
        <div class="container">
            <?php View::partial('components/alerts'); ?>
            <?= $content ?>
        </div>
    <?php else: ?>
        <div class="container pt-3"><?php View::partial('components/alerts'); ?></div>
        <?= $content ?>
    <?php endif; ?>
</main>
<?php View::partial('components/footer'); ?>
<?php View::partial('components/cookie-consent'); ?>

<!-- Modale de confirmation réutilisable : tout élément avec data-confirm="…" la déclenche -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mx-auto mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                <h5 class="mb-2" data-confirm-title>Confirmer l'action</h5>
                <p class="text-secondary mb-4" data-confirm-message>Voulez-vous vraiment continuer ?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger px-4" data-confirm-ok>Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Apparition au défilement
(function () {
    var els = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('is-visible'); }); return; }
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-visible'); obs.unobserve(en.target); } });
    }, { threshold: 0.12 });
    els.forEach(function (e) { obs.observe(e); });
})();

// Confirmation stylée : remplace confirm() natif. Ajouter data-confirm="message"
// sur un bouton submit, un lien ou un formulaire. Options : data-confirm-title,
// data-confirm-ok (libellé du bouton).
(function () {
    var el = document.getElementById('confirmModal');
    if (!el || typeof bootstrap === 'undefined') return;
    var modal = new bootstrap.Modal(el);
    var titleEl = el.querySelector('[data-confirm-title]');
    var msgEl = el.querySelector('[data-confirm-message]');
    var okEl = el.querySelector('[data-confirm-ok]');
    var pending = null;

    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-confirm]');
        if (!t) return;
        e.preventDefault();
        pending = t;
        msgEl.textContent = t.getAttribute('data-confirm') || 'Voulez-vous vraiment continuer ?';
        titleEl.textContent = t.getAttribute('data-confirm-title') || 'Confirmer l\'action';
        okEl.textContent = t.getAttribute('data-confirm-ok') || 'Confirmer';
        modal.show();
    });

    okEl.addEventListener('click', function () {
        var t = pending; pending = null; modal.hide();
        if (!t) return;
        if (t.tagName === 'A' && t.getAttribute('href')) { window.location.href = t.getAttribute('href'); return; }
        var form = t.form || t.closest('form');
        if (!form) return;
        // Préserve name/value du bouton (ex. decision=rejected) puis envoie sans
        // repasser par onsubmit (évite une double confirmation).
        if (t.name) {
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = t.name; h.value = t.value;
            form.appendChild(h);
        }
        form.submit();
    });
})();
</script>
</body>
</html>
