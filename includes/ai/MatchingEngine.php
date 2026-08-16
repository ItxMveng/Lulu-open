<?php
declare(strict_types=1);

final class MatchingEngine
{
    private IAProvider $provider;
    private PDO $db;

    public function __construct()
    {
        $this->provider = new IAProvider();
        $this->db = db();
    }

    public function scoreProfileForQuery(array $profile, string $searchQuery): array
    {
        $userId = (int) ($profile['user_id'] ?? 0);
        $queryHash = hash('sha256', mb_strtolower(trim($searchQuery)));
        $cached = $this->getCachedScore($userId, $queryHash);

        if ($cached) {
            return $cached;
        }

        $system = 'You are a matching engine. Return valid JSON with score (0-100) and summary.';
        $user = 'Voici un profil: ' . json_encode($profile, JSON_UNESCAPED_UNICODE) . ' La recherche est: ' . $searchQuery . '. Donne un score de 0 à 100 et un résumé de pertinence en 1 phrase.';
        $json = $this->provider->completeJson($system, $user);

        if (!is_array($json) || !isset($json['score'])) {
            $result = $this->fallbackScore($profile, $searchQuery);
        } else {
            $result = [
                'score' => max(0, min(100, (int) $json['score'])),
                'summary' => (string) ($json['summary'] ?? 'Pertinence calculée par l’IA.'),
            ];
        }

        $this->storeCache($userId, $queryHash, $result['score'], $result['summary']);
        return $result;
    }

    public function rankResults(array $profiles, string $query): array
    {
        foreach ($profiles as &$profile) {
            $match = $this->scoreProfileForQuery($profile, $query);
            $profile['ai_score'] = $match['score'];
            $profile['ai_summary'] = $match['summary'];
        }
        unset($profile);

        usort($profiles, static fn (array $left, array $right): int => ($right['ai_score'] ?? 0) <=> ($left['ai_score'] ?? 0));

        return $profiles;
    }

    private function getCachedScore(int $userId, string $queryHash): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $statement = $this->db->prepare('SELECT score, summary FROM matching_cache WHERE user_id = :user_id AND query_hash = :query_hash AND expires_at > NOW() LIMIT 1');
        $statement->execute(['user_id' => $userId, 'query_hash' => $queryHash]);
        $cached = $statement->fetch();

        if (!$cached) {
            return null;
        }

        return ['score' => (int) $cached['score'], 'summary' => (string) ($cached['summary'] ?? '')];
    }

    private function storeCache(int $userId, string $queryHash, int $score, string $summary): void
    {
        if ($userId <= 0) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO matching_cache (user_id, query_hash, score, summary, expires_at, created_at)
             VALUES (:user_id, :query_hash, :score, :summary, DATE_ADD(NOW(), INTERVAL 1 DAY), NOW())
             ON DUPLICATE KEY UPDATE score = VALUES(score), summary = VALUES(summary), expires_at = VALUES(expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'query_hash' => $queryHash,
            'score' => $score,
            'summary' => $summary,
        ]);
    }

    private function fallbackScore(array $profile, string $query): array
    {
        $tokens = $this->tokenize($query);
        $haystack = $this->tokenize(implode(' ', [
            $profile['display_name'] ?? '',
            $profile['bio'] ?? '',
            json_encode($profile['skills'] ?? []),
            json_encode($profile['categories'] ?? []),
        ]));

        $matches = count(array_intersect($tokens, $haystack));
        $score = min(95, max(20, $matches * 15));

        return [
            'score' => $score,
            'summary' => $matches > 0 ? 'Correspondance locale basée sur les mots-clés.' : 'Peu de recouvrement détecté localement.',
        ];
    }

    private function tokenize(string $value): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($value)) ?: [];
        return array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));
    }
}