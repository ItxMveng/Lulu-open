<?php
declare(strict_types=1);

final class AdminCategoryController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $categories = (new Category())->all();
        $this->render('admin/categories/index', ['title' => 'Catégories', 'categories' => $categories], 'admin');
    }

    public function save(): never
    {
        AuthMiddleware::requireRole(['admin']);
        verify_csrf();
        (new Category())->save([
            'id' => $_POST['id'] ?? null,
            'name' => trim((string) ($_POST['name'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'parent_id' => $_POST['parent_id'] ?? null,
            'icon' => trim((string) ($_POST['icon'] ?? '')),
        ]);
        flash('Catégorie enregistrée.', 'success');
        redirect('/admin/categories');
    }
}