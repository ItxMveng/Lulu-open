<?php
/**
 * Classe de validation et sanitization - LULU-OPEN
 * Approche whitelist pour sécurité maximale
 */

class Validator {
    
    /**
     * Vérifie qu'une valeur n'est pas vide
     */
    public static function required($value): bool {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }
    
    /**
     * Valide un email
     */
    public static function email($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Vérifie longueur minimale
     */
    public static function minLength($value, $min): bool {
        return mb_strlen(trim($value)) >= $min;
    }
    
    /**
     * Vérifie longueur maximale
     */
    public static function maxLength($value, $max): bool {
        return mb_strlen(trim($value)) <= $max;
    }
    
    /**
     * Vérifie que la valeur est alphanumérique (lettres, chiffres, espaces, tirets, underscores)
     */
    public static function alphanumeric($value): bool {
        return preg_match('/^[a-zA-Z0-9\s\-_]+$/', $value) === 1;
    }
    
    /**
     * Vérifie qu'un numéro de téléphone est valide (format international flexible)
     */
    public static function phone($phone): bool {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        return strlen($cleaned) >= 8 && strlen($cleaned) <= 15;
    }
    
    /**
     * Vérifie qu'une URL est valide
     */
    public static function url($url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Vérifie qu'un nombre est dans une plage
     */
    public static function between($value, $min, $max): bool {
        $num = floatval($value);
        return $num >= $min && $num <= $max;
    }
    
    /**
     * Sanitize une chaîne de caractères
     */
    public static function sanitizeString($value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize un email
     */
    public static function sanitizeEmail($email): string {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return strtolower(trim($email));
    }
    
    /**
     * Sanitize un entier
     */
    public static function sanitizeInt($value): int {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize un float
     */
    public static function sanitizeFloat($value): float {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize une URL
     */
    public static function sanitizeUrl($url): string {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Valide et retourne les erreurs pour un ensemble de champs
     * 
     * @param array $data Données à valider
     * @param array $rules Règles de validation ['field' => ['required', 'email', ...]]
     * @return array Tableau d'erreurs (vide si tout est valide)
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';
            
            foreach ($fieldRules as $rule) {
                // Règle simple (ex: 'required', 'email')
                if (is_string($rule)) {
                    switch ($rule) {
                        case 'required':
                            if (!self::required($value)) {
                                $errors[$field][] = "Le champ $field est requis.";
                            }
                            break;
                        case 'email':
                            if (!empty($value) && !self::email($value)) {
                                $errors[$field][] = "Le champ $field doit être un email valide.";
                            }
                            break;
                        case 'alphanumeric':
                            if (!empty($value) && !self::alphanumeric($value)) {
                                $errors[$field][] = "Le champ $field ne peut contenir que des lettres et chiffres.";
                            }
                            break;
                        case 'phone':
                            if (!empty($value) && !self::phone($value)) {
                                $errors[$field][] = "Le champ $field doit être un numéro de téléphone valide.";
                            }
                            break;
                        case 'url':
                            if (!empty($value) && !self::url($value)) {
                                $errors[$field][] = "Le champ $field doit être une URL valide.";
                            }
                            break;
                    }
                }
                // Règle avec paramètres (ex: ['min' => 8])
                elseif (is_array($rule)) {
                    foreach ($rule as $ruleName => $param) {
                        switch ($ruleName) {
                            case 'min':
                                if (!self::minLength($value, $param)) {
                                    $errors[$field][] = "Le champ $field doit contenir au moins $param caractères.";
                                }
                                break;
                            case 'max':
                                if (!self::maxLength($value, $param)) {
                                    $errors[$field][] = "Le champ $field ne peut pas dépasser $param caractères.";
                                }
                                break;
                            case 'between':
                                if (!self::between($value, $param[0], $param[1])) {
                                    $errors[$field][] = "Le champ $field doit être entre {$param[0]} et {$param[1]}.";
                                }
                                break;
                        }
                    }
                }
            }
        }
        
        return $errors;
    }
}
?>
