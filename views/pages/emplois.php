<section class="hero">
    <div class="container py-5 text-center">
        <span class="hero-eyebrow mb-3"><i class="bi bi-briefcase"></i> Emplois, missions & stages</span>
        <h1 class="fw-bold mb-2">Décrochez votre prochaine <span class="text-gradient">opportunité</span></h1>
        <p class="lead mx-auto mb-4" style="max-width: 48ch;">Des offres d'entreprises qui recrutent. Postulez en un clic avec votre CV et votre lettre.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/search?tab=offres')) ?>"><i class="bi bi-search me-1"></i>Voir les offres</a>
            <a class="btn btn-outline-primary btn-lg" href="<?= e(url('/register')) ?>">Créer mon profil candidat</a>
        </div>
    </div>
</section>
<?php $offers = $offers ?? []; ?>
<?php if (!empty($offers)): ?>
<section class="section pb-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h3 mb-1">Offres récentes</h2>
                <p class="text-secondary mb-0">Les dernières opportunités publiées.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(url('/search?tab=offres')) ?>">Toutes les offres <i class="bi bi-arrow-right ms-1"></i></a>
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
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-file-earmark-person"></i></span><h2 class="h6">Créez votre profil</h2><p class="text-secondary small mb-0">Mettez en avant vos compétences et votre expérience.</p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-send"></i></span><h2 class="h6">Postulez</h2><p class="text-secondary small mb-0">Candidatez aux offres qui vous correspondent en un clic.</p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-briefcase"></i></span><h2 class="h6">Soyez recruté</h2><p class="text-secondary small mb-0">Suivez vos candidatures et échangez avec les recruteurs.</p></div></div>
        </div>
    </div>
</section>
