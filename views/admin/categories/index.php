<?php $categories = $categories ?? []; ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Catégories</h1>
    <p class="text-secondary mb-0"><?= count($categories) ?> catégorie(s)</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form method="post" action="<?= e(url('/admin/categories')) ?>" class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Ajouter une catégorie</h2>
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="cat_name">Nom</label><input class="form-control" id="cat_name" type="text" name="name" required></div>
                <div class="mb-3"><label class="form-label" for="cat_slug">Slug</label><input class="form-control" id="cat_slug" type="text" name="slug" placeholder="ex: developpement-web" required></div>
                <div class="mb-3"><label class="form-label" for="cat_icon">Icône (Bootstrap Icons)</label><input class="form-control" id="cat_icon" type="text" name="icon" placeholder="ex: bi-code-slash"></div>
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Enregistrer</button>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <?php if (empty($categories)): ?>
                <div class="card-body"><?php View::partial('components/empty-state', ['icon' => 'bi-tags', 'title' => 'Aucune catégorie']); ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-surface-2"><tr><th class="ps-4">Icône</th><th>Nom</th><th>Slug</th></tr></thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="ps-4"><span class="category-icon" style="width:2.25rem;height:2.25rem;font-size:1rem;"><i class="bi <?= e((string) ($category['icon'] ?? 'bi-tag')) ?>"></i></span></td>
                                <td class="fw-semibold"><?= e((string) $category['name']) ?></td>
                                <td class="text-secondary"><code><?= e((string) $category['slug']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
