<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function redirectDashboard(): never
    {
        AuthMiddleware::requireAuth();
        redirect(dashboard_path_for_role(current_role()));
    }

    public function client(): void
    {
        $this->render('client/dashboard', ['title' => 'Tableau de bord client']);
    }

    public function entreprise(): void
    {
        $this->render('entreprise/dashboard', ['title' => 'Tableau de bord entreprise']);
    }

    public function admin(): void
    {
        $this->render('admin/dashboard', ['title' => 'Tableau de bord admin'], 'admin');
    }
}