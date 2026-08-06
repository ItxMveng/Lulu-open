<?php $offer = $offer ?? []; ?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a class="text-secondary small d-inline-flex align-items-center mb-3" href="<?= e(url('/offres/' . (int) ($offer['id'] ?? 0))) ?>"><i class="bi bi-arrow-left me-1"></i>Retour à l'offre</a>
            <h1 class="h3 mb-1">Postuler</h1>
            <p class="text-secondary mb-4"><?= e((string) ($offer['title'] ?? 'Offre')) ?></p>
    <form method="post" action="<?= e(url('/applications')) ?>" enctype="multipart/form-data" class="card shadow-sm">
        <div class="card-body p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="offer_id" value="<?= e((string) ($offer['id'] ?? 0)) ?>">
            <input type="hidden" name="entreprise_id" value="<?= e((string) ($offer['entreprise_id'] ?? 0)) ?>">
            <div class="mb-3"><label class="form-label" for="cv">CV (PDF)</label><input class="form-control" type="file" id="cv" name="cv" accept="application/pdf" required><div class="form-text">Format PDF, 5 Mo maximum.</div></div>
            <div class="mb-3"><label class="form-label" for="cover_letter">Lettre de motivation</label><textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" placeholder="Présentez votre motivation en quelques lignes…"></textarea></div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Envoyer ma candidature</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('/offres/' . (int) ($offer['id'] ?? 0))) ?>">Annuler</a>
            </div>
        </div>
    </form>
        </div>
    </div>
</section>