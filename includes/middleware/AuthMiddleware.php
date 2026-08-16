<?php
declare(strict_types=1);

final class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (!is_auth()) {
            flash('Vous devez vous connecter pour continuer.', 'warning');
            redirect('/login');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();

        if (!in_array((string) current_role(), $roles, true)) {
            abort(403, 'Accès interdit à cette ressource.');
        }
    }

    public static function requireGuest(): void
    {
        if (is_auth()) {
            redirect(dashboard_path_for_role(current_role()));
        }
    }
}