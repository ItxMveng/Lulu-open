<?php
$candidateTools = [
    ['bi-search-heart', 'Analyse de CV', 'Évaluez l\'adéquation de votre CV avec une offre et repérez ce qui manque.'],
    ['bi-magic', 'Optimisation de CV', 'Adaptez votre CV à une offre précise pour maximiser vos chances.'],
    ['bi-envelope-paper', 'Lettre de motivation', 'Générez une lettre personnalisée pour chaque candidature.'],
    ['bi-file-earmark-arrow-down', 'Génération de CV', 'Créez un CV professionnel à partir de votre profil, exportable en .docx.'],
    ['bi-link-45deg', 'Import d\'offre', 'Collez un lien, un PDF ou une image : l\'IA en extrait l\'essentiel.'],
    ['bi-lightning-charge', 'Candidature assistée', 'Postulez avec un CV et une lettre optimisés, en un clic.'],
];
$recruiterTools = [
    ['bi-pencil-square', 'Rédaction d\'offre', 'L\'IA rédige une offre claire et attractive à partir de vos besoins.'],
    ['bi-clipboard-data', 'Analyse des candidatures', 'Chaque candidature reçue est analysée automatiquement.'],
    ['bi-sort-numeric-down', 'Classement par adéquation', 'Les profils les plus pertinents remontent en premier (score de matching).'],
    ['bi-stopwatch', 'Gain de temps', 'Concentrez-vous sur les meilleurs profils, moins de tri manuel.'],
];
?>
<section class="hero">
    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= t('IA & matching') ?></li>
            </ol>
        </nav>
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="hero-eyebrow mb-3"><i class="bi bi-robot"></i> <?= t('IA & matching') ?></span>
                <h1 class="fw-bold mb-3"><?= t('L\'IA au service de la mise en relation') ?></h1>
                <p class="lead text-secondary mx-auto mb-0" style="max-width: 60ch;"><?= t('LULU-OPEN met l\'intelligence artificielle au service de la rencontre entre talents et opportunités. L\'IA accélère et éclaire — les décisions restent humaines.') ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section pt-4">
    <div class="container">
        <div class="row g-4">
            <!-- Candidats -->
            <div class="col-lg-6">
                <div class="card h-100 p-4 p-lg-5">
                    <span class="badge badge-soft-primary align-self-start mb-3"><i class="bi bi-person-badge me-1"></i> <?= t('Candidats & prestataires') ?></span>
                    <h2 class="h4 mb-4"><?= t('Présentez le bon profil, à la bonne offre') ?></h2>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($candidateTools as [$icon, $ti, $de]): ?>
                            <div class="d-flex gap-3">
                                <span class="category-icon flex-shrink-0"><i class="bi <?= e($icon) ?> text-primary"></i></span>
                                <div><div class="fw-semibold"><?= t($ti) ?></div><div class="text-secondary small"><?= t($de) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a class="btn btn-primary mt-4 align-self-start" href="<?= e(url(is_auth() && current_role() === 'client' ? '/client/ia' : '/register')) ?>"><?= t('Utiliser les outils IA') ?></a>
                </div>
            </div>
            <!-- Entreprises -->
            <div class="col-lg-6">
                <div class="card h-100 p-4 p-lg-5">
                    <span class="badge badge-soft-success align-self-start mb-3"><i class="bi bi-building me-1"></i> <?= t('Entreprises & recruteurs') ?></span>
                    <h2 class="h4 mb-4"><?= t('Recrutez plus vite, avec plus de justesse') ?></h2>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recruiterTools as [$icon, $ti, $de]): ?>
                            <div class="d-flex gap-3">
                                <span class="category-icon flex-shrink-0"><i class="bi <?= e($icon) ?> text-success"></i></span>
                                <div><div class="fw-semibold"><?= t($ti) ?></div><div class="text-secondary small"><?= t($de) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a class="btn btn-accent mt-4 align-self-start" href="<?= e(url(is_auth() && current_role() === 'entreprise' ? '/entreprise/offres/new' : '/register')) ?>"><?= t('Recruter avec l\'IA') ?></a>
                </div>
            </div>
        </div>

        <div class="alert alert-light border d-flex gap-3 align-items-start mt-4">
            <i class="bi bi-shield-check text-primary fs-4"></i>
            <div class="small text-secondary mb-0"><?= t('L\'IA est une aide à la décision : elle ne remplace ni votre jugement, ni celui des recruteurs. Vous gardez le contrôle de vos CV, de vos candidatures et de vos choix.') ?></div>
        </div>
    </div>
</section>

<section class="section pt-0">
    <div class="container">
        <div class="cta-band text-center p-5">
            <h2 class="mb-3"><?= t('Prêt à passer à la vitesse supérieure ?') ?></h2>
            <p class="mb-4 mx-auto opacity-75" style="max-width: 48ch;"><?= t('Créez votre compte et laissez l\'IA vous faire gagner du temps.') ?></p>
            <a class="btn btn-light btn-lg px-4 fw-semibold" href="<?= e(url('/register')) ?>"><?= t('Créer un compte') ?></a>
        </div>
    </div>
</section>
