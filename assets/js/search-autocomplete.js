/**
 * SEARCH-AUTOCOMPLETE.JS - LULU-OPEN
 * Autocomplete pour la barre de recherche
 */

(function() {
    'use strict';

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Fetch suggestions from API
    async function fetchSuggestions(query) {
        try {
            const response = await fetch(`/api/search-suggestions.php?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network error');
            return await response.json();
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            return [];
        }
    }

    // Display suggestions
    function displaySuggestions(suggestions, container) {
        if (!container) return;

        container.innerHTML = '';

        if (suggestions.length === 0) {
            container.style.display = 'none';
            return;
        }

        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `
                <i class="bi bi-${suggestion.type === 'category' ? 'tag' : 'search'}"></i>
                <span>${highlightMatch(suggestion.text, suggestion.query)}</span>
                ${suggestion.count ? `<span class="count">${suggestion.count}</span>` : ''}
            `;
            
            item.addEventListener('click', () => {
                selectSuggestion(suggestion);
                container.style.display = 'none';
            });

            container.appendChild(item);
        });

        container.style.display = 'block';
    }

    // Highlight matching text
    function highlightMatch(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    // Select a suggestion
    function selectSuggestion(suggestion) {
        const searchInput = document.querySelector('#search-input');
        if (searchInput) {
            searchInput.value = suggestion.text;
            
            if (suggestion.url) {
                window.location.href = suggestion.url;
            } else {
                searchInput.form.submit();
            }
        }
    }

    // Setup autocomplete
    function setupAutocomplete() {
        const searchInput = document.querySelector('#search-input');
        const suggestionsContainer = document.querySelector('#search-suggestions');

        if (!searchInput) return;

        // Create suggestions container if it doesn't exist
        let container = suggestionsContainer;
        if (!container) {
            container = document.createElement('div');
            container.id = 'search-suggestions';
            container.className = 'search-suggestions';
            searchInput.parentNode.appendChild(container);
        }

        // Input event with debounce
        searchInput.addEventListener('input', debounce(async (e) => {
            const query = e.target.value.trim();

            if (query.length < 2) {
                container.style.display = 'none';
                return;
            }

            // Show loading
            container.innerHTML = '<div class="suggestion-loading"><div class="spinner-sm"></div> Recherche...</div>';
            container.style.display = 'block';

            // Fetch and display suggestions
            const suggestions = await fetchSuggestions(query);
            displaySuggestions(suggestions.map(s => ({ ...s, query })), container);
        }, 300));

        // Close suggestions on outside click
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !container.contains(e.target)) {
                container.style.display = 'none';
            }
        });

        // Keyboard navigation
        let selectedIndex = -1;
        searchInput.addEventListener('keydown', (e) => {
            const items = container.querySelectorAll('.suggestion-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items, selectedIndex);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex].click();
            } else if (e.key === 'Escape') {
                container.style.display = 'none';
                selectedIndex = -1;
            }
        });
    }

    // Update selection highlight
    function updateSelection(items, index) {
        items.forEach((item, i) => {
            if (i === index) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    // Initialize
    function init() {
        setupAutocomplete();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
