<?php
$applications = $applications ?? [];
$statusOptions = ['en_attente' => 'En attente', 'vue' => 'Vue', 'entretien' => 'Entretien', 'acceptee' => 'Acceptée', 'rejetee' => 'Refusée'];
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Candidatures reçues</h1>
        <p class="text-secondary mb-0"><?= count($applications) ?> candidature(s)</p>
    </div>

    <?php if (empty($applications)): ?>
        <?php View::partial('components/empty-state', [
            'icon' => 'bi-people',
            'title' => 'Aucune candidature pour le moment',
            'text' => 'Publiez des offres pour attirer les talents.',
            'actionUrl' => url('/entreprise/offres/new'),
            'actionLabel' => 'Publier une offre',
        ]); ?>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-surface-2">
                        <tr><th class="ps-4">Offre</th><th>Reçue le</th><th>CV</th><th>Statut</th><th class="text-end pe-4">Mettre à jour</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= e((string) $application['title']) ?></td>
                            <td class="text-secondary"><?= e(date('d/m/Y', strtotime((string) ($application['created_at'] ?? 'now')))) ?></td>
                            <td>
                                <?php if (!empty($application['cv_path'])): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/' . ltrim((string) $application['cv_path'], '/'))) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>Voir</a>
                                <?php else: ?>
                                    <span class="text-secondary small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php View::partial('components/status-badge', ['status' => (string) $application['status']]); ?></td>
                            <td class="pe-4">
                                <form method="post" action="<?= e(url('/entreprise/candidatures/' . (int) $application['id'] . '/status')) ?>" class="d-flex gap-2 justify-content-end">
                                    <?= csrf_field() ?>
                                    <select class="form-select form-select-sm" style="max-width: 150px;" name="status">
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
