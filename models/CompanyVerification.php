<?php
declare(strict_types=1);

final class CompanyVerification extends Model
{
    public function findByUserId(int $userId): ?array
    {
        $s = $this->db->prepare('SELECT * FROM company_verifications WHERE user_id = :u LIMIT 1');
        $s->execute(['u' => $userId]);
        return $s->fetch() ?: null;
    }

    /** Enregistre (ou met à jour) un dossier et passe le compte en "pending". */
    public function submit(int $userId, array $d): void
    {
        $s = $this->db->prepare(
            'INSERT INTO company_verifications (user_id, legal_name, registration_number, country, website, sector, description, document_path, contact_phone, submitted_at, reviewed_at)
             VALUES (:u, :legal, :reg, :country, :web, :sector, :desc, :doc, :phone, NOW(), NULL)
             ON DUPLICATE KEY UPDATE
                legal_name = VALUES(legal_name), registration_number = VALUES(registration_number),
                country = VALUES(country), website = VALUES(website), sector = VALUES(sector),
                description = VALUES(description),
                document_path = COALESCE(VALUES(document_path), document_path),
                contact_phone = VALUES(contact_phone), submitted_at = NOW(), reviewed_at = NULL, admin_note = NULL'
        );
        $s->execute([
            'u' => $userId, 'legal' => $d['legal_name'], 'reg' => $d['registration_number'] ?? null,
            'country' => $d['country'] ?? null, 'web' => $d['website'] ?? null, 'sector' => $d['sector'] ?? null,
            'desc' => $d['description'] ?? null, 'doc' => $d['document_path'] ?? null, 'phone' => $d['contact_phone'] ?? null,
        ]);
        $this->db->prepare("UPDATE users SET verification_status = 'pending' WHERE id = :u")->execute(['u' => $userId]);
    }

    public function pending(): array
    {
        $s = $this->db->query(
            "SELECT cv.*, users.name, users.email, users.verification_status
             FROM company_verifications cv
             INNER JOIN users ON users.id = cv.user_id
             WHERE users.verification_status = 'pending'
             ORDER BY cv.submitted_at ASC"
        );
        return $s->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $s = $this->db->prepare('SELECT cv.*, users.name, users.email FROM company_verifications cv INNER JOIN users ON users.id = cv.user_id WHERE cv.id = :id LIMIT 1');
        $s->execute(['id' => $id]);
        return $s->fetch() ?: null;
    }

    public function review(int $userId, string $decision, string $note = ''): void
    {
        $status = $decision === 'verified' ? 'verified' : 'rejected';
        $this->db->prepare('UPDATE users SET verification_status = :s WHERE id = :u')->execute(['s' => $status, 'u' => $userId]);
        $this->db->prepare('UPDATE company_verifications SET admin_note = :n, reviewed_at = NOW() WHERE user_id = :u')->execute(['n' => $note ?: null, 'u' => $userId]);
    }

    public function countPending(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
    }
}
