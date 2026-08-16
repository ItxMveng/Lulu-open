<section class="py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h2 mb-2">Mentions légales</h1>
            <p class="text-secondary mb-4"><i class="bi bi-clock-history me-1"></i>Dernière mise à jour : <?= date('d/m/Y') ?></p>
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h5">Éditeur</h2>
                    <p class="text-secondary">LULU-OPEN — plateforme de mise en relation entre talents et entreprises.</p>
                    <h2 class="h5 mt-4">Hébergement</h2>
                    <p class="text-secondary">Le service est hébergé par un prestataire d'infrastructure cloud.</p>
                    <h2 class="h5 mt-4">Contact</h2>
                    <p class="text-secondary">Pour toute question : <a href="mailto:<?= e((string) env('MAIL_FROM_ADDRESS', 'contact@lulu-open.local')) ?>"><?= e((string) env('MAIL_FROM_ADDRESS', 'contact@lulu-open.local')) ?></a>.</p>
                    <p class="text-secondary small mb-0 mt-4"><i class="bi bi-info-circle me-1"></i>Ce document est un modèle. Les informations légales définitives seront complétées avant mise en production.</p>
                </div>
            </div>
        </div>
    </div>
</section>
