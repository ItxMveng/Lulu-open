<?php
declare(strict_types=1);

final class AdminController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll() ?: [];
        $this->render('admin/users/index', ['title' => 'Utilisateurs', 'users' => $users], 'admin');
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireRole(['admin']);
        $user = (new User())->findById((int) $id);
        $activities = (new Activity())->getRecent((int) $id, 20);
        $subscription = (new Subscription())->getCurrentByUserId((int) $id);
        $this->render('admin/users/show', ['title' => 'Fiche utilisateur', 'user' => $user, 'activities' => $activities, 'subscription' => $subscription], 'admin');
    }

    public function suspend(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        db()->prepare('UPDATE users SET status = "suspended", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        flash('Compte suspendu.', 'warning');
        redirect('/admin/users');
    }

    public function restore(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        db()->prepare('UPDATE users SET status = "active", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        flash('Compte réactivé.', 'success');
        redirect('/admin/users');
    }

    public function resetPasswordTemp(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        $tempPassword = bin2hex(random_bytes(4));
        (new User())->updatePassword((int) $id, password_hash($tempPassword, PASSWORD_BCRYPT));
        flash('Mot de passe temporaire généré: ' . $tempPassword, 'info');
        redirect('/admin/users/' . $id);
    }

    public function deleteUser(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        db()->prepare('UPDATE users SET status = "deleted", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        flash('Utilisateur marqué comme supprimé.', 'warning');
        redirect('/admin/users');
    }

    public function exportUsers(): never
    {
        AuthMiddleware::requireRole(['admin']);
        require base_path('api/admin-export.php');
    }
}