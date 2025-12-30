<?php
require_once __DIR__ . '/../../config/config.php';
$page_title = "CGU - Lulu-Open";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../layouts/header.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

<style>
.cgu-hero {
    background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
    color: white;
    padding: 80px 0 60px;
    margin-bottom: 60px;
}
.toc {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 25px;
    position: sticky;
    top: 20px;
}
.toc a {
    color: #000033;
    text-decoration: none;
    display: block;
    padding: 8px 0;
    transition: all 0.3s;
}
.toc a:hover {
    color: #0099FF;
    padding-left: 10px;
}
.accordion-button:not(.collapsed) {
    background-color: #0099FF;
    color: white;
}
.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(0,153,255,0.25);
}
</style>

<div class="container mt-3">
    <nav aria-label="breadcrumb" class="breadcrumb-custom">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('') ?>">Accueil</a></li>
            <li class="breadcrumb-item active">CGU</li>
        </ol>
    </nav>
</div>

<section class="cgu-hero">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Conditions Générales d'Utilisation</h1>
        <p class="lead" data-aos="fade-up" data-aos-delay="100">Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </div>
</section>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="toc">
                <h5 class="fw-bold mb-3">Sommaire</h5>
                <a href="#section1">1. Objet</a>
                <a href="#section2">2. Définitions</a>
                <a href="#section3">3. Inscription</a>
                <a href="#section4">4. Abonnements</a>
                <a href="#section5">5. Utilisation</a>
                <a href="#section6">6. Propriété intellectuelle</a>
                <a href="#section7">7. Responsabilités</a>
                <a href="#section8">8. Résiliation</a>
                <a href="#section9">9. Loi applicable</a>
                <a href="#section10">10. Modifications</a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="accordion" id="cguAccordion">
                
                <div class="accordion-item" id="section1">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            1. Objet
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation de la plateforme Lulu-Open, accessible à l'adresse lulu-open.com.</p>
                            <p>En accédant et en utilisant la plateforme, vous acceptez sans réserve les présentes CGU. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser la plateforme.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            2. Définitions
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li><strong>Plateforme :</strong> Le site web Lulu-Open et l'ensemble de ses services.</li>
                                <li><strong>Utilisateur :</strong> Toute personne accédant à la plateforme.</li>
                                <li><strong>Prestataire :</strong> Professionnel proposant ses services sur la plateforme.</li>
                                <li><strong>Candidat :</strong> Personne recherchant un emploi via la plateforme.</li>
                                <li><strong>Client :</strong> Utilisateur recherchant des services ou des candidats.</li>
                                <li><strong>Abonnement :</strong> Formule payante donnant accès à des fonctionnalités premium.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            3. Inscription et compte utilisateur
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>3.1 Conditions d'inscription :</strong> L'inscription est réservée aux personnes majeures et capables juridiquement.</p>
                            <p><strong>3.2 Informations exactes :</strong> Vous vous engagez à fournir des informations exactes, complètes et à jour lors de votre inscription.</p>
                            <p><strong>3.3 Sécurité du compte :</strong> Vous êtes responsable de la confidentialité de vos identifiants de connexion.</p>
                            <p><strong>3.4 Compte unique :</strong> Chaque utilisateur ne peut créer qu'un seul compte.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section4">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                            4. Abonnements et paiements
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>4.1 Formules :</strong> La plateforme propose des abonnements mensuels, trimestriels et annuels.</p>
                            <p><strong>4.2 Tarifs :</strong> Les tarifs sont indiqués en euros TTC et peuvent être modifiés à tout moment.</p>
                            <p><strong>4.3 Paiement :</strong> Le paiement s'effectue par carte bancaire ou tout autre moyen proposé.</p>
                            <p><strong>4.4 Renouvellement :</strong> L'abonnement est renouvelé automatiquement sauf résiliation.</p>
                            <p><strong>4.5 Remboursement :</strong> Aucun remboursement n'est effectué en cas de résiliation anticipée.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section5">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
                            5. Utilisation de la plateforme
                        </button>
                    </h2>
                    <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>5.1 Usage autorisé :</strong> La plateforme est destinée à un usage professionnel et personnel légal.</p>
                            <p><strong>5.2 Interdictions :</strong> Il est interdit de :</p>
                            <ul>
                                <li>Publier du contenu illégal, diffamatoire ou offensant</li>
                                <li>Usurper l'identité d'autrui</li>
                                <li>Tenter de pirater ou perturber la plateforme</li>
                                <li>Utiliser des robots ou scripts automatisés</li>
                                <li>Collecter des données d'autres utilisateurs</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section6">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6">
                            6. Propriété intellectuelle
                        </button>
                    </h2>
                    <div id="collapse6" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>6.1 Droits de la plateforme :</strong> Tous les éléments de la plateforme (design, logo, textes, code) sont protégés par le droit d'auteur.</p>
                            <p><strong>6.2 Contenu utilisateur :</strong> Vous conservez vos droits sur le contenu que vous publiez, mais accordez à Lulu-Open une licence d'utilisation.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section7">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7">
                            7. Responsabilités
                        </button>
                    </h2>
                    <div id="collapse7" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>7.1 Rôle de la plateforme :</strong> Lulu-Open est un intermédiaire et n'est pas partie aux contrats conclus entre utilisateurs.</p>
                            <p><strong>7.2 Disponibilité :</strong> Nous nous efforçons d'assurer la disponibilité de la plateforme mais ne garantissons pas un accès ininterrompu.</p>
                            <p><strong>7.3 Contenu utilisateur :</strong> Chaque utilisateur est responsable du contenu qu'il publie.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section8">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8">
                            8. Résiliation
                        </button>
                    </h2>
                    <div id="collapse8" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p><strong>8.1 Par l'utilisateur :</strong> Vous pouvez supprimer votre compte à tout moment depuis votre espace personnel.</p>
                            <p><strong>8.2 Par la plateforme :</strong> Nous nous réservons le droit de suspendre ou supprimer tout compte en cas de violation des CGU.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section9">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9">
                            9. Loi applicable et juridiction
                        </button>
                    </h2>
                    <div id="collapse9" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p>Les présentes CGU sont régies par le droit français. Tout litige sera soumis aux tribunaux compétents de Paris.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section10">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10">
                            10. Modifications des CGU
                        </button>
                    </h2>
                    <div id="collapse10" class="accordion-collapse collapse" data-bs-parent="#cguAccordion">
                        <div class="accordion-body">
                            <p>Nous nous réservons le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés des modifications par email ou notification sur la plateforme.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
