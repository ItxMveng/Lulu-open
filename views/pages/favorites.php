<section class="py-4">
    <h1 class="mb-4">Mes favoris</h1>
    <div class="row g-4">
        <?php foreach (($favorites ?? []) as $favorite): ?>
            <div class="col-lg-4"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h5"><?= e((string) $favorite['name']) ?></h2><p class="text-secondary mb-0">Rôle: <?= e((string) $favorite['role']) ?></p></div></div></div>
        <?php endforeach; ?>
    </div>
</section>