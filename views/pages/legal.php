<?php $L = legal_info(); ?>
<section class="py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= t('Mentions légales') ?></li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">Mentions légales</h1>
            <p class="text-secondary mb-4"><i class="bi bi-clock-history me-1"></i>Dernière mise à jour : <?= date('d/m/Y') ?></p>
            <div class="card">
                <div class="card-body p-4 p-lg-5 legal-body">
                    <h2 class="h5">Éditeur du site</h2>
                    <p class="text-secondary mb-1">Le site <strong>LULU-OPEN</strong> (ci-après « la Plateforme ») est édité par :</p>
                    <ul class="text-secondary">
                        <li>Dénomination : <?= legal_field($L['editor_name']) ?></li>
                        <li>Forme juridique : <?= legal_field($L['editor_status']) ?><?php if ($L['editor_capital'] !== ''): ?> — capital social : <?= e($L['editor_capital']) ?><?php endif; ?></li>
                        <li>Siège social : <?= legal_field($L['editor_address']) ?></li>
                        <li>Immatriculation (RCS / SIRET) : <?= legal_field($L['editor_reg']) ?></li>
                        <?php if ($L['editor_vat'] !== ''): ?><li>N° TVA intracommunautaire : <?= e($L['editor_vat']) ?></li><?php endif; ?>
                        <li>Directeur de la publication : <?= legal_field($L['publication_director']) ?></li>
                        <li>Contact : <a href="mailto:<?= e($L['contact_email']) ?>"><?= e($L['contact_email']) ?></a></li>
                    </ul>

                    <h2 class="h5 mt-4">Hébergement</h2>
                    <p class="text-secondary mb-1">La Plateforme et ses données sont hébergées par les prestataires suivants :</p>
                    <ul class="text-secondary">
                        <li>Application : <?= e($L['host_app']) ?></li>
                        <li>Base de données : <?= e($L['host_db']) ?></li>
                        <li>Fichiers (CV, images) : <?= e($L['host_files']) ?></li>
                    </ul>

                    <h2 class="h5 mt-4">Propriété intellectuelle</h2>
                    <p class="text-secondary">La marque « LULU-OPEN », le nom de domaine, la charte graphique, les textes et le code de la Plateforme sont protégés. Les contenus publiés par les utilisateurs (offres, profils, CV) restent la propriété de leurs auteurs, qui concèdent à la Plateforme une licence d'affichage strictement nécessaire au fonctionnement du service.</p>

                    <h2 class="h5 mt-4">Responsabilité</h2>
                    <p class="text-secondary">La Plateforme met en relation candidats, prestataires et entreprises. Elle vérifie l'identité des entreprises avant publication d'offres afin de <strong>réduire</strong> les risques d'annonces frauduleuses, sans pouvoir garantir l'absence totale de fraude ni l'issue d'une candidature ou d'une mission. Les utilisateurs restent responsables de leurs échanges et de leurs décisions.</p>

                    <h2 class="h5 mt-4">Signalement</h2>
                    <p class="text-secondary">Tout contenu suspect (annonce frauduleuse, comportement abusif) peut être signalé à <a href="mailto:<?= e($L['contact_email']) ?>?subject=Signalement">l'adresse de contact</a>. Un lien « Signaler » est également disponible sur chaque offre.</p>

                    <p class="text-secondary small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Document fourni à titre informatif. Une relecture par un professionnel du droit est recommandée avant exploitation commerciale.</p>
                </div>
            </div>
        </div>
    </div>
</section>
