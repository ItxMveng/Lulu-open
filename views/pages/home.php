<?php $categories = $categories ?? []; ?>

<!-- ============ HERO (épinglé) ============ -->
<div class="hero-pin" id="heroPin">
<section class="hero">
    <div class="hero-floats" aria-hidden="true">
        <span class="float-chip" style="top:14%;left:6%;animation-delay:0s;"><i class="bi bi-code-slash"></i>Développeur</span>
        <span class="float-chip" style="top:26%;right:8%;animation-delay:1.2s;"><i class="bi bi-palette"></i>Designer</span>
        <span class="float-chip" style="top:64%;left:9%;animation-delay:2s;"><i class="bi bi-megaphone"></i>Marketing</span>
        <span class="float-chip" style="bottom:14%;right:10%;animation-delay:0.6s;"><i class="bi bi-graph-up"></i>Data Analyst</span>
        <span class="float-chip" style="top:44%;left:3%;animation-delay:2.6s;"><i class="bi bi-building"></i>Entreprise vérifiée</span>
        <span class="float-chip" style="bottom:24%;right:4%;animation-delay:1.8s;"><i class="bi bi-cup-hot"></i>Cuisinier</span>
    </div>
    <div class="container py-5 py-lg-6">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="hero-eyebrow mb-4"><i class="bi bi-stars"></i> <?= t('La marketplace des talents et des entreprises') ?></span>
                <h1 class="fw-bold mb-3"><?= t('Trouvez le bon') ?> <span class="text-gradient"><?= t('talent') ?></span>,<br class="d-none d-md-block"> <?= t('décrochez la bonne') ?> <span class="text-gradient doodle-underline"><?= t('mission') ?><svg viewBox="0 0 200 12" fill="none" preserveAspectRatio="none"><path d="M2 8 C 40 2, 70 2, 100 6 S 160 12, 198 4" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg></span>.</h1>
                <p class="lead mx-auto mb-4" style="max-width: 46ch;"><?= t('Candidats et prestataires d\'un côté, entreprises et recruteurs de l\'autre. Offres, candidatures, messagerie et outils IA — au même endroit.') ?></p>

                <form action="<?= e(url('/search')) ?>" method="get" class="search-bar mx-auto d-flex flex-column flex-md-row align-items-stretch gap-2" style="max-width: 720px;">
                    <div class="d-flex align-items-center flex-grow-1 px-2">
                        <i class="bi bi-search text-secondary me-2"></i>
                        <input type="text" name="q" class="form-control" placeholder="<?= e(t('Métier, compétence, poste…')) ?>" aria-label="<?= e(t('Rechercher')) ?>">
                    </div>
                    <div class="d-flex align-items-center flex-grow-1 px-2 border-start-md">
                        <i class="bi bi-geo-alt text-secondary me-2"></i>
                        <input type="text" name="location" class="form-control" placeholder="<?= e(t('Ville ou télétravail')) ?>" aria-label="Localisation">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-4"><?= t('Rechercher') ?></button>
                </form>

                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 small text-secondary">
                    <span class="me-1"><?= t('Populaire :') ?></span>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=développeur')) ?>">Développeur</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=designer')) ?>">Designer</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=marketing')) ?>">Marketing</a>
                    <a class="badge badge-soft-primary" href="<?= e(url('/search?q=data')) ?>">Data</a>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-scroll-hint d-none d-lg-flex"><span><?= t('Défilez') ?></span><i class="bi bi-chevron-double-down"></i></div>
</section>
</div><!-- /.hero-pin -->
<div class="wave-divider"><svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path fill="var(--lulu-surface-2)" d="M0,24 C240,48 480,48 720,28 C960,8 1200,8 1440,28 L1440,48 L0,48 Z"/></svg></div>

<!-- ============ CATÉGORIES ============ -->
<?php if (!empty($categories)): ?>
<section class="section reveal">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h3 mb-1"><?= t('Explorez par catégorie') ?></h2>
                <p class="text-secondary mb-0"><?= t('Des profils et des offres dans tous les domaines.') ?></p>
            </div>
            <a class="btn btn-outline-secondary d-none d-sm-inline-flex" href="<?= e(url('/search')) ?>"><?= t('Tout voir') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3 g-lg-4">
            <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a class="category-card" href="<?= e(url('/search?category=' . urlencode((string) ($category['name'] ?? '')) . '&tab=profils')) ?>">
                        <span class="category-icon"><i class="bi <?= e((string) ($category['icon'] ?? 'bi-grid')) ?>"></i></span>
                        <span class="fw-semibold"><?= e(t((string) ($category['name'] ?? ''))) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ FONCTIONNALITÉS ============ -->
<section class="section reveal">
    <div class="container">
        <div class="text-center mb-5">
            <span class="hero-eyebrow mb-3"><i class="bi bi-grid-1x2"></i> <?= t('Nos chiffres') ?></span>
            <h2 class="h3 mb-2"><?= t('Tout ce qu\'il vous faut, au même endroit') ?></h2>
            <p class="text-secondary mb-0 mx-auto" style="max-width: 52ch;"><?= t('Une plateforme complète pensée pour l\'Afrique et ouverte sur le monde.') ?></p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['bi-lightning-charge-fill', 'Candidature en 1 clic', 'Postulez avec votre CV et une lettre générée par l\'IA, sans friction.', 'text-warning'],
                ['bi-robot', 'Assistant IA', 'Analyse de CV, génération de CV et de lettres adaptées à chaque offre.', 'text-primary'],
                ['bi-shield-lock-fill', 'Messagerie sécurisée', 'Échangez directement avec les recruteurs ou les candidats en toute sécurité.', 'text-success'],
                ['bi-patch-check-fill', 'Entreprises vérifiées', 'Chaque entreprise est vérifiée : fini les arnaques, place à la confiance.', 'text-primary'],
                ['bi-globe2', 'Multi-devises', 'Les tarifs s\'affichent automatiquement dans la devise de votre pays.', 'text-success'],
                ['bi-translate', 'Bilingue FR / EN', 'Naviguez en français ou en anglais, selon votre préférence.', 'text-warning'],
            ];
            foreach ($features as [$icon, $title, $desc, $color]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-hover h-100 p-4">
                        <span class="category-icon mb-3"><i class="bi <?= e($icon) ?> <?= e($color) ?>"></i></span>
                        <h3 class="h5 mb-2"><?= t($title) ?></h3>
                        <p class="text-secondary small mb-0"><?= t($desc) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ COMMENT ÇA MARCHE ============ -->
<section class="section bg-surface-2 reveal">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 mb-2"><?= t('Deux parcours, une plateforme') ?></h2>
            <p class="text-secondary mb-0"><?= t('Que vous cherchiez une opportunité ou un talent, tout est fluide.') ?></p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6 reveal-left">
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
            <div class="col-lg-6 reveal-right">
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

<!-- ============ CHIFFRES RÉELS ============ -->
<?php $hs = $homeStats ?? []; ?>
<section class="section reveal">
    <div class="container">
        <div class="cta-band p-4 p-lg-5">
            <div class="row text-center g-4 text-white">
                <div class="col-6 col-lg-3"><div class="stat-value text-white"><?= (int) ($hs['talents'] ?? 0) ?>+</div><div class="opacity-75 small"><?= t('talents actifs') ?></div></div>
                <div class="col-6 col-lg-3"><div class="stat-value text-white"><?= (int) ($hs['offers'] ?? 0) ?>+</div><div class="opacity-75 small"><?= t('offres en ligne') ?></div></div>
                <div class="col-6 col-lg-3"><div class="stat-value text-white"><?= (int) ($hs['categories'] ?? 0) ?></div><div class="opacity-75 small"><?= t('domaines métiers') ?></div></div>
                <div class="col-6 col-lg-3"><div class="stat-value text-white"><?= (int) ($hs['companies'] ?? 0) ?></div><div class="opacity-75 small"><?= t('entreprises vérifiées') ?></div></div>
            </div>
        </div>
    </div>
</section>

<!-- ============ TÉMOIGNAGES ============ -->
<section class="section reveal">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 mb-2"><?= t('Ils en parlent mieux que nous') ?></h2>
            <p class="text-secondary mb-0"><?= t('Des talents et des entreprises qui avancent avec LULU-OPEN.') ?></p>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['Aminata D.', 'Développeuse — Abidjan', 'Grâce à l\'assistant IA, j\'ai décroché un entretien en une semaine. Le CV généré était bluffant.', 'AD'],
                ['Sahel Talents', 'Cabinet de recrutement — Dakar', 'Le matching IA nous fait gagner un temps fou pour trier les candidatures reçues.', 'ST'],
                ['Kwame M.', 'Designer — Douala', 'Enfin une plateforme sérieuse où les entreprises sont vérifiées. Je postule en confiance.', 'KM'],
            ];
            foreach ($testimonials as [$name, $role, $quote, $ini]): ?>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="text-warning mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                        <p class="mb-3">« <?= t($quote) ?> »</p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                            <span class="avatar-sm"><?= e($ini) ?></span>
                            <div><div class="fw-semibold small"><?= e($name) ?></div><div class="text-secondary" style="font-size:.8rem;"><?= e($role) ?></div></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section bg-surface-2 reveal">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h2 class="h3 mb-2"><?= t('Questions fréquentes') ?></h2>
                    <p class="text-secondary mb-0"><?= t('Tout ce que vous devez savoir avant de commencer.') ?></p>
                </div>
                <div class="accordion" id="faqAccordion">
                    <?php
                    $faq = [
                        ['Est-ce gratuit ?', 'Oui, la création de compte et la candidature sont gratuites. Des plans payants débloquent des fonctionnalités avancées.'],
                        ['Comment fonctionne l\'IA ?', 'Notre assistant analyse votre profil et l\'offre pour générer un CV et une lettre adaptés, et évaluer votre adéquation.'],
                        ['Comment être sûr qu\'une entreprise est fiable ?', 'Toutes les entreprises passent par une vérification manuelle de notre équipe avant de pouvoir publier des offres.'],
                        ['Dans quels pays êtes-vous présents ?', 'La plateforme est pensée pour l\'Afrique et ouverte au monde entier, avec gestion automatique des devises locales.'],
                    ];
                    foreach ($faq as $i => [$q, $a]): ?>
                        <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"><?= t($q) ?></button>
                            </h3>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary"><?= t($a) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CTA FINALE ============ -->
<section class="section pt-0 reveal">
    <div class="container">
        <div class="cta-band text-center p-5 p-lg-6">
            <h2 class="mb-3"><?= t('Prêt à passer à la vitesse supérieure ?') ?></h2>
            <p class="mb-4 mx-auto opacity-75" style="max-width: 48ch;"><?= t('Rejoignez LULU-OPEN gratuitement et connectez-vous aux bonnes opportunités.') ?></p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('/register')) ?>"><?= t('Créer un compte') ?></a>
                <a class="btn btn-outline-light btn-lg px-4" href="<?= e(url('/pricing')) ?>"><?= t('Voir les tarifs') ?></a>
            </div>
        </div>
    </div>
</section>

<script>
// Hero épinglé : révélation progressive des badges pendant que la scène reste fixe
(function () {
    var pin = document.getElementById('heroPin');
    if (!pin || window.matchMedia('(max-width: 991px)').matches || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        pin && pin.querySelectorAll('.float-chip').forEach(function (c) { c.classList.add('is-in'); });
        return;
    }
    var chips = Array.prototype.slice.call(pin.querySelectorAll('.float-chip'));
    function onScroll() {
        var rect = pin.getBoundingClientRect();
        var total = pin.offsetHeight - window.innerHeight;
        var scrolled = Math.min(Math.max(-rect.top, 0), total);
        var progress = total > 0 ? scrolled / total : 1;
        chips.forEach(function (c, i) {
            var threshold = ((i + 1) / (chips.length + 2)) * 0.7;
            c.classList.toggle('is-in', progress >= threshold);
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
