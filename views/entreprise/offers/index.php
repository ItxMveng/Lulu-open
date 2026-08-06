<?php $offers = $offers ?? []; ?>
<section class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Mes offres</h1>
            <p class="text-secondary mb-0"><?= count($offers) ?> offre(s) publiée(s)</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/entreprise/offres/new')) ?>"><i class="bi bi-plus-lg me-1"></i>Nouvelle offre</a>
    </div>

    <?php if (empty($offers)): ?>
        <?php View::partial('components/empty-state', [
            'icon' => 'bi-megaphone',
            'title' => 'Aucune offre pour le moment',
            'text' => 'Publiez votre première offre pour recevoir des candidatures.',
            'actionUrl' => url('/entreprise/offres/new'),
            'actionLabel' => 'Publier une offre',
        ]); ?>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-surface-2">
                        <tr>
                            <th class="ps-4">Titre</th><th>Type</th><th>Statut</th><th>Localisation</th><th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $offer): $st = (string) ($offer['status'] ?? 'active'); ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><a class="text-body" href="<?= e(url('/offres/' . (int) $offer['id'])) ?>"><?= e((string) $offer['title']) ?></a></td>
                            <td class="text-capitalize"><?= e((string) ($offer['type'] ?? '')) ?></td>
                            <td><span class="badge <?= $st === 'active' ? 'badge-soft-success' : 'text-bg-secondary' ?>"><?= $st === 'active' ? 'Active' : e($st) ?></span></td>
                            <td class="text-secondary"><?= e((string) ($offer['location'] ?? '—')) ?></td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/entreprise/offres/' . (int) $offer['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= e(url('/entreprise/offres/' . (int) $offer['id'] . '/delete')) ?>" class="d-inline" onsubmit="return confirm('Fermer cette offre ?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button>
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
