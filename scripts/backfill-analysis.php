<?php
declare(strict_types=1);

/** Calcule et met en cache le score IA des candidatures qui n'en ont pas encore. */
require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI uniquement.' . PHP_EOL);
}

$applications = new Application();
$offers = new Offer();
$profiles = new Profile();
$analyzer = new CvAnalyzer();
$dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));

$pending = $applications->needingAnalysis(100);
echo count($pending) . ' candidature(s) à analyser.' . PHP_EOL;

foreach ($pending as $app) {
    $offer = $offers->findById((int) $app['offer_id']) ?? [];
    $profile = $profiles->getByUserId((int) $app['applicant_id']) ?? [];
    $text = implode("\n", array_filter([
        'Domaines : ' . implode(', ', $dec($profile['categories'] ?? '[]')),
        'Compétences : ' . implode(', ', $dec($profile['skills'] ?? '[]')),
        'Langues : ' . implode(', ', $dec($profile['languages'] ?? '[]')),
        'Présentation : ' . (string) ($profile['bio'] ?? ''),
    ]));
    $result = $analyzer->analyze($text, (string) ($offer['description'] ?? ''));
    $applications->saveAnalysis((int) $app['id'], (int) ($result['match_score'] ?? 0), $result);
    echo '  #' . $app['id'] . ' -> ' . ($result['match_score'] ?? '?') . '/100' . PHP_EOL;
}

echo 'Terminé.' . PHP_EOL;
