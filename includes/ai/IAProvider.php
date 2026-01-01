<?php
/**
 * Provider IA Central pour les candidatures
 */
class IAProvider {
    
    private static function getAnalyzeSystemPrompt(): string {
        return <<<EOT
Tu es un expert RH très exigeant spécialisé dans l'analyse de compatibilité CV/Offre.

TACHE :
1. Extraire les informations clés du CV et de l'offre telles qu'elles apparaissent dans les textes
2. Évaluer la compatibilité de manière critique et honnête
3. Fournir une analyse détaillée avec recommandations

RÈGLES STRICTES :
- Score réaliste 0-100 (ne pas surévaluer)
- Utilise UNIQUEMENT les informations présentes dans les textes
- Sois critique et exigeant dans l'évaluation
- Réponds UNIQUEMENT en JSON valide

FORMAT OBLIGATOIRE :
{
"cv_parsing": {
    "title": "Titre/Poste exact identifié dans le CV",
    "experience": "Résumé précis de l'expérience (années + domaine)",
    "skills": "Top 3-5 compétences clés extraites du CV"
},
"job_parsing": {
    "title": "Titre exact du poste recherché",
    "experience": "Expérience requise selon l'offre",
    "skills": "Top 3-5 compétences demandées dans l'offre"
},
"score": 45,
"global_fit": "faible",
"domain_match": "faible",
"experience_match": "moyen",
"skills_match": "faible",
"reasons": ["Raison précise 1", "Raison précise 2"],
"critical_gaps": ["Lacune critique 1", "Lacune critique 2"],
"missing_keywords": ["Mot-clé 1", "Mot-clé 2"],
"recommendations": ["Recommandation actionnable 1", "Recommandation actionnable 2"]
}
EOT;
    }
    
    public static function analyzeCvAndJob(string $cvText, string $jobText): array {
        // Nettoyer et valider les entrées
        $cvText = self::cleanInputText($cvText);
        $jobText = self::cleanInputText($jobText);
        
        // Vérifier la qualité des données
        if (strlen($cvText) < 100) {
            error_log("CV TEXT TOO SHORT: " . strlen($cvText) . " chars");
            return self::getCriticalFallbackAnalysis($cvText, $jobText);
        }
        
        if (strlen($jobText) < 50) {
            error_log("JOB TEXT TOO SHORT: " . strlen($jobText) . " chars");
            return ['error' => 'Veuillez fournir une description d\'offre plus détaillée (minimum 50 caractères).'];
        }
        
        // Tronquer intelligemment pour l'API
        $cvText = mb_substr($cvText, 0, 15000, 'UTF-8');
        $jobText = mb_substr($jobText, 0, 10000, 'UTF-8');
        
        $systemPrompt = self::getAnalyzeSystemPrompt();
        
        $userPrompt = "CV CANDIDAT:\n```\n$cvText\n```\n\nOFFRE EMPLOI:\n```\n$jobText\n```\n\nAnalyse cette compatibilité de manière TRÈS CRITIQUE. Extrais les informations exactes des textes et évalue honnêtement.";
        
        $raw = self::callLLM($systemPrompt, $userPrompt);
        
        if (!$raw) {
            error_log("MISTRAL NULL RESPONSE");
            return self::getCriticalFallbackAnalysis($cvText, $jobText);
        }
        
        $decoded = json_decode($raw, true);
        if (!$decoded || !isset($decoded['score'])) {
            error_log("MISTRAL INVALID JSON: " . substr($raw, 0, 500));
            return self::getCriticalFallbackAnalysis($cvText, $jobText);
        }
        
        // Valider et enrichir la réponse
        return self::validateAndEnrichAnalysis($decoded, $cvText, $jobText);
    }
    
    /**
     * Nettoie et valide le texte d'entrée
     */
    private static function cleanInputText(string $text): string {
        // Supprimer les caractères de contrôle et le bruit
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        
        // Supprimer les répétitions excessives de symboles
        $text = preg_replace('/([^\w\s])\s*\1\s*\1+/', ' ', $text);
        
        // Supprimer les lignes de bruit pur (plus de 80% de symboles)
        $lines = explode("\n", $text);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 3) continue;
            
            $alphaCount = preg_match_all('/[a-zA-Z0-9àâäéèêëïîôöùûüÿç]/', $line);
            $totalCount = strlen($line);
            
            if ($totalCount > 0 && ($alphaCount / $totalCount) > 0.2) {
                $cleanLines[] = $line;
            }
        }
        
        return implode("\n", $cleanLines);
    }
    
    /**
     * Valide et enrichit l'analyse IA
     */
    private static function validateAndEnrichAnalysis(array $analysis, string $cvText, string $jobText): array {
        // Valeurs par défaut
        $defaults = [
            'cv_parsing' => ['title' => 'Non extrait', 'experience' => 'Non extrait', 'skills' => 'Non extrait'],
            'job_parsing' => ['title' => 'Non extrait', 'experience' => 'Non extrait', 'skills' => 'Non extrait'],
            'score' => 0,
            'global_fit' => 'tres_faible',
            'domain_match' => 'faible',
            'experience_match' => 'faible',
            'skills_match' => 'faible',
            'reasons' => ['Analyse incomplète'],
            'critical_gaps' => [],
            'missing_keywords' => [],
            'recommendations' => []
        ];
        
        $analysis = array_merge($defaults, $analysis);
        
        // Extraire des informations si l'IA a échoué
        if ($analysis['cv_parsing']['title'] === 'Non extrait' || empty($analysis['cv_parsing']['title'])) {
            $analysis['cv_parsing'] = self::extractCvInfo($cvText);
        }
        
        if ($analysis['job_parsing']['title'] === 'Non extrait' || empty($analysis['job_parsing']['title'])) {
            $analysis['job_parsing'] = self::extractJobInfo($jobText);
        }
        
        return $analysis;
    }
    
    /**
     * Extraction d'informations CV par regex
     */
    private static function extractCvInfo(string $cvText): array {
        $info = ['title' => 'Profil professionnel', 'experience' => 'Expérience variée', 'skills' => 'Compétences techniques'];
        
        // Extraire titre/poste
        if (preg_match('/(?:développeur|ingénieur|chef|manager|consultant|analyste|cuisinier|pâtissier)\s+[\w\s]+/i', $cvText, $matches)) {
            $info['title'] = trim($matches[0]);
        }
        
        // Extraire expérience
        if (preg_match('/(\d+)\s+ans?\s+(?:d[\'\'])?expérience/i', $cvText, $matches)) {
            $info['experience'] = $matches[1] . ' ans d\'expérience';
        }
        
        // Extraire compétences
        $skills = [];
        $skillPatterns = ['PHP', 'JavaScript', 'Python', 'React', 'Vue', 'Laravel', 'MySQL', 'cuisine', 'pâtisserie', 'service'];
        foreach ($skillPatterns as $skill) {
            if (stripos($cvText, $skill) !== false) {
                $skills[] = $skill;
            }
        }
        if (!empty($skills)) {
            $info['skills'] = implode(', ', array_slice($skills, 0, 3));
        }
        
        return $info;
    }
    
    /**
     * Extraction d'informations offre par regex
     */
    private static function extractJobInfo(string $jobText): array {
        $info = ['title' => 'Poste spécialisé', 'experience' => 'Expérience requise', 'skills' => 'Compétences spécifiques'];
        
        // Extraire titre du poste
        if (preg_match('/(?:recherchons|recherche)\s+un[e]?\s+([^\n\.]+)/i', $jobText, $matches)) {
            $info['title'] = trim($matches[1]);
        }
        
        // Extraire expérience requise
        if (preg_match('/(\d+)\s+ans?\s+(?:d[\'\']expérience|minimum)/i', $jobText, $matches)) {
            $info['experience'] = $matches[1] . ' ans minimum';
        }
        
        // Extraire compétences demandées
        $skills = [];
        $skillPatterns = ['PHP', 'JavaScript', 'Python', 'React', 'Vue', 'Laravel', 'MySQL', 'cuisine', 'pâtisserie', 'service'];
        foreach ($skillPatterns as $skill) {
            if (stripos($jobText, $skill) !== false) {
                $skills[] = $skill;
            }
        }
        if (!empty($skills)) {
            $info['skills'] = implode(', ', array_slice($skills, 0, 3));
        }
        
        return $info;
    }
    
    public static function generateOptimizedCv(string $cvText, string $jobText, string $style = 'default'): string {
        $systemPrompt = "Tu es un expert en rédaction de CV. Tu dois créer un CV **parfaitement adapté** à l'offre d'emploi fournie, en utilisant le contenu réel du CV existant. Tu dois :\n- Reformuler et réorganiser le contenu existant pour correspondre aux exigences de l'offre\n- Intégrer naturellement les mots-clés de l'offre dans les descriptions\n- Mettre en avant les expériences et compétences les plus pertinentes\n- Adapter le vocabulaire au secteur ciblé\n- Créer un résumé professionnel accrocheur\n- NE PAS inventer de fausses expériences ou compétences\n\nTu réponds en JSON structuré avec un contenu riche et détaillé.";
        
        $userPrompt = "CV du candidat (contenu brut) :\n```\n$cvText\n```\n\nOffre d'emploi ciblée :\n```\n$jobText\n```\n\nTu dois créer un CV optimisé qui :\n1. Utilise un titre professionnel spécifique au poste visé\n2. Inclut un résumé de 4-6 phrases mettant en avant la valeur ajoutée\n3. Réorganise les expériences pour mettre en avant celles pertinentes pour l'offre\n4. Adapte les descriptions d'expériences avec le vocabulaire de l'offre\n5. Groupe les compétences par catégories pertinentes\n6. Inclut les formations et certifications importantes\n\nStyle : $style\n\nRéponds STRICTEMENT avec un JSON au format :\n```\n{\n  \"title\": \"Titre professionnel spécifique (ex: Développeur Full-Stack PHP/React - 5 ans d'expérience)\",\n  \"summary\": \"Résumé professionnel de 4-6 phrases mettant en avant la valeur ajoutée pour ce poste spécifique\",\n  \"sections\": [\n    {\n      \"title\": \"Expériences Professionnelles\",\n      \"items\": [\n        \"• Poste - Entreprise (dates) : Description détaillée adaptée à l'offre avec résultats quantifiés\",\n        \"• Autre expérience pertinente avec focus sur les compétences demandées\"\n      ]\n    },\n    {\n      \"title\": \"Compétences Techniques\",\n      \"items\": [\"Compétence clé 1\", \"Compétence clé 2\", \"...\"]\n    },\n    {\n      \"title\": \"Formation & Certifications\",\n      \"items\": [\"Diplôme/Certification - Établissement - Année\"]\n    }\n  ]\n}\n```";
        
        $response = self::callLLM($systemPrompt, $userPrompt);
        
        if ($response) {
            $decoded = json_decode($response, true);
            if ($decoded && isset($decoded['sections'])) {
                // Vérification de la qualité
                $summary = $decoded['summary'] ?? '';
                $sections = $decoded['sections'] ?? [];
                
                if (strlen($summary) < 200 || count($sections) < 2) {
                    // Log de la réponse invalide
                    file_put_contents(
                        __DIR__ . '/../../logs/ai_mistral.log',
                        "CV OPTIMISE INVALIDE: " . substr($response, 0, 500) . "\n\n",
                        FILE_APPEND
                    );
                    return json_encode(self::getFallbackOptimizedCvJson($cvText, $jobText, $style));
                }
                
                return json_encode($decoded); // Retourner le JSON pour stockage
            }
        }
        
        // Fallback
        return json_encode(self::getFallbackOptimizedCvJson($cvText, $jobText, $style));
    }
    
    public static function generateCoverLetter(string $cvText, array $cvData, array $jobData, string $style = 'professional'): string {
        $systemPrompt = "Tu es un expert en rédaction de lettres de motivation. Tu dois créer une lettre **personnalisée et convaincante** qui :\n- Démontre une compréhension précise du poste et de l'entreprise\n- Met en avant les expériences et compétences les plus pertinentes du candidat\n- Utilise des exemples concrets et des résultats quantifiés quand possible\n- Adopte un ton professionnel mais authentique\n- Fait le lien entre le profil du candidat et les besoins de l'entreprise\n\nLa lettre doit faire 300-500 mots et être structurée en 4-5 paragraphes.";
        
        $prenom = $cvData['prenom'] ?? '';
        $nom = $cvData['nom'] ?? '';
        $poste = $jobData['poste'] ?? '';
        $entreprise = $jobData['entreprise'] ?? '';
        $jobText = $jobData['job_text'] ?? '';
        $uniqueId = uniqid(); // Ajout d'un identifiant unique pour forcer une nouvelle génération
        $userPrompt = "ID de requête unique (à ignorer): $uniqueId\n\n" .
            "Informations du candidat :\n" .
            "Nom : $prenom $nom\n" .
            "CONTENU INTÉGRAL DU CV DU CANDIDAT:\n```\n$cvText\n```\n\n" .
            "POSTE VISÉ SPÉCIFIQUEMENT : $poste\n" .
            "ENTREPRISE CIBLÉE : $entreprise\n\n" .
            "Description détaillée de l'offre d'emploi :\n```\n$jobText\n```\n\n" .
            "Style souhaité : $style\n\n" .
            "INSTRUCTIONS CRITIQUES :\n" .
            "1. Cette lettre doit être UNIQUEMENT adaptée à ce poste spécifique ($poste chez $entreprise)\n" .
            "2. Analyse les exigences spécifiques de l'offre et relie-les aux expériences/compétences du candidat\n" .
            "3. Utilise le nom de l'entreprise et du poste dans l'accroche et tout au long de la lettre\n" .
            "4. Donne des exemples concrets qui correspondent aux besoins exprimés dans l'offre\n" .
            "5. Évite les formulations génériques - sois spécifique à cette opportunité\n" .
            "6. Termine par une ouverture vers un entretien personnalisée\n\n" .
            "Retourne uniquement le texte de la lettre, structuré en 4-5 paragraphes distincts, sans aucun commentaire additionnel.";
        
        $response = self::callLLM($systemPrompt, $userPrompt);
        
        if ($response) {
            $cleanResponse = trim($response);
            
            // Vérification de la qualité de la réponse
            if (strlen($cleanResponse) < 300 || substr_count($cleanResponse, "\n") < 3) {
                // Log de la réponse invalide
                file_put_contents(
                    __DIR__ . '/../../logs/ai_mistral.log',
                    "LETTRE INVALIDE (trop courte): " . $cleanResponse . "\n\n",
                    FILE_APPEND
                );
                return self::getFallbackCoverLetter($cvData, $jobData, $style);
            }
            
            return $cleanResponse;
        }
        
        // Fallback
        return self::getFallbackCoverLetter($cvData, $jobData, $style);
    }
    
    /**
     * Appel Mistral API
     */
    private static function callLLM(string $systemPrompt, string $userPrompt): ?string {
        if (!defined('AI_PROVIDER') || AI_PROVIDER !== 'mistral' || !defined('AI_API_KEY') || !AI_API_KEY) {
            error_log("MISTRAL CONFIG MISSING");
            return null;
        }
        
        $ch = curl_init();
        $payload = [
            'model' => AI_MODEL_NAME,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => AI_API_BASE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . AI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            error_log("MISTRAL API CURL ERROR: " . $curlError);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("MISTRAL API HTTP ERROR: Code $httpCode. Response: " . $response);
            return null;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log("INVALID RESPONSE: " . substr($response, 0, 500));
            return null;
        }
        
        $content = $data['choices'][0]['message']['content'];
        error_log("MISTRAL SUCCESS: " . strlen($content) . " chars");
        
        return $content;
    }
    
    /**
     * Fallbacks critiques si Mistral indisponible
     */
    private static function getCriticalFallbackAnalysis(string $cvText, string $jobText): array {
        $score = self::calculateCriticalScore($cvText, $jobText);
        $fit = $score >= 80 ? 'excellent' : ($score >= 60 ? 'bon' : ($score >= 40 ? 'moyen' : ($score >= 20 ? 'faible' : 'tres_faible')));
        
        $jobKeywords = self::extractJobKeywords($jobText);
        $cvKeywords = self::extractCvKeywords($cvText);
        $missingKeywords = array_diff($jobKeywords, $cvKeywords);
        
        return [
            'score' => $score,
            'global_fit' => $fit,
            'domain_match' => 'moyen',
            'experience_match' => 'moyen', 
            'skills_match' => 'moyen',
            'reasons' => $score < 50 ? ['Compétences principales manquantes'] : ['Profil globalement adapté'],
            'critical_gaps' => $score < 60 ? ['Manque d\'expérience spécifique au poste'] : [],
            'missing_keywords' => array_slice($missingKeywords, 0, 6),
            'recommendations' => $score < 50 ? ['Acquérir les compétences manquantes'] : ['Mettre en avant l\'expérience pertinente']
        ];
    }
    
    private static function getFallbackOptimizedCvJson(string $cvText, string $jobText, string $style): array {
        $keywords = self::extractJobKeywords($jobText);
        
        return [
            'title' => 'Professionnel Expérimenté',
            'summary' => 'Professionnel avec expérience solide, compétences techniques et capacités d\'adaptation. Motivé par les nouveaux défis et l\'innovation.',
            'sections' => [
                [
                    'title' => 'Compétences Clés',
                    'items' => array_slice($keywords, 0, 8)
                ],
                [
                    'title' => 'Expérience Professionnelle',
                    'items' => ['Gestion de projets complexes', 'Collaboration en équipe', 'Résultats mesurables']
                ],
                [
                    'title' => 'Formation',
                    'items' => ['Formation supérieure', 'Formation continue']
                ]
            ]
        ];
    }
    
    private static function buildCvSummary(array $cvData): string {
        $summary = "Candidat: " . ($cvData['prenom'] ?? '') . ' ' . ($cvData['nom'] ?? '') . "\n";
        $summary .= "Poste recherché: " . ($cvData['titre_poste_recherche'] ?? 'Non spécifié') . "\n";
        
        if (!empty($cvData['competences'])) {
            $summary .= "Compétences: " . substr($cvData['competences'], 0, 200) . "\n";
        }
        
        if (!empty($cvData['experiences_professionnelles'])) {
            $summary .= "Expériences: " . substr($cvData['experiences_professionnelles'], 0, 300) . "\n";
        }
        
        return $summary;
    }
    
    private static function buildDetailedCvSummary(array $cvData): string {
        $summary = "Candidat: " . ($cvData['prenom'] ?? '') . ' ' . ($cvData['nom'] ?? '') . "\n";
        $summary .= "Poste recherché: " . ($cvData['titre_poste_recherche'] ?? 'Non spécifié') . "\n";
        $summary .= "Niveau: " . ($cvData['niveau_experience'] ?? 'Non spécifié') . "\n";
        
        if (!empty($cvData['competences'])) {
            $summary .= "Compétences: " . $cvData['competences'] . "\n";
        }
        
        if (!empty($cvData['experiences_professionnelles'])) {
            $summary .= "Expériences: " . $cvData['experiences_professionnelles'] . "\n";
        }
        
        if (!empty($cvData['formations'])) {
            $summary .= "Formations: " . $cvData['formations'] . "\n";
        }
        
        return $summary;
    }
    
    private static function formatExperiences(array $cvData): string {
        $experiences = $cvData['experiences_professionnelles'] ?? '';
        if (empty($experiences)) {
            return "Aucune expérience renseignée";
        }
        
        // Formater les expériences (séparées par ---)
        $expBlocks = explode('---', $experiences);
        $formatted = [];
        
        foreach (array_slice($expBlocks, 0, 3) as $block) { // Max 3 expériences
            $lines = array_filter(explode("\n", trim($block)));
            if (!empty($lines)) {
                $formatted[] = "  - " . implode(', ', array_slice($lines, 0, 2)); // Poste + entreprise
            }
        }
        
        return !empty($formatted) ? implode("\n", $formatted) : "Expériences non détaillées";
    }
    
    private static function calculateCriticalScore(string $cvText, string $jobText): int {
        $jobKeywords = self::extractJobKeywords($jobText);
        $cvKeywords = self::extractCvKeywords($cvText);
        $matches = array_intersect($jobKeywords, $cvKeywords);
        
        $baseScore = count($jobKeywords) > 0 ? (count($matches) / count($jobKeywords)) * 100 : 0;
        
        return max(10, min(100, intval($baseScore)));
    }
    
    private static function getFallbackCoverLetter(array $cvData, array $jobData, string $style): string {
        $nom = ($cvData['prenom'] ?? '') . ' ' . ($cvData['nom'] ?? '');
        $poste = $jobData['poste'] ?? 'le poste';
        $entreprise = $jobData['entreprise'] ?? 'votre entreprise';
        
        return "Madame, Monsieur,\n\nJe souhaite présenter ma candidature pour le poste de $poste chez $entreprise.\n\nMon expérience et mes compétences correspondent aux exigences du poste.\n\nJe serais ravi de vous rencontrer pour échanger.\n\nCordialement,\n$nom";
    }
    
    private static function extractJobKeywords(string $text): array {
        $keywords = [];
        $skills = ['PHP', 'JavaScript', 'Python', 'React', 'Vue', 'MySQL', 'Git', 'leadership', 'communication', 'gestion', 'Laravel', 'développement', 'web', 'cuisinier', 'cuisine', 'pâtisserie'];
        
        foreach ($skills as $skill) {
            if (stripos($text, $skill) !== false) {
                $keywords[] = $skill;
            }
        }
        
        return array_unique($keywords);
    }
    
    private static function extractCvKeywords(string $text): array {
        return self::extractJobKeywords($text);
    }
}
?>
