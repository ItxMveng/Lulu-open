<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

global $database;
$userId = $_SESSION['user_id'];

try {
    // Récupérer le profil prestataire
    $profile = $database->fetch("SELECT id, portfolio_images FROM profils_prestataires WHERE utilisateur_id = ?", [$userId]);

    if (!$profile) {
        echo json_encode(['error' => 'Profil prestataire non trouvé']);
        exit;
    }

    $portfolioItems = $profile['portfolio_images'] ? json_decode($profile['portfolio_images'], true) : [];
    if (!is_array($portfolioItems)) {
        $portfolioItems = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_POST['action'] ?? $input['action'] ?? '';

        if ($action === 'add_portfolio') {
            $type = $_POST['type'] ?? '';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';

            if (!$title) {
                echo json_encode(['success' => false, 'error' => 'Titre requis']);
                exit;
            }

            $newItem = [
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($type === 'image') {
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'error' => 'Image requise']);
                    exit;
                }
                $imagePath = uploadFile($_FILES['image'], 'portfolios');
                if (!$imagePath) {
                    echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement de l\'image']);
                    exit;
                }
                $newItem['url'] = $imagePath;
            } elseif ($type === 'link') {
                $url = $_POST['url'] ?? '';
                if (!$url) {
                    echo json_encode(['success' => false, 'error' => 'URL requise']);
                    exit;
                }
                $newItem['url'] = $url;
            } else {
                echo json_encode(['success' => false, 'error' => 'Type invalide']);
                exit;
            }

            $portfolioItems[] = $newItem;

            $database->update('profils_prestataires',
                ['portfolio_images' => json_encode($portfolioItems)],
                'utilisateur_id = ?',
                [$userId]
            );

            echo json_encode(['success' => true]);

        } elseif ($action === 'remove_portfolio') {
            $index = $input['index'] ?? -1;

            if ($index >= 0 && isset($portfolioItems[$index])) {
                // Supprimer le fichier si c'est une image
                if ($portfolioItems[$index]['type'] === 'image' && isset($portfolioItems[$index]['url'])) {
                    deleteFile($portfolioItems[$index]['url']);
                }

                array_splice($portfolioItems, $index, 1);

                $database->update('profils_prestataires',
                    ['portfolio_images' => json_encode($portfolioItems)],
                    'utilisateur_id = ?',
                    [$userId]
                );

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Index invalide']);
            }

        } else {
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        }

    } else {
        // GET request - return portfolio items
        echo json_encode(['items' => $portfolioItems]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
