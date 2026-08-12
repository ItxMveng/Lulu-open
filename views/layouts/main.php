<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="LULU-OPEN — la marketplace qui connecte les talents et les entreprises : offres, candidatures, messagerie et outils IA.">
    <title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
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
</body>
</html>
