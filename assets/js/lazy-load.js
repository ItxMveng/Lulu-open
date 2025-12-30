/**
 * LAZY-LOAD.JS - LULU-OPEN
 * Lazy loading des images avec Intersection Observer
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        rootMargin: '50px 0px',
        threshold: 0.01
    };

    // Créer l'observer
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                loadImage(img);
                observer.unobserve(img);
            }
        });
    }, config);

    // Fonction pour charger une image
    function loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;

        if (!src) return;

        // Créer une nouvelle image pour précharger
        const tempImg = new Image();
        
        tempImg.onload = function() {
            img.src = src;
            if (srcset) {
                img.srcset = srcset;
            }
            img.classList.remove('lazy');
            img.classList.add('lazy-loaded');
            
            // Animation fade-in
            img.style.opacity = '0';
            setTimeout(() => {
                img.style.transition = 'opacity 0.3s ease';
                img.style.opacity = '1';
            }, 10);
        };

        tempImg.onerror = function() {
            img.src = img.dataset.fallback || '/assets/images/placeholder.jpg';
            img.classList.add('lazy-error');
        };

        tempImg.src = src;
    }

    // Initialiser le lazy loading
    function init() {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        if ('IntersectionObserver' in window) {
            lazyImages.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback pour les navigateurs anciens
            lazyImages.forEach(img => loadImage(img));
        }
    }

    // Fonction pour ajouter de nouvelles images lazy
    window.lazyLoadImages = function(container) {
        const lazyImages = container.querySelectorAll('img.lazy');
        lazyImages.forEach(img => imageObserver.observe(img));
    };

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Réinitialiser lors des changements dynamiques
    const mutationObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    const lazyImages = node.querySelectorAll ? node.querySelectorAll('img.lazy') : [];
                    lazyImages.forEach(img => imageObserver.observe(img));
                }
            });
        });
    });

    mutationObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

})();
