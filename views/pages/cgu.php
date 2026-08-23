<?php $L = legal_info(); ?>
<section class="py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= t('Conditions générales') ?></li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">Conditions générales d'utilisation</h1>
            <p class="text-secondary mb-4"><i class="bi bi-clock-history me-1"></i>Dernière mise à jour : <?= date('d/m/Y') ?></p>
            <div class="card">
                <div class="card-body p-4 p-lg-5 legal-body">
                    <h2 class="h5">1. Objet et acceptation</h2>
                    <p class="text-secondary">Les présentes conditions générales d'utilisation (« CGU ») encadrent l'accès et l'usage de la Plateforme LULU-OPEN, qui met en relation des candidats et prestataires avec des entreprises et recruteurs. En créant un compte ou en utilisant la Plateforme, vous acceptez les CGU.</p>

                    <h2 class="h5 mt-4">2. Définitions</h2>
                    <ul class="text-secondary">
                        <li><strong>Candidat / Prestataire</strong> : utilisateur qui postule à des offres et/ou propose des services.</li>
                        <li><strong>Entreprise / Recruteur</strong> : utilisateur qui publie des offres et recherche des talents.</li>
                        <li><strong>Offre</strong> : annonce d'emploi, de mission ou de stage. <strong>Prestation</strong> : service proposé par un prestataire.</li>
                    </ul>

                    <h2 class="h5 mt-4">3. Inscription et compte</h2>
                    <p class="text-secondary">L'utilisateur s'engage à fournir des informations exactes et à jour, à préserver la confidentialité de ses identifiants et à ne pas usurper l'identité d'un tiers. Un compte est personnel.</p>

                    <h2 class="h5 mt-4">4. Vérification des entreprises</h2>
                    <p class="text-secondary">Les comptes entreprise font l'objet d'une vérification par notre équipe avant de pouvoir publier des offres. Cette vérification vise à <strong>réduire</strong> les risques d'annonces frauduleuses ; elle ne constitue pas une garantie absolue.</p>

                    <h2 class="h5 mt-4">5. Obligations des utilisateurs</h2>
                    <p class="text-secondary">L'utilisateur s'interdit notamment de publier des contenus illicites, trompeurs ou portant atteinte aux droits des tiers, de harceler d'autres utilisateurs, de contourner la sécurité de la Plateforme ou de collecter des données de manière abusive.</p>

                    <h2 class="h5 mt-4">6. Contenus</h2>
                    <p class="text-secondary">Les contenus publiés (offres, profils, CV, prestations) relèvent de la responsabilité de leur auteur, qui garantit en détenir les droits et concède à la Plateforme une licence d'affichage limitée au fonctionnement du service.</p>

                    <h2 class="h5 mt-4">7. Abonnements et paiements</h2>
                    <p class="text-secondary">Certaines fonctionnalités relèvent d'abonnements payants, réglés via notre prestataire de paiement sécurisé. Les tarifs sont affichés dans la devise locale à titre indicatif, la référence étant l'euro. Les conditions de résiliation sont accessibles depuis l'espace d'abonnement.</p>

                    <h2 class="h5 mt-4">8. Outils d'intelligence artificielle</h2>
                    <p class="text-secondary">Les outils IA (analyse et génération de CV et de lettres, rédaction et analyse d'offres, matching) sont fournis comme <strong>aide à la décision</strong>. Ils ne garantissent ni l'obtention d'un emploi, ni la qualité d'une candidature, et ne remplacent pas le jugement des utilisateurs.</p>

                    <h2 class="h5 mt-4">9. Responsabilité</h2>
                    <p class="text-secondary">La Plateforme fournit un service de mise en relation. Elle ne garantit pas l'issue des candidatures, missions ou recrutements, ni l'exactitude des contenus publiés par les utilisateurs. Sa responsabilité ne saurait être engagée pour les échanges et décisions intervenant entre utilisateurs.</p>

                    <h2 class="h5 mt-4">10. Signalement et modération</h2>
                    <p class="text-secondary">Un lien « Signaler » est disponible sur les offres, et tout abus peut être signalé à <a href="mailto:<?= e($L['contact_email']) ?>?subject=Signalement"><?= e($L['contact_email']) ?></a>. Nous pouvons retirer un contenu ou suspendre un compte en cas de manquement.</p>

                    <h2 class="h5 mt-4">11. Suppression de compte</h2>
                    <p class="text-secondary">Vous pouvez supprimer définitivement votre compte à tout moment depuis la page « Mon profil ». Les données associées sont effacées, sous réserve des obligations légales de conservation.</p>

                    <h2 class="h5 mt-4">12. Données personnelles</h2>
                    <p class="text-secondary">Le traitement de vos données est décrit dans notre <a href="<?= e(url('/privacy')) ?>">politique de confidentialité</a>.</p>

                    <h2 class="h5 mt-4">13. Modification des CGU</h2>
                    <p class="text-secondary">Les CGU peuvent évoluer. La version applicable est celle en vigueur au moment de l'utilisation de la Plateforme.</p>

                    <h2 class="h5 mt-4">14. Droit applicable</h2>
                    <p class="text-secondary">Les présentes CGU sont régies par le droit applicable au siège de l'éditeur<?php if ($L['country'] !== ''): ?> (<?= e($L['country']) ?>)<?php endif; ?>. À défaut de résolution amiable, les tribunaux compétents seront saisis.</p>

                    <p class="text-secondary small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Document fourni à titre informatif. Une relecture par un professionnel du droit est recommandée avant exploitation commerciale.</p>
                </div>
            </div>
        </div>
    </div>
</section>
