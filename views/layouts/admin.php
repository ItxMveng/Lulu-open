<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> | Admin <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= e(url('/admin/dashboard')) ?>">Administration LULU-OPEN</a>
        <div class="d-flex gap-3 align-items-center text-white">
            <a class="text-white text-decoration-none" href="<?= e(url('/')) ?>">Voir le site</a>
            <?php if (is_auth()): ?>
                <a class="btn btn-outline-light btn-sm" href="<?= e(url('/logout')) ?>">Déconnexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container-fluid py-4">
    <?php View::partial('components/alerts'); ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>