/**
 * ANIMATIONS.JS - LULU-OPEN
 * Animations personnalisées et micro-interactions
 */

(function() {
    'use strict';

    // === TYPING EFFECT ===
    window.typeWriter = function(text, element, speed = 50) {
        if (!element) return;
        
        let i = 0;
        element.innerHTML = '';
        element.classList.add('typing-cursor');
        
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else {
                element.classList.remove('typing-cursor');
            }
        }
        
        type();
    };

    // === COUNTER ANIMATION ===
    window.animateCounter = function(element, end, duration = 1000) {
        if (!element) return;
        
        const start = 0;
        const increment = end / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                element.textContent = end;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    };

    // === PARALLAX EFFECT ===
    function initParallax() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        if (parallaxElements.length === 0) return;
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const speed = element.dataset.parallax || 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        }, { passive: true });
    }

    // === REVEAL ON SCROLL ===
    function initScrollReveal() {
        const revealElements = document.querySelectorAll('.reveal');
        
        if (revealElements.length === 0) return;
        
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15
        });
        
        revealElements.forEach(element => revealObserver.observe(element));
    }

    // === RIPPLE EFFECT ===
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        button.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }

    function initRippleEffect() {
        const rippleButtons = document.querySelectorAll('.btn, button');
        rippleButtons.forEach(button => {
            button.addEventListener('click', createRipple);
        });
    }

    // === FAVORITE BUTTON ANIMATION ===
    function initFavoriteButtons() {
        const favoriteButtons = document.querySelectorAll('.btn-favorite');
        
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (this.classList.contains('active')) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                } else {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }
            });
        });
    }

    // === STAGGER ANIMATION ===
    function initStaggerAnimation() {
        const staggerContainers = document.querySelectorAll('[data-stagger]');
        
        staggerContainers.forEach(container => {
            const items = container.children;
            const delay = parseInt(container.dataset.stagger) || 100;
            
            Array.from(items).forEach((item, index) => {
                item.classList.add('stagger-item');
                item.style.animationDelay = `${index * delay}ms`;
            });
        });
    }

    // === SMOOTH SCROLL ===
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // === KEYBOARD NAVIGATION DETECTION ===
    function initKeyboardNav() {
        let isKeyboard = false;
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                isKeyboard = true;
                document.body.classList.add('keyboard-nav');
            }
        });
        
        document.addEventListener('mousedown', () => {
            isKeyboard = false;
            document.body.classList.remove('keyboard-nav');
        });
    }

    // === SCROLL TO TOP BUTTON ===
    function initScrollToTop() {
        const scrollBtn = document.getElementById('scroll-to-top');
        if (!scrollBtn) return;
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        }, { passive: true });
        
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // === IMAGE HOVER ZOOM ===
    function initImageZoom() {
        const zoomContainers = document.querySelectorAll('.image-overlay-container');
        
        zoomContainers.forEach(container => {
            const img = container.querySelector('img');
            if (!img) return;
            
            container.addEventListener('mouseenter', () => {
                img.style.transform = 'scale(1.1)';
            });
            
            container.addEventListener('mouseleave', () => {
                img.style.transform = 'scale(1)';
            });
        });
    }

    // === INIT ALL ===
    function init() {
        initParallax();
        initScrollReveal();
        initRippleEffect();
        initFavoriteButtons();
        initStaggerAnimation();
        initSmoothScroll();
        initKeyboardNav();
        initScrollToTop();
        initImageZoom();
    }

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exposer les fonctions globalement
    window.LuluAnimations = {
        typeWriter: window.typeWriter,
        animateCounter: window.animateCounter,
        reinit: init
    };

})();
