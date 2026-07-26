<section class="py-4">
    <h1 class="mb-4">Postuler à l'offre</h1>
    <form method="post" action="<?= e(url('/applications')) ?>" enctype="multipart/form-data" class="card shadow-sm border-0">
        <div class="card-body p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="offer_id" value="<?= e((string) ($offer['id'] ?? 0)) ?>">
            <input type="hidden" name="entreprise_id" value="<?= e((string) ($offer['entreprise_id'] ?? 0)) ?>">
            <div class="mb-3"><label class="form-label">CV PDF</label><input class="form-control" type="file" name="cv" accept="application/pdf" required></div>
            <div class="mb-3"><label class="form-label">Lettre de motivation</label><textarea class="form-control" name="cover_letter" rows="8"></textarea></div>
            <button class="btn btn-primary" type="submit">Envoyer ma candidature</button>
        </div>
    </form>
</section>