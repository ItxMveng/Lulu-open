<?php
/**
 * Composant Flash Message - LULU-OPEN
 * Affiche les messages de retour d'action avec animations
 */

$flashMessage = getFlashMessage();
if ($flashMessage):
    $type = $flashMessage['type'];
    $message = $flashMessage['message'];
    
    // Mapping des types vers les classes Bootstrap et icônes
    $typeConfig = [
        'success' => [
            'class' => 'alert-success',
            'icon' => '✅',
            'title' => 'Succès'
        ],
        'error' => [
            'class' => 'alert-danger',
            'icon' => '❌',
            'title' => 'Erreur'
        ],
        'warning' => [
            'class' => 'alert-warning',
            'icon' => '⚠️',
            'title' => 'Attention'
        ],
        'info' => [
            'class' => 'alert-info',
            'icon' => 'ℹ️',
            'title' => 'Information'
        ]
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['info'];
?>

<div class="flash-message-container" id="flashMessage">
    <div class="alert <?= $config['class'] ?> alert-dismissible fade show flash-alert" role="alert">
        <div class="d-flex align-items-start">
            <div class="flash-icon me-3">
                <?= $config['icon'] ?>
            </div>
            <div class="flash-content flex-grow-1">
                <h6 class="flash-title mb-1"><?= $config['title'] ?></h6>
                <p class="flash-message mb-0"><?= htmlspecialchars($message) ?></p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        
        <!-- Progress bar pour auto-dismiss -->
        <div class="flash-progress">
            <div class="flash-progress-bar"></div>
        </div>
    </div>
</div>

<style>
.flash-message-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
    animation: slideInRight 0.5s ease-out;
}

.flash-alert {
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}

.flash-icon {
    font-size: 1.5rem;
    line-height: 1;
}

.flash-title {
    font-weight: 600;
    font-size: 0.95rem;
}

.flash-message {
    font-size: 0.9rem;
    line-height: 1.4;
}

.flash-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
}

.flash-progress-bar {
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    width: 100%;
    animation: progressBar 5s linear forwards;
}

/* Animations */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes progressBar {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .flash-message-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
}

/* Variants de couleurs personnalisées */
.alert-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    color: white;
}

.alert-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
}

.alert-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.alert-success .btn-close,
.alert-danger .btn-close,
.alert-info .btn-close {
    filter: brightness(0) invert(1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const flashMessage = document.getElementById('flashMessage');
    
    if (flashMessage) {
        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            dismissFlashMessage();
        }, 5000);
        
        // Gestion du clic sur le bouton de fermeture
        const closeBtn = flashMessage.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', dismissFlashMessage);
        }
        
        // Fermeture au clic sur le message
        flashMessage.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('flash-alert')) {
                dismissFlashMessage();
            }
        });
    }
    
    function dismissFlashMessage() {
        if (flashMessage) {
            flashMessage.style.animation = 'slideOutRight 0.3s ease-in forwards';
            setTimeout(() => {
                flashMessage.remove();
            }, 300);
        }
    }
});
</script>

<?php endif; ?>

<?php
/**
 * Fonction helper pour inclure le composant flash message
 * Usage: includeFlashMessage() dans vos vues
 */
function includeFlashMessage() {
    include __DIR__ . '/flash_message.php';
}
?>