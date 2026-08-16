<?php
declare(strict_types=1);

final class AdminPageController extends Controller
{
    public function edit(string $slug): void
    {
        AuthMiddleware::requireRole(['admin']);
        $statement = db()->prepare('SELECT * FROM pages_statiques WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $page = $statement->fetch() ?: ['slug' => $slug, 'title' => ucfirst($slug), 'body' => ''];
        $this->render('admin/pages/edit', ['title' => 'Page statique', 'page' => $page], 'admin');
    }

    public function update(string $slug): never
    {
        AuthMiddleware::requireRole(['admin']);
        verify_csrf();
        db()->prepare('INSERT INTO pages_statiques (slug, title, body, created_at, updated_at) VALUES (:slug, :title, :body, NOW(), NOW()) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body), updated_at = NOW()')->execute([
            'slug' => $slug,
            'title' => trim((string) ($_POST['title'] ?? ucfirst($slug))),
            'body' => (string) ($_POST['body'] ?? ''),
        ]);
        flash('Page mise à jour.', 'success');
        redirect('/admin/pages/' . $slug);
    }
}