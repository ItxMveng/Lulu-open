<section class="hero">
    <div class="container py-5 text-center">
        <span class="hero-eyebrow mb-3"><i class="bi bi-briefcase"></i> <?= t('Emplois, missions & stages') ?></span>
        <h1 class="fw-bold mb-2"><?= t('Décrochez votre prochaine') ?> <span class="text-gradient"><?= t('opportunité') ?></span></h1>
        <p class="lead mx-auto mb-4" style="max-width: 48ch;"><?= t('Des offres d\'entreprises qui recrutent. Postulez en un clic avec votre CV et votre lettre.') ?></p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/search?tab=offres')) ?>"><i class="bi bi-search me-1"></i><?= t('Voir les offres') ?></a>
            <a class="btn btn-outline-primary btn-lg" href="<?= e(url('/register')) ?>"><?= t('Créer mon profil candidat') ?></a>
        </div>
    </div>
</section>
<?php $offers = $offers ?? []; ?>
<?php if (!empty($offers)): ?>
<section class="section pb-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h3 mb-1"><?= t('Offres récentes') ?></h2>
                <p class="text-secondary mb-0"><?= t('Les dernières opportunités publiées.') ?></p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(url('/search?tab=offres')) ?>"><?= t('Toutes les offres') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($offers as $offer): ?>
                <div class="col-md-6 col-lg-4"><?php View::partial('components/offer-card', ['offer' => $offer]); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container text-center">
        <div class="row g-4">
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-file-earmark-person"></i></span><h2 class="h6"><?= t('Créez votre profil') ?></h2><p class="text-secondary small mb-0"><?= t('Mettez en avant vos compétences et votre expérience.') ?></p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-send"></i></span><h2 class="h6"><?= t('Postulez') ?></h2><p class="text-secondary small mb-0"><?= t('Candidatez aux offres qui vous correspondent en un clic.') ?></p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-briefcase"></i></span><h2 class="h6"><?= t('Soyez recruté') ?></h2><p class="text-secondary small mb-0"><?= t('Suivez vos candidatures et échangez avec les recruteurs.') ?></p></div></div>
        </div>
    </div>
</section>

<section class="section bg-surface-2 reveal">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <span class="hero-eyebrow mb-3"><i class="bi bi-robot"></i> <?= t('Assistant IA') ?></span>
                <h2 class="h3 mb-3"><?= t('Postulez plus vite, avec de meilleurs documents') ?></h2>
                <p class="text-secondary"><?= t('Notre intelligence artificielle analyse chaque offre et votre profil pour :') ?></p>
                <ul class="d-flex flex-column gap-2 list-unstyled">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i><?= t('évaluer votre adéquation avec l\'offre (score sur 100),') ?></li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i><?= t('générer un CV optimisé et téléchargeable (.docx / PDF),') ?></li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i><?= t('rédiger une lettre de motivation personnalisée,') ?></li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i><?= t('importer une offre depuis un lien, un fichier ou une image.') ?></li>
                </ul>
                <a class="btn btn-primary mt-2" href="<?= e(url('/register')) ?>"><?= t('Essayer gratuitement') ?></a>
            </div>
            <div class="col-lg-6">
                <div class="card p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="score-ring score-success" style="--v:82;--sz:72px;"><span>82<small>/100</small></span></div>
                        <div><div class="fw-semibold"><?= t('Adéquation forte') ?></div><div class="text-secondary small"><?= t('Exemple d\'analyse IA') ?></div></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2"><span class="badge badge-soft-success">PHP</span><span class="badge badge-soft-success">Laravel</span><span class="badge badge-soft-success">MySQL</span></div>
                    <div class="d-flex flex-wrap gap-2"><span class="status-badge status-en_attente"><?= t('Docker à renforcer') ?></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section reveal">
    <div class="container">
        <div class="cta-band text-center p-5">
            <h2 class="mb-3"><?= t('Votre prochain emploi vous attend') ?></h2>
            <p class="mb-4 opacity-75 mx-auto" style="max-width:46ch;"><?= t('Des opportunités en Afrique et à l\'international. Créez votre profil et postulez dès aujourd\'hui.') ?></p>
            <a class="btn btn-light btn-lg fw-semibold" href="<?= e(url('/search?tab=offres')) ?>"><?= t('Voir toutes les offres') ?></a>
        </div>
    </div>
</section>
