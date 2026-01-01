<?php
require_once '../../../config/config.php';
require_once '../../../includes/middleware.php';
require_once '../../../models/Message.php';

require_login();

if (!in_array($_SESSION['user_type'], ['prestataire', 'prestataire_candidat'])) {
    header('Location: ../../../login.php');
    exit;
}

$messageModel = new Message();
$db = Database::getInstance()->getConnection();

$sql = "SELECT
            CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END AS interlocuteur_id,
            u.prenom, u.nom, u.photo_profil, u.type_utilisateur,
            m.date_envoi AS derniere_date,
            m.contenu AS dernier_message,
            m.sujet,
            SUM(CASE WHEN m.destinataire_id = ? AND m.lu = 0 THEN 1 ELSE 0 END) as non_lus
        FROM messages m
        INNER JOIN (
            SELECT
                CASE WHEN expediteur_id = ? THEN destinataire_id ELSE expediteur_id END AS contact_id,
                MAX(date_envoi) AS max_date
            FROM messages
            WHERE expediteur_id = ? OR destinataire_id = ?
            GROUP BY contact_id
        ) latest ON (m.expediteur_id = ? AND m.destinataire_id = latest.contact_id OR m.destinataire_id = ? AND m.expediteur_id = latest.contact_id) AND m.date_envoi = latest.max_date
        JOIN utilisateurs u ON u.id = CASE WHEN m.expediteur_id = ? THEN m.destinataire_id ELSE m.expediteur_id END
        GROUP BY interlocuteur_id
        ORDER BY derniere_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getPhotoUrl($photo) {
    if (!$photo) return url('assets/images/default-avatar.png');
    if (strpos($photo, 'http') === 0) return $photo;
    $photo = str_replace(['uploads/profiles/profiles/', 'profiles/profiles/'], 'uploads/profiles/', $photo);
    if (strpos($photo, 'uploads/') === 0) return url($photo);
    return url('uploads/profiles/' . $photo);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root { --primary: #0099FF; --dark: #000033; }
    body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
    .admin-sidebar { position: fixed; top: 0; left: 0; width: 250px; height: 100vh; background: linear-gradient(135deg, var(--dark), var(--primary)); color: white; padding: 2rem 0; z-index: 1000; }
    .sidebar-header { padding: 0 2rem; margin-bottom: 2rem; }
    .sidebar-nav .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 2rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
    .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
    .admin-content { margin-left: 250px; padding: 2rem; min-height: 100vh; }
    .message-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 15px; color: white; }
    .conversation-item { border-bottom: 1px solid #e9ecef; cursor: pointer; transition: all 0.3s; }
    .conversation-item:hover { background: #f8f9fa; transform: translateX(8px); }
    .conversation-item.active { background: #e7f3ff; border-left: 5px solid #667eea; }
    .message-bubble { max-width: 65%; padding: 1rem 1.25rem; border-radius: 20px; margin-bottom: 1rem; word-wrap: break-word; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .message-sent { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-left: auto; float: right; clear: both; }
    .message-received { background: white; color: #2d3748; margin-right: auto; float: left; clear: both; border: 1px solid #e9ecef; }
    .message-time { font-size: 0.7rem; opacity: 0.8; margin-top: 0.5rem; display: block; }
    .emoji-picker { position: absolute; bottom: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 10px; padding: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; display: none; max-width: 300px; }
    .emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 5px; }
    .emoji-btn { background: none; border: none; font-size: 1.2rem; padding: 5px; border-radius: 5px; cursor: pointer; transition: background 0.2s; }
    .emoji-btn:hover { background: #f0f0f0; }
    .file-preview { max-width: 200px; border-radius: 10px; margin-top: 5px; }
    .file-attachment { background: rgba(0,0,0,0.1); padding: 10px; border-radius: 10px; margin-top: 5px; display: inline-block; }
    .message-actions { opacity: 0; transition: opacity 0.2s; }
    .message-bubble:hover .message-actions { opacity: 1; }
    .bi-check2-all { color: #007bff !important; }
    .bi-check2 { color: #6c757d; }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-link"><i class="bi bi-grid"></i> Dashboard</a>
            <a href="../profile/edit.php" class="nav-link"><i class="bi bi-person"></i> Mon Profil</a>
            <a href="inbox.php" class="nav-link active"><i class="bi bi-chat-dots"></i> Messages</a>
            <a href="../../abonnement.php" class="nav-link"><i class="bi bi-credit-card"></i> Abonnement</a>
            <a href="../../../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </div>

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
                                <div class="conversation-item" onclick="openConversation(<?= $conv['interlocuteur_id'] ?>)" data-contact-id="<?= $conv['interlocuteur_id'] ?>">
                                    <div class="d-flex align-items-center p-3">
                                        <div class="avatar me-3">
                                            <img src="<?= getPhotoUrl($conv['photo_profil']) ?>" alt="Avatar" class="rounded-circle" width="50" height="50" style="object-fit: cover;" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>';">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?></h6>
                                            <p class="mb-0 small text-muted text-truncate"><?= htmlspecialchars(substr($conv['dernier_message'] ?? 'Aucun message', 0, 40)) ?></p>
                                        </div>
                                        <?php if ($conv['non_lus'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $conv['non_lus'] ?></span>
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
                        <div class="d-flex align-items-center justify-content-center h-100" id="no-conversation">
                            <div class="text-center text-muted">
                                <div class="mb-3" style="font-size: 4rem;">📨</div>
                                <p class="fs-5">Choisissez une conversation pour voir les messages</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-none p-3" id="messageForm" style="border-top: 2px solid #e9ecef;">
                        <form id="sendMessageForm" enctype="multipart/form-data" class="d-flex gap-2">
                            <div class="mb-2" id="file-preview" style="display: none;">
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                                    <i class="bi bi-paperclip"></i>
                                    <span id="file-name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                            <div class="input-group position-relative">
                                <input type="file" id="fileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx,.txt">
                                <input type="text" id="messageInput" class="form-control form-control-lg" placeholder="💬 Écrivez votre message..." required style="border-radius: 25px; border: 2px solid #e9ecef;">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('fileInput').click()"><i class="bi bi-paperclip"></i></button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleEmojiPicker()"><i class="bi bi-emoji-smile"></i></button>
                                <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 25px; padding: 0.5rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                                <div class="emoji-picker" id="emojiPicker">
                                    <div class="emoji-grid">
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😀')">😀</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😃')">😃</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😄')">😄</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😁')">😁</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😊')">😊</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😍')">😍</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('🤔')">🤔</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😢')">😢</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😭')">😭</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('😡')">😡</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('👍')">👍</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('👎')">👎</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('👌')">👌</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('✌️')">✌️</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('🤝')">🤝</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('🙏')">🙏</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('❤️')">❤️</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('💯')">💯</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('🔥')">🔥</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('⭐')">⭐</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('✅')">✅</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('❌')">❌</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('⚠️')">⚠️</button>
                                        <button type="button" class="emoji-btn" onclick="addEmoji('💡')">💡</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
let currentContactId = null;

function openConversation(contactId) {
    currentContactId = contactId;
    loadConversation(contactId);
}

async function loadConversation(contactId) {
    try {
        const response = await fetch(`<?= url('api/messages.php') ?>?action=get_conversation&user_id=${contactId}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        const data = await response.json();

        if (data.success) {
            displayConversation(data.conversation, data.user);
            const messageForm = document.getElementById('messageForm');
            const noConversation = document.getElementById('no-conversation');
            if (messageForm) messageForm.classList.remove('d-none');
            if (noConversation) noConversation.style.display = 'none';
        } else {
            throw new Error(data.message || 'Erreur inconnue');
        }
    } catch (error) {
        console.error('Erreur loadConversation:', error);
        alert('Erreur lors du chargement: ' + error.message);
    }
}

function displayConversation(messages, user) {
    const container = document.getElementById('messagesContainer');
    const header = document.getElementById('chatHeader');

    header.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-person-circle"></i> ${user.prenom} ${user.nom}</h6>
            <button class="btn btn-outline-light btn-sm" onclick="deleteConversation(${currentContactId})">
                <i class="bi bi-trash"></i> Supprimer
            </button>
        </div>
    `;

    let html = '';
    messages.forEach(message => {
        const isFromUser = message.expediteur_id == <?= $_SESSION['user_id'] ?>;
        const bubbleClass = isFromUser ? 'message-sent' : 'message-received';

        let messageContent = message.contenu;
        
        if (message.fichier_joint) {
            const fileName = message.fichier_joint.split('/').pop();
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName);
            
            if (isImage) {
                messageContent += `<br><img src="<?= url('') ?>${message.fichier_joint}" class="file-preview" alt="Image">`;
            } else {
                messageContent += `<br><div class="file-attachment"><i class="bi bi-file-earmark"></i> <a href="<?= url('') ?>${message.fichier_joint}" target="_blank">${fileName}</a></div>`;
            }
        }

        const readStatus = message.lu == 1 ? '<i class="bi bi-check2-all text-primary"></i>' : '<i class="bi bi-check2"></i>';
        const canDelete = isFromUser;

        html += `
            <div style="clear: both; margin-bottom: 0.5rem;" data-message-id="${message.id}">
                <div class="message-bubble ${bubbleClass} position-relative">
                    ${canDelete ? `<div class="message-actions position-absolute top-0 end-0 p-1" style="display: none;">
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMessage(${message.id})" title="Supprimer">
                            <i class="bi bi-trash" style="font-size: 0.7rem;"></i>
                        </button>
                    </div>` : ''}
                    <div>${messageContent}</div>
                    <small class="message-time d-flex justify-content-between align-items-center">
                        <span>${new Date(message.date_envoi).toLocaleString('fr-FR')}</span>
                        ${isFromUser ? readStatus : ''}
                    </small>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
    
    document.querySelectorAll('.message-bubble').forEach(bubble => {
        const actions = bubble.querySelector('.message-actions');
        if (actions) {
            bubble.addEventListener('mouseenter', function() { actions.style.display = 'block'; });
            bubble.addEventListener('mouseleave', function() { actions.style.display = 'none'; });
        }
    });
}

let selectedFile = null;

document.getElementById('fileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        selectedFile = file;
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-preview').style.display = 'block';
    }
});

function removeFile() {
    selectedFile = null;
    document.getElementById('fileInput').value = '';
    document.getElementById('file-preview').style.display = 'none';
}

function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.style.display = picker.style.display === 'block' ? 'none' : 'block';
}

function addEmoji(emoji) {
    const input = document.getElementById('messageInput');
    input.value += emoji;
    input.focus();
    document.getElementById('emojiPicker').style.display = 'none';
}

document.addEventListener('click', function(e) {
    const picker = document.getElementById('emojiPicker');
    const emojiBtn = e.target.closest('.btn-outline-secondary');
    if (!picker.contains(e.target) && !emojiBtn) {
        picker.style.display = 'none';
    }
});

async function deleteMessage(messageId) {
    if (!confirm('Supprimer ce message ?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_message');
        formData.append('message_id', messageId);

        const response = await fetch('<?= url('api/messages.php') ?>', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            document.querySelector(`[data-message-id="${messageId}"]`).remove();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

async function deleteConversation(userId) {
    if (!confirm('Supprimer toute la conversation ? Cette action est irréversible.')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_conversation');
        formData.append('user_id', userId);

        const response = await fetch('<?= url('api/messages.php') ?>', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau');
    }
}

document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!currentContactId) return;

    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();

    if (!message && !selectedFile) return;

    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('destinataire_id', currentContactId);
        formData.append('sujet', 'Message');
        formData.append('contenu', message);
        
        if (selectedFile) {
            formData.append('fichier', selectedFile);
        }

        const response = await fetch('<?= url('api/messages.php') ?>', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageInput.value = '';
            removeFile();
            await loadConversation(currentContactId);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur réseau: ' + error.message);
    }
});

setInterval(() => {
    if (currentContactId) {
        loadConversation(currentContactId);
    }
}, 30000);
    </script>
</body>
</html>