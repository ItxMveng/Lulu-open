<?php
$authed = is_auth();
$role = current_role();
$authUser = auth_user() ?? [];
$userName = trim((string) ($authUser['name'] ?? ''));
$initials = $userName !== '' ? mb_strtoupper(mb_substr($userName, 0, 1)) : '?';
if (preg_match('/\s(\S)/u', $userName, $m)) {
    $initials .= mb_strtoupper($m[1]);
}
$roleLabels = ['client' => 'Candidat', 'entreprise' => 'Entreprise', 'admin' => 'Administrateur'];
$dashboardPath = dashboard_path_for_role($role);
$profilePath = $role === 'entreprise' ? '/entreprise/profile/edit' : '/client/profile/edit';
?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>LULU-OPEN</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <?php if (!$authed): ?>
                <!-- Visiteur : liens publics -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/services')) ?>">Prestations</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/emplois')) ?>">Recrutement</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/pricing')) ?>">Tarifs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/contact')) ?>">Contact</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                    <a class="btn btn-outline-primary" href="<?= e(url('/login')) ?>">Connexion</a>
                    <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Créer un compte</a>
                </div>
            <?php else: ?>
                <!-- Connecté : liens de rôle uniquement (pas de liens publics) -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($role === 'client'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/search')) ?>">Rechercher</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/client/candidatures')) ?>">Mes candidatures</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/client/ia')) ?>">Outils IA</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/messages')) ?>">Messages</a></li>
                    <?php elseif ($role === 'entreprise'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/entreprise/offres')) ?>">Mes offres</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/entreprise/candidatures')) ?>">Candidatures</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/messages')) ?>">Messages</a></li>
                    <?php elseif ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/admin/users')) ?>">Utilisateurs</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/admin/subscriptions')) ?>">Abonnements</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/admin/categories')) ?>">Catégories</a></li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <!-- Notifications -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary position-relative" id="notificationsToggle" data-bs-toggle="dropdown" type="button" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <span id="notificationsCount" class="badge text-bg-danger position-absolute top-0 start-100 translate-middle rounded-pill" style="display:none;">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 300px;" id="notificationsMenu">
                            <p class="text-secondary small mb-0 px-2 py-1">Chargement…</p>
                        </div>
                    </div>

                    <!-- Menu utilisateur -->
                    <div class="dropdown">
                        <button class="user-menu-toggle" id="userMenuToggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                            <span class="avatar-sm"><?= e($initials) ?></span>
                            <span class="d-none d-sm-inline text-truncate" style="max-width: 120px;"><?= e($userName !== '' ? $userName : 'Mon compte') ?></span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <div class="px-3 py-2">
                                <div class="fw-semibold text-truncate"><?= e($userName !== '' ? $userName : 'Mon compte') ?></div>
                                <div class="text-secondary small"><?= e($roleLabels[$role] ?? '') ?></div>
                            </div>
                            <hr class="my-1">
                            <a class="dropdown-item" href="<?= e(url($dashboardPath)) ?>"><i class="bi bi-grid me-2"></i>Tableau de bord</a>
                            <?php if ($role !== 'admin'): ?>
                                <a class="dropdown-item" href="<?= e(url($profilePath)) ?>"><i class="bi bi-person-gear me-2"></i>Mon profil</a>
                                <a class="dropdown-item" href="<?= e(url('/abonnement')) ?>"><i class="bi bi-gem me-2"></i>Mon abonnement</a>
                            <?php endif; ?>
                            <hr class="my-1">
                            <a class="dropdown-item text-danger" href="<?= e(url('/logout')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php if ($authed): ?>
<script>
document.getElementById('notificationsToggle')?.addEventListener('click', async () => {
    try {
        const response = await fetch('<?= e(url('/api/notifications?action=list')) ?>');
        const payload = await response.json();
        const items = payload.items || [];
        const count = document.getElementById('notificationsCount');
        count.textContent = items.length;
        count.style.display = items.length ? '' : 'none';
        const menu = document.getElementById('notificationsMenu');
        menu.innerHTML = items.length
            ? items.map(item => `<div class="dropdown-item small text-wrap">${item.type}</div>`).join('')
            : '<p class="text-secondary small mb-0 px-2 py-1">Aucune notification non lue.</p>';
    } catch (e) { /* silencieux */ }
});
</script>
<?php endif; ?>
