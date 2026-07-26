<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}

$profileModel = new Profile();
$enhancer = new ProfileEnhancer();
$profile = $profileModel->getByUserId((int) current_user_id()) ?? [];
json_response($enhancer->suggest($profile));