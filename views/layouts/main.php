<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    $metaTitle = ($pageTitle ?? APP_NAME) . ' | ' . APP_NAME;
    $metaDescription = $metaDescription ?? 'LULU-OPEN, la marketplace africaine de l\'emploi et des talents : trouvez un job, un freelance ou un candidat, postulez en un clic et boostez votre CV avec l\'IA.';
    $metaKeywords = $metaKeywords ?? 'emploi Afrique, recrutement, offres d\'emploi, freelance, talents, CV, lettre de motivation IA, candidature, jobs, marketplace talents';
    $canonical = APP_URL . request_path();
    ?>
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0369A1">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="<?= Lang::current() === 'en' ? 'en_US' : 'fr_FR' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
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
</head>
<body class="d-flex flex-column min-vh-100">
<?php View::partial('components/navbar'); ?>
<main class="flex-grow-1<?= empty($fullWidth) ? ' py-4' : '' ?>">
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
</script>
</body>
</html>
