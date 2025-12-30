<?php
// Fonctions d'internationalisation

/**
 * Obtenir la salutation selon l'heure et la langue
 */
function getGreeting($langue = 'fr') {
    $hour = date('H');
    
    $greetings = [
        'fr' => [
            'morning' => 'Bonjour',
            'afternoon' => 'Bon après-midi',
            'evening' => 'Bonsoir'
        ],
        'en' => [
            'morning' => 'Good morning',
            'afternoon' => 'Good afternoon',
            'evening' => 'Good evening'
        ],
        'es' => [
            'morning' => 'Buenos días',
            'afternoon' => 'Buenas tardes',
            'evening' => 'Buenas noches'
        ],
        'de' => [
            'morning' => 'Guten Morgen',
            'afternoon' => 'Guten Tag',
            'evening' => 'Guten Abend'
        ],
        'it' => [
            'morning' => 'Buongiorno',
            'afternoon' => 'Buon pomeriggio',
            'evening' => 'Buonasera'
        ],
        'pt' => [
            'morning' => 'Bom dia',
            'afternoon' => 'Boa tarde',
            'evening' => 'Boa noite'
        ],
        'ar' => [
            'morning' => 'صباح الخير',
            'afternoon' => 'مساء الخير',
            'evening' => 'مساء الخير'
        ]
    ];
    
    $period = 'morning';
    if ($hour >= 12 && $hour < 18) {
        $period = 'afternoon';
    } elseif ($hour >= 18 || $hour < 5) {
        $period = 'evening';
    }
    
    return $greetings[$langue][$period] ?? $greetings['fr'][$period];
}

/**
 * Formater un montant selon la devise
 */
function formatCurrency($amount, $devise = 'EUR') {
    $symbols = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF',
        'CAD' => 'CAD $',
        'MAD' => 'DH',
        'XOF' => 'CFA',
        'XAF' => 'FCFA'
    ];
    
    $symbol = $symbols[$devise] ?? '€';
    $formatted = number_format($amount, 2, ',', ' ');
    
    // Position du symbole selon la devise
    if (in_array($devise, ['USD', 'GBP', 'CAD'])) {
        return $symbol . ' ' . $formatted;
    }
    
    return $formatted . ' ' . $symbol;
}

/**
 * Détecter la langue du navigateur
 */
function detectBrowserLanguage() {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    $supported = ['fr', 'en', 'es', 'de', 'it', 'pt', 'ar'];
    return in_array($lang, $supported) ? $lang : 'fr';
}

/**
 * Détecter la devise selon le pays (via IP ou paramètres)
 */
function detectCurrency($codeIso = null) {
    $currencies = [
        'FR' => 'EUR', 'BE' => 'EUR', 'LU' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
        'US' => 'USD', 'CA' => 'CAD', 'GB' => 'GBP', 'CH' => 'CHF',
        'MA' => 'MAD', 'TN' => 'TND', 'DZ' => 'DZD',
        'SN' => 'XOF', 'CI' => 'XOF', 'BJ' => 'XOF', 'TG' => 'XOF', 'ML' => 'XOF',
        'CM' => 'XAF', 'GA' => 'XAF', 'CG' => 'XAF', 'TD' => 'XAF'
    ];
    
    return $currencies[$codeIso] ?? 'EUR';
}
