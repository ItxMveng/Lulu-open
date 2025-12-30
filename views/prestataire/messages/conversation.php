<?php
require_once '../../../config/config.php';
requireLogin();

global $database;
$userId = $_SESSION['user_id'];
$messageId = $_GET['id'] ?? null;

if (!$messageId) {
    redirect('inbox.php');
}

// Récupérer le message et les informations du contact
$message = $database->fetch("
    SELECT m.*, u.nom, u.prenom, u.email, u.type_utilisateur, u.photo_profil
    FROM messages m
    JOIN utilisateurs u ON (
        CASE 
            WHEN m.expediteur_id = ? THEN m.destinataire_id = u.id
            ELSE m.expediteur_id = u.id
        END
    )
    WHERE m.id = ? AND (m.expediteur_id = ? OR m.destinataire_id = ?)
", [$userId, $messageId, $userId, $userId]);

if (!$message) {
    redirect('inbox.php');
}

// Déterminer le contact (l'autre personne dans la conversation)
$contactId = $message['expediteur_id'] == $userId ? $message['destinataire_id'] : $message['expediteur_id'];
$contact = $database->fetch(
    "SELECT id, nom, prenom, email, type_utilisateur, photo_profil FROM utilisateurs WHERE id = ?",
    [$contactId]
);

// Récupérer tous les messages entre ces deux utilisateurs
$messages = $database->fetchAll("
    SELECT m.*, 
           exp.nom as expediteur_nom, exp.prenom as expediteur_prenom, exp.photo_profil as expediteur_photo
    FROM messages m
    JOIN utilisateurs exp ON exp.id = m.expediteur_id
    WHERE (m.expediteur_id = ? AND m.destinataire_id = ?) 
       OR (m.expediteur_id = ? AND m.destinataire_id = ?)
    ORDER BY m.date_envoi ASC
", [$userId, $contactId, $contactId, $userId]);

// Marquer les messages comme lus
$database->query(
    "UPDATE messages SET lu = 1 WHERE destinataire_id = ? AND expediteur_id = ?",
    [$userId, $contactId]
);

// Générer un token CSRF
$csrf_token = generateCSRFToken();
$title = 'Conversation avec ' . $contact['prenom'] . ' ' . $contact['nom'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Conversation' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-link">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="../profile/edit.php" class="nav-link">
                <i class="icon">✏️</i> Mon Profil
            </a>
            <a href="inbox.php" class="nav-link active">
                <i class="icon">💬</i> Messages
            </a>
            <a href="../subscription/checkout.php" class="nav-link">
                <i class="icon">💳</i> Abonnement
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="conversation-container">
            <!-- Conversation Header -->
            <div class="conversation-header">
                <div class="contact-info">
                    <div class="contact-avatar">
                        <?php if ($contact['photo_profil']): ?>
                            <img src="../../uploads/<?= $contact['photo_profil'] ?>" alt="Photo">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($contact['prenom'], 0, 1) . substr($contact['nom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="contact-details">
                        <h5><?= htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']) ?></h5>
                        <span class="contact-type badge bg-<?= $contact['type_utilisateur'] === 'prestataire' ? 'primary' : 'success' ?>">
                            <?= ucfirst($contact['type_utilisateur']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="conversation-actions">
                    <a href="/lulu/profile/view.php?id=<?= $contact['id'] ?>" class="btn btn-outline-primary btn-sm">
                        Voir le profil
                    </a>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <div class="messages-list" id="messagesList">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages-yet">
                            <p class="text-muted text-center">Aucun message dans cette conversation</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?= $message['expediteur_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>" 
                                 data-message-id="<?= $message['id'] ?>">
                                
                                <div class="message-avatar">
                                    <?php 
                                    $avatar = $message['expediteur_id'] == $_SESSION['user_id'] ? 
                                             ($_SESSION['user_photo'] ?? null) : 
                                             $message['expediteur_photo'];
                                    ?>
                                    <?php if ($avatar): ?>
                                        <img src="../../uploads/<?= $avatar ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php 
                                            $name = $message['expediteur_id'] == $_SESSION['user_id'] ? 
                                                   $_SESSION['user_name'] : 
                                                   $message['expediteur_prenom'] . ' ' . $message['expediteur_nom'];
                                            echo strtoupper(substr($name, 0, 2));
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-author">
                                            <?= $message['expediteur_id'] == $_SESSION['user_id'] ? 'Vous' : 
                                                htmlspecialchars($message['expediteur_prenom'] . ' ' . $message['expediteur_nom']) ?>
                                        </span>
                                        <span class="message-time">
                                            <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($message['sujet']): ?>
                                        <div class="message-subject">
                                            <strong><?= htmlspecialchars($message['sujet']) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-text">
                                        <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Message Form -->
            <div class="message-form-container">
                <form id="messageForm" class="message-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="destinataire_id" value="<?= $contact['id'] ?>">
                    
                    <div class="form-group mb-3">
                        <input type="text" class="form-control" name="sujet" placeholder="Sujet du message..." required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <textarea class="form-control" name="contenu" rows="3" 
                                  placeholder="Tapez votre message..." required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="sendBtn">
                            <span class="btn-text">Envoyer</span>
                            <span class="btn-loading" style="display: none;">
                                <div class="loading-spinner"></div>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        let pollingInterval;

        // Send message
        document.getElementById('messageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendBtn = document.getElementById('sendBtn');
            const btnText = sendBtn.querySelector('.btn-text');
            const btnLoading = sendBtn.querySelector('.btn-loading');
            
            // Show loading
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            sendBtn.disabled = true;
            
            try {
                const response = await fetch('/lulu/api/send-message.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear form
                    this.reset();
                    
                    // Refresh messages
                    await loadNewMessages();
                    
                    // Scroll to bottom
                    scrollToBottom();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion: ' + error.message);
            } finally {
                // Hide loading
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                sendBtn.disabled = false;
            }
        });

        // Load new messages
        async function loadNewMessages() {
            try {
                const response = await fetch(`/lulu/api/get-conversation.php?contact_id=<?= $contact['id'] ?>&last_message_id=${lastMessageId}`);
                const result = await response.json();
                
                if (result.success && result.messages.length > 0) {
                    const messagesList = document.getElementById('messagesList');
                    
                    result.messages.forEach(message => {
                        const messageElement = createMessageElement(message);
                        messagesList.appendChild(messageElement);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                    
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Erreur chargement messages:', error);
            }
        }

        // Create message element
        function createMessageElement(message) {
            const div = document.createElement('div');
            const isSent = message.expediteur_id == <?= $_SESSION['user_id'] ?>;
            
            div.className = `message-item ${isSent ? 'sent' : 'received'}`;
            div.dataset.messageId = message.id;
            
            div.innerHTML = `
                <div class="message-avatar">
                    <div class="avatar-placeholder">
                        ${isSent ? '<?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>' : 
                                  message.expediteur_prenom.charAt(0) + message.expediteur_nom.charAt(0)}
                    </div>
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author">
                            ${isSent ? 'Vous' : message.expediteur_prenom + ' ' + message.expediteur_nom}
                        </span>
                        <span class="message-time">
                            ${new Date(message.date_envoi).toLocaleString('fr-FR')}
                        </span>
                    </div>
                    ${message.sujet ? `<div class="message-subject"><strong>${message.sujet}</strong></div>` : ''}
                    <div class="message-text">
                        ${message.contenu.replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
            
            return div;
        }

        // Scroll to bottom
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }

        // Start polling for new messages
        function startPolling() {
            pollingInterval = setInterval(loadNewMessages, 5000); // Poll every 5 seconds
        }

        // Stop polling
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            startPolling();
        });

        // Stop polling when page is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopPolling();
            } else {
                startPolling();
            }
        });

        // Auto-resize textarea
        document.querySelector('textarea[name="contenu"]').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>

    <style>
        :root {
            --primary-color: #0099FF;
            --primary-dark: #000033;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --border-radius-lg: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 153, 255, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            --font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-page {
            background: #f8f9fa;
            font-family: var(--font-family);
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav .icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .conversation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }
        
        .conversation-header {
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light-gray);
        }
        
        .contact-info {
            display: flex;
            align-items: center;
        }
        
        .contact-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
        }
        
        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .contact-details h5 {
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }
        
        .messages-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .message-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .message-item.sent {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message-item.sent .message-content {
            background: var(--primary-color);
            color: white;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        
        .message-item.sent .message-header {
            color: rgba(255,255,255,0.8);
        }
        
        .message-subject {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .message-item.sent .message-subject {
            color: rgba(255,255,255,0.9);
        }
        
        .message-text {
            line-height: 1.5;
        }
        
        .message-form-container {
            padding: 1.5rem;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        
        .message-form .form-control {
            border: 2px solid #E9ECEF;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .message-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.25);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
        }
        
        .btn-loading {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .no-messages-yet {
            text-align: center;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .conversation-container {
                height: calc(100vh - 80px);
                margin: 0;
                border-radius: 0;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</body>
</html>