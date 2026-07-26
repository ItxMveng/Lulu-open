<footer class="site-footer mt-auto pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-2" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>LULU-OPEN</a>
                <p class="text-secondary small mb-0" style="max-width: 32ch;">La marketplace qui connecte les talents et les entreprises : offres, candidatures, messagerie et outils IA.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 mb-3">Explorer</h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/services')) ?>">Prestations</a></li>
                    <li><a href="<?= e(url('/emplois')) ?>">Recrutement</a></li>
                    <li><a href="<?= e(url('/pricing')) ?>">Tarifs</a></li>
                    <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 mb-3">Compte</h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/login')) ?>">Connexion</a></li>
                    <li><a href="<?= e(url('/register')) ?>">Créer un compte</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h3 class="h6 mb-3">Légal</h3>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= e(url('/cgu')) ?>">Conditions générales</a></li>
                    <li><a href="<?= e(url('/privacy')) ?>">Confidentialité</a></li>
                    <li><a href="<?= e(url('/legal')) ?>">Mentions légales</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-4" style="border-color: var(--lulu-border);">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-secondary">
            <span>© <?= date('Y') ?> LULU-OPEN. Tous droits réservés.</span>
            <span>Fait avec soin pour les talents et les entreprises.</span>
        </div>
    </div>
</footer>
