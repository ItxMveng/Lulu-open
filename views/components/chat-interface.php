<div class="chat-interface" id="chatInterface">
    <div class="chat-header">
        <div class="d-flex align-items-center">
            <div class="chat-avatar me-3">
                <?php if ($conversation['photo_profil']): ?>
                    <img src="/uploads/profiles/<?= $conversation['photo_profil'] ?>" 
                         alt="Avatar" class="rounded-circle" width="40" height="40">
                <?php else: ?>
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" 
                         style="width: 40px; height: 40px;">
                        <?= strtoupper(substr($conversation['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0"><?= htmlspecialchars($conversation['nom']) ?></h6>
                <small class="text-muted" id="userStatus">
                    <span class="status-indicator"></span>
                    <span id="statusText">Hors ligne</span>
                </small>
            </div>
            <div class="chat-actions">
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleChatInfo()">
                    <i class="bi bi-info-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="closeChat()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <div class="messages-container">
            <!-- Les messages seront chargés ici -->
        </div>
        <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <small class="text-muted ms-2">En train d'écrire...</small>
        </div>
    </div>
    
    <div class="chat-input">
        <form id="messageForm" class="d-flex align-items-end gap-2">
            <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="flex-grow-1">
                <textarea class="form-control" id="messageInput" name="message" 
                          placeholder="Tapez votre message..." rows="1" 
                          style="resize: none; max-height: 120px;"></textarea>
            </div>
            
            <div class="input-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleEmoji()">
                    <i class="bi bi-emoji-smile"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="attachFile()">
                    <i class="bi bi-paperclip"></i>
                </button>
                <button type="submit" class="btn btn-primary btn-sm" id="sendBtn">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
        
        <input type="file" id="fileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx">
    </div>
</div>

<style>
.chat-interface {
    height: 600px;
    display: flex;
    flex-direction: column;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow: hidden;
    background: white;
}

.chat-header {
    padding: 1rem;
    background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: #f8f9fa;
}

.messages-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    max-width: 80%;
    animation: messageSlideIn 0.3s ease-out;
}

.message.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message.received {
    align-self: flex-start;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
}

.message-content {
    background: white;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    position: relative;
}

.message.sent .message-content {
    background: #0099FF;
    color: white;
}

.message-text {
    margin: 0;
    word-wrap: break-word;
}

.message-time {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0.25rem;
}

.message.sent .message-time {
    text-align: right;
}

.chat-input {
    padding: 1rem;
    background: white;
    border-top: 1px solid #dee2e6;
}

.input-actions {
    display: flex;
    gap: 0.25rem;
}

.typing-indicator {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    margin-top: 1rem;
}

.typing-dots {
    display: flex;
    gap: 0.25rem;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
    animation: typingBounce 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
    display: inline-block;
    margin-right: 0.25rem;
}

.status-indicator.online {
    background: #28a745;
}

.message-attachment {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-attachment i {
    font-size: 1.2rem;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes typingBounce {
    0%, 80%, 100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}

/* Auto-resize textarea */
.form-control:focus {
    border-color: #0099FF;
    box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.25);
}

/* Scrollbar personnalisée */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
class ChatInterface {
    constructor(conversationId) {
        this.conversationId = conversationId;
        this.lastMessageId = 0;
        this.isTyping = false;
        this.typingTimeout = null;
        this.pollInterval = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadMessages();
        this.startPolling();
        this.setupAutoResize();
    }
    
    setupEventListeners() {
        // Soumission du formulaire
        document.getElementById('messageForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Détection de frappe
        document.getElementById('messageInput').addEventListener('input', () => {
            this.handleTyping();
        });
        
        // Raccourci clavier
        document.getElementById('messageInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Upload de fichier
        document.getElementById('fileInput').addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.uploadFile(e.target.files[0]);
            }
        });
    }
    
    async loadMessages() {
        try {
            const response = await fetch(`/api/messages/conversation/${this.conversationId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayMessages(data.messages);
                this.scrollToBottom();
                
                if (data.messages.length > 0) {
                    this.lastMessageId = Math.max(...data.messages.map(m => m.id));
                }
            }
        } catch (error) {
            console.error('Erreur chargement messages:', error);
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        try {
            const formData = new FormData(document.getElementById('messageForm'));
            
            const response = await fetch('/api/messages/send', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                this.resizeTextarea(input);
                this.addMessage(data.message, true);
                this.scrollToBottom();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            console.error('Erreur envoi message:', error);
            alert('Erreur de connexion');
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="bi bi-send"></i>';
        }
    }
    
    async pollNewMessages() {
        try {
            const response = await fetch(`/api/messages/poll/${this.conversationId}?since=${this.lastMessageId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(message => {
                    this.addMessage(message, false);
                });
                
                this.lastMessageId = Math.max(...data.messages.map(m => m.id));
                this.scrollToBottom();
            }
            
            // Mettre à jour le statut utilisateur
            if (data.user_status) {
                this.updateUserStatus(data.user_status);
            }
            
        } catch (error) {
            console.error('Erreur polling:', error);
        }
    }
    
    displayMessages(messages) {
        const container = document.querySelector('.messages-container');
        container.innerHTML = '';
        
        messages.forEach(message => {
            this.addMessage(message, message.expediteur_id == <?= $_SESSION['user_id'] ?>);
        });
    }
    
    addMessage(message, isSent) {
        const container = document.querySelector('.messages-container');
        const messageEl = document.createElement('div');
        messageEl.className = `message ${isSent ? 'sent' : 'received'}`;
        
        const avatar = isSent ? 
            `<div class="message-avatar bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 0.8rem;">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>` :
            `<div class="message-avatar bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 0.8rem;">
                <?= strtoupper(substr($conversation['nom'], 0, 1)) ?>
            </div>`;
        
        messageEl.innerHTML = `
            ${avatar}
            <div class="message-content">
                <p class="message-text">${this.formatMessage(message.contenu)}</p>
                ${message.fichier_joint ? this.renderAttachment(message.fichier_joint) : ''}
                <div class="message-time">${this.formatTime(message.date_envoi)}</div>
            </div>
        `;
        
        container.appendChild(messageEl);
    }
    
    formatMessage(text) {
        // Échapper le HTML et convertir les liens
        text = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        // Convertir les URLs en liens
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
        
        // Convertir les retours à la ligne
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }
    
    formatTime(datetime) {
        const date = new Date(datetime);
        const now = new Date();
        
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) + 
                   ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
    }
    
    renderAttachment(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);
        
        if (isImage) {
            return `
                <div class="message-attachment">
                    <img src="/uploads/messages/${filename}" alt="Image" 
                         style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                </div>
            `;
        } else {
            return `
                <div class="message-attachment">
                    <i class="bi bi-file-earmark"></i>
                    <a href="/uploads/messages/${filename}" target="_blank">${filename}</a>
                </div>
            `;
        }
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    startPolling() {
        this.pollInterval = setInterval(() => {
            this.pollNewMessages();
        }, 3000); // Poll toutes les 3 secondes
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
    
    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }
        
        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }, 2000);
    }
    
    async sendTypingStatus(isTyping) {
        try {
            await fetch('/api/messages/typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    is_typing: isTyping
                })
            });
        } catch (error) {
            console.error('Erreur statut frappe:', error);
        }
    }
    
    updateUserStatus(status) {
        const indicator = document.querySelector('.status-indicator');
        const text = document.getElementById('statusText');
        
        if (status.online) {
            indicator.classList.add('online');
            text.textContent = 'En ligne';
        } else {
            indicator.classList.remove('online');
            text.textContent = `Vu ${this.formatTime(status.last_seen)}`;
        }
    }
    
    setupAutoResize() {
        const textarea = document.getElementById('messageInput');
        
        textarea.addEventListener('input', () => {
            this.resizeTextarea(textarea);
        });
    }
    
    resizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }
    
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('conversation_id', this.conversationId);
        formData.append('csrf_token', '<?= $csrf_token ?>');
        
        try {
            const response = await fetch('/api/messages/upload', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.addMessage(data.message, true);
                this.scrollToBottom();
            } else {
                alert('Erreur upload: ' + data.message);
            }
        } catch (error) {
            console.error('Erreur upload:', error);
            alert('Erreur de connexion');
        }
    }
    
    destroy() {
        this.stopPolling();
    }
}

// Fonctions globales
function toggleEmoji() {
    // TODO: Implémenter le sélecteur d'emoji
    console.log('Toggle emoji picker');
}

function attachFile() {
    document.getElementById('fileInput').click();
}

function toggleChatInfo() {
    // TODO: Afficher les informations de la conversation
    console.log('Toggle chat info');
}

function closeChat() {
    if (window.chatInterface) {
        window.chatInterface.destroy();
    }
    
    // Fermer l'interface de chat
    document.getElementById('chatInterface').style.display = 'none';
}

// Initialiser le chat
document.addEventListener('DOMContentLoaded', function() {
    const conversationId = <?= $conversation['id'] ?>;
    window.chatInterface = new ChatInterface(conversationId);
});

// Nettoyer lors de la fermeture de la page
window.addEventListener('beforeunload', function() {
    if (window.chatInterface) {
        window.chatInterface.destroy();
    }
});
</script>