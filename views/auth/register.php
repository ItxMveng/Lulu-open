<?php $role = (string) old('role', 'client'); ?>
<section class="row justify-content-center py-5">
    <div class="col-11 col-sm-9 col-md-7 col-lg-6 col-xl-5">
        <div class="text-center mb-4">
            <a class="navbar-brand justify-content-center d-inline-flex mb-2" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>LULU-OPEN</a>
            <h1 class="h3 mb-1">Créez votre compte</h1>
            <p class="text-secondary mb-0">Gratuit — commencez en une minute.</p>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <form method="post" action="<?= e(url('/register')) ?>">
                    <?= csrf_field() ?>

                    <label class="form-label">Je m'inscris en tant que</label>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="role-option">
                                <input type="radio" name="role" id="role_client" value="client" <?= $role === 'client' ? 'checked' : '' ?>>
                                <label for="role_client">
                                    <span class="role-ic"><i class="bi bi-person-badge"></i></span>
                                    <span><span class="fw-semibold d-block">Candidat / Talent</span><span class="text-secondary small">Je postule et propose mes services</span></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="role-option">
                                <input type="radio" name="role" id="role_entreprise" value="entreprise" <?= $role === 'entreprise' ? 'checked' : '' ?>>
                                <label for="role_entreprise">
                                    <span class="role-ic"><i class="bi bi-building"></i></span>
                                    <span><span class="fw-semibold d-block">Entreprise</span><span class="text-secondary small">Je publie des offres et recrute</span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name">Nom complet</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) old('name')) ?>" autocomplete="name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" autocomplete="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Mot de passe</label>
                            <div class="input-group">
                                <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Afficher le mot de passe"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text">8 caractères minimum.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirmation</label>
                            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 btn-lg mt-4" type="submit">Créer mon compte</button>
                </form>
            </div>
        </div>
        <p class="text-center text-secondary mt-4 mb-0">Déjà un compte ? <a class="fw-semibold" href="<?= e(url('/login')) ?>">Se connecter</a></p>
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
