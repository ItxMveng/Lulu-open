<?php
declare(strict_types=1);

/**
 * Stockage objet compatible S3 (pensé pour Cloudflare R2), sans dépendance.
 *
 * But : rendre les fichiers uploadés (CV, photos, justificatifs) PERSISTANTS.
 * Sur l'hébergement gratuit (disque éphémère), tout ce qui est écrit sur le
 * disque local disparaît à chaque redéploiement/veille. Quand les variables
 * R2_* sont définies, on pousse une copie sur R2 ; le service des fichiers
 * (routeur /uploads/*) va la rechercher là si elle manque en local.
 *
 * Variables d'environnement attendues :
 *   R2_ENDPOINT           https://<accountid>.r2.cloudflarestorage.com
 *   R2_BUCKET             nom du bucket
 *   R2_ACCESS_KEY_ID      clé d'accès (R2 API token)
 *   R2_SECRET_ACCESS_KEY  secret
 *   R2_REGION             (optionnel, défaut "auto")
 *
 * La « clé » d'objet est le chemin relatif du projet, ex. "uploads/cv/ab12.docx",
 * pour rester 1:1 avec les chemins stockés en base — aucune conversion ailleurs.
 */
final class Storage
{
    public static function enabled(): bool
    {
        return env('R2_ENDPOINT', '') !== ''
            && env('R2_BUCKET', '') !== ''
            && env('R2_ACCESS_KEY_ID', '') !== ''
            && env('R2_SECRET_ACCESS_KEY', '') !== '';
    }

    /** Envoie/écrase un objet. Retourne true si l'upload distant a réussi. */
    public static function put(string $key, string $body, string $contentType = 'application/octet-stream'): bool
    {
        if (!self::enabled()) {
            return false;
        }
        [$status] = self::request('PUT', $key, $body, ['Content-Type' => $contentType]);
        return $status >= 200 && $status < 300;
    }

    /** Récupère le contenu d'un objet, ou null s'il est absent/erreur. */
    public static function get(string $key): ?string
    {
        if (!self::enabled()) {
            return null;
        }
        [$status, $body] = self::request('GET', $key);
        return ($status >= 200 && $status < 300) ? $body : null;
    }

    /** Supprime un objet (sans échouer si absent). */
    public static function delete(string $key): void
    {
        if (!self::enabled()) {
            return;
        }
        self::request('DELETE', $key);
    }

    /* ------------------------------------------------------------------ Interne */

    /**
     * @return array{0:int,1:string} [code HTTP, corps]
     */
    private static function request(string $method, string $key, string $body = '', array $extraHeaders = []): array
    {
        $endpoint = rtrim((string) env('R2_ENDPOINT', ''), '/');
        $bucket   = (string) env('R2_BUCKET', '');
        $access   = (string) env('R2_ACCESS_KEY_ID', '');
        $secret   = (string) env('R2_SECRET_ACCESS_KEY', '');
        $region   = (string) env('R2_REGION', 'auto');
        $service  = 's3';

        $host = parse_url($endpoint, PHP_URL_HOST) ?: '';
        // Chemin style path : /{bucket}/{clé encodée segment par segment}
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', ltrim($key, '/'))));
        $canonicalUri = '/' . rawurlencode($bucket) . '/' . $encodedKey;
        $url = $endpoint . $canonicalUri;

        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $payloadHash = hash('sha256', $body);

        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $now,
        ];
        foreach ($extraHeaders as $k => $v) {
            $headers[strtolower($k)] = $v;
        }
        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= $k . ':' . trim((string) $v) . "\n";
        }
        $signedHeaders = implode(';', array_keys($headers));

        $canonicalRequest = implode("\n", [
            $method,
            $canonicalUri,
            '', // query string vide
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = $date . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $now,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate    = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = 'AWS4-HMAC-SHA256 '
            . 'Credential=' . $access . '/' . $scope . ', '
            . 'SignedHeaders=' . $signedHeaders . ', '
            . 'Signature=' . $signature;

        $curlHeaders = ['Authorization: ' . $authorization];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        if ($method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }
        if (defined('CA_BUNDLE') && CA_BUNDLE) {
            curl_setopt($curl, CURLOPT_CAINFO, CA_BUNDLE);
        }

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($response === false || $err !== '') {
            self::log($method, $key, 0, $err);
            return [0, ''];
        }
        if ($status < 200 || $status >= 300) {
            self::log($method, $key, $status, (string) $response);
        }
        return [$status, (string) $response];
    }

    private static function log(string $method, string $key, int $status, string $detail): void
    {
        @file_put_contents(
            LOG_PATH . DIRECTORY_SEPARATOR . 'storage.log',
            json_encode([
                'ts' => date('c'), 'method' => $method, 'key' => $key,
                'status' => $status, 'detail' => mb_substr($detail, 0, 500),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }
}
