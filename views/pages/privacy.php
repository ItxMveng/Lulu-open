<?php
require_once __DIR__ . '/../../config/config.php';
$page_title = "Confidentialité - Lulu-Open";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../layouts/header.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

<style>
.privacy-hero {
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
.right-icon {
    width: 40px;
    height: 40px;
    background: #0099FF;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 10px;
}
</style>

<div class="container mt-3">
    <nav aria-label="breadcrumb" class="breadcrumb-custom">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('') ?>">Accueil</a></li>
            <li class="breadcrumb-item active">Politique de confidentialité</li>
        </ol>
    </nav>
</div>

<section class="privacy-hero">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Politique de confidentialité</h1>
        <p class="lead" data-aos="fade-up" data-aos-delay="100">Protection de vos données personnelles - RGPD</p>
        <p data-aos="fade-up" data-aos-delay="200">Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </div>
</section>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="toc">
                <h5 class="fw-bold mb-3">Sommaire</h5>
                <a href="#section1">1. Responsable</a>
                <a href="#section2">2. Données collectées</a>
                <a href="#section3">3. Finalités</a>
                <a href="#section4">4. Base légale</a>
                <a href="#section5">5. Destinataires</a>
                <a href="#section6">6. Conservation</a>
                <a href="#section7">7. Vos droits</a>
                <a href="#section8">8. Cookies</a>
                <a href="#section9">9. Sécurité</a>
                <a href="#section10">10. Contact DPO</a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="accordion" id="privacyAccordion">
                
                <div class="accordion-item" id="section1">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            1. Responsable du traitement
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p><strong>Lulu-Open</strong> est le responsable du traitement de vos données personnelles.</p>
                            <p>Adresse : [Adresse de l'entreprise]<br>
                            Email : dpo@lulu-open.com<br>
                            Téléphone : [Numéro de téléphone]</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            2. Données collectées
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Nous collectons les données suivantes :</p>
                            <ul>
                                <li><strong>Données d'identification :</strong> nom, prénom, email, téléphone</li>
                                <li><strong>Données de connexion :</strong> adresse IP, logs de connexion</li>
                                <li><strong>Données professionnelles :</strong> CV, compétences, expériences, portfolio</li>
                                <li><strong>Données de localisation :</strong> ville, code postal, région</li>
                                <li><strong>Données de paiement :</strong> informations bancaires (via prestataire sécurisé)</li>
                                <li><strong>Données de navigation :</strong> cookies, pages visitées</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            3. Finalités du traitement
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Vos données sont utilisées pour :</p>
                            <ul>
                                <li>Créer et gérer votre compte utilisateur</li>
                                <li>Fournir les services de la plateforme</li>
                                <li>Traiter vos paiements et abonnements</li>
                                <li>Vous mettre en relation avec d'autres utilisateurs</li>
                                <li>Améliorer nos services et votre expérience</li>
                                <li>Vous envoyer des communications (avec votre consentement)</li>
                                <li>Assurer la sécurité de la plateforme</li>
                                <li>Respecter nos obligations légales</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section4">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                            4. Base légale du traitement
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Le traitement de vos données repose sur :</p>
                            <ul>
                                <li><strong>Exécution du contrat :</strong> pour fournir nos services</li>
                                <li><strong>Consentement :</strong> pour les communications marketing</li>
                                <li><strong>Intérêt légitime :</strong> pour améliorer nos services et assurer la sécurité</li>
                                <li><strong>Obligation légale :</strong> pour respecter la réglementation</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section5">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
                            5. Destinataires des données
                        </button>
                    </h2>
                    <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Vos données peuvent être partagées avec :</p>
                            <ul>
                                <li>Les autres utilisateurs de la plateforme (selon votre profil public)</li>
                                <li>Nos prestataires techniques (hébergement, paiement, email)</li>
                                <li>Les autorités légales si requis par la loi</li>
                            </ul>
                            <p>Nous ne vendons jamais vos données à des tiers.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section6">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6">
                            6. Durée de conservation
                        </button>
                    </h2>
                    <div id="collapse6" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li><strong>Compte actif :</strong> pendant toute la durée d'utilisation</li>
                                <li><strong>Compte supprimé :</strong> 30 jours après suppression</li>
                                <li><strong>Données de paiement :</strong> 10 ans (obligation légale)</li>
                                <li><strong>Logs de connexion :</strong> 12 mois</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section7">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7">
                            7. Vos droits
                        </button>
                    </h2>
                    <div id="collapse7" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-eye"></i></span>
                                <strong>Droit d'accès :</strong> obtenir une copie de vos données
                            </div>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-pencil"></i></span>
                                <strong>Droit de rectification :</strong> corriger vos données inexactes
                            </div>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-trash"></i></span>
                                <strong>Droit à l'effacement :</strong> supprimer vos données
                            </div>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-hand-index"></i></span>
                                <strong>Droit d'opposition :</strong> vous opposer au traitement
                            </div>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-download"></i></span>
                                <strong>Droit à la portabilité :</strong> récupérer vos données dans un format structuré
                            </div>
                            <div class="mb-3">
                                <span class="right-icon"><i class="bi bi-pause-circle"></i></span>
                                <strong>Droit à la limitation :</strong> limiter le traitement de vos données
                            </div>
                            <p class="mt-4">Pour exercer vos droits, contactez-nous à : <strong>dpo@lulu-open.com</strong></p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section8">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8">
                            8. Cookies et traceurs
                        </button>
                    </h2>
                    <div id="collapse8" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Nous utilisons des cookies pour :</p>
                            <ul>
                                <li><strong>Cookies essentiels :</strong> nécessaires au fonctionnement (session, sécurité)</li>
                                <li><strong>Cookies analytiques :</strong> mesurer l'audience et améliorer le site</li>
                                <li><strong>Cookies fonctionnels :</strong> mémoriser vos préférences</li>
                            </ul>
                            <p>Vous pouvez gérer vos préférences de cookies dans les paramètres de votre navigateur.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section9">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9">
                            9. Sécurité des données
                        </button>
                    </h2>
                    <div id="collapse9" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Nous mettons en œuvre des mesures techniques et organisationnelles pour protéger vos données :</p>
                            <ul>
                                <li>Chiffrement SSL/TLS pour les transmissions</li>
                                <li>Hachage des mots de passe</li>
                                <li>Accès restreint aux données personnelles</li>
                                <li>Sauvegardes régulières</li>
                                <li>Surveillance et détection des intrusions</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" id="section10">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10">
                            10. Contact DPO
                        </button>
                    </h2>
                    <div id="collapse10" class="accordion-collapse collapse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                            <p>Pour toute question concernant vos données personnelles, contactez notre Délégué à la Protection des Données (DPO) :</p>
                            <p>
                                <strong>Email :</strong> dpo@lulu-open.com<br>
                                <strong>Courrier :</strong> Lulu-Open - DPO, [Adresse]
                            </p>
                            <p>Vous avez également le droit de déposer une réclamation auprès de la CNIL (www.cnil.fr).</p>
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
