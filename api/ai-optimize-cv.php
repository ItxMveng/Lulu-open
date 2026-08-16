<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}
if (current_role() !== 'entreprise') {
    json_response(['error' => 'Accès refusé'], 403);
}

$payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$relativePath = (string) ($payload['cv_path'] ?? '');
$offerText = (string) ($payload['offer_text'] ?? '');
$absolutePath = $relativePath !== '' ? base_path($relativePath) : '';

$analyzer = new CvAnalyzer();
$optimizer = new CvOptimizer();
$cvText = is_file($absolutePath) ? $analyzer->extractTextFromPdf($absolutePath) : '';
$result = $optimizer->optimize($cvText, $offerText, 'entreprise');
json_response($result);