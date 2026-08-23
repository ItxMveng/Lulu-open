<?php
declare(strict_types=1);

final class Service extends Model
{
    /** Types de tarification proposés (slug => libellé). */
    public static function priceTypes(): array
    {
        return [
            'from'   => 'À partir de',
            'fixed'  => 'Forfait',
            'hourly' => 'Par heure',
            'daily'  => 'Par jour',
        ];
    }

    /** Rendu lisible d'un prix selon son type, dans la devise du visiteur. */
    public static function formatPrice(?float $price, string $type): string
    {
        if ($price === null) {
            return 'Sur devis';
        }
        $amount = money($price);
        return match ($type) {
            'hourly' => $amount . '/h',
            'daily'  => $amount . '/jour',
            'fixed'  => $amount . ' (forfait)',
            default  => 'À partir de ' . $amount,
        };
    }

    /** Toutes les prestations d'un prestataire (vue propriétaire). */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE user_id = :u ORDER BY is_active DESC, created_at DESC');
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /** Prestations actives (affichage public). */
    public function activeForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE user_id = :u AND is_active = 1 ORDER BY created_at DESC');
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM services WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(int $userId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO services (user_id, title, category, description, image_path, price, price_type, delivery_days, is_active, created_at, updated_at)
             VALUES (:u, :title, :category, :description, :image_path, :price, :price_type, :delivery_days, :is_active, NOW(), NOW())'
        );
        $stmt->execute($this->bind($userId, $data));
        return (int) $this->db->lastInsertId();
    }

    /** Mise à jour restreinte au propriétaire. */
    public function update(int $id, int $userId, array $data): void
    {
        $params = $this->bind($userId, $data);
        $params['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE services SET title = :title, category = :category, description = :description, image_path = :image_path,
                price = :price, price_type = :price_type, delivery_days = :delivery_days, is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id AND user_id = :u'
        );
        $stmt->execute($params);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM services WHERE id = :id AND user_id = :u');
        $stmt->execute(['id' => $id, 'u' => $userId]);
    }

    /** Prépare et normalise les paramètres liés. */
    private function bind(int $userId, array $data): array
    {
        $priceTypes = array_keys(self::priceTypes());
        $type = in_array($data['price_type'] ?? '', $priceTypes, true) ? $data['price_type'] : 'from';
        $price = ($data['price'] ?? '') !== '' ? round((float) $data['price'], 2) : null;
        $delivery = ($data['delivery_days'] ?? '') !== '' ? max(0, (int) $data['delivery_days']) : null;

        return [
            'u' => $userId,
            'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 160),
            'category' => ($data['category'] ?? '') !== '' ? mb_substr((string) $data['category'], 0, 150) : null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'image_path' => ($data['image_path'] ?? null) ?: null,
            'price' => $price,
            'price_type' => $type,
            'delivery_days' => $delivery,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
