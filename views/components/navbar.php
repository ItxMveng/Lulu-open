<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url('/')) ?>">LULU-OPEN</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/services')) ?>">Prestations</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/emplois')) ?>">Recrutement</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/pricing')) ?>">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/contact')) ?>">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (is_auth()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle position-relative" id="notificationsToggle" data-bs-toggle="dropdown" type="button">
                            Notifications <span id="notificationsCount" class="badge text-bg-danger">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 320px;" id="notificationsMenu">
                            <p class="text-secondary small mb-0">Chargement...</p>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!is_auth()): ?>
                    <a class="btn btn-outline-primary" href="<?= e(url('/login')) ?>">Connexion</a>
                    <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Créer un compte</a>
                <?php elseif (current_role() === 'admin'): ?>
                    <a class="btn btn-outline-dark" href="<?= e(url('/admin/dashboard')) ?>">Admin</a>
                    <a class="btn btn-danger" href="<?= e(url('/logout')) ?>">Déconnexion</a>
                <?php elseif (current_role() === 'client'): ?>
                    <a class="btn btn-outline-primary" href="<?= e(url('/client/dashboard')) ?>">Espace client</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/messages')) ?>">Messages</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/abonnement')) ?>">Abonnement</a>
                    <a class="btn btn-danger" href="<?= e(url('/logout')) ?>">Déconnexion</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary" href="<?= e(url('/entreprise/dashboard')) ?>">Espace entreprise</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/messages')) ?>">Messages</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/entreprise/offres')) ?>">Mes offres</a>
                    <a class="btn btn-danger" href="<?= e(url('/logout')) ?>">Déconnexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php if (is_auth()): ?>
<script>
document.getElementById('notificationsToggle')?.addEventListener('click', async () => {
    const response = await fetch('<?= e(url('/api/notifications.php?action=list')) ?>');
    const payload = await response.json();
    const items = payload.items || [];
    document.getElementById('notificationsCount').textContent = items.length;
    const menu = document.getElementById('notificationsMenu');
    menu.innerHTML = items.length ? items.map(item => `<div class="dropdown-item small">${item.type}</div>`).join('') : '<p class="text-secondary small mb-0">Aucune notification non lue.</p>';
});
</script>
<?php endif; ?>