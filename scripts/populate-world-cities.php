<?php
require_once '../config/db.php';

function getCountries() {
    $url = "https://restcountries.com/v3.1/all?fields=name,cca2,region";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: LULU-OPEN/1.0',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return [];
}

function getCitiesForCountry($countryCode) {
    // GeoNames gratuit avec inscription sur geonames.org
    $username = 'demo'; // Remplacez par votre username
    
    $url = "http://api.geonames.org/searchJSON?country={$countryCode}&featureClass=P&maxRows=20&username={$username}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: LULU-OPEN/1.0',
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        return $data['geonames'] ?? [];
    }
    
    return [];
}

try {
    $pdo = getConnection();
    
    echo "Récupération des pays...\n";
    $countries = getCountries();
    
    foreach ($countries as $country) {
        $nom = $country['name']['common'] ?? '';
        $code = $country['cca2'] ?? '';
        $continent = $country['region'] ?? '';
        
        if ($nom && $code) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO pays (nom, code_iso, continent) VALUES (?, ?, ?)");
            $stmt->execute([$nom, $code, $continent]);
            $paysId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM pays WHERE code_iso = '$code'")->fetchColumn();
            
            echo "Pays: $nom ($code)\n";
            
            $cities = getCitiesForCountry($code);
            
            foreach ($cities as $city) {
                $nomVille = $city['name'] ?? '';
                $population = $city['population'] ?? 0;
                $lat = $city['lat'] ?? null;
                $lng = $city['lng'] ?? null;
                
                if ($nomVille) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO villes (nom, pays_id, population, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nomVille, $paysId, $population, $lat, $lng]);
                    echo "  - Ville: $nomVille\n";
                }
            }
            
            sleep(1);
        }
    }
    
    echo "Mise à jour suggestions...\n";
    
    $pdo->exec("DROP TABLE IF EXISTS suggestions_localisation");
    $pdo->exec("
        CREATE TABLE suggestions_localisation AS
        SELECT 
            CONCAT(v.nom, ', ', p.nom) as suggestion,
            v.nom as ville,
            p.nom as pays,
            p.code_iso,
            v.id as ville_id,
            p.id as pays_id
        FROM villes v 
        JOIN pays p ON v.pays_id = p.id
    ");
    
    $pdo->exec("ALTER TABLE suggestions_localisation ADD INDEX idx_suggestion (suggestion)");
    
    echo "Terminé !\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>