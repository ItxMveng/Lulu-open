<?php
declare(strict_types=1);

final class Profile extends Model
{
    public function getByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT profiles.*, users.name, users.email, users.role FROM profiles INNER JOIN users ON users.id = profiles.user_id WHERE profiles.user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function save(int $userId, array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO profiles (
                user_id, type, display_name, bio, photo_path, location, lat, lng, categories, skills, languages,
                hourly_rate, availability, portfolio, certifications, is_visible, updated_at, created_at
            ) VALUES (
                :user_id, :type, :display_name, :bio, :photo_path, :location, :lat, :lng, :categories, :skills, :languages,
                :hourly_rate, :availability, :portfolio, :certifications, :is_visible, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                type = VALUES(type),
                display_name = VALUES(display_name),
                bio = VALUES(bio),
                photo_path = VALUES(photo_path),
                location = VALUES(location),
                lat = VALUES(lat),
                lng = VALUES(lng),
                categories = VALUES(categories),
                skills = VALUES(skills),
                languages = VALUES(languages),
                hourly_rate = VALUES(hourly_rate),
                availability = VALUES(availability),
                portfolio = VALUES(portfolio),
                certifications = VALUES(certifications),
                is_visible = VALUES(is_visible),
                updated_at = NOW()'
        );

        $statement->execute([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'services',
            'display_name' => $data['display_name'] ?? $data['name'] ?? 'Profil entreprise',
            'bio' => $data['bio'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'location' => $data['location'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'categories' => isset($data['categories']) ? json_encode($data['categories'], JSON_UNESCAPED_UNICODE) : null,
            'skills' => isset($data['skills']) ? json_encode($data['skills'], JSON_UNESCAPED_UNICODE) : null,
            'languages' => isset($data['languages']) ? json_encode($data['languages'], JSON_UNESCAPED_UNICODE) : null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'availability' => $data['availability'] ?? null,
            'portfolio' => isset($data['portfolio']) ? json_encode($data['portfolio'], JSON_UNESCAPED_UNICODE) : null,
            'certifications' => isset($data['certifications']) ? json_encode($data['certifications'], JSON_UNESCAPED_UNICODE) : null,
            'is_visible' => isset($data['is_visible']) ? (int) $data['is_visible'] : 1,
        ]);
    }

    public function search(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(24, (int) ($filters['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;

        $conditions = ['profiles.is_visible = 1', "users.role = 'entreprise'", "users.status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(profiles.display_name LIKE :query OR profiles.bio LIKE :query OR JSON_SEARCH(profiles.skills, "one", :query_term) IS NOT NULL)';
            $params['query'] = '%' . trim((string) $filters['q']) . '%';
            $params['query_term'] = trim((string) $filters['q']);
        }

        if (!empty($filters['type'])) {
            $conditions[] = 'profiles.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['location'])) {
            $conditions[] = 'profiles.location LIKE :location';
            $params['location'] = '%' . trim((string) $filters['location']) . '%';
        }

        if (!empty($filters['rate_min'])) {
            $conditions[] = 'profiles.hourly_rate >= :rate_min';
            $params['rate_min'] = (float) $filters['rate_min'];
        }

        if (!empty($filters['rate_max'])) {
            $conditions[] = 'profiles.hourly_rate <= :rate_max';
            $params['rate_max'] = (float) $filters['rate_max'];
        }

        if (!empty($filters['available'])) {
            $conditions[] = 'profiles.availability IS NOT NULL';
        }

        if (!empty($filters['category'])) {
            $conditions[] = 'JSON_SEARCH(profiles.categories, "one", :category) IS NOT NULL';
            $params['category'] = (string) $filters['category'];
        }

        $sortMap = [
            'pertinence' => 'profiles.updated_at DESC',
            'note' => 'profiles.profile_views DESC',
            'recent' => 'profiles.created_at DESC',
            'vues' => 'profiles.profile_views DESC',
        ];
        $sort = $sortMap[$filters['sort'] ?? 'pertinence'] ?? $sortMap['pertinence'];

        $where = implode(' AND ', $conditions);

        $countStatement = $this->db->prepare("SELECT COUNT(*) FROM profiles INNER JOIN users ON users.id = profiles.user_id WHERE {$where}");
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $sql = "SELECT profiles.*, users.name, users.email FROM profiles INNER JOIN users ON users.id = profiles.user_id WHERE {$where} ORDER BY {$sort} LIMIT :limit OFFSET :offset";
        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $items = $statement->fetchAll() ?: [];

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil(max($total, 1) / $perPage),
        ];
    }

    public function searchProfiles(array $filters): array
    {
        return $this->search($filters);
    }

    public function getPublicProfile(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT profiles.*, users.name, users.role, users.created_at AS user_created_at,
                    COALESCE(AVG(reviews.score), 0) AS average_score,
                    COUNT(reviews.id) AS review_count
             FROM profiles
             INNER JOIN users ON users.id = profiles.user_id
             LEFT JOIN reviews ON reviews.reviewed_id = users.id
             WHERE profiles.user_id = :user_id AND profiles.is_visible = 1
             GROUP BY profiles.id, users.id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }
}