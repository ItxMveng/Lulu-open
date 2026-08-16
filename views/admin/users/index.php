<?php
$users = $users ?? [];
$roleLabels = ['admin' => 'Admin', 'client' => 'Candidat', 'entreprise' => 'Entreprise'];
$statusMap = [
    'active' => ['Actif', 'badge-soft-success'],
    'pending' => ['En attente', 'status-en_attente'],
    'suspended' => ['Suspendu', 'status-rejetee'],
    'deleted' => ['Supprimé', 'text-bg-secondary'],
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Utilisateurs</h1>
        <p class="text-secondary mb-0"><?= count($users) ?> compte(s)</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(url('/api/admin-export?type=users')) ?>"><i class="bi bi-download me-1"></i>Exporter CSV</a>
</div>

<?php if (empty($users)): ?>
    <?php View::partial('components/empty-state', ['icon' => 'bi-people', 'title' => 'Aucun utilisateur']); ?>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-surface-2">
                    <tr><th class="ps-4">#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th class="text-end pe-4"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): $st = (string) ($user['status'] ?? ''); [$sl, $sc] = $statusMap[$st] ?? [$st, 'text-bg-secondary']; ?>
                    <tr>
                        <td class="ps-4 text-secondary"><?= (int) $user['id'] ?></td>
                        <td class="fw-semibold"><?= e((string) $user['name']) ?></td>
                        <td class="text-secondary"><?= e((string) $user['email']) ?></td>
                        <td><span class="badge badge-soft-primary"><?= e($roleLabels[(string) $user['role']] ?? (string) $user['role']) ?></span></td>
                        <td><span class="badge <?= e($sc) ?> status-badge"><?= e($sl) ?></span></td>
                        <td class="text-end pe-4"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/users/' . (int) $user['id'])) ?>">Détails</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
