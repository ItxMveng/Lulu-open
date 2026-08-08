<?php
declare(strict_types=1);

final class Application extends Model
{
    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO applications (applicant_id, client_id, entreprise_id, offer_id, status, cv_path, cover_letter, created_at, updated_at)
             VALUES (:applicant_id, :client_id, :entreprise_id, :offer_id, :status, :cv_path, :cover_letter, NOW(), NOW())'
        );
        $statement->execute([
            'applicant_id' => $data['applicant_id'],
            'client_id' => $data['client_id'] ?? null,
            'entreprise_id' => $data['entreprise_id'],
            'offer_id' => $data['offer_id'],
            'status' => $data['status'] ?? 'en_attente',
            'cv_path' => $data['cv_path'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function sentByApplicant(int $applicantId): array
    {
        $statement = $this->db->prepare('SELECT applications.*, offers.title FROM applications INNER JOIN offers ON offers.id = applications.offer_id WHERE applicant_id = :applicant_id ORDER BY applications.created_at DESC');
        $statement->execute(['applicant_id' => $applicantId]);
        return $statement->fetchAll() ?: [];
    }

    public function receivedByEntreprise(int $entrepriseId): array
    {
        $statement = $this->db->prepare('SELECT applications.*, offers.title FROM applications INNER JOIN offers ON offers.id = applications.offer_id WHERE applications.entreprise_id = :entreprise_id ORDER BY applications.created_at DESC');
        $statement->execute(['entreprise_id' => $entrepriseId]);
        return $statement->fetchAll() ?: [];
    }

    public function findForEntreprise(int $id, int $entrepriseId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT applications.*, offers.title, offers.description AS offer_description
             FROM applications
             INNER JOIN offers ON offers.id = applications.offer_id
             WHERE applications.id = :id AND applications.entreprise_id = :entreprise_id
             LIMIT 1'
        );
        $statement->execute(['id' => $id, 'entreprise_id' => $entrepriseId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare('UPDATE applications SET status = :status, updated_at = NOW() WHERE id = :id');
        return $statement->execute(['id' => $id, 'status' => $status]);
    }

    public function delete(int $id, int $applicantId): bool
    {
        $statement = $this->db->prepare('DELETE FROM applications WHERE id = :id AND applicant_id = :applicant_id');
        return $statement->execute(['id' => $id, 'applicant_id' => $applicantId]);
    }
}