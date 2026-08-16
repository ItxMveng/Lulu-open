<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only' . PHP_EOL);
}

$model = new SavedSearch();
$searches = $model->allAlertEnabled();

foreach ($searches as $search) {
    file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'search_alerts.log', sprintf("[%s] Search %d checked\n", date('Y-m-d H:i:s'), $search['id']), FILE_APPEND);
}

echo 'Alertes vérifiées.' . PHP_EOL;