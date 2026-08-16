<section class="py-4">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-4">
            <h1 class="h3 mb-3">Contactez-nous</h1>
            <p class="text-secondary">Une question, une suggestion ? Notre équipe vous répond rapidement.</p>
            <div class="d-flex flex-column gap-3 mt-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="category-icon"><i class="bi bi-envelope"></i></span>
                    <div>
                        <div class="fw-semibold">Email</div>
                        <a class="text-secondary small" href="mailto:<?= e((string) env('MAIL_FROM_ADDRESS', 'contact@lulu-open.local')) ?>"><?= e((string) env('MAIL_FROM_ADDRESS', 'contact@lulu-open.local')) ?></a>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="category-icon"><i class="bi bi-clock"></i></span>
                    <div>
                        <div class="fw-semibold">Réactivité</div>
                        <div class="text-secondary small">Réponse sous 24–48h ouvrées</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <form method="post" action="<?= e(url('/contact')) ?>" class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) old('name')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?= e((string) old('message')) ?></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-send me-1"></i>Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</section>
