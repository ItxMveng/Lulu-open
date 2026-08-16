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
$writer = new OfferWriter();
$result = $writer->generate(
    (string) ($payload['title'] ?? ''),
    (string) ($payload['sector'] ?? ''),
    (array) ($payload['skills'] ?? []),
    (string) ($payload['contractType'] ?? ''),
    (string) ($payload['companyDescription'] ?? '')
);
json_response($result);