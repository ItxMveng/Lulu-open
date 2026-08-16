<?php
declare(strict_types=1);

/**
 * Gestion de la devise d'affichage.
 * - Base des prix : EUR (plans stockés en EUR).
 * - Détection automatique via l'IP du visiteur (marché africain prioritaire).
 * - Sélection manuelle possible, mémorisée en session.
 *
 * Les taux CFA (XOF/XAF) sont fixes (parité EUR). Les autres sont approximatifs
 * et peuvent être rafraîchis (scripts/refresh-rates.php).
 */
final class CurrencyService
{
    /** code => [symbole, nom, taux (unités par 1 EUR)] */
    private const CURRENCIES = [
        // Afrique
        'XOF' => ['FCFA', 'Franc CFA (BCEAO)', 655.957],
        'XAF' => ['FCFA', 'Franc CFA (BEAC)', 655.957],
        'NGN' => ['₦', 'Naira nigérian', 1750.0],
        'GHS' => ['₵', 'Cedi ghanéen', 16.0],
        'KES' => ['KSh', 'Shilling kényan', 140.0],
        'MAD' => ['DH', 'Dirham marocain', 10.8],
        'DZD' => ['DA', 'Dinar algérien', 145.0],
        'TND' => ['DT', 'Dinar tunisien', 3.4],
        'EGP' => ['E£', 'Livre égyptienne', 53.0],
        'ZAR' => ['R', 'Rand sud-africain', 20.0],
        'RWF' => ['FRw', 'Franc rwandais', 1450.0],
        'CDF' => ['FC', 'Franc congolais', 3000.0],
        'UGX' => ['USh', 'Shilling ougandais', 4000.0],
        'TZS' => ['TSh', 'Shilling tanzanien', 2800.0],
        'ETB' => ['Br', 'Birr éthiopien', 130.0],
        'GNF' => ['FG', 'Franc guinéen', 9300.0],
        'MUR' => ['₨', 'Roupie mauricienne', 50.0],
        // International
        'EUR' => ['€', 'Euro', 1.0],
        'USD' => ['$', 'Dollar américain', 1.08],
        'GBP' => ['£', 'Livre sterling', 0.85],
        'CAD' => ['$CA', 'Dollar canadien', 1.47],
        'CHF' => ['CHF', 'Franc suisse', 0.95],
        'CNY' => ['¥', 'Yuan chinois', 7.8],
        'JPY' => ['¥', 'Yen japonais', 165.0],
        'INR' => ['₹', 'Roupie indienne', 90.0],
        'AED' => ['د.إ', 'Dirham émirati', 3.97],
        'SAR' => ['﷼', 'Riyal saoudien', 4.05],
        'BRL' => ['R$', 'Réal brésilien', 5.9],
        'AUD' => ['$AU', 'Dollar australien', 1.64],
    ];

    /** ISO pays => devise (large couverture, monde entier) */
    private const COUNTRY_CURRENCY = [
        // Zone CFA
        'SN' => 'XOF', 'CI' => 'XOF', 'BJ' => 'XOF', 'BF' => 'XOF', 'ML' => 'XOF', 'NE' => 'XOF', 'TG' => 'XOF', 'GW' => 'XOF',
        'CM' => 'XAF', 'GA' => 'XAF', 'CG' => 'XAF', 'TD' => 'XAF', 'CF' => 'XAF', 'GQ' => 'XAF',
        // Afrique
        'NG' => 'NGN', 'GH' => 'GHS', 'KE' => 'KES', 'MA' => 'MAD', 'DZ' => 'DZD', 'TN' => 'TND', 'EG' => 'EGP',
        'ZA' => 'ZAR', 'RW' => 'RWF', 'CD' => 'CDF', 'UG' => 'UGX', 'TZ' => 'TZS', 'ET' => 'ETB', 'GN' => 'GNF', 'MU' => 'MUR',
        // Europe (zone euro + hors)
        'FR' => 'EUR', 'BE' => 'EUR', 'DE' => 'EUR', 'ES' => 'EUR', 'IT' => 'EUR', 'PT' => 'EUR', 'NL' => 'EUR', 'IE' => 'EUR', 'LU' => 'EUR',
        'GB' => 'GBP', 'CH' => 'CHF',
        // Amériques
        'US' => 'USD', 'CA' => 'CAD', 'BR' => 'BRL',
        // Asie / Moyen-Orient / Océanie
        'CN' => 'CNY', 'JP' => 'JPY', 'IN' => 'INR', 'AE' => 'AED', 'SA' => 'SAR', 'AU' => 'AUD',
    ];

    public static function defaultCurrency(): string
    {
        return strtoupper((string) env('DEFAULT_CURRENCY', 'XOF'));
    }

    /** Devise active : override manuel > détection IP (cache session) > défaut. */
    public static function current(): string
    {
        if (!empty($_SESSION['currency']) && isset(self::CURRENCIES[$_SESSION['currency']])) {
            return (string) $_SESSION['currency'];
        }
        if (!isset($_SESSION['currency_detected'])) {
            $_SESSION['currency_detected'] = self::detectFromIp();
        }
        $code = (string) $_SESSION['currency_detected'];
        return isset(self::CURRENCIES[$code]) ? $code : self::defaultCurrency();
    }

    public static function set(string $code): void
    {
        $code = strtoupper($code);
        if (isset(self::CURRENCIES[$code])) {
            $_SESSION['currency'] = $code;
        }
    }

    public static function all(): array
    {
        return self::CURRENCIES;
    }

    public static function info(?string $code = null): array
    {
        $code = $code ?? self::current();
        $c = self::CURRENCIES[$code] ?? self::CURRENCIES['EUR'];
        return ['code' => $code, 'symbol' => $c[0], 'name' => $c[1], 'rate' => $c[2]];
    }

    /** Convertit un montant EUR et le formate dans la devise active. */
    public static function format(float $amountEur, ?string $code = null): string
    {
        $info = self::info($code);
        $value = $amountEur * (float) $info['rate'];
        // Pas de décimales pour les devises "sans centimes" usuelles.
        $noDecimals = in_array($info['code'], ['XOF', 'XAF', 'NGN', 'RWF', 'CDF', 'DZD', 'UGX', 'TZS', 'GNF', 'JPY', 'ETB'], true);
        $formatted = number_format($value, $noDecimals ? 0 : 2, ',', ' ');
        $symbol = $info['symbol'];
        // Symbole après pour FCFA et devises texte, avant pour €/$.
        return in_array($symbol, ['€', '$', '$CA'], true) ? $symbol . $formatted : $formatted . ' ' . $symbol;
    }

    private static function detectFromIp(): string
    {
        $ip = self::clientIp();
        if ($ip === '' || self::isPrivateIp($ip) || !function_exists('curl_init')) {
            return self::defaultCurrency();
        }

        try {
            $curl = curl_init('https://ipwho.is/' . $ip . '?fields=country_code,currency');
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ]);
            if (defined('CA_BUNDLE') && CA_BUNDLE) {
                curl_setopt($curl, CURLOPT_CAINFO, CA_BUNDLE);
            }
            $raw = curl_exec($curl);
            curl_close($curl);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            $country = is_array($data) ? strtoupper((string) ($data['country_code'] ?? '')) : '';
            if ($country !== '' && isset(self::COUNTRY_CURRENCY[$country])) {
                return self::COUNTRY_CURRENCY[$country];
            }
        } catch (Throwable) {
        }

        return self::defaultCurrency();
    }

    private static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
