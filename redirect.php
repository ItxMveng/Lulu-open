<?php
// Redirection automatique vers le routage unifié
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$query = $_SERVER['QUERY_STRING'] ?? '';

// Déterminer la nouvelle URL
if (strpos($uri, 'search.php') !== false) {
    $newUrl = '/lulu/search';
} elseif (strpos($uri, 'login.php') !== false) {
    $newUrl = '/lulu/login';
} elseif (strpos($uri, 'register.php') !== false) {
    $newUrl = '/lulu/register';
} elseif (strpos($uri, 'index.php') !== false) {
    $newUrl = '/lulu/';
} else {
    $newUrl = '/lulu/';
}

// Ajouter les paramètres de requête si présents
if (!empty($query)) {
    $newUrl .= '?' . $query;
}

// Redirection permanente
header("Location: $newUrl", true, 301);
exit;
?>