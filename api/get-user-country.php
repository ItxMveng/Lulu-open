<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$userIP = getUserIP();

// Pour le développement local, utiliser une IP publique de test
if ($userIP === '127.0.0.1' || $userIP === '::1' || strpos($userIP, '192.168.') === 0) {
    $userIP = '8.8.8.8'; // IP Google pour test
}

// API gratuite ipapi.co (1000 requêtes/jour)
$url = "http://ipapi.co/{$userIP}/json/";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: LULU-OPEN/1.0',
        'timeout' => 3
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response) {
    $data = json_decode($response, true);
    
    if (isset($data['country_code'])) {
        echo json_encode([
            'country_code' => strtoupper($data['country_code']),
            'country_name' => $data['country_name'] ?? '',
            'city' => $data['city'] ?? '',
            'ip' => $userIP
        ]);
    } else {
        // Fallback par défaut
        echo json_encode([
            'country_code' => 'FR',
            'country_name' => 'France',
            'city' => '',
            'ip' => $userIP
        ]);
    }
} else {
    // Fallback par défaut
    echo json_encode([
        'country_code' => 'FR',
        'country_name' => 'France',
        'city' => '',
        'ip' => $userIP
    ]);
}
?>