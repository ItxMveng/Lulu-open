<section class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Mes offres</h1>
        <a class="btn btn-primary" href="<?= e(url('/entreprise/offres/new')) ?>">Nouvelle offre</a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Titre</th><th>Type</th><th>Statut</th><th>Localisation</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach (($offers ?? []) as $offer): ?>
                    <tr>
                        <td><?= e($offer['title']) ?></td>
                        <td><?= e($offer['type']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($offer['status']) ?></span></td>
                        <td><?= e((string) ($offer['location'] ?? '')) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/entreprise/offres/' . $offer['id'] . '/edit')) ?>">Modifier</a>
                            <form method="post" action="<?= e(url('/entreprise/offres/' . $offer['id'] . '/delete')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Fermer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>