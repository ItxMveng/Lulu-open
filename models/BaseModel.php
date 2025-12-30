<?php
/**
 * Modèle de base - LULU-OPEN
 */

class BaseModel {
    
    protected $db;
    protected $table;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    protected function validateRequired($fields, $data) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Le champ $field est requis";
            }
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }
    
    protected function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format d\'email invalide');
        }
    }
    
    protected function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!preg_match('/^[\+]?[1-9][\d]{8,14}$/', $phone)) {
            throw new Exception('Format de téléphone invalide');
        }
    }
    
    protected function validateUrl($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Format d\'URL invalide');
        }
    }
    
    protected function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            throw new Exception('Format de date invalide');
        }
    }
    
    protected function validateNumeric($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            throw new Exception('La valeur doit être numérique');
        }
        
        if ($min !== null && $value < $min) {
            throw new Exception("La valeur doit être supérieure ou égale à $min");
        }
        
        if ($max !== null && $value > $max) {
            throw new Exception("La valeur doit être inférieure ou égale à $max");
        }
    }
    
    protected function validateLength($value, $min = null, $max = null) {
        $length = strlen($value);
        
        if ($min !== null && $length < $min) {
            throw new Exception("La longueur doit être d'au moins $min caractères");
        }
        
        if ($max !== null && $length > $max) {
            throw new Exception("La longueur ne doit pas dépasser $max caractères");
        }
    }
    
    protected function validateEnum($value, $allowedValues) {
        if (!in_array($value, $allowedValues)) {
            $allowed = implode(', ', $allowedValues);
            throw new Exception("Valeur non autorisée. Valeurs acceptées: $allowed");
        }
    }
    
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    public function commit() {
        return $this->db->commit();
    }
    
    public function rollback() {
        return $this->db->rollback();
    }
    
    protected function executeInTransaction($callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    protected function sanitizeData($data, $allowedFields = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (empty($allowedFields) || in_array($key, $allowedFields)) {
                if (is_string($value)) {
                    $sanitized[$key] = trim($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }
        
        return $sanitized;
    }
    
    protected function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    protected function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    protected function generateSlug($text) {
        // Conversion en minuscules
        $text = strtolower($text);
        
        // Remplacement des caractères spéciaux
        $text = str_replace(
            ['à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'd', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'th', 'y'],
            $text
        );
        
        // Suppression des caractères non alphanumériques
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Remplacement des espaces et tirets multiples par un seul tiret
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Suppression des tirets en début et fin
        return trim($text, '-');
    }
    
    protected function formatPrice($price) {
        return number_format($price, 2, ',', ' ') . ' €';
    }
    
    protected function formatDate($date, $format = 'd/m/Y H:i') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date->format($format);
    }
    
    protected function logError($message, $context = []) {
        $logMessage = $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage);
    }
    
    public function getTableName() {
        return $this->table;
    }
}
?>