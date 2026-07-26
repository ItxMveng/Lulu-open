<section class="py-4">
    <h1 class="mb-4">Contact</h1>
    <form method="post" action="<?= e(url('/contact')) ?>" class="card shadow-sm border-0">
        <div class="card-body p-4">
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
                    <textarea class="form-control" id="message" name="message" rows="5" required><?= e((string) old('message')) ?></textarea>
                </div>
            </div>
            <button class="btn btn-primary mt-4" type="submit">Envoyer</button>
        </div>
    </form>
</section>