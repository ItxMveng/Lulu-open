<footer class="site-footer mt-auto pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-2" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>LULU-OPEN</a>
                <p class="text-secondary small mb-0" style="max-width: 32ch;"><?= t('La marketplace qui connecte les talents et les entreprises : offres, candidatures, messagerie et outils IA.') ?></p>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 mb-3"><?= t('Explorer') ?></h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/emplois')) ?>"><?= t('Recrutement') ?></a></li>
                    <li><a href="<?= e(url('/services')) ?>"><?= t('Prestations') ?></a></li>
                    <li><a href="<?= e(url('/categories')) ?>"><?= t('Domaines') ?></a></li>
                    <li><a href="<?= e(url('/ia')) ?>"><?= t('IA & matching') ?></a></li>
                    <li><a href="<?= e(url('/pricing')) ?>"><?= t('Tarifs') ?></a></li>
                    <li><a href="<?= e(url('/contact')) ?>"><?= t('Contact') ?></a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 mb-3"><?= t('Compte') ?></h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/login')) ?>"><?= t('Connexion') ?></a></li>
                    <li><a href="<?= e(url('/register')) ?>"><?= t('Créer un compte') ?></a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h3 class="h6 mb-3"><?= t('Légal') ?></h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/cgu')) ?>"><?= t('Conditions générales') ?></a></li>
                    <li><a href="<?= e(url('/privacy')) ?>"><?= t('Confidentialité') ?></a></li>
                    <li><a href="<?= e(url('/legal')) ?>"><?= t('Mentions légales') ?></a></li>
                </ul>
            </div>
        </div>
        <?php $footerCats = (new Category())->all(); ?>
        <?php if (!empty($footerCats)): ?>
            <div class="mt-4">
                <div class="text-secondary small mb-2"><?= t('Domaines populaires') ?></div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (array_slice($footerCats, 0, 12) as $fc): ?>
                        <a class="badge badge-soft-primary text-decoration-none" href="<?= e(url('/categorie/' . rawurlencode((string) ($fc['slug'] ?? '')))) ?>"><?= e(t((string) ($fc['name'] ?? ''))) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <hr class="my-4" style="border-color: var(--lulu-border);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 small text-secondary">
            <span>© <?= date('Y') ?> LULU-OPEN. <?= t('Tous droits réservés.') ?></span>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" hidden data-pwa-install class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i><?= t('Installer l\'app') ?></button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-translate me-1"></i><?= strtoupper(Lang::current()) ?></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item <?= Lang::current() === 'fr' ? 'active' : '' ?>" href="<?= e(url('/langue/fr')) ?>">Français</a></li>
                        <li><a class="dropdown-item <?= Lang::current() === 'en' ? 'active' : '' ?>" href="<?= e(url('/langue/en')) ?>">English</a></li>
                    </ul>
                </div>
                <?php $cur = CurrencyService::info(); ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-globe2 me-1"></i><?= e($cur['code']) ?></button>
                    <ul class="dropdown-menu dropdown-menu-end" style="max-height:260px;overflow:auto;">
                        <?php foreach (CurrencyService::all() as $code => $c): ?>
                            <li><a class="dropdown-item <?= $code === $cur['code'] ? 'active' : '' ?>" href="<?= e(url('/devise/' . $code)) ?>"><?= e($code) ?> — <?= e($c[1]) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
