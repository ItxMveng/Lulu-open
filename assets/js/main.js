// LULU-OPEN - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather Icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // Initialize all components
    initNavbar();
    initSearch();
    initCategories();
    initScrollAnimations();
    initParallax();
});

// Navbar functionality
function initNavbar() {
    const navbar = document.getElementById('mainNav');
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });
    
    // Mobile menu toggle
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
    }
}

// Search functionality
function initSearch() {
    const searchForm = document.getElementById('heroSearch');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(searchForm);
            const searchParams = {
                type: formData.get('type'),
                q: formData.get('query'), // Changé de 'query' à 'q' pour correspondre au contrôleur
                location: formData.get('location')
            };
            
            // Validation basique
            if (!searchParams.type) {
                alert('Veuillez sélectionner un type de recherche');
                return;
            }
            
            // Animate search button
            const searchBtn = searchForm.querySelector('.search-btn');
            const originalContent = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i data-feather="loader" class="animate-spin"></i>';
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            
            // Redirection vers la page de recherche
            setTimeout(() => {
                window.location.href = `search.php?${new URLSearchParams(searchParams)}`;
            }, 500);
        });
    }
    
    // Search input animations
    const searchInputs = document.querySelectorAll('.search-input, .search-select');
    searchInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('search-focus');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('search-focus');
        });
    });
}

// Categories loading and display
function initCategories() {
    const categoriesGrid = document.getElementById('categoriesGrid');
    
    if (categoriesGrid) {
        loadCategories();
    }
}

async function loadCategories() {
    try {
        // Simulate API call (replace with actual API)
        const categories = [
            { id: 1, name: 'Informatique', icon: 'monitor', count: 245, color: '#0099FF' },
            { id: 2, name: 'Artisanat', icon: 'tool', count: 189, color: '#FF6B35' },
            { id: 3, name: 'Design', icon: 'palette', count: 156, color: '#9B59B6' },
            { id: 4, name: 'Conseil', icon: 'briefcase', count: 134, color: '#2ECC71' },
            { id: 5, name: 'Santé', icon: 'heart', count: 98, color: '#E74C3C' },
            { id: 6, name: 'Éducation', icon: 'book', count: 87, color: '#F39C12' },
            { id: 7, name: 'Marketing', icon: 'trending-up', count: 76, color: '#1ABC9C' },
            { id: 8, name: 'Finance', icon: 'dollar-sign', count: 65, color: '#34495E' }
        ];
        
        const categoriesGrid = document.getElementById('categoriesGrid');
        categoriesGrid.innerHTML = '';
        
        categories.forEach((category, index) => {
            const categoryCard = createCategoryCard(category);
            categoryCard.style.animationDelay = `${index * 0.1}s`;
            categoriesGrid.appendChild(categoryCard);
        });
        
    } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error);
    }
}

function createCategoryCard(category) {
    const col = document.createElement('div');
    col.className = 'col-lg-3 col-md-4 col-sm-6';
    
    // Sanitize inputs
    const sanitizedName = escapeHtml(category.name);
    const sanitizedIcon = escapeHtml(category.icon);
    const sanitizedColor = escapeHtml(category.color);
    const sanitizedCount = parseInt(category.count) || 0;
    const sanitizedId = parseInt(category.id) || 0;
    
    // Create elements safely
    const cardDiv = document.createElement('div');
    cardDiv.className = 'category-card card-hover';
    cardDiv.setAttribute('data-aos', 'fade-up');
    
    const iconDiv = document.createElement('div');
    iconDiv.className = 'category-icon';
    iconDiv.style.background = `linear-gradient(135deg, ${sanitizedColor}, ${sanitizedColor}99)`;
    
    const icon = document.createElement('i');
    icon.setAttribute('data-feather', sanitizedIcon);
    iconDiv.appendChild(icon);
    
    const title = document.createElement('h5');
    title.textContent = sanitizedName;
    
    const description = document.createElement('p');
    description.className = 'text-muted';
    description.textContent = `${sanitizedCount} professionnels`;
    
    const link = document.createElement('a');
    link.href = `search.php?category=${sanitizedId}`;
    link.className = 'btn btn-outline-primary btn-sm';
    link.textContent = 'Explorer';
    
    cardDiv.appendChild(iconDiv);
    cardDiv.appendChild(title);
    cardDiv.appendChild(description);
    cardDiv.appendChild(link);
    col.appendChild(cardDiv);
    
    return col;
}

// HTML escaping function to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Scroll animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, observerOptions);
    
    // Observe all elements with scroll-reveal class
    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.observe(el);
    });
}

// Parallax effect
function initParallax() {
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.parallax');
        
        parallaxElements.forEach(element => {
            const speed = element.dataset.speed || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Utility functions
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${escapeHtml(type)}`;
    
    const toastContent = document.createElement('div');
    toastContent.className = 'toast-content';
    
    const icon = document.createElement('i');
    icon.setAttribute('data-feather', getToastIcon(type));
    
    const span = document.createElement('span');
    span.textContent = message;
    
    toastContent.appendChild(icon);
    toastContent.appendChild(span);
    toast.appendChild(toastContent);
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };
    return icons[type] || 'info';
}

// Loading states
function showLoading(element) {
    const originalContent = element.innerHTML;
    element.dataset.originalContent = originalContent;
    element.textContent = '';
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    element.appendChild(spinner);
    element.disabled = true;
}

function hideLoading(element) {
    element.textContent = '';
    element.innerHTML = element.dataset.originalContent;
    element.disabled = false;
}

// Local storage helpers
function setLocalStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.error('Erreur localStorage:', error);
    }
}

function getLocalStorage(key) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    } catch (error) {
        console.error('Erreur localStorage:', error);
        return null;
    }
}

// Performance monitoring
function measurePerformance(name, fn) {
    const start = performance.now();
    const result = fn();
    const end = performance.now();
    console.log(`${name} took ${end - start} milliseconds`);
    return result;
}

// Export functions for use in other scripts
window.LuluOpen = {
    showToast,
    showLoading,
    hideLoading,
    validateEmail,
    validatePhone,
    setLocalStorage,
    getLocalStorage,
    debounce,
    throttle
};
