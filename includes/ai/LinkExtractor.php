<?php
declare(strict_types=1);

/**
 * Récupère le contenu textuel principal d'une page web (ex: une offre d'emploi).
 * Inclut une garde anti-SSRF basique (rejette les hôtes internes/privés).
 */
final class LinkExtractor
{
    public static function fetch(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !function_exists('curl_init')) {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '' || self::isPrivateHost($host)) {
            return null;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LuluOpenBot/1.0)',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        if (defined('CA_BUNDLE') && CA_BUNDLE) {
            curl_setopt($curl, CURLOPT_CAINFO, CA_BUNDLE);
        }
        $html = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($html) || $html === '' || $httpCode >= 400) {
            return null;
        }

        return self::htmlToText($html);
    }

    private static function htmlToText(string $html): string
    {
        // Retire scripts/styles/nav/footer avant extraction.
        $html = preg_replace('#<(script|style|noscript|svg|head|nav|footer|header)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(\s*\n\s*){2,}/u', "\n\n", $text) ?? $text;
        $text = trim($text);

        return mb_substr($text, 0, 8000);
    }

    private static function isPrivateHost(string $host): bool
    {
        $host = strtolower($host);
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return true;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return false;
    }
}
