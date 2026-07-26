<section class="py-5 text-center">
    <h1 class="display-6">Une erreur est survenue</h1>
    <p class="text-secondary"><?= e($message ?? 'Merci de réessayer dans quelques instants.') ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Retour à l'accueil</a>
</section>