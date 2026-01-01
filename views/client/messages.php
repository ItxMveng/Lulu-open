<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../models/Message.php';

require_login();

if ($_SESSION['user_type'] !== 'client') {
    header('Location: ../../login.php');
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
    :root { --primary-dark: #000033; --primary-blue: #0099FF; }
    body { background: #f8f9fa; }
    .main-content { padding: 2rem; min-height: 100vh; }
    .conversation-item { transition: all 0.3s ease; cursor: pointer; }
    .conversation-item:hover { background: #f8f9fa; }
    .unread-badge { background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; }
    .message-bubble { border-radius: 18px; position: relative; word-wrap: break-word; }
    .message-bubble.from-user { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; }
    .message-bubble.from-other { background: #f8f9fa; border: 1px solid #e9ecef; }
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
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>

    <div class="main-content">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Messages</li>
            </ol>
        </nav>

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-chat-dots-fill me-2"></i>Messagerie
                </h1>
                <p class="text-muted">Gérer vos conversations</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Conversations</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots" style="font-size: 3rem; color: #ccc;"></i>
                                <h6 class="mt-3">Aucune conversation</h6>
                                <p class="text-muted small">Les messages apparaîtront ici</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($conversations as $conv): ?>
                                <div class="list-group-item conversation-item p-3 border-0" onclick="openConversation(<?= $conv['interlocuteur_id'] ?>)">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= getPhotoUrl($conv['photo_profil']) ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>';">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?></h6>
                                                <?php if ($conv['non_lus'] > 0): ?>
                                                    <span class="unread-badge"><?= $conv['non_lus'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1 text-muted small"><strong><?= htmlspecialchars($conv['sujet']) ?></strong></p>
                                            <p class="mb-0 text-muted small truncate"><?= htmlspecialchars(substr($conv['dernier_message'], 0, 50)) ?>...</p>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($conv['derniere_date'])) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" id="conversation-title"><i class="bi bi-chat-dots me-2"></i>Sélectionnez une conversation</h5>
                    </div>
                    <div class="card-body" id="conversation-content" style="height: 500px; overflow-y: auto;">
                        <div class="text-center py-5" id="no-conversation">
                            <i class="bi bi-chat-dots" style="font-size: 5rem; color: #ccc;"></i>
                            <h5 class="mt-3">Aucune conversation sélectionnée</h5>
                            <p class="text-muted">Cliquez sur une conversation pour l'ouvrir</p>
                        </div>
                    </div>
                    <div class="card-footer" id="message-form" style="display: none;">
                        <form id="sendMessageForm" enctype="multipart/form-data">
                            <div class="mb-2" id="file-preview" style="display: none;">
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                                    <i class="bi bi-paperclip"></i>
                                    <span id="file-name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                            <div class="input-group position-relative">
                                <input type="file" id="fileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx,.txt">
                                <input type="text" class="form-control" id="messageInput" placeholder="Tapez votre message..." required>
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('fileInput').click()"><i class="bi bi-paperclip"></i></button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleEmojiPicker()"><i class="bi bi-emoji-smile"></i></button>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
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
        let currentInterlocutorId = null;

        function openConversation(interlocutorId) {
            currentInterlocutorId = interlocutorId;
            loadConversation(interlocutorId);
        }

        async function loadConversation(interlocutorId) {
            try {
                const response = await fetch(`<?= url('api/messages.php') ?>?action=get_conversation&user_id=${interlocutorId}`);
                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                const data = await response.json();

                if (data.success) {
                    displayConversation(data.conversation, data.user);
                    const messageForm = document.getElementById('message-form');
                    const noConversation = document.getElementById('no-conversation');
                    if (messageForm) messageForm.style.display = 'block';
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
            const content = document.getElementById('conversation-content');
            const title = document.getElementById('conversation-title');

            title.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div><i class="bi bi-chat-dots me-2"></i>Conversation avec ${user.prenom} ${user.nom}</div>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteConversation(${currentInterlocutorId})">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            `;

            let html = '';
            messages.forEach(message => {
                const isFromUser = message.expediteur_id == <?= $_SESSION['user_id'] ?>;
                const alignClass = isFromUser ? 'justify-content-end' : 'justify-content-start';
                const bubbleClass = isFromUser ? 'from-user' : 'from-other';

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
                    <div class="d-flex mb-3 ${alignClass}" data-message-id="${message.id}">
                        <div class="message-bubble ${bubbleClass} p-3 position-relative" style="max-width: 70%;">
                            ${canDelete ? `<div class="message-actions position-absolute top-0 end-0 p-1" style="display: none;">
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMessage(${message.id})" title="Supprimer">
                                    <i class="bi bi-trash" style="font-size: 0.7rem;"></i>
                                </button>
                            </div>` : ''}
                            <div class="fw-bold small mb-1">${message.prenom} ${message.nom}</div>
                            <div>${messageContent}</div>
                            <div class="small opacity-75 mt-1 d-flex justify-content-between align-items-center">
                                <span>${new Date(message.date_envoi).toLocaleString('fr-FR')}</span>
                                ${isFromUser ? readStatus : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            content.innerHTML = html;
            content.scrollTop = content.scrollHeight;
            
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

            if (!currentInterlocutorId) return;

            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message && !selectedFile) return;

            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('destinataire_id', currentInterlocutorId);
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
                    await loadConversation(currentInterlocutorId);
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau: ' + error.message);
            }
        });

        setInterval(() => {
            if (currentInterlocutorId) {
                loadConversation(currentInterlocutorId);
            }
        }, 30000);
    </script>
</body>
</html>