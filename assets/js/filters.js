/**
 * FILTERS.JS - LULU-OPEN
 * Gestion des filtres dynamiques
 */

(function() {
    'use strict';

    let activeFilters = {
        categories: [],
        location: '',
        priceMin: '',
        priceMax: '',
        rating: ''
    };

    // Apply filters
    async function applyFilters() {
        const resultsContainer = document.querySelector('.results-container');
        if (!resultsContainer) return;

        // Show loading
        showLoading(resultsContainer);

        // Build query string
        const params = new URLSearchParams();
        if (activeFilters.categories.length > 0) {
            params.append('categories', activeFilters.categories.join(','));
        }
        if (activeFilters.location) {
            params.append('location', activeFilters.location);
        }
        if (activeFilters.priceMin) {
            params.append('price_min', activeFilters.priceMin);
        }
        if (activeFilters.priceMax) {
            params.append('price_max', activeFilters.priceMax);
        }
        if (activeFilters.rating) {
            params.append('rating', activeFilters.rating);
        }

        try {
            const response = await fetch(`/api/filter-results.php?${params.toString()}`);
            const data = await response.json();

            displayResults(data.results, resultsContainer);
            updateResultsCount(data.total);
            updateURL(params);
        } catch (error) {
            console.error('Error applying filters:', error);
            showError(resultsContainer);
        }
    }

    // Show loading state
    function showLoading(container) {
        container.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Chargement des résultats...</p>
            </div>
        `;
    }

    // Show error state
    function showError(container) {
        container.innerHTML = `
            <div class="error-state">
                <i class="bi bi-exclamation-triangle"></i>
                <p>Une erreur est survenue. Veuillez réessayer.</p>
            </div>
        `;
    }

    // Display results
    function displayResults(results, container) {
        if (results.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <i class="bi bi-search"></i>
                    <h3>Aucun résultat trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche</p>
                    <button class="btn btn-primary" onclick="resetFilters()">Réinitialiser les filtres</button>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        results.forEach((result, index) => {
            const card = createResultCard(result);
            card.classList.add('stagger-item');
            card.style.animationDelay = `${index * 0.1}s`;
            container.appendChild(card);
        });
    }

    // Create result card
    function createResultCard(result) {
        const card = document.createElement('div');
        card.className = 'profile-card hover-lift';
        card.innerHTML = `
            <div class="image-overlay-container">
                <img src="${result.image || '/assets/images/placeholder.jpg'}" 
                     alt="${result.name}" 
                     class="lazy" 
                     data-src="${result.image}">
                <div class="image-overlay">
                    <a href="${result.url}" class="btn btn-sm btn-light">Voir le profil</a>
                </div>
                ${result.verified ? '<span class="badge-verified"><i class="bi bi-patch-check-fill"></i></span>' : ''}
            </div>
            <div class="profile-info">
                <h3>${result.name}</h3>
                <p class="category">${result.category}</p>
                <div class="rating">
                    <div class="stars" data-rating="${result.rating}">${generateStars(result.rating)}</div>
                    <span class="rating-count">(${result.reviews_count} avis)</span>
                </div>
                ${result.price ? `<p class="price">${result.price}</p>` : ''}
                <div class="actions">
                    <button class="btn-favorite" data-id="${result.id}">
                        <i class="bi bi-heart"></i>
                    </button>
                    <a href="${result.contact_url}" class="btn btn-primary btn-sm">
                        <i class="bi bi-envelope"></i> Contacter
                    </a>
                </div>
            </div>
        `;
        return card;
    }

    // Generate stars HTML
    function generateStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

        let html = '';
        for (let i = 0; i < fullStars; i++) html += '★';
        if (hasHalfStar) html += '☆';
        for (let i = 0; i < emptyStars; i++) html += '☆';

        return html;
    }

    // Update results count
    function updateResultsCount(total) {
        const countElement = document.querySelector('.results-count');
        if (countElement) {
            window.animateCounter(countElement, total);
        }
    }

    // Update URL with filters
    function updateURL(params) {
        const newURL = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newURL);
    }

    // Reset filters
    window.resetFilters = function() {
        activeFilters = {
            categories: [],
            location: '',
            priceMin: '',
            priceMax: '',
            rating: ''
        };

        // Reset UI
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.classList.remove('active');
        });
        document.querySelectorAll('.filter-group select').forEach(select => {
            select.value = '';
        });
        document.querySelectorAll('.filter-group input').forEach(input => {
            input.value = '';
        });

        applyFilters();
    };

    // Toggle view (grid/list)
    window.toggleView = function(view) {
        const container = document.querySelector('.results-container');
        if (!container) return;

        container.classList.toggle('grid-view', view === 'grid');
        container.classList.toggle('list-view', view === 'list');

        // Update toggle buttons
        document.querySelectorAll('.view-toggle button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });

        // Save preference
        localStorage.setItem('preferredView', view);
    };

    // Setup filter pills
    function setupFilterPills() {
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                const category = this.dataset.category;
                this.classList.toggle('active');

                if (this.classList.contains('active')) {
                    activeFilters.categories.push(category);
                } else {
                    activeFilters.categories = activeFilters.categories.filter(c => c !== category);
                }

                applyFilters();
            });
        });
    }

    // Setup location filter
    function setupLocationFilter() {
        const locationSelect = document.querySelector('#location-filter');
        if (locationSelect) {
            locationSelect.addEventListener('change', function() {
                activeFilters.location = this.value;
                applyFilters();
            });
        }
    }

    // Setup price filters
    function setupPriceFilters() {
        const priceMin = document.querySelector('#price-min');
        const priceMax = document.querySelector('#price-max');

        if (priceMin) {
            priceMin.addEventListener('change', function() {
                activeFilters.priceMin = this.value;
                applyFilters();
            });
        }

        if (priceMax) {
            priceMax.addEventListener('change', function() {
                activeFilters.priceMax = this.value;
                applyFilters();
            });
        }
    }

    // Setup rating filter
    function setupRatingFilter() {
        const ratingSelect = document.querySelector('#rating-filter');
        if (ratingSelect) {
            ratingSelect.addEventListener('change', function() {
                activeFilters.rating = this.value;
                applyFilters();
            });
        }
    }

    // Load filters from URL
    function loadFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);

        if (params.has('categories')) {
            activeFilters.categories = params.get('categories').split(',');
        }
        if (params.has('location')) {
            activeFilters.location = params.get('location');
        }
        if (params.has('price_min')) {
            activeFilters.priceMin = params.get('price_min');
        }
        if (params.has('price_max')) {
            activeFilters.priceMax = params.get('price_max');
        }
        if (params.has('rating')) {
            activeFilters.rating = params.get('rating');
        }
    }

    // Initialize
    function init() {
        loadFiltersFromURL();
        setupFilterPills();
        setupLocationFilter();
        setupPriceFilters();
        setupRatingFilter();

        // Load saved view preference
        const preferredView = localStorage.getItem('preferredView') || 'grid';
        toggleView(preferredView);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
