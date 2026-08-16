<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only' . PHP_EOL);
}

$subscriptions = new Subscription();
$current = $subscriptions->allActive();

foreach ($current as $subscription) {
    if (!empty($subscription['ends_at']) && strtotime((string) $subscription['ends_at']) < time()) {
        $user = (new User())->findById((int) $subscription['user_id']);
        if ($user) {
            $subscriptions->downgradeToFree((int) $user['id'], (string) $user['role']);
        }
    }

    if (!empty($subscription['renews_at']) && strtotime((string) $subscription['renews_at']) <= strtotime('+7 days')) {
        file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'cron_subscriptions.log', sprintf("[%s] Reminder for subscription %d\n", date('Y-m-d H:i:s'), $subscription['id']), FILE_APPEND);
    }
}

echo 'Subscriptions cron completed.' . PHP_EOL;