<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}

$payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$relativePath = (string) ($payload['cv_path'] ?? '');
$offerText = (string) ($payload['offer_text'] ?? '');
$tone = (string) ($payload['tone'] ?? 'professional');
$absolutePath = $relativePath !== '' ? base_path($relativePath) : '';

$analyzer = new CvAnalyzer();
$generator = new CoverLetterGenerator();
$cvText = is_file($absolutePath) ? $analyzer->extractTextFromPdf($absolutePath) : '';
$text = $generator->generate($cvText, $offerText, (string) (auth_user()['name'] ?? 'Utilisateur'), 'Entreprise cible', $tone);
json_response(['text' => $text]);