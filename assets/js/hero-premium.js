/**
 * Hero Premium Background - Enhanced JavaScript Effects
 * Ajoute des effets de parallaxe et d'interactivité au fond animé
 */

document.addEventListener('DOMContentLoaded', function() {
    const heroBackground = document.querySelector('.hero-background');
    const heroSection = document.querySelector('.hero-section');
    
    if (!heroBackground || !heroSection) return;

    // Variables pour les effets
    let mouseX = 0;
    let mouseY = 0;
    let isMouseInHero = false;

    // Effet de parallaxe au survol de la souris
    function handleMouseMove(e) {
        if (!isMouseInHero) return;
        
        const rect = heroSection.getBoundingClientRect();
        mouseX = (e.clientX - rect.left) / rect.width;
        mouseY = (e.clientY - rect.top) / rect.height;
        
        // Appliquer l'effet de parallaxe subtil
        const moveX = (mouseX - 0.5) * 20;
        const moveY = (mouseY - 0.5) * 20;
        
        heroBackground.style.transform = `translate(${moveX}px, ${moveY}px) scale(1.05)`;
    }

    // Détecter l'entrée/sortie de la souris
    function handleMouseEnter() {
        isMouseInHero = true;
        heroBackground.style.transition = 'transform 0.3s ease-out';
    }

    function handleMouseLeave() {
        isMouseInHero = false;
        heroBackground.style.transform = 'translate(0px, 0px) scale(1)';
        heroBackground.style.transition = 'transform 0.6s ease-out';
    }

    // Effet de scroll parallaxe
    function handleScroll() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        if (scrolled < window.innerHeight) {
            heroBackground.style.transform = `translate3d(0, ${rate}px, 0)`;
        }
    }

    // Animation des couleurs basée sur l'heure
    function updateGradientByTime() {
        const hour = new Date().getHours();
        let colors;
        
        if (hour >= 6 && hour < 12) {
            // Matin - couleurs douces
            colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#00c9ff'];
        } else if (hour >= 12 && hour < 18) {
            // Après-midi - couleurs vives
            colors = ['#4facfe', '#00f2fe', '#fa709a', '#fee140', '#667eea'];
        } else if (hour >= 18 && hour < 22) {
            // Soirée - couleurs chaudes
            colors = ['#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#764ba2'];
        } else {
            // Nuit - couleurs sombres et mystérieuses
            colors = ['#667eea', '#764ba2', '#2196f3', '#21cbf3', '#667eea'];
        }
        
        const gradient = `linear-gradient(45deg, ${colors.join(', ')})`;
        heroBackground.style.background = gradient;
        heroBackground.style.backgroundSize = '400% 400%';
    }

    // Effet de pulsation subtile
    function addPulseEffect() {
        let pulseIntensity = 0;
        
        setInterval(() => {
            pulseIntensity += 0.02;
            const opacity = 0.05 + Math.sin(pulseIntensity) * 0.02;
            
            const overlay = heroBackground.querySelector('::after') || heroBackground;
            if (overlay) {
                overlay.style.setProperty('--pulse-opacity', opacity);
            }
        }, 100);
    }

    // Initialisation des événements
    if (window.innerWidth > 768) { // Désactiver sur mobile pour les performances
        heroSection.addEventListener('mousemove', handleMouseMove);
        heroSection.addEventListener('mouseenter', handleMouseEnter);
        heroSection.addEventListener('mouseleave', handleMouseLeave);
        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    // Initialiser les effets
    updateGradientByTime();
    addPulseEffect();
    
    // Mettre à jour les couleurs toutes les heures
    setInterval(updateGradientByTime, 3600000);

    // Effet de révélation au chargement
    setTimeout(() => {
        heroBackground.style.opacity = '1';
        heroBackground.style.transition = 'opacity 2s ease-in-out';
    }, 100);

    // Performance: Réduire la fréquence des événements sur mobile
    if (window.innerWidth <= 768) {
        let ticking = false;
        
        function optimizedScroll() {
            if (!ticking) {
                requestAnimationFrame(() => {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', optimizedScroll, { passive: true });
    }
});

// Fonction utilitaire pour créer des particules flottantes (optionnel)
function createFloatingParticles() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;

    for (let i = 0; i < 5; i++) {
        const particle = document.createElement('div');
        particle.className = 'floating-particle';
        particle.style.cssText = `
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: floatParticle ${15 + Math.random() * 10}s linear infinite;
        `;
        
        heroSection.appendChild(particle);
    }
}

// CSS pour les particules (à ajouter dynamiquement)
const particleCSS = `
@keyframes floatParticle {
    0% {
        transform: translateY(100vh) translateX(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) translateX(${Math.random() * 200 - 100}px);
        opacity: 0;
    }
}
`;

// Ajouter le CSS des particules
const style = document.createElement('style');
style.textContent = particleCSS;
document.head.appendChild(style);

// Initialiser les particules (optionnel - décommenter si souhaité)
// createFloatingParticles();