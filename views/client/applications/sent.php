<?php $applications = $applications ?? []; ?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Mes candidatures</h1>
        <p class="text-secondary mb-0"><?= count($applications) ?> candidature(s) envoyée(s)</p>
    </div>

    <?php if (empty($applications)): ?>
        <?php View::partial('components/empty-state', [
            'icon' => 'bi-send',
            'title' => 'Vous n\'avez pas encore postulé',
            'text' => 'Parcourez les offres et postulez en un clic.',
            'actionUrl' => url('/search'),
            'actionLabel' => 'Trouver une offre',
        ]); ?>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-surface-2">
                        <tr><th class="ps-4">Offre</th><th>Envoyée le</th><th>Statut</th><th class="text-end pe-4"></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><a class="text-body" href="<?= e(url('/offres/' . (int) ($application['offer_id'] ?? 0))) ?>"><?= e((string) $application['title']) ?></a></td>
                            <td class="text-secondary"><?= e(date('d/m/Y', strtotime((string) ($application['created_at'] ?? 'now')))) ?></td>
                            <td><?php View::partial('components/status-badge', ['status' => (string) $application['status']]); ?></td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-2 justify-content-end">
                                    <?php if (!empty($application['entreprise_id'])): ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/messages/nouveau/' . (int) $application['entreprise_id'])) ?>" title="Contacter le recruteur"><i class="bi bi-chat-dots"></i></a>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('/applications/' . (int) $application['id'] . '/delete')) ?>" onsubmit="return confirm('Retirer cette candidature ?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash me-1"></i>Retirer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
