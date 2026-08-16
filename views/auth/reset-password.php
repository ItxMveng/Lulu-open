<section class="row justify-content-center py-5">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-4">Réinitialiser le mot de passe</h1>
                <form method="post" action="<?= e(url('/reset-password')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="password">Nouveau mot de passe</label>
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Confirmation</label>
                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Mettre à jour le mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</section>