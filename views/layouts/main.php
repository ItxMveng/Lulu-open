<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'LULU-OPEN' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/animations.css" rel="stylesheet">
    <?= $additionalCSS ?? '' ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    
    <?php if (isset($showNavbar) && $showNavbar): ?>
        <?php include BASE_PATH . '/views/components/navbar.php'; ?>
    <?php endif; ?>
    
    <?php if (isset($showSidebar) && $showSidebar): ?>
        <div class="d-flex">
            <?php include BASE_PATH . '/views/components/sidebar.php'; ?>
            <main class="flex-grow-1 main-content">
    <?php else: ?>
        <main class="main-content">
    <?php endif; ?>
    
    <?php if ($flashMessage = getFlashMessage()): ?>
        <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flashMessage['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?= $content ?>
    
    <?php if (isset($showSidebar) && $showSidebar): ?>
            </main>
        </div>
    <?php else: ?>
        </main>
    <?php endif; ?>
    
    <?php if (isset($showFooter) && $showFooter): ?>
        <?php include BASE_PATH . '/views/components/footer.php'; ?>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/main.js"></script>
    <?= $additionalJS ?? '' ?>
</body>
</html>