<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Utilisateurs</h1>
        <a class="btn btn-outline-primary" href="<?= e(url('/api/admin-export.php?type=users')) ?>">Exporter CSV</a>
    </div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th></th></tr></thead><tbody><?php foreach (($users ?? []) as $user): ?><tr><td><?= e((string) $user['id']) ?></td><td><?= e((string) $user['name']) ?></td><td><?= e((string) $user['email']) ?></td><td><?= e((string) $user['role']) ?></td><td><?= e((string) $user['status']) ?></td><td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/users/' . $user['id'])) ?>">Voir</a></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>