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

    public function receivedByEntreprise(int $entrepriseId, string $sort = 'score'): array
    {
        $order = $sort === 'recent'
            ? 'applications.created_at DESC'
            : 'applications.match_score IS NULL, applications.match_score DESC, applications.created_at DESC';
        $statement = $this->db->prepare(
            'SELECT applications.*, offers.title,
                    users.name AS candidate_name, users.email AS candidate_email,
                    profiles.location AS candidate_location, profiles.skills AS candidate_skills,
                    profiles.categories AS candidate_categories, profiles.bio AS candidate_bio,
                    profiles.hourly_rate AS candidate_rate, profiles.photo_path AS candidate_photo
             FROM applications
             INNER JOIN offers ON offers.id = applications.offer_id
             INNER JOIN users ON users.id = applications.applicant_id
             LEFT JOIN profiles ON profiles.user_id = applications.applicant_id
             WHERE applications.entreprise_id = :entreprise_id
             ORDER BY ' . $order
        );
        $statement->execute(['entreprise_id' => $entrepriseId]);
        return $statement->fetchAll() ?: [];
    }

    public function findForEntreprise(int $id, int $entrepriseId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT applications.*, offers.title, offers.description AS offer_description,
                    users.name AS candidate_name, users.email AS candidate_email,
                    profiles.skills AS candidate_skills, profiles.categories AS candidate_categories,
                    profiles.languages AS candidate_languages, profiles.bio AS candidate_bio,
                    profiles.location AS candidate_location
             FROM applications
             INNER JOIN offers ON offers.id = applications.offer_id
             INNER JOIN users ON users.id = applications.applicant_id
             LEFT JOIN profiles ON profiles.user_id = applications.applicant_id
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

    public function saveAnalysis(int $id, int $score, array $analysis): void
    {
        $statement = $this->db->prepare('UPDATE applications SET match_score = :score, analysis = :analysis, updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id, 'score' => max(0, min(100, $score)), 'analysis' => json_encode($analysis, JSON_UNESCAPED_UNICODE) ?: null]);
    }

    public function saveInterview(int $id, ?string $at, ?string $location, ?string $note): void
    {
        $statement = $this->db->prepare('UPDATE applications SET interview_at = :at, interview_location = :loc, interview_note = :note WHERE id = :id');
        $statement->execute(['id' => $id, 'at' => $at ?: null, 'loc' => $location ?: null, 'note' => $note ?: null]);
    }

    public function needingAnalysis(int $limit = 50): array
    {
        $statement = $this->db->prepare('SELECT id, applicant_id, offer_id FROM applications WHERE match_score IS NULL ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $statement->execute();
        return $statement->fetchAll() ?: [];
    }

    public function delete(int $id, int $applicantId): bool
    {
        $statement = $this->db->prepare('DELETE FROM applications WHERE id = :id AND applicant_id = :applicant_id');
        return $statement->execute(['id' => $id, 'applicant_id' => $applicantId]);
    }
}