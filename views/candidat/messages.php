<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/sidebar.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

global $database;
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

// Gestion du contact automatique
$contact_user_id = $_GET['contact'] ?? null;
$contact_user = null;

if ($contact_user_id) {
    $contact_user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ? AND id != ?", [$contact_user_id, $_SESSION['user_id']]);
    if (!$contact_user) {
        flashMessage('Utilisateur non trouvé.', 'error');
        header('Location: messages.php');
        exit;
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $destinataire_id = $_POST['destinataire_id'];
        $contenu = trim($_POST['contenu']);
        
        if (!empty($contenu) && $destinataire_id != $_SESSION['user_id']) {
            $database->query("INSERT INTO messages (expediteur_id, destinataire_id, contenu, date_envoi) VALUES (?, ?, ?, NOW())", 
                [$_SESSION['user_id'], $destinataire_id, $contenu]);
            flashMessage('Message envoyé avec succès !', 'success');
        }
    }
    
    if ($action === 'mark_read') {
        $message_id = $_POST['message_id'];
        $database->query("UPDATE messages SET lu = 1 WHERE id = ? AND destinataire_id = ?", 
            [$message_id, $_SESSION['user_id']]);
    }
}

// Récupérer les conversations
$conversations = $database->fetchAll("
    SELECT DISTINCT 
        CASE 
            WHEN m.expediteur_id = ? THEN m.destinataire_id 
            ELSE m.expediteur_id 
        END as contact_id,
        u.prenom, u.nom, u.photo_profil,
        (SELECT contenu FROM messages m2 
         WHERE (m2.expediteur_id = ? AND m2.destinataire_id = contact_id) 
            OR (m2.expediteur_id = contact_id AND m2.destinataire_id = ?)
         ORDER BY m2.date_envoi DESC LIMIT 1) as dernier_message,
        (SELECT date_envoi FROM messages m2 
         WHERE (m2.expediteur_id = ? AND m2.destinataire_id = contact_id) 
            OR (m2.expediteur_id = contact_id AND m2.destinataire_id = ?)
         ORDER BY m2.date_envoi DESC LIMIT 1) as derniere_date,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.expediteur_id = contact_id AND m3.destinataire_id = ? AND m3.lu = 0) as non_lus
    FROM messages m
    JOIN utilisateurs u ON u.id = CASE 
        WHEN m.expediteur_id = ? THEN m.destinataire_id 
        ELSE m.expediteur_id 
    END
    WHERE m.expediteur_id = ? OR m.destinataire_id = ?
    ORDER BY derniere_date DESC
", [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);

// Si contact automatique, ajouter à la liste
if ($contact_user && !in_array($contact_user['id'], array_column($conversations, 'contact_id'))) {
    array_unshift($conversations, [
        'contact_id' => $contact_user['id'],
        'prenom' => $contact_user['prenom'],
        'nom' => $contact_user['nom'],
        'photo_profil' => $contact_user['photo_profil'],
        'dernier_message' => '',
        'derniere_date' => date('Y-m-d H:i:s'),
        'non_lus' => 0
    ]);
}

$contact_actuel = $_GET['contact'] ?? null;
$messages_conversation = [];

if ($contact_actuel) {
    $messages_conversation = $database->fetchAll("
        SELECT m.*, u.prenom, u.nom, u.photo_profil
        FROM messages m
        JOIN utilisateurs u ON u.id = m.expediteur_id
        WHERE (m.expediteur_id = ? AND m.destinataire_id = ?) 
           OR (m.expediteur_id = ? AND m.destinataire_id = ?)
        ORDER BY m.date_envoi ASC
    ", [$_SESSION['user_id'], $contact_actuel, $contact_actuel, $_SESSION['user_id']]);
    
    // Marquer comme lus
    $database->query("UPDATE messages SET lu = 1 WHERE expediteur_id = ? AND destinataire_id = ?", 
        [$contact_actuel, $_SESSION['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .messages-container {
            height: calc(100vh - 100px);
            display: flex;
        }
        .conversations-list {
            width: 350px;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        .conversation-item:hover {
            background: #f8f9fa;
            color: inherit;
        }
        .conversation-item.active {
            background: #e3f2fd;
            border-left: 4px solid #0099ff;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            background: white;
        }
        .messages-area {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message-bubble {
            max-width: 70%;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
        }
        .message-sent {
            background: #0099ff;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .message-received {
            background: white;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        .message-input-area {
            padding: 1rem;
            background: white;
            border-top: 1px solid #dee2e6;
        }
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            min-width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php renderSidebar($_SESSION['user_type'], 'messages.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-0">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show m-3">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="messages-container">
                <!-- Liste des conversations -->
                <div class="conversations-list">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">💬 Messages</h5>
                    </div>
                    
                    <?php if (empty($conversations)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                            <p class="mt-2">Aucune conversation</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?contact=<?= $conv['contact_id'] ?>" 
                               class="conversation-item d-block <?= $contact_actuel == $conv['contact_id'] ? 'active' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if ($conv['photo_profil']): ?>
                                            <img src="../../uploads/profiles/<?= $conv['photo_profil'] ?>" 
                                                 class="rounded-circle" width="45" height="45">
                                        <?php else: ?>
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                 style="width: 45px; height: 45px;">
                                                <?= strtoupper(substr($conv['prenom'], 0, 1) . substr($conv['nom'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1"><?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?></h6>
                                            <?php if ($conv['non_lus'] > 0): ?>
                                                <span class="unread-badge"><?= $conv['non_lus'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <?= htmlspecialchars(substr($conv['dernier_message'], 0, 50)) ?>...
                                        </p>
                                        <small class="text-muted">
                                            <?= date('d/m H:i', strtotime($conv['derniere_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Zone de chat -->
                <div class="chat-area">
                    <?php if ($contact_actuel): ?>
                        <?php 
                        $contact_info = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$contact_actuel]);
                        ?>
                        
                        <!-- En-tête du chat -->
                        <div class="chat-header">
                            <div class="d-flex align-items-center">
                                <?php if ($contact_info['photo_profil']): ?>
                                    <img src="../../uploads/profiles/<?= $contact_info['photo_profil'] ?>" 
                                         class="rounded-circle me-3" width="40" height="40">
                                <?php else: ?>
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3" 
                                         style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($contact_info['prenom'], 0, 1) . substr($contact_info['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($contact_info['prenom'] . ' ' . $contact_info['nom']) ?></h6>
                                    <small class="text-muted"><?= ucfirst($contact_info['type_utilisateur']) ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="messages-area" id="messagesArea">
                            <?php foreach ($messages_conversation as $msg): ?>
                                <div class="message-bubble <?= $msg['expediteur_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received' ?>">
                                    <div><?= nl2br(htmlspecialchars($msg['contenu'])) ?></div>
                                    <div class="message-time">
                                        <?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Zone de saisie -->
                        <div class="message-input-area">
                            <form method="POST" class="d-flex">
                                <input type="hidden" name="action" value="send_message">
                                <input type="hidden" name="destinataire_id" value="<?= $contact_actuel ?>">
                                <input type="text" class="form-control me-2" name="contenu" 
                                       placeholder="Tapez votre message..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i>
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="bi bi-chat-square-dots" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">Sélectionnez une conversation</h5>
                                <p>Choisissez un contact pour commencer à discuter</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll vers le bas
        const messagesArea = document.getElementById('messagesArea');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Actualisation automatique des messages
        <?php if ($contact_actuel): ?>
        setInterval(function() {
            fetch('../../api/check-new-messages.php?contact=<?= $contact_actuel ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages) {
                        location.reload();
                    }
                });
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>