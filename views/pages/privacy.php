<?php $L = legal_info(); ?>
<section class="py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= t('Confidentialité') ?></li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">Politique de confidentialité</h1>
            <p class="text-secondary mb-4"><i class="bi bi-clock-history me-1"></i>Dernière mise à jour : <?= date('d/m/Y') ?></p>
            <div class="card">
                <div class="card-body p-4 p-lg-5 legal-body">
                    <p class="text-secondary">La protection de vos données personnelles est une priorité. Cette politique décrit, conformément au Règlement général sur la protection des données (RGPD), les données que nous traitons, pourquoi, avec qui, combien de temps, et les droits dont vous disposez.</p>

                    <h2 class="h5 mt-4">1. Responsable du traitement</h2>
                    <p class="text-secondary">Le responsable du traitement est l'éditeur de la Plateforme : <?= legal_field($L['editor_name']) ?><?php if ($L['editor_address'] !== ''): ?>, <?= e($L['editor_address']) ?><?php endif; ?>. Contact : <a href="mailto:<?= e($L['dpo_email']) ?>"><?= e($L['dpo_email']) ?></a>.</p>

                    <h2 class="h5 mt-4">2. Données collectées</h2>
                    <ul class="text-secondary">
                        <li><strong>Compte</strong> : nom, adresse email, mot de passe (stocké chiffré/haché), rôle (candidat ou entreprise).</li>
                        <li><strong>Profil</strong> : présentation, compétences, domaines, localisation, photo, disponibilité, tarif, portfolio, certifications, CV.</li>
                        <li><strong>Activité</strong> : offres publiées, candidatures, messages, favoris, recherches et alertes enregistrées.</li>
                        <li><strong>Vérification entreprise</strong> : justificatifs transmis pour la vérification d'identité de l'entreprise.</li>
                        <li><strong>Paiement</strong> : en cas d'abonnement, les données de paiement sont traitées par notre prestataire (voir §4) ; nous ne stockons pas vos coordonnées bancaires.</li>
                        <li><strong>Données techniques</strong> : adresse IP (utilisée notamment pour afficher la devise locale), journaux techniques, préférences (langue, devise, cookies).</li>
                    </ul>

                    <h2 class="h5 mt-4">3. Finalités et bases légales</h2>
                    <ul class="text-secondary">
                        <li>Fournir le service de mise en relation, la messagerie et les outils IA — <em>exécution du contrat</em>.</li>
                        <li>Vérifier les entreprises et sécuriser la Plateforme — <em>intérêt légitime</em>.</li>
                        <li>Gérer les abonnements et la facturation — <em>exécution du contrat / obligation légale</em>.</li>
                        <li>Envoyer des notifications et alertes que vous activez — <em>consentement</em>.</li>
                        <li>Améliorer et sécuriser le service — <em>intérêt légitime</em>.</li>
                    </ul>

                    <h2 class="h5 mt-4">4. Destinataires et sous-traitants</h2>
                    <p class="text-secondary">Vos données ne sont <strong>pas revendues</strong>. Elles peuvent être traitées par des sous-traitants agissant pour notre compte :</p>
                    <ul class="text-secondary">
                        <li><strong>Hébergement</strong> : <?= e($L['host_app']) ?> ; <?= e($L['host_db']) ?> ; <?= e($L['host_files']) ?>.</li>
                        <li><strong>Intelligence artificielle</strong> : Mistral AI (France, Union européenne) — pour l'analyse et la génération de CV, lettres et offres. Les contenus envoyés servent uniquement à produire le résultat demandé.</li>
                        <li><strong>Paiement</strong> : Stripe — traitement sécurisé des abonnements.</li>
                        <li><strong>Email</strong> : notre prestataire d'envoi (SMTP) pour les notifications transactionnelles.</li>
                        <li><strong>Géolocalisation de la devise</strong> : un service de géolocalisation d'adresse IP, sans stockage nominatif.</li>
                    </ul>

                    <h2 class="h5 mt-4">5. Transferts hors Union européenne</h2>
                    <p class="text-secondary">Certains prestataires d'hébergement et de paiement sont établis hors de l'UE (notamment aux États-Unis). Ces transferts sont encadrés par des garanties appropriées (clauses contractuelles types de la Commission européenne ou mécanismes équivalents).</p>

                    <h2 class="h5 mt-4">6. Durées de conservation</h2>
                    <p class="text-secondary">Vos données sont conservées tant que votre compte est actif. En cas de suppression de compte, elles sont effacées, sous réserve des obligations légales de conservation (facturation, sécurité) qui imposent une conservation limitée de certaines données.</p>

                    <h2 class="h5 mt-4">7. Vos droits</h2>
                    <p class="text-secondary">Vous disposez des droits d'<strong>accès</strong>, de <strong>rectification</strong>, d'<strong>effacement</strong>, d'<strong>opposition</strong>, de <strong>limitation</strong> et de <strong>portabilité</strong>. Vous pouvez les exercer à tout moment :</p>
                    <ul class="text-secondary">
                        <li>directement depuis votre espace (modification du profil, et <strong>suppression définitive de votre compte</strong> dans la page « Mon profil ») ;</li>
                        <li>ou en écrivant à <a href="mailto:<?= e($L['dpo_email']) ?>"><?= e($L['dpo_email']) ?></a>.</li>
                    </ul>
                    <p class="text-secondary">Vous pouvez également introduire une réclamation auprès de l'autorité de protection des données compétente (en France, la CNIL — cnil.fr).</p>

                    <h2 class="h5 mt-4">8. Sécurité</h2>
                    <p class="text-secondary">Les mots de passe sont hachés, les échanges sont chiffrés (HTTPS), les accès sont restreints et les entreprises sont vérifiées avant publication. Aucun système n'étant infaillible, nous vous invitons à choisir un mot de passe robuste et à signaler tout incident.</p>

                    <h2 class="h5 mt-4">9. Cookies</h2>
                    <p class="text-secondary">La Plateforme utilise un nombre limité de cookies : un cookie de <strong>session</strong> (indispensable à la connexion), la mémorisation de vos <strong>préférences</strong> (langue, devise) et de votre <strong>choix de consentement</strong>. Aucun cookie publicitaire tiers n'est déposé. Vous pouvez gérer votre choix via le bandeau de consentement.</p>

                    <h2 class="h5 mt-4">10. Contact</h2>
                    <p class="text-secondary">Pour toute question relative à vos données : <a href="mailto:<?= e($L['dpo_email']) ?>"><?= e($L['dpo_email']) ?></a>.</p>

                    <p class="text-secondary small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Document fourni à titre informatif. Une relecture par un professionnel du droit est recommandée avant exploitation commerciale.</p>
                </div>
            </div>
        </div>
    </div>
</section>
