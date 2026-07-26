<section class="row justify-content-center py-5">
    <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
        <div class="text-center mb-4">
            <a class="navbar-brand justify-content-center d-inline-flex mb-2" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>LULU-OPEN</a>
            <h1 class="h3 mb-1">Content de vous revoir</h1>
            <p class="text-secondary mb-0">Connectez-vous à votre espace.</p>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e(url('/login')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" autocomplete="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0" for="password">Mot de passe</label>
                            <a class="small" href="<?= e(url('/forgot-password')) ?>">Mot de passe oublié ?</a>
                        </div>
                        <div class="input-group mt-1">
                            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Afficher le mot de passe"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 btn-lg" type="submit">Se connecter</button>
                </form>
            </div>
        </div>
        <p class="text-center text-secondary mt-4 mb-0">Pas encore de compte ? <a class="fw-semibold" href="<?= e(url('/register')) ?>">Créer un compte</a></p>
    </div>
</section>

<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
});
</script>
