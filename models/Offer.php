<?php
declare(strict_types=1);

final class Offer extends Model
{
    public function allByEntreprise(int $entrepriseId): array
    {
        $statement = $this->db->prepare('SELECT * FROM offers WHERE entreprise_id = :entreprise_id ORDER BY created_at DESC');
        $statement->execute(['entreprise_id' => $entrepriseId]);
        return $statement->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM offers WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $offer = $statement->fetch();
        return $offer ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO offers (
                entreprise_id, title, description, type, contract_type, location, remote_ok, salary_min, salary_max,
                skills_required, category_id, status, expires_at, views_count, created_at, updated_at
            ) VALUES (
                :entreprise_id, :title, :description, :type, :contract_type, :location, :remote_ok, :salary_min, :salary_max,
                :skills_required, :category_id, :status, :expires_at, 0, NOW(), NOW()
            )'
        );

        $statement->execute([
            'entreprise_id' => $data['entreprise_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'] ?? 'emploi',
            'contract_type' => $data['contract_type'] ?? null,
            'location' => $data['location'] ?? null,
            'remote_ok' => !empty($data['remote_ok']) ? 1 : 0,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'skills_required' => isset($data['skills_required']) ? json_encode($data['skills_required'], JSON_UNESCAPED_UNICODE) : null,
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->db->prepare(
            'UPDATE offers SET
                title = :title,
                description = :description,
                type = :type,
                contract_type = :contract_type,
                location = :location,
                remote_ok = :remote_ok,
                salary_min = :salary_min,
                salary_max = :salary_max,
                skills_required = :skills_required,
                category_id = :category_id,
                status = :status,
                expires_at = :expires_at,
                updated_at = NOW()
             WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'] ?? 'emploi',
            'contract_type' => $data['contract_type'] ?? null,
            'location' => $data['location'] ?? null,
            'remote_ok' => !empty($data['remote_ok']) ? 1 : 0,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'skills_required' => isset($data['skills_required']) ? json_encode($data['skills_required'], JSON_UNESCAPED_UNICODE) : null,
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $statement = $this->db->prepare('UPDATE offers SET status = :status, updated_at = NOW() WHERE id = :id');
        return $statement->execute(['id' => $id, 'status' => 'closed']);
    }

    public function publicSearch(array $filters = []): array
    {
        $conditions = ["status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(title LIKE :query OR description LIKE :query)';
            $params['query'] = '%' . trim((string) $filters['q']) . '%';
        }

        if (!empty($filters['location'])) {
            $conditions[] = 'location LIKE :location';
            $params['location'] = '%' . trim((string) $filters['location']) . '%';
        }

        if (!empty($filters['country'])) {
            $conditions[] = 'location LIKE :country';
            $params['country'] = '%' . trim((string) $filters['country']) . '%';
        }

        if (!empty($filters['type'])) {
            $conditions[] = 'type = :type';
            $params['type'] = trim((string) $filters['type']);
        }

        $where = implode(' AND ', $conditions);
        $statement = $this->db->prepare("SELECT * FROM offers WHERE {$where} ORDER BY created_at DESC");
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    }
}