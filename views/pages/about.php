<section class="hero">
    <div class="container py-5 text-center">
        <span class="hero-eyebrow mb-3"><i class="bi bi-info-circle"></i> <?= t('À propos') ?></span>
        <h1 class="fw-bold mb-2"><?= t('Connecter les talents et les entreprises') ?></h1>
        <p class="lead mx-auto mb-0" style="max-width: 52ch;"><?= t('LULU-OPEN réunit candidats, prestataires et recruteurs sur une plateforme claire, sûre et pensée pour aller à l\'essentiel.') ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 card-hover p-4">
                    <span class="category-icon mb-3"><i class="bi bi-bullseye"></i></span>
                    <h2 class="h5"><?= t('Notre mission') ?></h2>
                    <p class="text-secondary mb-0"><?= t('Rendre la rencontre entre offre et demande de compétences simple, rapide et humaine.') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 card-hover p-4">
                    <span class="category-icon mb-3"><i class="bi bi-shield-check"></i></span>
                    <h2 class="h5"><?= t('Confiance & sécurité') ?></h2>
                    <p class="text-secondary mb-0"><?= t('Messagerie sécurisée, profils vérifiés et données protégées, pour échanger en confiance.') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 card-hover p-4">
                    <span class="category-icon mb-3"><i class="bi bi-lightning-charge"></i></span>
                    <h2 class="h5"><?= t('Efficacité') ?></h2>
                    <p class="text-secondary mb-0"><?= t('Candidature en un clic, outils IA et tableau de bord centralisé : moins de friction, plus de résultats.') ?></p>
                </div>
            </div>
        </div>

        <div class="cta-band text-center p-5 mt-5">
            <h2 class="mb-3"><?= t('Rejoignez la communauté LULU-OPEN') ?></h2>
            <a class="btn btn-light btn-lg fw-semibold" href="<?= e(url('/register')) ?>"><?= t('Créer un compte gratuit') ?></a>
        </div>
    </div>
</section>
