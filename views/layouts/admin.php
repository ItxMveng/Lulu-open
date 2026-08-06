<?php
$currentPath = request_path();
$navItems = [
    ['/admin/dashboard', 'bi-speedometer2', 'Tableau de bord'],
    ['/admin/users', 'bi-people', 'Utilisateurs'],
    ['/admin/subscriptions', 'bi-gem', 'Abonnements'],
    ['/admin/categories', 'bi-tags', 'Catégories'],
    ['/admin/messages', 'bi-chat-square-text', 'Messages'],
];
$isActive = static function (string $path) use ($currentPath): bool {
    return $currentPath === $path || str_starts_with($currentPath, $path . '/');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> | Admin <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= e(url('/admin/dashboard')) ?>"><span class="brand-dot"></span>Admin</a>
        <nav class="admin-nav">
            <?php foreach ($navItems as [$path, $icon, $label]): ?>
                <a class="nav-link <?= $isActive($path) ? 'active' : '' ?>" href="<?= e(url($path)) ?>"><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></a>
            <?php endforeach; ?>
            <hr class="my-2" style="border-color: rgba(255,255,255,0.2);">
            <a class="nav-link" href="<?= e(url('/')) ?>"><i class="bi bi-box-arrow-up-right"></i>Voir le site</a>
            <a class="nav-link" href="<?= e(url('/logout')) ?>"><i class="bi bi-box-arrow-right"></i>Déconnexion</a>
        </nav>
    </aside>
    <div class="admin-main">
        <div class="admin-topbar px-4 py-3">
            <span class="fw-semibold text-secondary"><i class="bi bi-shield-lock me-1"></i>Administration LULU-OPEN</span>
        </div>
        <main class="p-4">
            <?php View::partial('components/alerts'); ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
