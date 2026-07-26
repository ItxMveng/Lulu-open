<section class="row justify-content-center py-5">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-4">Mot de passe oublié</h1>
                <form method="post" action="<?= e(url('/forgot-password')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Envoyer le lien</button>
                </form>
            </div>
        </div>
    </div>
</section>