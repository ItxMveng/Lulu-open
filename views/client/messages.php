<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_client();

$messageModel = new Message();
$conversations = $messageModel->getConversations($_SESSION['user_id']);
$page_title = "Mes Messages - LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-dark: #000033; --primary-blue: #0099FF; }
        body { background: #f8f9fa; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 1rem; border-radius: 15px 15px 0 0; }
        .conversation-item { display: block; text-decoration: none; color: inherit; transition: all 0.2s; border-bottom: 1px solid #dee2e6; }
        .conversation-item:hover { background: #f8f9fa; }
        .conversation-item.unread { background: rgba(0, 153, 255, 0.05); border-left: 4px solid var(--primary-blue); }
        .conversation-list { max-height: 600px; overflow-y: auto; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Messages</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <div class="row">
            <div class="col-lg-4">
                <div class="card-custom">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Conversations</h5>
                        <span class="badge bg-light text-dark"><?= count($conversations) ?></span>
                    </div>
                    
                    <div class="p-3 border-bottom">
                        <input type="text" class="form-control" placeholder="Rechercher..." id="searchConversation">
                    </div>
                    
                    <div class="conversation-list">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center p-4 text-muted">
                                <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                                <p class="mt-2">Aucune conversation</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                            <a href="conversation.php?id=<?= $conv['interlocuteur_id'] ?>"
                               class="conversation-item <?= ($conv['non_lus'] ?? 0) > 0 ? 'unread' : '' ?>">
                                <div class="d-flex align-items-center p-3">
                                    <div class="position-relative me-3">
                                        <?php 
                                        $photoPath = $conv['photo_profil'] ? '/lulu/uploads/profiles/' . basename($conv['photo_profil']) : '';
                                        if ($conv['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                                        ?>
                                            <img src="<?= $photoPath ?>" 
                                                 alt="Avatar" class="rounded-circle"
                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="rounded-circle bg-primary text-white d-none align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px; font-weight: bold;">
                                                <?= strtoupper(mb_substr($conv['prenom'], 0, 1)) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px; font-weight: bold;">
                                                <?= strtoupper(mb_substr($conv['prenom'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($conv['non_lus'] ?? 0) > 0): ?>
                                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                                                <?= $conv['non_lus'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom'], ENT_QUOTES, 'UTF-8') ?></h6>
                                            <small class="text-muted"><?= time_ago($conv['derniere_date']) ?></small>
                                        </div>
                                        <p class="mb-0 text-muted small text-truncate">Dernière conversation</p>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card-custom p-5 text-center" style="min-height: 500px; display: flex; align-items: center; justify-content: center;">
                    <div>
                        <i class="bi bi-chat-square-text" style="font-size: 5rem; color: var(--primary-blue); opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Sélectionnez une conversation</h4>
                        <p class="text-muted">Choisissez une conversation pour commencer à discuter</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
