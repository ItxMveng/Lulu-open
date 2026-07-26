<section class="py-4">
    <h1 class="mb-4">Mes candidatures envoyées</h1>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Offre</th><th>Statut</th><th>Envoyée le</th><th></th></tr></thead>
                <tbody>
                <?php foreach (($applications ?? []) as $application): ?>
                    <tr>
                        <td><?= e((string) $application['title']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e((string) $application['status']) ?></span></td>
                        <td><?= e((string) $application['created_at']) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/applications/' . $application['id'] . '/delete')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Retirer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>