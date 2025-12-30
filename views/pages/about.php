<?php
require_once __DIR__ . '/../../config/config.php';
$page_title = "À propos - Lulu-Open";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../layouts/header.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

<style>
.hero-about {
    background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
    color: white;
    padding: 100px 0 80px;
    margin-bottom: 60px;
}
.value-card {
    border: none;
    border-radius: 15px;
    padding: 30px;
    height: 100%;
    transition: transform 0.3s, box-shadow 0.3s;
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.value-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,153,255,0.3);
}
.value-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #0099FF, #000033);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    color: white;
}
.step-number {
    width: 60px;
    height: 60px;
    background: #0099FF;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: bold;
    margin: 0 auto 20px;
}
.cta-section {
    background: #000033;
    color: white;
    padding: 60px 0;
    margin-top: 80px;
}
</style>

<div class="container mt-3">
    <nav aria-label="breadcrumb" class="breadcrumb-custom">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('') ?>">Accueil</a></li>
            <li class="breadcrumb-item active">À propos</li>
        </ol>
    </nav>
</div>

<section class="hero-about">
    <div class="container text-center">
        <h1 class="display-3 fw-bold mb-4" data-aos="fade-up">À propos de Lulu-Open</h1>
        <p class="lead fs-4" data-aos="fade-up" data-aos-delay="100">La plateforme qui connecte talents et opportunités</p>
    </div>
</section>

<section class="container mb-5">
    <div class="row align-items-center">
        <div class="col-lg-6" data-aos="fade-right">
            <h2 class="fw-bold mb-4" style="color: #000033;">Notre Mission</h2>
            <p class="fs-5 mb-3">Lulu-Open est née d'une vision simple : faciliter la rencontre entre les professionnels talentueux et ceux qui ont besoin de leurs services.</p>
            <p class="fs-5 mb-3">Nous croyons que chaque artisan, prestataire de services et candidat mérite une plateforme moderne, transparente et efficace pour développer son activité et trouver des opportunités.</p>
            <p class="fs-5">Notre mission est de créer un écosystème où la qualité, la confiance et la simplicité sont au cœur de chaque interaction.</p>
        </div>
        <div class="col-lg-6" data-aos="fade-left">
            <img src="/assets/images/mission.jpg" alt="Notre mission" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/600x400/0099FF/FFFFFF?text=Notre+Mission'">
        </div>
    </div>
</section>

<section class="bg-light py-5 mb-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5" style="color: #000033;" data-aos="fade-up">Comment ça marche</h2>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="text-center">
                    <div class="step-number">1</div>
                    <h4 class="fw-bold mb-3">Inscrivez-vous</h4>
                    <p>Créez votre compte en quelques clics. Choisissez votre profil : prestataire, candidat ou client.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="text-center">
                    <div class="step-number">2</div>
                    <h4 class="fw-bold mb-3">Créez votre profil</h4>
                    <p>Mettez en valeur vos compétences, votre expérience et vos réalisations avec un profil complet.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center">
                    <div class="step-number">3</div>
                    <h4 class="fw-bold mb-3">Connectez-vous</h4>
                    <p>Trouvez des clients, des missions ou des talents. Échangez et collaborez en toute confiance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5">
    <h2 class="text-center fw-bold mb-5" style="color: #000033;" data-aos="fade-up">Nos Valeurs</h2>
    <div class="row g-4">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4 class="text-center fw-bold mb-3">Confiance</h4>
                <p class="text-center">Système d'avis vérifiés et profils authentifiés pour des échanges en toute sérénité.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <h4 class="text-center fw-bold mb-3">Simplicité</h4>
                <p class="text-center">Interface intuitive et processus optimisés pour gagner du temps et de l'efficacité.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-star"></i>
                </div>
                <h4 class="text-center fw-bold mb-3">Qualité</h4>
                <p class="text-center">Sélection rigoureuse et mise en avant des meilleurs professionnels de chaque secteur.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h4 class="text-center fw-bold mb-3">Communauté</h4>
                <p class="text-center">Un réseau bienveillant où chacun peut grandir et réussir ensemble.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container text-center">
        <h2 class="fw-bold mb-4" data-aos="fade-up">Prêt à rejoindre l'aventure ?</h2>
        <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Rejoignez des milliers de professionnels qui font confiance à Lulu-Open</p>
        <a href="/register.php" class="btn btn-lg px-5 py-3" style="background: #0099FF; color: white; border-radius: 50px;" data-aos="fade-up" data-aos-delay="200">
            <i class="bi bi-person-plus me-2"></i>Créer mon compte gratuitement
        </a>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
