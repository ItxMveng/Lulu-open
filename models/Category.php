<?php
declare(strict_types=1);

final class Category extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll() ?: [];
    }

    public function save(array $data): void
    {
        if (!empty($data['id'])) {
            $statement = $this->db->prepare('UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id, icon = :icon, updated_at = NOW() WHERE id = :id');
            $statement->execute($data);
            return;
        }

        $statement = $this->db->prepare('INSERT INTO categories (name, slug, parent_id, icon, created_at, updated_at) VALUES (:name, :slug, :parent_id, :icon, NOW(), NOW())');
        $statement->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'parent_id' => $data['parent_id'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);
    }
}