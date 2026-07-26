<section class="py-4">
    <h1 class="mb-4">Candidatures reçues</h1>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Offre</th><th>Statut</th><th>CV</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach (($applications ?? []) as $application): ?>
                    <tr>
                        <td><?= e((string) $application['title']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e((string) $application['status']) ?></span></td>
                        <td><?= !empty($application['cv_path']) ? e((string) $application['cv_path']) : 'Aucun CV' ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/entreprise/candidatures/' . $application['id'] . '/status')) ?>" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <select class="form-select form-select-sm" name="status">
                                    <?php foreach (['en_attente', 'vue', 'entretien', 'rejetee', 'acceptee'] as $status): ?><option value="<?= e($status) ?>" <?= $application['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Mettre à jour</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>