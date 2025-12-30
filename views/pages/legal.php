<?php
require_once __DIR__ . '/../../config/config.php';
$page_title = "Mentions Légales - Lulu-Open";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../layouts/header.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <div class="container mt-3">
        <nav aria-label="breadcrumb" class="breadcrumb-custom">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('') ?>">Accueil</a></li>
                <li class="breadcrumb-item active">Mentions légales</li>
            </ol>
        </nav>
    </div>
    
    <section style="background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 80px 0 60px; margin-bottom: 60px;">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Mentions Légales</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Informations légales et éditoriales</p>
        </div>
    </section>
    
    <main class="main-content">
        <div class="container mb-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card-custom p-4">
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">1. Éditeur du site</h2>
                            <p><strong>Raison sociale :</strong> Lulu-Open</p>
                            <p><strong>Forme juridique :</strong> [À compléter]</p>
                            <p><strong>Capital social :</strong> [À compléter]</p>
                            <p><strong>Siège social :</strong> [Adresse complète]</p>
                            <p><strong>RCS :</strong> [Numéro RCS]</p>
                            <p><strong>SIRET :</strong> [Numéro SIRET]</p>
                            <p><strong>TVA intracommunautaire :</strong> [Numéro TVA]</p>
                            <p><strong>Email :</strong> <a href="mailto:contact@lulu-open.com">contact@lulu-open.com</a></p>
                            <p><strong>Téléphone :</strong> +33 1 23 45 67 89</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">2. Directeur de la publication</h2>
                            <p><strong>Nom :</strong> [Nom du directeur]</p>
                            <p><strong>Email :</strong> <a href="mailto:direction@lulu-open.com">direction@lulu-open.com</a></p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">3. Hébergement</h2>
                            <p><strong>Hébergeur :</strong> [Nom de l'hébergeur]</p>
                            <p><strong>Adresse :</strong> [Adresse de l'hébergeur]</p>
                            <p><strong>Téléphone :</strong> [Téléphone de l'hébergeur]</p>
                            <p><strong>Site web :</strong> <a href="#" target="_blank">[URL de l'hébergeur]</a></p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">4. Propriété intellectuelle</h2>
                            <p>L'ensemble du contenu de ce site (textes, images, vidéos, logos, icônes, etc.) est la propriété exclusive de Lulu-Open ou de ses partenaires.</p>
                            <p>Toute reproduction, distribution, modification, adaptation, retransmission ou publication de ces différents éléments est strictement interdite sans l'accord exprès par écrit de Lulu-Open.</p>
                            <p>Les marques et logos présents sur le site sont déposés par Lulu-Open ou éventuellement par ses partenaires.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">5. Données personnelles</h2>
                            <p>Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition aux données personnelles vous concernant.</p>
                            <p>Pour exercer ces droits, vous pouvez nous contacter à l'adresse : <a href="mailto:dpo@lulu-open.com">dpo@lulu-open.com</a></p>
                            <p>Pour plus d'informations, consultez notre <a href="<?= url('privacy') ?>">Politique de Confidentialité</a>.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">6. Cookies</h2>
                            <p>Ce site utilise des cookies pour améliorer l'expérience utilisateur et réaliser des statistiques de visite.</p>
                            <p>Vous pouvez paramétrer votre navigateur pour refuser les cookies. Cependant, certaines fonctionnalités du site pourraient ne plus être accessibles.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">7. Responsabilité</h2>
                            <p>Lulu-Open s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site. Toutefois, Lulu-Open ne peut garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition sur ce site.</p>
                            <p>Lulu-Open ne pourra être tenue responsable des dommages directs ou indirects résultant de l'accès au site ou de l'utilisation du site, y compris l'inaccessibilité, les pertes de données, détériorations, destructions ou virus qui pourraient affecter l'équipement informatique de l'utilisateur.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">8. Liens hypertextes</h2>
                            <p>Le site peut contenir des liens hypertextes vers d'autres sites. Lulu-Open n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu.</p>
                            <p>La création de liens hypertextes vers le site Lulu-Open nécessite une autorisation préalable écrite.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h2 class="fw-bold mb-3" style="color: #000033;">9. Droit applicable</h2>
                            <p>Les présentes mentions légales sont régies par le droit français.</p>
                            <p>En cas de litige, les tribunaux français seront seuls compétents.</p>
                        </section>
                        
                        <section>
                            <h2 class="fw-bold mb-3" style="color: #000033;">10. Contact</h2>
                            <p>Pour toute question concernant les mentions légales, vous pouvez nous contacter :</p>
                            <ul>
                                <li><strong>Par email :</strong> <a href="mailto:contact@lulu-open.com">contact@lulu-open.com</a></li>
                                <li><strong>Par téléphone :</strong> +33 1 23 45 67 89</li>
                                <li><strong>Par courrier :</strong> [Adresse postale complète]</li>
                            </ul>
                        </section>
                        
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    
    <?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
