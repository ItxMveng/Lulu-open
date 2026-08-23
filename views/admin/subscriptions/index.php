<?php
$subscriptions = $subscriptions ?? [];
$statusMap = ['active' => ['Actif', 'badge-soft-success'], 'inactive' => ['Inactif', 'text-bg-secondary'], 'cancelled' => ['Annulé', 'status-rejetee']];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Abonnements</h1>
        <p class="text-secondary mb-0"><?= count($subscriptions) ?> abonnement(s)</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/subscriptions/stats')) ?>"><i class="bi bi-bar-chart me-1"></i>Statistiques</a>
</div>

<?php if (empty($subscriptions)): ?>
    <?php View::partial('components/empty-state', ['icon' => 'bi-gem', 'title' => 'Aucun abonnement']); ?>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-surface-2">
                    <tr><th class="ps-4">#</th><th>Utilisateur</th><th>Plan</th><th>Statut</th><th class="text-end pe-4"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($subscriptions as $subscription): $st = (string) ($subscription['status'] ?? ''); [$sl, $sc] = $statusMap[$st] ?? [$st, 'text-bg-secondary']; ?>
                    <tr>
                        <td class="ps-4 text-secondary"><?= (int) $subscription['id'] ?></td>
                        <td>#<?= (int) ($subscription['user_id'] ?? 0) ?></td>
                        <td class="fw-semibold"><?= e((string) ($subscription['plan_name'] ?? '')) ?></td>
                        <td><span class="badge <?= e($sc) ?> status-badge"><?= e($sl) ?></span></td>
                        <td class="text-end pe-4">
                            <?php if ($st === 'active'): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(url('/admin/subscriptions/' . (int) $subscription['id'] . '/cancel')) ?>" data-confirm="L'abonnement sera annulé immédiatement." data-confirm-title="Annuler cet abonnement ?" data-confirm-ok="Annuler l'abonnement">Annuler</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
