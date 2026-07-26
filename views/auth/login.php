<section class="row justify-content-center py-5">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-4">Connexion</h1>
                <form method="post" action="<?= e(url('/login')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="<?= e(url('/forgot-password')) ?>">Mot de passe oublié ?</a>
                        <a href="<?= e(url('/register')) ?>">Créer un compte</a>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</section>