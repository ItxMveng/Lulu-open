<?php
ob_start();
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}
require_once '../../includes/sidebar.php';
require_once '../../includes/ai/IAProvider.php';
require_once '../../includes/ai/CvUtils.php';
require_once '../../includes/ai/ImageOcrExtractor.php';

function scrapeLinkContent($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "", // Gère automatiquement gzip/deflate
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            error_log("SCRAPING FAILED: $url - HTTP $httpCode");
            return null;
        }

        // Use DOMDocument for robust parsing
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress warnings from malformed HTML
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);

        // Remove script, style, and common noise tags
        foreach ($xpath->query('//script|//style|//nav|//header|//footer|//aside|//form') as $node) {
            $node->parentNode->removeChild($node);
        }

        // Try to find the main content area, more specific first
        $mainNode = $xpath->query('//main | //article | //div[contains(@id, "description")] | //div[contains(@class, "job-description")] | //div[contains(@id, "content")]')->item(0);

        // If we found a main node, use its text content, otherwise use the whole body
        $bodyNode = $mainNode ?: $xpath->query('//body')->item(0);

        $text = $bodyNode ? $bodyNode->textContent : strip_tags($html);

        // Final cleaning
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        error_log("SCRAPING SUCCESS: $url - " . strlen($text) . " chars");
        return strlen($text) > 100 ? $text : null;

    } catch (Exception $e) {
        error_log("SCRAPING ERROR: $url - " . $e->getMessage());
        return null;
    }
}

function extractCvSummary($cvText, $cvData) {
    // Priorité absolue au texte extrait du PDF
    // Recherche d'un titre de poste commun en début de CV
    $firstLines = substr($cvText, 0, 500);
    if (preg_match('/(?:développeur|ingénieur|chef|manager|consultant|analyste|pâtissier|cuisinier|technicien|assistant|directeur|responsable)\s+[a-zà-ÿ0-9\s\-\&]+/i', $firstLines, $matches)) {
        return trim($matches[0]);
    }
    
    if (!empty($cvData['titre_poste_recherche'])) {
        return $cvData['titre_poste_recherche'] . ' - ' . ($cvData['niveau_experience'] ?? 'Niveau non spécifié');
    }
    
    return 'Profil professionnel';
}

function extractCvExperience($cvText, $cvData) {
    // Essayer de trouver la durée d'expérience dans le texte
    if (preg_match('/(\d+)\s+ans?\s+d[\'\']expérience/i', $cvText, $matches)) {
        return $matches[1] . ' ans d\'expérience';
    }
    
    if (!empty($cvData['experiences_professionnelles'])) {
        $exp = explode('---', $cvData['experiences_professionnelles'])[0];
        $lines = explode("\n", $exp);
        return implode(' - ', array_slice($lines, 0, 2));
    }
    
    return 'Expérience variée';
}

function extractCvSkills($cvText, $cvData) {
    // Si le texte est riche, on ne se fie pas à la BDD en premier
    // Mais l'extraction de compétences par regex est complexe, on garde la BDD en fallback
    // L'IA fera le vrai travail d'extraction
    
    if (!empty($cvData['competences'])) {
        $skills = explode(',', $cvData['competences']);
        return implode(', ', array_slice($skills, 0, 3));
    }
    
    // Fallback texte
    if (strlen($cvText) > 100) return 'Compétences du CV';
    
    return 'Compétences techniques';
}

function extractJobRequirements($jobText) {
    if (preg_match('/(?:recherchons|recherche)\s+un[e]?\s+([^\n\.]+)/i', $jobText, $matches)) {
        return trim($matches[1]);
    }
    return 'Profil spécialisé';
}

function extractJobExperience($jobText) {
    if (preg_match('/(\d+)\s+ans?\s+(?:d[\'\']expérience|minimum)/i', $jobText, $matches)) {
        return $matches[1] . ' ans d\'expérience minimum';
    }
    return 'Expérience ciblée';
}

function extractJobSkills($jobText) {
    $skills = [];
    $patterns = ['PHP', 'JavaScript', 'Python', 'Java', 'Laravel', 'React', 'Vue', 'Angular', 'MySQL', 'PostgreSQL'];
    
    foreach ($patterns as $skill) {
        if (stripos($jobText, $skill) !== false) {
            $skills[] = $skill;
        }
    }
    
    return !empty($skills) ? implode(', ', array_slice($skills, 0, 3)) : 'Compétences spécifiques';
}

function stripEmojis($text) {
    // Supprime les caractères 4 octets (emojis) pour compatibilité MySQL utf8mb3
    return $text ? preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text) : $text;
}

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

global $database;
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Pour les actions AJAX, définir le header JSON
    if (in_array($action, ['analyze_cv', 'generate_cv', 'generate_letter'])) {
        header('Content-Type: application/json');
        // Désactiver l'affichage des erreurs pour éviter de polluer le JSON
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 120);
        
        // Gestionnaire d'arrêt pour capturer les erreurs fatales et renvoyer du JSON
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
                if (ob_get_length()) ob_end_clean(); // Nettoyer le buffer avant d'envoyer le JSON
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Erreur critique serveur: ' . $error['message']]);
            }
        });
    }
    
    if ($action === 'add_candidature') {
        $entreprise = stripEmojis($_POST['entreprise']);
        $poste = stripEmojis($_POST['poste']);
        $type_candidature = $_POST['type_candidature'];
        $contenu = stripEmojis($_POST['contenu'] ?? '');
        $lien_offre = $_POST['lien_offre'] ?? '';
        
        // Gestion du fichier
        $fichier_path = null;
        if (isset($_FILES['fichier_offre']) && $_FILES['fichier_offre']['error'] === 0) {
            $filename = 'offre_' . $_SESSION['user_id'] . '_' . time() . '_' . $_FILES['fichier_offre']['name'];
            $upload_path = '../../uploads/offres/' . $filename;
            
            if (!is_dir('../../uploads/offres/')) {
                mkdir('../../uploads/offres/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['fichier_offre']['tmp_name'], $upload_path)) {
                $fichier_path = $filename;
            }
        }
        
        $database->query("INSERT INTO candidatures (utilisateur_id, entreprise, poste, type_candidature, contenu, lien_offre, fichier_offre, date_candidature, statut) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'en_attente')", 
            [$_SESSION['user_id'], $entreprise, $poste, $type_candidature, $contenu, $lien_offre, $fichier_path]);
        
        flashMessage('Candidature ajoutée avec succès !', 'success');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // MODIFIER CANDIDATURE
    if ($action === 'edit_candidature') {
        $id = $_POST['candidature_id'];
        $entreprise = stripEmojis($_POST['entreprise']);
        $poste = stripEmojis($_POST['poste']);
        $type_candidature = $_POST['type_candidature'];
        $contenu = stripEmojis($_POST['contenu'] ?? '');
        $lien_offre = $_POST['lien_offre'] ?? '';
        $statut = $_POST['statut'];
        
        // Vérifier que la candidature appartient à l'utilisateur
        $existing = $database->fetch("SELECT * FROM candidatures WHERE id = ? AND utilisateur_id = ?", [$id, $_SESSION['user_id']]);
        if (!$existing) {
            flashMessage('Candidature non trouvée', 'error');
        } else {
            // Gestion du nouveau fichier
            $fichier_path = $existing['fichier_offre'];
            if (isset($_FILES['fichier_offre']) && $_FILES['fichier_offre']['error'] === 0) {
                // Supprimer l'ancien fichier
                if ($fichier_path && file_exists('../../uploads/offres/' . $fichier_path)) {
                    unlink('../../uploads/offres/' . $fichier_path);
                }
                
                $filename = 'offre_' . $_SESSION['user_id'] . '_' . time() . '_' . $_FILES['fichier_offre']['name'];
                $upload_path = '../../uploads/offres/' . $filename;
                
                if (move_uploaded_file($_FILES['fichier_offre']['tmp_name'], $upload_path)) {
                    $fichier_path = $filename;
                }
            }
            
            $database->query("UPDATE candidatures SET entreprise = ?, poste = ?, type_candidature = ?, contenu = ?, lien_offre = ?, fichier_offre = ?, statut = ? WHERE id = ?", 
                [$entreprise, $poste, $type_candidature, $contenu, $lien_offre, $fichier_path, $statut, $id]);
            
            flashMessage('Candidature modifiée avec succès !', 'success');
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // SUPPRIMER CANDIDATURE
    if ($action === 'delete_candidature') {
        $id = $_POST['candidature_id'];
        
        // Vérifier que la candidature appartient à l'utilisateur
        $existing = $database->fetch("SELECT * FROM candidatures WHERE id = ? AND utilisateur_id = ?", [$id, $_SESSION['user_id']]);
        if (!$existing) {
            flashMessage('Candidature non trouvée', 'error');
        } else {
            // Supprimer le fichier associé
            if ($existing['fichier_offre'] && file_exists('../../uploads/offres/' . $existing['fichier_offre'])) {
                unlink('../../uploads/offres/' . $existing['fichier_offre']);
            }
            
            $database->query("DELETE FROM candidatures WHERE id = ?", [$id]);
            flashMessage('Candidature supprimée avec succès !', 'success');
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // NOUVELLE ANALYSE IA - Utilise CV PDF du profil
    if ($action === 'analyze_cv') {
        // Nettoyer tous les buffers de sortie pour garantir un JSON pur
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Configuration erreur silencieuse
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $candidatureId = $_POST['candidature_id'] ?? 0;
        
        try {
            if (!$candidatureId) {
                echo json_encode(['success' => false, 'error' => 'ID candidature requis']);
                exit;
            }
            
            // 1. Récupérer candidature spécifique
            $candidature = $database->fetch("SELECT * FROM candidatures WHERE id = ? AND utilisateur_id = ?", [$candidatureId, $_SESSION['user_id']]);
            if (!$candidature) {
                echo json_encode(['success' => false, 'error' => 'Candidature non trouvée']);
                exit;
            }
            
            // 2. Récupérer CV du profil
            $cvData = CvUtils::getCvDataFromDatabase($_SESSION['user_id'], $database);
            if (!$cvData || empty($cvData['cv_file'])) {
                echo json_encode(['success' => false, 'error' => 'Vous devez d\'abord téléverser votre CV PDF dans votre profil.']);
                exit;
            }
            
            // 3. Extraire texte du CV PDF avec validation robuste
            $cvFilePath = '../../uploads/cv/' . $cvData['cv_file'];
            
            // Essayer plusieurs méthodes d'extraction
            $cvText = null;
            
            // Méthode 1: SimplePdfExtractor amélioré
            if (class_exists('SimplePdfExtractor')) {
                $cvText = SimplePdfExtractor::extractText($cvFilePath);
            }
            
            // Méthode 2: Spatie PDF (si disponible)
            if (!$cvText && class_exists('Spatie\PdfToText\Pdf')) {
                try {
                    $cvText = \Spatie\PdfToText\Pdf::getText($cvFilePath);
                } catch (Exception $e) {
                    error_log("Spatie PDF failed: " . $e->getMessage());
                }
            }
            
            // Méthode 3: Fallback base de données
            if (!$cvText || strlen(trim($cvText)) < 100) {
                $cvText = CvUtils::combineCvSources(null, $cvData);
                if (strlen(trim($cvText)) < 100) {
                    echo json_encode(['success' => false, 'error' => 'CV insuffisant. Veuillez compléter votre profil avec plus d\'informations ou utiliser un PDF avec texte sélectionnable.']);
                    exit;
                }
                error_log("Using database CV data: " . strlen($cvText) . " chars");
            } else {
                error_log("PDF extraction success: " . strlen($cvText) . " chars");
            }
            // 4. Construire jobText depuis la candidature spécifique avec nettoyage
            $jobParts = [];
            if (!empty($candidature['contenu'])) {
                $jobParts[] = "Description fournie :\n" . $candidature['contenu'];
            }
            if (!empty($candidature['lien_offre'])) {
                $scrapedContent = scrapeLinkContent($candidature['lien_offre']);
                if ($scrapedContent && strlen($scrapedContent) > 300) {
                    $jobParts[] = "Contenu extrait du lien :\n" . $scrapedContent;
                    error_log("SCRAPING SUCCESS: " . strlen($scrapedContent) . " chars");
                } else {
                    // ERREUR STRICTE POUR LE LIEN
                    echo json_encode(['success' => false, 'error' => 'Impossible de lire le contenu de l\'offre via le lien (protection anti-robot ou site sécurisé). Veuillez copier-coller la description du poste dans le champ "Description textuelle" de la candidature.']);
                    exit;
                }
            }
            if (!empty($candidature['fichier_offre'])) {
                $offreFilePath = '../../uploads/offres/' . $candidature['fichier_offre'];
                $offreText = CvUtils::extractTextFromPdfOrDoc($offreFilePath);
                
                // Gestion spéciale pour les images
                if (!$offreText && in_array(strtolower(pathinfo($offreFilePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $offreText = ImageOcrExtractor::extractTextFromImage($offreFilePath);
                }
                
                // Vérification et réparation du texte de l'offre si nécessaire
                if ($offreText && strlen($offreText) > 100) {
                    $cleanOffre = trim($offreText);
                    // Détection de garbage (ratio alphanumérique faible ou trop de caractères isolés)
                    $alphaNum = preg_replace('/[^a-zA-Z0-9\p{L}]/u', '', $cleanOffre);
                    $ratio = strlen($cleanOffre) > 0 ? strlen($alphaNum) / strlen($cleanOffre) : 0;
                    
                    if ($ratio < 0.4 || preg_match_all('/\b\w\b/', substr($cleanOffre, 0, 500)) > 20) {
                        error_log("OFFRE TEXTE CASSÉ DÉTECTÉ - Réparation nécessaire");
                        // Nettoyage basique au lieu de repairText
                        $cleanOffre = preg_replace('/[^\w\s\p{L}\p{P}]/u', ' ', $cleanOffre);
                        $cleanOffre = preg_replace('/\s+/', ' ', $cleanOffre);
                        $offreText = trim($cleanOffre);
                    }
                    
                    $jobParts[] = "Texte extrait de l'offre jointe :\n" . $offreText;
                } elseif ($offreText) {
                    echo json_encode(['success' => false, 'error' => 'Le fichier de l\'offre ne contient pas assez de texte lisible. Merci de coller la description dans le champ texte.']);
                    exit;
                }
            }
            
            $jobText = implode("\n\n", $jobParts);
            
            // Vérification des sources d'offre
            if (empty($candidature['contenu']) && empty($candidature['fichier_offre']) && empty($candidature['lien_offre'])) {
                echo json_encode(['success' => false, 'error' => 'Veuillez fournir au moins une description, un lien ou un fichier d\'offre.']);
                exit;
            }
            
            if (empty(trim($jobText)) || strlen(trim($jobText)) < 50) {
                echo json_encode(['success' => false, 'error' => 'Veuillez fournir une description d\'offre plus détaillée (minimum 50 caractères).']);
                exit;
            }
            
            // 5. Analyse IA avec Mistral - FORCER nouvelle analyse
            error_log("ANALYSE CANDIDATURE $candidatureId - CV: " . strlen($cvText) . " - JOB: " . strlen($jobText));
            
            // Libérer la session pour éviter les blocages lors des requêtes successives
            session_write_close();
            
            $analysis = IAProvider::analyzeCvAndJob($cvText, $jobText);
            
            if (!$analysis || !isset($analysis['score'])) {
                $lastError = error_get_last();
                $errorMessage = 'Erreur analyse IA. ';
                if (AI_DEBUG && $lastError) {
                    $errorMessage .= 'Détails: ' . $lastError['message'];
                } else {
                    $errorMessage .= 'Le service est peut-être indisponible. Consultez les logs serveur.';
                }
                echo json_encode(['success' => false, 'error' => $errorMessage]);
                exit;
            }
            
            // Ajouter l'ID de candidature pour éviter la confusion
            $analysis['candidature_id'] = $candidatureId;
            $analysis['timestamp'] = time();
            
            // Validation et nettoyage de l'analyse
            $analysis = array_merge([
                'score' => 0,
                'global_fit' => 'inconnu',
                'domain_match' => 'inconnu',
                'experience_match' => 'inconnu',
                'skills_match' => 'inconnu',
                'reasons' => ['Analyse en cours'],
                'critical_gaps' => [],
                'missing_keywords' => [],
                'recommendations' => []
            ], $analysis);
            
            // Ajouter des informations de debug
            $analysis['debug_cv_length'] = strlen($cvText);
            $analysis['debug_job_length'] = strlen($jobText);
            $analysis['debug_cv_snippet'] = substr($cvText, 0, 300);
            $analysis['debug_job_snippet'] = substr($jobText, 0, 300);
            
            // Extraire des informations du CV pour l'affichage
            // UTILISATION PRIORITAIRE DES DONNÉES EXTRAITES PAR L'IA
            $analysis['cv_summary'] = $analysis['cv_parsing']['title'] ?? 'Non extrait par l\'IA';
            $analysis['cv_experience'] = $analysis['cv_parsing']['experience'] ?? 'Non extrait par l\'IA';
            $analysis['cv_skills'] = $analysis['cv_parsing']['skills'] ?? 'Non extrait par l\'IA';
            
            // Extraire des informations de l'offre
            $analysis['job_requirements'] = $analysis['job_parsing']['title'] ?? 'Non extrait par l\'IA';
            $analysis['job_experience'] = $analysis['job_parsing']['experience'] ?? 'Non extrait par l\'IA';
            $analysis['job_skills'] = $analysis['job_parsing']['skills'] ?? 'Non extrait par l\'IA';
            
            // 6. Sauvegarder en BDD avec ID unique
            $database->query(
                "UPDATE candidatures SET analyse_ia = ?, score_compatibilite = ? WHERE id = ?",
                [json_encode($analysis), $analysis['score'], $candidatureId]
            );
            
            error_log("ANALYSE SAVED for candidature $candidatureId - Score: " . $analysis['score']);
            
            $jsonOutput = json_encode(['success' => true, 'analysis' => $analysis], JSON_INVALID_UTF8_SUBSTITUTE);
            if ($jsonOutput === false) {
                echo json_encode(['success' => false, 'error' => 'Erreur encodage JSON: ' . json_last_error_msg()]);
            } else {
                echo $jsonOutput;
            }
            exit;
            
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    // GÉNÉRATION CV OPTIMISÉ - Utilise CV PDF du profil + JSON structuré
    if ($action === 'generate_cv') {
        // Nettoyer tous les buffers de sortie
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $templateId = $_POST['template_id'] ?? 1;
        $candidatureId = $_POST['candidature_id'] ?? 0;
        
        try {
            // 1. Récupérer CV du profil
            $cvData = CvUtils::getCvDataFromDatabase($_SESSION['user_id'], $database);
            if (!$cvData || empty($cvData['cv_file'])) {
                echo json_encode(['success' => false, 'error' => 'CV PDF requis dans votre profil']);
                exit;
            }
            
            // 2. Extraire texte CV avec méthodes multiples
            $cvFilePath = '../../uploads/cv/' . $cvData['cv_file'];
            $cvText = null;
            
            // Essayer extraction PDF
            if (class_exists('SimplePdfExtractor')) {
                $cvText = SimplePdfExtractor::extractText($cvFilePath);
            }
            
            // Fallback Spatie
            if (!$cvText && class_exists('Spatie\PdfToText\Pdf')) {
                try {
                    $cvText = \Spatie\PdfToText\Pdf::getText($cvFilePath);
                } catch (Exception $e) {
                    error_log("Spatie error: " . $e->getMessage());
                }
            }
            
            // Fallback base de données
            if (!$cvText || strlen(trim($cvText)) < 100) {
                $cvText = CvUtils::combineCvSources(null, $cvData);
                if (strlen(trim($cvText)) < 50) {
                    echo json_encode(['success' => false, 'error' => 'CV insuffisant pour génération']);
                    exit;
                }
            }
            
            // 3. Construire jobText spécifique à la candidature
            $jobText = "Optimisation générale du CV";
            if ($candidatureId) {
                $candidature = $database->fetch("SELECT * FROM candidatures WHERE id = ? AND utilisateur_id = ?", [$candidatureId, $_SESSION['user_id']]);
                if ($candidature) {
                    $jobParts = [];
                    if (!empty($candidature['contenu'])) {
                        $jobParts[] = "Description fournie :\n" . $candidature['contenu'];
                    }
                    if (!empty($candidature['lien_offre'])) {
                        $scrapedContent = scrapeLinkContent($candidature['lien_offre']);
                        if ($scrapedContent) {
                            $jobParts[] = "Contenu extrait du lien :\n" . $scrapedContent;
                        }
                    }
                    if (!empty($candidature['fichier_offre'])) {
                        $offreFilePath = '../../uploads/offres/' . $candidature['fichier_offre'];
                        $offreText = CvUtils::extractTextFromPdfOrDoc($offreFilePath);
                        if ($offreText) $jobParts[] = "Texte extrait de l'offre jointe :\n" . $offreText;
                    }
                    
                    if (!empty($jobParts)) {
                        $jobText = implode("\n\n", $jobParts);
                    }
                }
            }
            
            // Libérer la session avant l'appel IA
            session_write_close();
            
            // 4. Génération contenu optimisé avec Mistral (JSON)
            $optimizedCvJson = IAProvider::generateOptimizedCv($cvText, $jobText);
            
            if (!$optimizedCvJson) {
                echo json_encode(['success' => false, 'error' => 'Erreur génération IA']);
                exit;
            }
            
            // 5. Stocker le JSON optimisé
            if ($candidatureId) {
                $existingAnalysis = $database->fetch("SELECT analyse_ia FROM candidatures WHERE id = ?", [$candidatureId]);
                $analysisData = $existingAnalysis && $existingAnalysis['analyse_ia'] ? json_decode($existingAnalysis['analyse_ia'], true) : [];
                $analysisData['cv_optimise_json'] = $optimizedCvJson;
                
                $database->query(
                    "UPDATE candidatures SET analyse_ia = ? WHERE id = ?",
                    [json_encode($analysisData), $candidatureId]
                );
            }
            
            // 6. Rendu template avec JSON
            $templateFile = "templates/cv-template-$templateId.php";
            if (file_exists($templateFile)) {
                require_once $templateFile;
                $renderFunction = "renderCvTemplate$templateId";
                
                if (function_exists($renderFunction)) {
                    $cvJsonData = json_decode($optimizedCvJson, true);
                    if (!$cvJsonData) {
                        echo json_encode(['success' => false, 'error' => 'JSON CV invalide']);
                        exit;
                    }
                    
                    $html = $renderFunction([
                        'cvData' => $cvJsonData,
                        'user' => $user,
                        'cv' => $cvData
                    ]);
                    
                    $jsonOutput = json_encode(['success' => true, 'html' => $html, 'template_id' => $templateId], JSON_INVALID_UTF8_SUBSTITUTE);
                    if ($jsonOutput === false) {
                        echo json_encode(['success' => false, 'error' => 'Erreur encodage JSON CV']);
                    } else {
                        echo $jsonOutput;
                    }
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => "Fonction $renderFunction non trouvée"]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => "Template $templateFile non trouvé"]);
                exit;
            }
            
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    // GÉNÉRATION LETTRE DE MOTIVATION
    if ($action === 'generate_letter') {
        // Nettoyer tous les buffers de sortie
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $templateId = $_POST['template_id'] ?? 1;
        $candidatureId = $_POST['candidature_id'] ?? 0;
        $style = $_POST['style'] ?? 'professional';
        
        try {
            // 1. Récupérer candidature spécifique
            if (!$candidatureId) {
                echo json_encode(['success' => false, 'error' => 'ID candidature requis']);
                exit;
            }
            
            $candidature = $database->fetch("SELECT * FROM candidatures WHERE id = ? AND utilisateur_id = ?", [$candidatureId, $_SESSION['user_id']]);
            if (!$candidature) {
                echo json_encode(['success' => false, 'error' => 'Candidature non trouvée']);
                exit;
            }
            
            // 2. Récupérer CV avec extraction flexible
            $cvData = CvUtils::getCvDataFromDatabase($_SESSION['user_id'], $database);
            if (!$cvData) {
                echo json_encode(['success' => false, 'error' => 'Profil CV non trouvé']);
                exit;
            }
            
            $cvText = '';
            if (!empty($cvData['cv_file'])) {
                $cvFilePath = '../../uploads/cv/' . $cvData['cv_file'];
                if (file_exists($cvFilePath) && class_exists('SimplePdfExtractor')) {
                    $cvText = SimplePdfExtractor::extractText($cvFilePath);
                }
            }
            
            // Fallback sur les données de profil
            if (!$cvText || strlen(trim($cvText)) < 50) {
                $cvText = CvUtils::combineCvSources(null, $cvData);
            }
            
            // 3. Construire jobData spécifique
            $jobParts = [];
            if (!empty($candidature['contenu'])) $jobParts[] = $candidature['contenu'];
            if (!empty($candidature['lien_offre'])) {
                $scrapedContent = scrapeLinkContent($candidature['lien_offre']);
                if ($scrapedContent) {
                    $jobParts[] = $scrapedContent;
                }
            }
            
            $jobData = [
                'poste' => $candidature['poste'] ?? 'Poste',
                'entreprise' => $candidature['entreprise'] ?? 'Entreprise',
                'job_text' => !empty($jobParts) ? implode("\n", $jobParts) : 'Description du poste'
            ];
            
            // Libérer la session avant l'appel IA
            session_write_close();
            
            // 4. Génération contenu unique
            $letterContent = IAProvider::generateCoverLetter($cvText, $cvData, $jobData, $style);
            
            // 5. Rendu template
            $templateFile = "templates/letter-template-$templateId.php";
            if (file_exists($templateFile)) {
                require_once $templateFile;
                $renderFunction = "renderLetterTemplate$templateId";
                
                if (function_exists($renderFunction)) {
                    $html = $renderFunction([
                        'content' => $letterContent,
                        'user' => $user,
                        'job' => $jobData
                    ]);
                    
                    $jsonOutput = json_encode(['success' => true, 'html' => $html, 'template_id' => $templateId], JSON_INVALID_UTF8_SUBSTITUTE);
                    if ($jsonOutput === false) {
                        echo json_encode(['success' => false, 'error' => 'Erreur encodage JSON Lettre']);
                    } else {
                        echo $jsonOutput;
                    }
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'error' => 'Template lettre non trouvé']);
            exit;
            
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Récupérer les candidatures
$candidatures = $database->fetchAll("SELECT * FROM candidatures WHERE utilisateur_id = ? ORDER BY date_candidature DESC", [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Candidatures - LULU-OPEN</title>
    <link href="../../assets/css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .candidature-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
        }
        .candidature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-accepte { background: #d1edff; color: #0c5460; }
        .status-refuse { background: #f8d7da; color: #721c24; }
        .ai-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .ai-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }
        .score-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .score-excellent { background: #28a745; }
        .score-good { background: #ffc107; color: #000; }
        .score-average { background: #fd7e14; }
        .score-poor { background: #dc3545; }
        .cv-preview {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php renderSidebar($_SESSION['user_type'], 'candidatures.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">💼 Mes Candidatures</h1>
                    <p class="text-muted mb-0">Gérez vos candidatures et optimisez votre CV avec l'IA</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidatureModal">
                    <i class="bi bi-plus-lg me-2"></i>Nouvelle candidature
                </button>
            </div>

            <!-- Section IA -->
            <div class="ai-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-3">🤖 Assistant IA Mistral</h3>
                        <p class="mb-3">Notre IA Mistral analyse votre CV par rapport aux offres d'emploi, identifie les améliorations possibles et génère une version optimisée.</p>
                        <div class="ai-card">
                            <h6>✨ Fonctionnalités IA :</h6>
                            <ul class="mb-0">
                                <li>Analyse de compatibilité CV/Offre avec Mistral Large</li>
                                <li>Suggestions d'amélioration personnalisées</li>
                                <li>Génération de CV optimisé par IA</li>
                                <li>Création automatique de lettres de motivation</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="ai-card">
                            <i class="bi bi-robot" style="font-size: 4rem;"></i>
                            <h5 class="mt-2">Mistral IA</h5>
                            <p class="mb-0">Technologie avancée</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Affichage des résultats d'analyse -->
            <?php if (isset($_SESSION['analyse_result'])): ?>
                <?php $result = $_SESSION['analyse_result']; unset($_SESSION['analyse_result']); ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">🎯 Résultats de l'analyse IA</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="score-circle <?= $result['score'] >= 80 ? 'score-excellent' : ($result['score'] >= 60 ? 'score-good' : ($result['score'] >= 40 ? 'score-average' : 'score-poor')) ?>">
                                    <?= round($result['score']) ?>%
                                </div>
                                <p class="mt-2 fw-bold">Score de compatibilité</p>
                            </div>
                            <div class="col-md-9">
                                <h6>Suggestions d'amélioration :</h6>
                                <?php $suggestions = json_decode($result['suggestions'], true); ?>
                                <ul>
                                    <?php foreach ($suggestions as $suggestion): ?>
                                        <li><?= htmlspecialchars($suggestion) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="mt-3">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#cvAmelioreModal">
                                        <i class="bi bi-download me-2"></i>Voir le CV amélioré
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal CV amélioré -->
                <div class="modal fade" id="cvAmelioreModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">CV Amélioré par l'IA</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="cv-preview">
                                    <?= $result['cv_ameliore'] ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="button" class="btn btn-primary" onclick="downloadCV()">
                                    <i class="bi bi-download me-2"></i>Télécharger PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des candidatures -->
            <div class="row">
                <?php if (empty($candidatures)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-briefcase" style="font-size: 4rem; color: #dee2e6;"></i>
                            <h4 class="mt-3 text-muted">Aucune candidature</h4>
                            <p class="text-muted">Commencez par ajouter votre première candidature</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidatureModal">
                                <i class="bi bi-plus-lg me-2"></i>Ajouter une candidature
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($candidatures as $candidature): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card candidature-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($candidature['poste']) ?></h5>
                                        <span class="status-badge status-<?= $candidature['statut'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $candidature['statut'])) ?>
                                        </span>
                                    </div>
                                    
                                    <h6 class="text-primary"><?= htmlspecialchars($candidature['entreprise']) ?></h6>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($candidature['date_candidature'])) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-light text-dark">
                                            <?= ucfirst($candidature['type_candidature']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($candidature['lien_offre']): ?>
                                        <p class="small">
                                            <i class="bi bi-link-45deg"></i>
                                            <a href="<?= htmlspecialchars($candidature['lien_offre']) ?>" target="_blank">Voir l'offre</a>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($candidature['fichier_offre']): ?>
                                        <p class="small">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <a href="../../uploads/offres/<?= htmlspecialchars($candidature['fichier_offre']) ?>" target="_blank">Fichier joint</a>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($candidature['lettre_motivation']): ?>
                                        <p class="small">
                                            <i class="bi bi-envelope-paper"></i>
                                            <button class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#lettreModal<?= $candidature['id'] ?>">Voir la lettre de motivation</button>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="analyzeWithAI(<?= $candidature['id'] ?>)">
                                            <i class="bi bi-robot"></i> Analyser IA
                                        </button>
                                        <button class="btn btn-outline-success btn-sm" 
                                                onclick="generateCoverLetter(<?= $candidature['id'] ?>, '<?= addslashes($candidature['poste']) ?>', '<?= addslashes($candidature['entreprise']) ?>')">
                                            <i class="bi bi-envelope"></i> Lettre
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="generateCV(<?= $candidature['id'] ?>)">
                                            <i class="bi bi-file-earmark-text"></i> CV
                                        </button>
                                    </div>
                                    <div class="btn-group w-100 mt-2" role="group">
                                        <button class="btn btn-outline-warning btn-sm" 
                                                onclick="editCandidature(<?= $candidature['id'] ?>)">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteCandidature(<?= $candidature['id'] ?>)">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Lettre de motivation -->
                        <?php if ($candidature['lettre_motivation']): ?>
                            <div class="modal fade" id="lettreModal<?= $candidature['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Lettre de motivation - <?= htmlspecialchars($candidature['poste']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="lettre-content" style="white-space: pre-line; line-height: 1.6;">
                                                <?= htmlspecialchars($candidature['lettre_motivation']) ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <button type="button" class="btn btn-primary" onclick="downloadLetter(<?= $candidature['id'] ?>)">
                                                <i class="bi bi-download me-2"></i>Télécharger
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Nouvelle candidature -->
    <div class="modal fade" id="addCandidatureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Candidature</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_candidature">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Entreprise *</label>
                                <input type="text" class="form-control" name="entreprise" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Poste *</label>
                                <input type="text" class="form-control" name="poste" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Type de candidature *</label>
                                <select class="form-select" name="type_candidature" required>
                                    <option value="">Sélectionner</option>
                                    <option value="lien">Lien vers l'offre</option>
                                    <option value="texte">Description textuelle</option>
                                    <option value="fichier">Fichier PDF/Document</option>
                                </select>
                            </div>
                            <div class="col-12" id="lien_section" style="display: none;">
                                <label class="form-label">Lien de l'offre</label>
                                <input type="url" class="form-control" name="lien_offre" placeholder="https://...">
                            </div>
                            <div class="col-12" id="texte_section" style="display: none;">
                                <label class="form-label">Description de l'offre</label>
                                <textarea class="form-control" name="contenu" rows="4" placeholder="Copiez ici le contenu de l'offre d'emploi..."></textarea>
                            </div>
                            <div class="col-12" id="fichier_section" style="display: none;">
                                <label class="form-label">Fichier de l'offre</label>
                                <input type="file" class="form-control" name="fichier_offre" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter la candidature</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modifier candidature -->
    <div class="modal fade" id="editCandidatureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la Candidature</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_candidature">
                        <input type="hidden" name="candidature_id" id="edit_candidature_id">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Entreprise *</label>
                                <input type="text" class="form-control" name="entreprise" id="edit_entreprise" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Poste *</label>
                                <input type="text" class="form-control" name="poste" id="edit_poste" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="statut" id="edit_statut">
                                    <option value="en_attente">En attente</option>
                                    <option value="accepte">Accepté</option>
                                    <option value="refuse">Refusé</option>
                                    <option value="entretien">Entretien</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type de candidature *</label>
                                <select class="form-select" name="type_candidature" id="edit_type_candidature" required>
                                    <option value="lien">Lien vers l'offre</option>
                                    <option value="texte">Description textuelle</option>
                                    <option value="fichier">Fichier PDF/Document</option>
                                </select>
                            </div>
                            <div class="col-12" id="edit_lien_section">
                                <label class="form-label">Lien de l'offre</label>
                                <input type="url" class="form-control" name="lien_offre" id="edit_lien_offre" placeholder="https://...">
                            </div>
                            <div class="col-12" id="edit_texte_section">
                                <label class="form-label">Description de l'offre</label>
                                <textarea class="form-control" name="contenu" id="edit_contenu" rows="4"></textarea>
                            </div>
                            <div class="col-12" id="edit_fichier_section">
                                <label class="form-label">Nouveau fichier (optionnel)</label>
                                <input type="file" class="form-control" name="fichier_offre" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                <small class="text-muted">Laissez vide pour conserver le fichier actuel</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du formulaire de candidature
        document.querySelector('select[name="type_candidature"]').addEventListener('change', function() {
            const sections = ['lien_section', 'texte_section', 'fichier_section'];
            sections.forEach(id => document.getElementById(id).style.display = 'none');
            
            if (this.value) {
                document.getElementById(this.value + '_section').style.display = 'block';
            }
        });

        // Fonction utilitaire pour nettoyer les modals existants (évite les doublons et conflits)
        function removeModalIfExists(modalId) {
            // Retirer le focus de l'élément actif pour éviter les erreurs aria-hidden
            if (document.activeElement) {
                document.activeElement.blur();
            }

            const existingModal = document.getElementById(modalId);
            if (existingModal) {
                const instance = bootstrap.Modal.getInstance(existingModal);
                if (instance) {
                    instance.dispose();
                }
                existingModal.remove();
                
                // Nettoyer le backdrop qui peut parfois rester
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }

        // Variables globales pour stocker les données d'analyse - RESET à chaque analyse
        let currentCandidatureId = null;
        let currentJobTitle = '';
        let currentCompany = '';
        
        // Fonction d'analyse IA avec loading
        function analyzeWithAI(candidatureId) {
            // RESET des variables pour éviter la confusion
            currentCandidatureId = candidatureId;
            currentJobTitle = '';
            currentCompany = '';
            
            // Récupérer les infos de la candidature
            const candidatures = <?= json_encode($candidatures) ?>;
            const candidature = candidatures.find(c => c.id == candidatureId);
            if (candidature) {
                currentJobTitle = candidature.poste;
                currentCompany = candidature.entreprise;
            }
            
            console.log('Analyse candidature:', candidatureId, currentJobTitle, currentCompany);
            
            // Afficher le loading
            showLoadingModal('Analyse en cours avec Mistral IA...');
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=analyze_cv&candidature_id=${candidatureId}&timestamp=${Date.now()}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Réponse serveur brute (non-JSON):', text);
                        throw new Error('Réponse serveur invalide ou vide. Vérifiez les logs PHP.');
                    }
                });
            })
            .then(data => {
                hideLoadingModal();
                if (data.success) {
                    showAnalysisResult(data.analysis);
                } else {
                    showErrorModal('Erreur d\'analyse', data.error || 'Le service Mistral est temporairement indisponible. Réessayez plus tard.');
                }
            })
            .catch(error => {
                hideLoadingModal();
                console.error('Erreur:', error);
                showErrorModal('Erreur de communication', 'Problème de connexion. Vérifiez votre connexion internet.');
            });
        }

        // Fonction de génération de lettre avec loading
        function generateCoverLetter(candidatureId, jobTitle, company) {
            showLoadingModal('Génération de la lettre avec Mistral IA...');
            
            const styles = ['professional', 'moderne', 'créatif'];
            const randomStyle = styles[Math.floor(Math.random() * styles.length)];
            const templateId = Math.floor(Math.random() * 2) + 1; // 1 ou 2
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=generate_letter&template_id=${templateId}&candidature_id=${candidatureId}&style=${randomStyle}&timestamp=${Date.now()}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Réponse non-JSON:', text);
                        throw new Error('Réponse serveur invalide');
                    }
                });
            })
            .then(data => {
                hideLoadingModal();
                if (data.success) {
                    showLetterPreview(data.html, data.template_id);
                } else {
                    showErrorModal('Erreur de génération', data.error || 'Le service Mistral est temporairement indisponible.');
                }
            })
            .catch(error => {
                hideLoadingModal();
                console.error('Erreur:', error);
                showErrorModal('Erreur de communication', 'Problème de connexion.');
            });
        }
        
        function generateCV(candidatureId) {
            showLoadingModal('Génération du CV optimisé avec Mistral IA...');
            
            // Sélection aléatoire parmi 3 templates
            const templateId = Math.floor(Math.random() * 3) + 1;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=generate_cv&template_id=${templateId}&candidature_id=${candidatureId}&timestamp=${Date.now()}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Réponse non-JSON:', text);
                        throw new Error('Réponse serveur invalide');
                    }
                });
            })
            .then(data => {
                hideLoadingModal();
                if (data.success) {
                    showCVPreview(data.html, data.template_id);
                } else {
                    showErrorModal('Erreur de génération', data.error || 'Le service Mistral est temporairement indisponible.');
                }
            })
            .catch(error => {
                hideLoadingModal();
                console.error('Erreur:', error);
                showErrorModal('Erreur de communication', 'Problème de connexion.');
            });
        }
        
        // Modals de loading et erreur
        function showLoadingModal(message) {
            removeModalIfExists('loadingModal');
            
            const modal = `
                <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-4">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <h5>${message}</h5>
                                <p class="text-muted mb-0">Cela peut prendre quelques secondes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalInstance = new bootstrap.Modal(document.getElementById('loadingModal'), {
                backdrop: 'static',
                keyboard: false
            });
            modalInstance.show();
        }
        
        function hideLoadingModal() {
            const modal = document.getElementById('loadingModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                setTimeout(() => modal.remove(), 300);
            }
        }
        
        function showErrorModal(title, message) {
            removeModalIfExists('errorModal');
            
            const modal = `
                <div class="modal fade" id="errorModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">⚠️ ${title}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${message}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalInstance = new bootstrap.Modal(document.getElementById('errorModal'));
            modalInstance.show();
            
            // Auto-suppression après fermeture
            document.getElementById('errorModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }
        
        function showAnalysisResult(analysis) {
            removeModalIfExists('analysisModal');
            
            const modal = `
                <div class="modal fade" id="analysisModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">🤖 Analyse Critique par Mistral IA</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <div class="score-circle ${analysis.score >= 80 ? 'score-excellent' : (analysis.score >= 50 ? 'score-good' : (analysis.score >= 30 ? 'score-average' : 'score-poor'))}">
                                            ${analysis.score}%
                                        </div>
                                        <p class="mt-2 fw-bold">Score de compatibilité</p>
                                        <span class="badge ${analysis.global_fit === 'excellent' ? 'bg-success' : (analysis.global_fit === 'bon' ? 'bg-primary' : (analysis.global_fit === 'moyen' ? 'bg-warning' : 'bg-danger'))} fs-6">
                                            ${(analysis.global_fit || 'inconnu').toUpperCase()}
                                        </span>
                                        ${analysis.global_fit === 'tres_faible' ? '<div class="alert alert-danger mt-2 p-2"><small><strong>Votre CV ne correspond pas à cette offre.</strong><br>Il faudra le retravailler en profondeur.</small></div>' : ''}
                                        ${analysis.global_fit === 'faible' ? '<div class="alert alert-warning mt-2 p-2"><small><strong>Compatibilité faible.</strong><br>Des améliorations importantes sont nécessaires.</small></div>' : ''}
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row mb-3">
                                            <div class="col-4">
                                                <span class="badge ${(analysis.domain_match || 'inconnu') === 'bon' ? 'bg-success' : ((analysis.domain_match || 'inconnu') === 'moyen' ? 'bg-warning' : 'bg-danger')} w-100">Domaine: ${analysis.domain_match || 'inconnu'}</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="badge ${(analysis.experience_match || 'inconnu') === 'bon' ? 'bg-success' : ((analysis.experience_match || 'inconnu') === 'moyen' ? 'bg-warning' : 'bg-danger')} w-100">Expérience: ${analysis.experience_match || 'inconnu'}</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="badge ${(analysis.skills_match || 'inconnu') === 'bon' ? 'bg-success' : ((analysis.skills_match || 'inconnu') === 'moyen' ? 'bg-warning' : 'bg-danger')} w-100">Compétences: ${analysis.skills_match || 'inconnu'}</span>
                                            </div>
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">📊 Analyse Détaillée CV vs Offre</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="text-primary">📄 Votre CV indique :</h6>
                                                        <ul class="small">
                                                            <li>Profil : ${analysis.cv_summary || 'Profil professionnel'}</li>
                                                            <li>Expérience : ${analysis.cv_experience || 'Expérience variée'}</li>
                                                            <li>Compétences : ${analysis.cv_skills || 'Compétences techniques'}</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="text-info">🎯 L'offre recherche :</h6>
                                                        <ul class="small">
                                                            <li>Profil : ${analysis.job_requirements || 'Profil spécialisé'}</li>
                                                            <li>Expérience : ${analysis.job_experience || 'Expérience ciblée'}</li>
                                                            <li>Compétences : ${analysis.job_skills || 'Compétences spécifiques'}</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                
                                                <div class="alert ${(analysis.score || 0) >= 60 ? 'alert-success' : 'alert-warning'} mt-3">
                                                    <h6>🔍 Verdict de l'IA :</h6>
                                                    <ul class="mb-0">${(analysis.reasons || ['Analyse en cours']).map(r => `<li>${r}</li>`).join('')}</ul>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        ${(analysis.critical_gaps && analysis.critical_gaps.length > 0) ? `
                                        <div class="alert alert-danger">
                                            <h6>⚠️ Lacunes Critiques :</h6>
                                            <ul class="mb-0">${analysis.critical_gaps.map(g => `<li class="text-danger fw-bold">${g}</li>`).join('')}</ul>
                                        </div>
                                        ` : ''}
                                        
                                        ${(analysis.missing_keywords && analysis.missing_keywords.length > 0) ? `
                                        <h6 class="mt-3">🔑 Mots-clés Manquants :</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${analysis.missing_keywords.map(k => `<span class="badge bg-secondary">${k}</span>`).join('')}
                                        </div>
                                        ` : ''}
                                        
                                        ${(analysis.recommendations && analysis.recommendations.length > 0) ? `
                                        <div class="alert alert-info mt-3">
                                            <h6>💡 Recommandations :</h6>
                                            <ul class="mb-0">${analysis.recommendations.map(r => `<li>${r}</li>`).join('')}</ul>
                                        </div>
                                        ` : ''}
                                        
                                        ${<?= AI_DEBUG ? 'true' : 'false' ?> ? `
                                        <details class="mt-3">
                                            <summary class="text-muted">Debug IA (mode développement)</summary>
                                            <div class="small mt-2">
                                                <p><strong>Longueur CV:</strong> ${analysis.debug_cv_length || 'N/A'} caractères</p>
                                                <p><strong>Longueur Offre:</strong> ${analysis.debug_job_length || 'N/A'} caractères</p>
                                                <p><strong>Extrait CV:</strong> ${analysis.debug_cv_snippet || 'N/A'}</p>
                                                <p><strong>Extrait Offre:</strong> ${analysis.debug_job_snippet || 'N/A'}</p>
                                            </div>
                                        </details>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                ${(analysis.score || 0) >= 40 ? '<button type="button" class="btn btn-success" onclick="generateOptimizedCVFromAnalysis()"><i class="bi bi-magic me-2"></i>Générer CV Optimisé</button>' : '<button type="button" class="btn btn-warning" onclick="alert(\'Score trop faible pour optimisation automatique. Consultez les recommandations.\')">Score trop faible</button>'}
                                <button type="button" class="btn btn-primary" onclick="generateCoverLetterFromAnalysis()"><i class="bi bi-envelope me-2"></i>Générer Lettre</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalInstance = new bootstrap.Modal(document.getElementById('analysisModal'));
            modalInstance.show();
        }
        
        function showLetterPreview(html, templateId) {
            removeModalIfExists('letterPreviewModal');
            
            const modal = `
                <div class="modal fade" id="letterPreviewModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">📝 Lettre de motivation générée</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="letter-preview" style="max-height: 500px; overflow-y: auto;">
                                    ${html}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="button" class="btn btn-primary" onclick="printContent()">Imprimer</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalInstance = new bootstrap.Modal(document.getElementById('letterPreviewModal'));
            modalInstance.show();
        }
        
        function showCVPreview(html, templateId) {
            removeModalIfExists('cvPreviewModal');
            
            const modal = `
                <div class="modal fade" id="cvPreviewModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">📄 CV optimisé - Template ${templateId}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="cv-preview" style="max-height: 600px; overflow-y: auto;">
                                    ${html}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="button" class="btn btn-primary" onclick="printContent()">Imprimer</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalInstance = new bootstrap.Modal(document.getElementById('cvPreviewModal'));
            modalInstance.show();
        }
        
        function printContent() {
            const content = document.querySelector('.letter-preview, .cv-preview').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head><title>Document</title></head>
                    <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Variables globales pour stocker les données d'analyse - RESET à chaque analyse
        
        function generateOptimizedCVFromAnalysis() {
            if (currentCandidatureId) {
                generateCV(currentCandidatureId);
            }
        }
        
        function generateCoverLetterFromAnalysis() {
            if (currentCandidatureId && currentJobTitle && currentCompany) {
                generateCoverLetter(currentCandidatureId, currentJobTitle, currentCompany);
            }
        }
        
        // CRUD Functions
        function editCandidature(id) {
            // Récupérer les données de la candidature
            const candidatures = <?= json_encode($candidatures) ?>;
            const candidature = candidatures.find(c => c.id == id);
            
            if (candidature) {
                document.getElementById('edit_candidature_id').value = candidature.id;
                document.getElementById('edit_entreprise').value = candidature.entreprise;
                document.getElementById('edit_poste').value = candidature.poste;
                document.getElementById('edit_statut').value = candidature.statut;
                document.getElementById('edit_type_candidature').value = candidature.type_candidature;
                document.getElementById('edit_lien_offre').value = candidature.lien_offre || '';
                document.getElementById('edit_contenu').value = candidature.contenu || '';
                
                // Afficher les sections appropriées
                toggleEditSections(candidature.type_candidature);
                
                const modal = new bootstrap.Modal(document.getElementById('editCandidatureModal'));
                modal.show();
            }
        }
        
        function toggleEditSections(type) {
            const sections = ['edit_lien_section', 'edit_texte_section', 'edit_fichier_section'];
            sections.forEach(id => {
                const element = document.getElementById(id);
                if (element) element.style.display = 'none';
            });
            
            if (type && document.getElementById('edit_' + type + '_section')) {
                document.getElementById('edit_' + type + '_section').style.display = 'block';
            }
        }
        
        function deleteCandidature(id) {
            if (confirm('Voulez-vous vraiment supprimer cette candidature ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_candidature">
                    <input type="hidden" name="candidature_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Gestion du changement de type dans le modal d'édition
        document.getElementById('edit_type_candidature').addEventListener('change', function() {
            toggleEditSections(this.value);
        });
    </script>
</body>
</html>
