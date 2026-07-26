<section class="row justify-content-center py-5">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-4">Créer un compte</h1>
                <form method="post" action="<?= e(url('/register')) ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control" type="text" id="name" name="name" value="<?= e((string) old('name')) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Mot de passe</label>
                            <input class="form-control" type="password" id="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Confirmation</label>
                            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Je crée un compte</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="role_client" value="client" <?= old('role', 'client') === 'client' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_client">Client</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="role_entreprise" value="entreprise" <?= old('role') === 'entreprise' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_entreprise">Entreprise</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-4" type="submit">Créer mon compte</button>
                </form>
            </div>
        </div>
    </div>
</section>