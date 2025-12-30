<?php
/**
 * Fonctions helper pour les vues
 */

if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return "À l'instant";
        if ($diff < 3600) return floor($diff / 60) . " min";
        if ($diff < 86400) return floor($diff / 3600) . " h";
        if ($diff < 604800) return floor($diff / 86400) . " j";
        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/lulu/';
        return $baseUrl . 'assets/' . ltrim($path, '/');
    }
}
?>
