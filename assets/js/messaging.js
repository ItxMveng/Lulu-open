/**
 * Gestion de la messagerie CLIENT - LULU-OPEN
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Recherche conversations
    const searchInput = document.getElementById('searchConversation');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-item');
            
            conversations.forEach(conv => {
                const text = conv.textContent.toLowerCase();
                conv.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
    }
    
    // Auto-scroll messages
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Polling nouveaux messages (toutes les 10s)
    if (messagesContainer) {
        setInterval(checkNewMessages, 10000);
    }
});

/**
 * Vérifier nouveaux messages
 */
function checkNewMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const interlocuteurId = urlParams.get('id');
    
    if (!interlocuteurId) return;
    
    fetch(`/lulu/api/check-new-messages.php?interlocuteur_id=${interlocuteurId}`)
        .then(response => response.json())
        .then(data => {
            if (data.has_new) {
                location.reload();
            }
        })
        .catch(error => console.error('Erreur check messages:', error));
}

/**
 * Envoyer message
 */
function sendMessage(destinataireId, message) {
    const formData = new FormData();
    formData.append('destinataire_id', destinataireId);
    formData.append('message', message);
    formData.append('sujet', 'Message');
    
    return fetch('/lulu/api/send-message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json());
}
