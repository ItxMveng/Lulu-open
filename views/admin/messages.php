<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

$db = Database::getInstance()->getConnection();

// Récupérer les conversations de l'admin
$adminId = $_SESSION['user_id'];
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
$stmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId, $adminId, $adminId, $adminId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Messages - Admin LULU-OPEN";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
    .admin-content {
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
        background: #f8f9fa;
    }

    @media (max-width: 991.98px) {
        .admin-content {
            margin-left: 0;
            padding-top: calc(60px + 2rem);
        }
    }

    .conversation-item {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .conversation-item:hover {
        background: #f8f9fa;
    }

    .unread-badge {
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= url('views/admin/dashboard.php') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Messages</li>
                </ol>
            </div>
        </nav>

        <!-- En-tête -->
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-12">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-chat-dots-fill me-2"></i>Messagerie Admin
                </h1>
                <p class="text-muted">Gérer vos conversations avec les utilisateurs</p>
            </div>
        </div>

        <div class="row">
            <!-- Liste des conversations -->
            <div class="col-md-4" data-aos="fade-right">
                <div class="card-custom">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>Conversations
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots" style="font-size: 3rem; color: var(--text-muted);"></i>
                                <h6 class="mt-3">Aucune conversation</h6>
                                <p class="text-muted small">Les messages des utilisateurs apparaîtront ici</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($conversations as $conv): ?>
                                <div class="list-group-item conversation-item p-3 border-0"
                                     onclick="openConversation(<?= $conv['interlocuteur_id'] ?>)">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $conv['photo_profil'] ? url($conv['photo_profil']) : url('assets/images/default-avatar.png') ?>"
                                             class="rounded-circle me-3"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="mb-1 fw-bold">
                                                    <?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?>
                                                </h6>
                                                <?php if ($conv['non_lus'] > 0): ?>
                                                    <span class="unread-badge"><?= $conv['non_lus'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1 text-muted small">
                                                <strong><?= htmlspecialchars($conv['sujet']) ?></strong>
                                            </p>
                                            <p class="mb-0 text-muted small truncate">
                                                <?= htmlspecialchars(substr($conv['dernier_message'], 0, 50)) ?>...
                                            </p>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($conv['derniere_date'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Zone de conversation -->
            <div class="col-md-8" data-aos="fade-left">
                <div class="card-custom">
                    <div class="card-header">
                        <h5 class="mb-0" id="conversation-title">
                            <i class="bi bi-chat-dots me-2"></i>Sélectionnez une conversation
                        </h5>
                    </div>
                    <div class="card-body" id="conversation-content" style="height: 500px; overflow-y: auto;">
                        <div class="text-center py-5" id="no-conversation">
                            <i class="bi bi-chat-dots" style="font-size: 5rem; color: var(--text-muted);"></i>
                            <h5 class="mt-3">Aucune conversation sélectionnée</h5>
                            <p class="text-muted">Cliquez sur une conversation pour l'ouvrir</p>
                        </div>
                    </div>
                    <div class="card-footer" id="message-form" style="display: none;">
                        <form id="sendMessageForm">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInput"
                                       placeholder="Tapez votre message..." required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        let currentInterlocutorId = null;

        function openConversation(interlocutorId) {
            currentInterlocutorId = interlocutorId;
            loadConversation(interlocutorId);
        }

        async function loadConversation(interlocutorId) {
            try {
                const response = await fetch(`<?= url('api/admin-messages.php') ?>?action=get_conversation&user_id=${interlocutorId}`);
                const data = await response.json();

                if (data.success) {
                    displayConversation(data.conversation, data.user);
                    document.getElementById('message-form').style.display = 'block';
                    document.getElementById('no-conversation').style.display = 'none';
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }

        function displayConversation(messages, user) {
            const content = document.getElementById('conversation-content');
            const title = document.getElementById('conversation-title');

            title.innerHTML = `<i class="bi bi-chat-dots me-2"></i>Conversation avec ${user.prenom} ${user.nom}`;

            let html = '';
            messages.forEach(message => {
                const isFromAdmin = message.expediteur_id == <?= $adminId ?>;
                const alignClass = isFromAdmin ? 'justify-content-end' : 'justify-content-start';
                const bgClass = isFromAdmin ? 'bg-primary text-white' : 'bg-light';

                html += `
                    <div class="d-flex mb-3 ${alignClass}">
                        <div class="message-bubble ${bgClass} p-3 rounded" style="max-width: 70%;">
                            <div class="fw-bold small mb-1">${message.prenom} ${message.nom}</div>
                            <div>${message.contenu}</div>
                            <div class="small opacity-75 mt-1">${new Date(message.date_envoi).toLocaleString('fr-FR')}</div>
                        </div>
                    </div>
                `;
            });

            content.innerHTML = html;
            content.scrollTop = content.scrollHeight;
        }

        document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!currentInterlocutorId) return;

            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message) return;

            try {
                const response = await fetch('<?= url('api/admin-messages.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_message',
                        destinataire_id: currentInterlocutorId,
                        sujet: 'Réponse admin',
                        contenu: message
                    })
                });

                const data = await response.json();

                if (data.success) {
                    messageInput.value = '';
                    loadConversation(currentInterlocutorId);
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        });
    </script>
</body>
</html>