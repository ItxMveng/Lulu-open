<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (!is_auth() || current_role() !== 'admin') {
    http_response_code(403);
    exit('403');
}

$type = (string) ($_GET['type'] ?? 'users');
$rows = [];
$filename = $type . '-' . date('Ymd-His') . '.csv';

if ($type === 'subscriptions') {
    $rows = db()->query('SELECT * FROM subscriptions')->fetchAll() ?: [];
} elseif ($type === 'applications') {
    $rows = db()->query('SELECT * FROM applications')->fetchAll() ?: [];
} else {
    $rows = db()->query('SELECT * FROM users')->fetchAll() ?: [];
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
$stream = fopen('php://output', 'wb');
if ($stream && !empty($rows)) {
    fputcsv($stream, array_keys($rows[0]), ';');
    foreach ($rows as $row) {
        fputcsv($stream, $row, ';');
    }
}
if ($stream) {
    fclose($stream);
}
exit;