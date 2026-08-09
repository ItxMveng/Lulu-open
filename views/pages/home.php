<?php $categories = $categories ?? []; ?>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="container py-5 py-lg-6">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="hero-eyebrow mb-4"><i class="bi bi-stars"></i> La marketplace des talents et des entreprises</span>
                <h1 class="fw-bold mb-3">Trouvez le bon <span class="text-gradient">talent</span>,<br class="d-none d-md-block"> décrochez la bonne <span class="text-gradient">mission</span>.</h1>
                <p class="lead mx-auto mb-4" style="max-width: 46ch;">Candidats et prestataires d'un côté, entreprises et recruteurs de l'autre. Offres, candidatures, messagerie et outils IA — au même endroit.</p>

                <form action="<?= e(url('/search')) ?>" method="get" class="search-bar mx-auto d-flex flex-column flex-md-row align-items-stretch gap-2" style="max-width: 720px;">
                    <div class="d-flex align-items-center flex-grow-1 px-2">
                        <i class="bi bi-search text-secondary me-2"></i>
                        <input type="text" name="q" class="form-control" placeholder="Métier, compétence, poste…" aria-label="Recherche">
                    </div>
                    <div class="d-flex align-items-center flex-grow-1 px-2 border-start-md">
                        <i class="bi bi-geo-alt text-secondary me-2"></i>
                        <input type="text" name="location" class="form-control" placeholder="Ville ou télétravail" aria-label="Localisation">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-4">Rechercher</button>
                </form>

                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 small text-secondary">
                    <span class="me-1">Populaire :</span>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=développeur')) ?>">Développeur</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=designer')) ?>">Designer</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=marketing')) ?>">Marketing</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=data')) ?>">Data</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CATÉGORIES ============ -->
<?php if (!empty($categories)): ?>
<section class="section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h3 mb-1">Explorez par catégorie</h2>
                <p class="text-secondary mb-0">Des profils et des offres dans tous les domaines.</p>
            </div>
            <a class="btn btn-outline-secondary d-none d-sm-inline-flex" href="<?= e(url('/search')) ?>">Tout voir <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3 g-lg-4">
            <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a class="category-card" href="<?= e(url('/search?category=' . urlencode((string) ($category['name'] ?? '')) . '&tab=profils')) ?>">
                        <span class="category-icon"><i class="bi <?= e((string) ($category['icon'] ?? 'bi-grid')) ?>"></i></span>
                        <span class="fw-semibold"><?= e((string) ($category['name'] ?? '')) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ COMMENT ÇA MARCHE ============ -->
<section class="section bg-surface-2">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 mb-2">Deux parcours, une plateforme</h2>
            <p class="text-secondary mb-0">Que vous cherchiez une opportunité ou un talent, tout est fluide.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 p-4 p-lg-5">
                    <span class="badge badge-soft-primary align-self-start mb-3"><i class="bi bi-person-badge me-1"></i> Candidats & prestataires</span>
                    <h3 class="h4 mb-4">Mettez-vous en avant</h3>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex gap-3"><span class="step-num">1</span><div><h4 class="h6 mb-1">Créez votre profil</h4><p class="text-secondary small mb-0">Compétences, expériences, services proposés.</p></div></div>
                        <div class="d-flex gap-3"><span class="step-num">2</span><div><h4 class="h6 mb-1">Postulez aux offres</h4><p class="text-secondary small mb-0">Candidatez en un clic avec votre CV et une lettre.</p></div></div>
                        <div class="d-flex gap-3"><span class="step-num">3</span><div><h4 class="h6 mb-1">Soyez contacté</h4><p class="text-secondary small mb-0">Les entreprises vous trouvent et vous écrivent.</p></div></div>
                    </div>
                    <a class="btn btn-primary mt-4 align-self-start" href="<?= e(url('/register')) ?>">Je suis un talent</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 p-4 p-lg-5">
                    <span class="badge badge-soft-success align-self-start mb-3"><i class="bi bi-building me-1"></i> Entreprises & recruteurs</span>
                    <h3 class="h4 mb-4">Recrutez plus vite</h3>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex gap-3"><span class="step-num" style="background: var(--lulu-accent);">1</span><div><h4 class="h6 mb-1">Publiez vos offres</h4><p class="text-secondary small mb-0">Emploi, mission ou stage, en quelques minutes.</p></div></div>
                        <div class="d-flex gap-3"><span class="step-num" style="background: var(--lulu-accent);">2</span><div><h4 class="h6 mb-1">Recevez les candidatures</h4><p class="text-secondary small mb-0">Centralisées, avec analyse IA à la clé.</p></div></div>
                        <div class="d-flex gap-3"><span class="step-num" style="background: var(--lulu-accent);">3</span><div><h4 class="h6 mb-1">Sourcez les talents</h4><p class="text-secondary small mb-0">Recherchez et contactez directement les profils.</p></div></div>
                    </div>
                    <a class="btn btn-accent mt-4 align-self-start" href="<?= e(url('/register')) ?>">Je recrute</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CONFIANCE / STATS ============ -->
<section class="section">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-lg-3"><div class="stat-value">2</div><div class="text-secondary small">parcours dédiés</div></div>
            <div class="col-6 col-lg-3"><div class="stat-value"><i class="bi bi-lightning-charge-fill text-warning"></i></div><div class="text-secondary small">candidature en 1 clic</div></div>
            <div class="col-6 col-lg-3"><div class="stat-value"><i class="bi bi-robot"></i></div><div class="text-secondary small">analyse IA des CV</div></div>
            <div class="col-6 col-lg-3"><div class="stat-value"><i class="bi bi-shield-check text-success"></i></div><div class="text-secondary small">messagerie sécurisée</div></div>
        </div>
    </div>
</section>

<!-- ============ CTA FINALE ============ -->
<section class="section pt-0">
    <div class="container">
        <div class="cta-band text-center p-5 p-lg-6">
            <h2 class="mb-3">Prêt à passer à la vitesse supérieure ?</h2>
            <p class="mb-4 mx-auto opacity-75" style="max-width: 48ch;">Rejoignez LULU-OPEN gratuitement et connectez-vous aux bonnes opportunités.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('/register')) ?>">Créer un compte</a>
                <a class="btn btn-outline-light btn-lg px-4" href="<?= e(url('/pricing')) ?>">Voir les tarifs</a>
            </div>
        </div>
    </div>
</section>
