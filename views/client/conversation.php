<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_client();

$interlocuteurId = $_GET['id'] ?? null;
if (!$interlocuteurId) {
    header('Location: messages.php');
    exit;
}

$messageModel = new Message();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT prenom, nom, photo_profil FROM utilisateurs WHERE id = ?");
$stmt->execute([$interlocuteurId]);
$interlocuteur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$interlocuteur) {
    header('Location: messages.php');
    exit;
}

$messages = $messageModel->getConversation($_SESSION['user_id'], $interlocuteurId);
$messageModel->markAsRead($_SESSION['user_id'], $interlocuteurId);
$page_title = "Conversation avec {$interlocuteur['prenom']} - LULU-OPEN";
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
        .messages-container { height: 500px; overflow-y: auto; background: #f8f9fa; padding: 1rem; }
        .message-bubble.own .message-content { background: linear-gradient(135deg, #0099FF, #00ccff); color: white; }
        .message-bubble.other .message-content { background: white; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <main class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-custom">
                    <div class="card-header-custom d-flex align-items-center">
                        <a href="messages.php" class="btn btn-sm btn-light me-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <?php 
                        $photoPath = $interlocuteur['photo_profil'] ? '/lulu/uploads/profiles/' . basename($interlocuteur['photo_profil']) : '';
                        if ($interlocuteur['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                        ?>
                            <img src="<?= $photoPath ?>" 
                                 alt="Avatar" class="rounded-circle me-3"
                                 style="width: 40px; height: 40px; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="rounded-circle me-3 bg-white text-primary d-none align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; font-weight: bold;">
                                <?= strtoupper(mb_substr($interlocuteur['prenom'], 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="rounded-circle me-3 bg-white text-primary d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; font-weight: bold;">
                                <?= strtoupper(mb_substr($interlocuteur['prenom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($interlocuteur['prenom'] . ' ' . $interlocuteur['nom'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <small class="text-white-50">
                                <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i> En ligne
                            </small>
                        </div>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                                <p class="mt-3">Démarrez la conversation !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($messages) as $msg): ?>
                                <?php $isOwn = $msg['expediteur_id'] == $_SESSION['user_id']; ?>
                                <div class="message-bubble <?= $isOwn ? 'own' : 'other' ?> mb-3">
                                    <div class="d-flex <?= $isOwn ? 'justify-content-end' : 'justify-content-start' ?>">
                                        <?php if (!$isOwn): 
                                            $msgPhotoPath = $msg['photo_profil'] ? '/lulu/uploads/profiles/' . basename($msg['photo_profil']) : '';
                                            if ($msg['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $msgPhotoPath)):
                                        ?>
                                            <img src="<?= $msgPhotoPath ?>" 
                                                 class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;"
                                                 onerror="this.style.display='none';">
                                        <?php else: ?>
                                            <div class="rounded-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                <?= strtoupper(mb_substr($msg['prenom'], 0, 1)) ?>
                                            </div>
                                        <?php endif; endif; ?>
                                        
                                        <div class="message-content p-3 rounded-3" style="max-width: 70%;">
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($msg['contenu'], ENT_QUOTES, 'UTF-8')) ?></p>
                                            <small class="<?= $isOwn ? 'text-white-50' : 'text-muted' ?>">
                                                <?= time_ago($msg['date_envoi']) ?>
                                                <?php if ($isOwn): ?>
                                                    <i class="bi bi-check2-all <?= $msg['lu'] ? 'text-info' : '' ?>"></i>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($isOwn): 
                                            $ownPhotoPath = $_SESSION['photo_profil'] ? '/lulu/uploads/profiles/' . basename($_SESSION['photo_profil']) : '';
                                            if ($_SESSION['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $ownPhotoPath)):
                                        ?>
                                            <img src="<?= $ownPhotoPath ?>" 
                                                 class="rounded-circle ms-2" style="width: 30px; height: 30px; object-fit: cover;"
                                                 onerror="this.style.display='none';">
                                        <?php else: ?>
                                            <div class="rounded-circle ms-2 bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                <?= strtoupper(mb_substr($_SESSION['prenom'], 0, 1)) ?>
                                            </div>
                                        <?php endif; endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="border-top p-3">
                        <form id="sendMessageForm" class="d-flex align-items-end gap-2">
                            <input type="hidden" name="destinataire_id" value="<?= $interlocuteurId ?>">
                            <div class="flex-grow-1">
                                <textarea class="form-control" id="messageInput" name="message" rows="2"
                                          placeholder="Écrivez votre message..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const container = document.getElementById('messagesContainer');
        if (container) container.scrollTop = container.scrollHeight;
        
        document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) {
                alert('Veuillez saisir un message');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('sujet', 'Message');
            
            try {
                const response = await fetch('/lulu/api/send-message.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Erreur serveur');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Erreur inconnue'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du message');
            }
        });
    </script>
</body>
</html>
