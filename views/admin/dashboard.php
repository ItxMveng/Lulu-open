<?php
$stats = $stats ?? [];
$recentUsers = $recentUsers ?? [];
$roleLabels = ['client' => 'Talent', 'entreprise' => 'Entreprise', 'admin' => 'Admin'];
$statusMap = ['active' => ['Actif', 'badge-soft-success'], 'pending' => ['En attente', 'status-en_attente'], 'suspended' => ['Suspendu', 'status-rejetee'], 'deleted' => ['Supprimé', 'text-bg-secondary']];
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Tableau de bord</h1>
    <p class="text-secondary mb-0">Vue d'ensemble et pilotage de la plateforme.</p>
</div>

<?php if ((int) ($stats['pending'] ?? 0) > 0): ?>
    <a href="<?= e(url('/admin/verifications')) ?>" class="lulu-alert lulu-alert-warning mb-4 text-decoration-none">
        <i class="bi bi-patch-exclamation-fill"></i>
        <div><strong><?= (int) $stats['pending'] ?> entreprise(s)</strong> en attente de vérification. Cliquez pour traiter les dossiers.</div>
    </a>
<?php endif; ?>

<div class="row g-3">
    <?php
    $tiles = [
        ['/admin/users', 'bi-people', 'Utilisateurs', $stats['users'] ?? 0],
        ['/admin/users', 'bi-person-badge', 'Talents', $stats['talents'] ?? 0],
        ['/admin/verifications', 'bi-building-check', 'Entreprises vérifiées', $stats['verified'] ?? 0],
        ['/admin/verifications', 'bi-hourglass-split', 'En attente', $stats['pending'] ?? 0],
        ['/admin/subscriptions', 'bi-megaphone', 'Offres actives', $stats['offers'] ?? 0],
        ['/admin/users', 'bi-send', 'Candidatures', $stats['applications'] ?? 0],
        ['/admin/subscriptions', 'bi-gem', 'Abonnements actifs', $stats['subscriptions'] ?? 0],
        ['/admin/categories', 'bi-tags', 'Catégories', $stats['categories'] ?? 0],
    ];
    foreach ($tiles as [$href, $icon, $label, $value]): ?>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url($href)) ?>">
                <span class="stat-ic"><i class="bi <?= e($icon) ?>"></i></span>
                <span><span class="stat-n"><?= (int) $value ?></span><span class="stat-l d-block"><?= e($label) ?></span></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Inscriptions récentes</h2>
                    <a class="small fw-semibold" href="<?= e(url('/admin/users')) ?>">Tous les utilisateurs</a>
                </div>
                <?php if (empty($recentUsers)): ?>
                    <p class="text-secondary small mb-0">Aucune inscription.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-surface-2"><tr><th class="ps-3">Nom</th><th>Rôle</th><th>Statut</th><th>Inscrit le</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentUsers as $u): $st = (string) ($u['status'] ?? ''); [$sl, $sc] = $statusMap[$st] ?? [$st, 'text-bg-secondary']; ?>
                                <tr>
                                    <td class="ps-3"><a class="fw-semibold text-body" href="<?= e(url('/admin/users/' . (int) $u['id'])) ?>"><?= e((string) $u['name']) ?></a><div class="text-secondary small"><?= e((string) $u['email']) ?></div></td>
                                    <td><span class="badge badge-soft-primary"><?= e($roleLabels[(string) $u['role']] ?? (string) $u['role']) ?></span></td>
                                    <td><span class="badge <?= e($sc) ?> status-badge"><?= e($sl) ?></span><?php if (($u['role'] ?? '') === 'entreprise' && ($u['verification_status'] ?? '') === 'verified'): ?> <i class="bi bi-patch-check-fill text-success" title="Vérifiée"></i><?php endif; ?></td>
                                    <td class="text-secondary small"><?= e(date('d/m/Y', strtotime((string) $u['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing:.05em;">Actions rapides</h2>
                <div class="d-flex flex-column gap-2">
                    <a class="quick-link" href="<?= e(url('/admin/verifications')) ?>"><span class="qic"><i class="bi bi-patch-check"></i></span> Vérifications entreprises</a>
                    <a class="quick-link" href="<?= e(url('/admin/users')) ?>"><span class="qic"><i class="bi bi-people"></i></span> Gérer les utilisateurs</a>
                    <a class="quick-link" href="<?= e(url('/admin/subscriptions')) ?>"><span class="qic"><i class="bi bi-gem"></i></span> Abonnements</a>
                    <a class="quick-link" href="<?= e(url('/admin/categories')) ?>"><span class="qic"><i class="bi bi-tags"></i></span> Catégories</a>
                    <a class="quick-link" href="<?= e(url('/api/admin-export?type=users')) ?>"><span class="qic"><i class="bi bi-download"></i></span> Exporter les utilisateurs</a>
                    <a class="quick-link" href="<?= e(url('/')) ?>" target="_blank"><span class="qic"><i class="bi bi-box-arrow-up-right"></i></span> Voir le site public</a>
                </div>
            </div>
        </div>
    </div>
</div>
