<?php
session_start();
require_once '../../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /lulu/login.php');
    exit;
}

$autoLoadContact = isset($_GET['contact']) ? (int)$_GET['contact'] : null;

global $database;
$userId = $_SESSION['user_id'];

$sql = "SELECT DISTINCT 
            CASE 
                WHEN m.expediteur_id = ? THEN m.destinataire_id 
                ELSE m.expediteur_id 
            END as contact_id,
            u.nom, u.prenom, u.photo_profil,
            (SELECT contenu FROM messages WHERE 
                (expediteur_id = ? AND destinataire_id = contact_id) OR 
                (expediteur_id = contact_id AND destinataire_id = ?)
                ORDER BY id DESC LIMIT 1) as last_message,
            (SELECT id FROM messages WHERE 
                (expediteur_id = ? AND destinataire_id = contact_id) OR 
                (expediteur_id = contact_id AND destinataire_id = ?)
                ORDER BY id DESC LIMIT 1) as last_message_id,
            (SELECT COUNT(*) FROM messages WHERE 
                expediteur_id = contact_id AND destinataire_id = ? AND lu = 0) as unread_count
        FROM messages m
        JOIN utilisateurs u ON u.id = CASE 
            WHEN m.expediteur_id = ? THEN m.destinataire_id 
            ELSE m.expediteur_id 
        END
        WHERE m.expediteur_id = ? OR m.destinataire_id = ?
        ORDER BY last_message_id DESC";

$conversations = $database->fetchAll($sql, [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Messages - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="admin-page" style="font-family: 'Inter', sans-serif;">
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
            
            <a href="../../abonnement.php" class="nav-link">
                <i class="icon">💳</i> Abonnement
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="message-header mb-4">
            <a href="../dashboard.php" class="btn btn-light shadow-sm mb-3">
                <i class="bi bi-arrow-left"></i> Retour au dashboard
            </a>
            <h1 class="h2 fw-bold mb-2">💬 Mes Messages</h1>
            <p class="text-white mb-0">Conversations en temps réel avec les clients</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem;">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-chat-dots"></i> Conversations</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 650px; overflow-y: auto;">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item" onclick="loadConversation(<?= $conv['contact_id'] ?>, '<?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?>')" data-contact-id="<?= $conv['contact_id'] ?>">
                                    <div class="d-flex align-items-center p-3">
                                        <div class="avatar me-3">
                                            <?php if ($conv['photo_profil']): ?>
                                                <img src="/lulu/uploads/profiles/<?= $conv['photo_profil'] ?>" 
                                                     alt="Avatar" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                     style="width: 50px; height: 50px; font-size: 1.2rem;">
                                                    <?= strtoupper(substr($conv['prenom'], 0, 1) . substr($conv['nom'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?></h6>
                                            <p class="mb-0 small text-muted text-truncate"><?= htmlspecialchars(substr($conv['last_message'] ?? 'Aucun message', 0, 40)) ?></p>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-3" style="font-size: 3rem;">💬</div>
                                <p class="text-muted mb-0">Aucune conversation</p>
                                <small class="text-muted">Les messages clients apparaîtront ici</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card shadow-sm border-0" style="height: 700px; display: flex; flex-direction: column; border-radius: 15px; overflow: hidden;">
                    <div class="card-header" id="chatHeader" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem;">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-person-circle"></i> Sélectionnez une conversation</h6>
                    </div>
                    <div class="card-body flex-grow-1 p-4" id="messagesContainer" style="overflow-y: auto; background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center text-muted">
                                <div class="mb-3" style="font-size: 4rem;">📨</div>
                                <p class="fs-5">Choisissez une conversation pour voir les messages</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-none p-3" id="messageForm" style="border-top: 2px solid #e9ecef;">
                        <form onsubmit="sendMessage(event)" class="d-flex gap-2">
                            <input type="hidden" id="contactId">
                            <input type="text" id="messageInput" class="form-control form-control-lg" placeholder="💬 Écrivez votre message..." required style="border-radius: 25px; border: 2px solid #e9ecef;">
                            <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 25px; padding: 0.5rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
let currentContactId = null;
let messageCheckInterval = null;
let lastMessageId = 0;
const autoLoadContactId = <?= $autoLoadContact ? $autoLoadContact : 'null' ?>;

function loadConversation(contactId, contactName) {
    currentContactId = contactId;
    lastMessageId = 0;
    document.getElementById('contactId').value = contactId;
    document.getElementById('chatHeader').innerHTML = `<h6 class="mb-0 fw-bold"><i class="bi bi-person-circle"></i> ${contactName}</h6>`;
    document.getElementById('chatHeader').style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    document.getElementById('chatHeader').style.color = 'white';
    document.getElementById('messageForm').classList.remove('d-none');
    
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeItem = document.querySelector(`[data-contact-id="${contactId}"]`);
    if (activeItem) activeItem.classList.add('active');
    
    fetchMessages(contactId, true);
    
    if (messageCheckInterval) clearInterval(messageCheckInterval);
    messageCheckInterval = setInterval(() => fetchMessages(contactId, false), 2000);
}

async function fetchMessages(contactId, scrollToBottom = false) {
    try {
        const response = await fetch(`/lulu/api/get-conversation.php?contact_id=${contactId}`);
        const data = await response.json();
        
        const container = document.getElementById('messagesContainer');
        const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
        
        if (data.success && data.messages) {
            const newMessages = data.messages.filter(msg => msg.id > lastMessageId);
            
            if (newMessages.length > 0 || scrollToBottom) {
                container.innerHTML = data.messages.map(msg => `
                    <div style="clear: both; margin-bottom: 0.5rem;">
                        <div class="message-bubble ${msg.is_sent ? 'message-sent' : 'message-received'}">
                            <div>${escapeHtml(msg.contenu)}</div>
                            <small class="message-time">${formatTime(msg.created_at)}</small>
                        </div>
                    </div>
                `).join('');
                
                if (data.messages.length > 0) {
                    lastMessageId = Math.max(...data.messages.map(m => m.id));
                }
                
                if (scrollToBottom || wasAtBottom) {
                    setTimeout(() => {
                        container.scrollTop = container.scrollHeight;
                    }, 100);
                }
            }
        }
    } catch (error) {
        console.error('Erreur chargement messages:', error);
    }
}

async function sendMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    const contactId = document.getElementById('contactId').value;
    
    if (!message || !contactId) return;
    
    input.disabled = true;
    
    try {
        const response = await fetch('/lulu/api/send-message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                destinataire_id: contactId,
                contenu: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            await fetchMessages(contactId, true);
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'envoyer le message'));
        }
    } catch (error) {
        console.error('Erreur envoi message:', error);
        alert('Erreur lors de l\'envoi du message');
    } finally {
        input.disabled = false;
        input.focus();
    }
}

function formatTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / 3600000);
    
    if (hours < 1) return 'À l\'instant';
    if (hours < 24) return `Il y a ${hours}h`;
    return date.toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'});
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.addEventListener('beforeunload', () => {
    if (messageCheckInterval) clearInterval(messageCheckInterval);
});

if (autoLoadContactId) {
    fetch(`/lulu/api/get-user-info.php?id=${autoLoadContactId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadConversation(autoLoadContactId, `${data.user.prenom} ${data.user.nom}`);
            }
        })
        .catch(err => console.error('Erreur chargement contact:', err));
}
    </script>

    <style>
        .message-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .message-header h1 {
            color: white;
        }

        .conversation-item {
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 1rem;
        }

        .conversation-item:hover {
            background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%);
            transform: translateX(8px);
        }

        .conversation-item.active {
            background: linear-gradient(135deg, #e7f3ff 0%, #d4e9ff 100%);
            border-left: 5px solid #667eea;
            box-shadow: inset 0 0 10px rgba(102, 126, 234, 0.1);
        }

        .conversation-item:last-child {
            border-bottom: none;
        }

        .message-bubble {
            max-width: 65%;
            padding: 1rem 1.25rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            word-wrap: break-word;
            animation: slideIn 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            float: right;
            clear: both;
        }

        .message-received {
            background: white;
            color: #2d3748;
            margin-right: auto;
            border-bottom-left-radius: 5px;
            border: 1px solid #e9ecef;
            float: left;
            clear: both;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-top: 0.5rem;
            display: block;
        }

        .message-sent .message-time {
            color: rgba(255,255,255,0.9);
        }

        .message-received .message-time {
            color: #718096;
        }

        #messagesContainer::-webkit-scrollbar {
            width: 10px;
        }

        #messagesContainer::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #messagesContainer::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        #messagesContainer::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

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
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%) !important;
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
            color: white;
            text-decoration: none;
            transition: var(--transition);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .sidebar-nav .icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .admin-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: var(--light-gray);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .card-header h5 {
            margin-bottom: 0;
            color: var(--primary-dark);
        }

        .profile-photo-container {
            position: relative;
            display: inline-block;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
        }

        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--light-gray);
            border: 2px dashed var(--medium-gray);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--medium-gray);
        }

        .profile-photo-placeholder .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .portfolio-item {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .portfolio-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .portfolio-link-preview {
            width: 80px;
            height: 80px;
            background: var(--light-gray);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--medium-gray);
        }

        .portfolio-link-preview .icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
    </style>
</body>
</html>