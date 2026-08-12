<section class="hero">
    <div class="container py-5 text-center">
        <span class="hero-eyebrow mb-3"><i class="bi bi-tools"></i> Prestations & talents</span>
        <h1 class="fw-bold mb-2">Trouvez le <span class="text-gradient">prestataire</span> idéal</h1>
        <p class="lead mx-auto mb-4" style="max-width: 48ch;">Développeurs, designers, consultants… Parcourez des profils qualifiés et contactez-les directement.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/search?tab=profils')) ?>"><i class="bi bi-search me-1"></i>Explorer les profils</a>
            <a class="btn btn-outline-primary btn-lg" href="<?= e(url('/register')) ?>">Proposer mes services</a>
        </div>
    </div>
</section>
<section class="section">
    <div class="container text-center">
        <div class="row g-4">
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-search"></i></span><h2 class="h6">Cherchez</h2><p class="text-secondary small mb-0">Filtrez par métier, compétence, localisation et budget.</p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-chat-dots"></i></span><h2 class="h6">Échangez</h2><p class="text-secondary small mb-0">Contactez les prestataires via la messagerie sécurisée.</p></div></div>
            <div class="col-md-4"><div class="card h-100 card-hover p-4"><span class="category-icon mb-3"><i class="bi bi-check2-circle"></i></span><h2 class="h6">Collaborez</h2><p class="text-secondary small mb-0">Lancez votre projet avec le bon talent.</p></div></div>
        </div>
    </div>
</section>

<?php $categories = $categories ?? []; ?>
<?php if (!empty($categories)): ?>
<section class="section bg-surface-2 reveal">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="h3 mb-2">Des talents dans tous les domaines</h2>
            <p class="text-secondary mb-0">Parcourez les prestataires par secteur d'activité.</p>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php foreach ($categories as $cat): ?>
                <a class="badge badge-soft-primary py-2 px-3" href="<?= e(url('/search?tab=profils&category=' . urlencode((string) $cat['name']))) ?>"><i class="bi <?= e((string) ($cat['icon'] ?? 'bi-tag')) ?> me-1"></i><?= e(t((string) $cat['name'])) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section reveal">
    <div class="container">
        <div class="cta-band text-center p-5">
            <h2 class="mb-3">Vous êtes un talent ? Mettez-vous en avant.</h2>
            <p class="mb-4 opacity-75 mx-auto" style="max-width:46ch;">Créez un profil pro, ajoutez votre portfolio et votre CV, et recevez des opportunités.</p>
            <a class="btn btn-light btn-lg fw-semibold" href="<?= e(url('/register')) ?>">Créer mon profil gratuitement</a>
        </div>
    </div>
</section>
