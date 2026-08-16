<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Connexion']);
    }

    public function handleLogin(): never
    {
        verify_csrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            flash('Merci de saisir un email et un mot de passe valides.', 'danger');
            store_old_input($_POST);
            redirect('/login');
        }

        if ($this->isRateLimited($email)) {
            flash('Trop de tentatives de connexion. Réessayez dans 15 minutes.', 'danger');
            redirect('/login');
        }

        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->registerFailedAttempt($email);
            flash('Identifiants invalides.', 'danger');
            store_old_input(['email' => $email]);
            redirect('/login');
        }

        if (in_array($user['status'], ['suspended', 'deleted'], true)) {
            flash('Votre compte est actuellement indisponible.', 'danger');
            redirect('/login');
        }

        $this->clearFailedAttempts($email);
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'verification_status' => $user['verification_status'] ?? null,
        ];

        clear_old_input();
        $this->users->updateLoginTimestamp((int) $user['id']);
        flash('Connexion réussie.', 'success');
        redirect(dashboard_path_for_role((string) $user['role']));
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Créer un compte']);
    }

    public function handleRegister(): never
    {
        verify_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');
        $role = (string) ($_POST['role'] ?? 'client');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $confirm) {
            flash('Merci de vérifier les informations du formulaire.', 'danger');
            store_old_input($_POST);
            redirect('/register');
        }

        if (!in_array($role, ['client', 'entreprise'], true)) {
            flash('Le rôle sélectionné est invalide.', 'danger');
            redirect('/register');
        }

        if ($this->users->findByEmail($email)) {
            flash('Un compte existe déjà avec cet email.', 'danger');
            store_old_input($_POST);
            redirect('/register');
        }

        $userId = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'status' => 'active',
            'subscription_status' => 'active',
        ]);

        $profileType = $role === 'entreprise' ? 'recrutement' : 'services';
        $this->users->createProfileForUser($userId, $name, $profileType);
        $this->users->assignDefaultSubscription($userId, $role);

        // Les entreprises doivent être vérifiées avant de pouvoir publier des offres.
        if ($role === 'entreprise') {
            $this->users->setVerificationStatus($userId, 'pending');
            $html = '<p>Bonjour ' . e($name) . ',</p><p>Votre compte entreprise a été créé. Avant de pouvoir publier des offres, votre entreprise doit être <strong>vérifiée</strong>.</p><p>Rendez-vous dans votre espace pour soumettre votre dossier de vérification.</p>';
            MailHelper::send($email, 'Votre compte entreprise — vérification requise', $html, 'Créez votre dossier de vérification.');
        } else {
            $html = '<p>Bonjour ' . e($name) . ',</p><p>Bienvenue sur LULU-OPEN ! Votre compte est prêt, complétez votre profil pour être visible des entreprises.</p>';
            MailHelper::send($email, 'Bienvenue sur LULU-OPEN', $html, 'Votre compte LULU-OPEN a été créé.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role, 'verification_status' => $role === 'entreprise' ? 'pending' : null];

        clear_old_input();
        flash($role === 'entreprise' ? 'Compte créé. Soumettez votre dossier de vérification pour publier des offres.' : 'Compte créé avec succès.', 'success');
        redirect(dashboard_path_for_role($role));
    }

    public function logout(): never
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
        redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot-password', ['title' => 'Mot de passe oublié']);
    }

    public function handleForgotPassword(): never
    {
        verify_csrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Merci de renseigner un email valide.', 'danger');
            redirect('/forgot-password');
        }

        $user = $this->users->findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = new DateTimeImmutable('+1 hour');
            $this->users->createPasswordReset((int) $user['id'], $token, $expiresAt);

            $resetLink = url('/reset-password/' . $token);
            $html = sprintf('<p>Bonjour %s,</p><p>Voici votre lien de réinitialisation : <a href="%s">%s</a></p>', e((string) $user['name']), e($resetLink), e($resetLink));
            MailHelper::send((string) $user['email'], 'Réinitialisation de votre mot de passe', $html, 'Lien de réinitialisation: ' . $resetLink);
        }

        flash('Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.', 'info');
        redirect('/forgot-password');
    }

    public function showResetPassword(string $token): void
    {
        $reset = $this->users->findPasswordResetByToken($token);

        if (!$reset || $reset['used_at'] !== null || strtotime((string) $reset['expires_at']) < time()) {
            flash('Le lien de réinitialisation est invalide ou expiré.', 'danger');
            redirect('/forgot-password');
        }

        $this->render('auth/reset-password', [
            'title' => 'Réinitialiser le mot de passe',
            'token' => $token,
        ]);
    }

    public function handleResetPassword(): never
    {
        verify_csrf();

        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        $reset = $this->users->findPasswordResetByToken($token);
        if (!$reset || $reset['used_at'] !== null || strtotime((string) $reset['expires_at']) < time()) {
            flash('Le lien de réinitialisation est invalide ou expiré.', 'danger');
            redirect('/forgot-password');
        }

        if (strlen($password) < 8 || $password !== $confirm) {
            flash('Le mot de passe doit faire au moins 8 caractères et correspondre à la confirmation.', 'danger');
            redirect('/reset-password/' . $token);
        }

        $this->users->updatePassword((int) $reset['user_id'], password_hash($password, PASSWORD_BCRYPT));
        $this->users->markPasswordResetUsed($token);

        flash('Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.', 'success');
        redirect('/login');
    }

    private function isRateLimited(string $email): bool
    {
        $attempts = $_SESSION['_login_attempts'][$this->attemptKey($email)] ?? null;

        if (!is_array($attempts) || empty($attempts['blocked_until'])) {
            return false;
        }

        return (int) $attempts['blocked_until'] > time();
    }

    private function registerFailedAttempt(string $email): void
    {
        $key = $this->attemptKey($email);
        $attempts = $_SESSION['_login_attempts'][$key] ?? ['count' => 0, 'blocked_until' => null];
        $attempts['count'] = (int) $attempts['count'] + 1;

        if ($attempts['count'] >= 5) {
            $attempts['blocked_until'] = time() + (15 * 60);
            $attempts['count'] = 5;
        }

        $_SESSION['_login_attempts'][$key] = $attempts;
    }

    private function clearFailedAttempts(string $email): void
    {
        unset($_SESSION['_login_attempts'][$this->attemptKey($email)]);
    }

    private function attemptKey(string $email): string
    {
        return sha1($email);
    }
}