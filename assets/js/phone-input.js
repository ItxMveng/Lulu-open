// Indicatifs téléphoniques avec drapeaux CSS
const phoneCountries = {
    'FR': { code: '+33', flag: 'FR', name: 'France' },
    'US': { code: '+1', flag: 'US', name: 'États-Unis' },
    'CA': { code: '+1', flag: 'CA', name: 'Canada' },
    'GB': { code: '+44', flag: 'GB', name: 'Royaume-Uni' },
    'DE': { code: '+49', flag: 'DE', name: 'Allemagne' },
    'ES': { code: '+34', flag: 'ES', name: 'Espagne' },
    'IT': { code: '+39', flag: 'IT', name: 'Italie' },
    'BE': { code: '+32', flag: 'BE', name: 'Belgique' },
    'CH': { code: '+41', flag: 'CH', name: 'Suisse' },
    'MA': { code: '+212', flag: 'MA', name: 'Maroc' },
    'DZ': { code: '+213', flag: 'DZ', name: 'Algérie' },
    'TN': { code: '+216', flag: 'TN', name: 'Tunisie' },
    'SN': { code: '+221', flag: 'SN', name: 'Sénégal' },
    'CI': { code: '+225', flag: 'CI', name: 'Côte d\'Ivoire' },
    'CM': { code: '+237', flag: 'CM', name: 'Cameroun' },
    'AU': { code: '+61', flag: 'AU', name: 'Australie' },
    'JP': { code: '+81', flag: 'JP', name: 'Japon' },
    'CN': { code: '+86', flag: 'CN', name: 'Chine' },
    'IN': { code: '+91', flag: 'IN', name: 'Inde' },
    'BR': { code: '+55', flag: 'BR', name: 'Brésil' },
    'MX': { code: '+52', flag: 'MX', name: 'Mexique' }
};

// Fonction pour générer le drapeau CSS
function getFlagHTML(countryCode) {
    return `<span class="flag-icon flag-icon-${countryCode.toLowerCase()}"></span>`;
}

function initPhoneInput(inputId) {
    const phoneInput = document.getElementById(inputId);
    if (!phoneInput) return;

    // Créer le conteneur du sélecteur de pays
    const phoneContainer = document.createElement('div');
    phoneContainer.className = 'phone-input-container';
    
    const countrySelector = document.createElement('div');
    countrySelector.className = 'country-selector';
    countrySelector.innerHTML = `
        <div class="selected-country" id="selectedCountry">
            <span class="flag-display">FR</span>
            <span class="code">+33</span>
            <i class="bi bi-chevron-down"></i>
        </div>
        <div class="country-dropdown" id="countryDropdown" style="display: none;">
            ${Object.entries(phoneCountries).map(([code, country]) => `
                <div class="country-option" data-code="${code}">
                    <span class="flag-display">${country.flag}</span>
                    <span class="name">${country.name}</span>
                    <span class="code">${country.code}</span>
                </div>
            `).join('')}
        </div>
    `;

    // Remplacer l'input par le nouveau conteneur
    phoneInput.parentNode.insertBefore(phoneContainer, phoneInput);
    phoneContainer.appendChild(countrySelector);
    phoneContainer.appendChild(phoneInput);

    // Modifier l'input
    phoneInput.placeholder = '6 12 34 56 78';
    phoneInput.className += ' phone-number-input';

    // Détecter le pays de l'utilisateur
    fetch('../../api/get-user-country.php')
        .then(response => response.json())
        .then(data => {
            if (data.country_code && phoneCountries[data.country_code]) {
                updateSelectedCountry(data.country_code);
            }
        })
        .catch(() => {
            // Garder la France par défaut
        });

    // Gestion des événements
    const selectedCountry = document.getElementById('selectedCountry');
    const dropdown = document.getElementById('countryDropdown');

    selectedCountry.addEventListener('click', () => {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });

    // Sélection d'un pays
    dropdown.addEventListener('click', (e) => {
        const option = e.target.closest('.country-option');
        if (option) {
            const countryCode = option.dataset.code;
            updateSelectedCountry(countryCode);
            dropdown.style.display = 'none';
        }
    });

    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!phoneContainer.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    function updateSelectedCountry(countryCode) {
        const country = phoneCountries[countryCode];
        if (country) {
            selectedCountry.textContent = '';
            
            const flagSpan = document.createElement('span');
            flagSpan.className = 'flag-display';
            flagSpan.textContent = country.flag;
            
            const codeSpan = document.createElement('span');
            codeSpan.className = 'code';
            codeSpan.textContent = country.code;
            
            const chevron = document.createElement('i');
            chevron.className = 'bi bi-chevron-down';
            
            selectedCountry.appendChild(flagSpan);
            selectedCountry.appendChild(codeSpan);
            selectedCountry.appendChild(chevron);
            
            // Créer un input hidden pour stocker l'indicatif
            let hiddenInput = document.getElementById('phone_country_code');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'phone_country_code';
                hiddenInput.name = 'phone_country_code';
                phoneContainer.appendChild(hiddenInput);
            }
            hiddenInput.value = country.code;
        }
    }
}

// CSS intégré
const phoneInputCSS = `
.phone-input-container {
    position: relative;
    display: flex;
    border: 2px solid #E9ECEF;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.phone-input-container:focus-within {
    border-color: #0099FF;
    box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.25);
}

.country-selector {
    position: relative;
}

.selected-country {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    cursor: pointer;
    min-width: 100px;
    user-select: none;
}

.selected-country:hover {
    background: #e9ecef;
}

.country-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.country-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.country-option:hover {
    background: #f8f9fa;
}

.country-option:last-child {
    border-bottom: none;
}

.phone-number-input {
    flex: 1;
    border: none !important;
    outline: none !important;
    padding: 0.75rem 1rem !important;
    box-shadow: none !important;
}

.flag-display {
    display: inline-block;
    width: 24px;
    height: 16px;
    background: #0099FF;
    color: white;
    text-align: center;
    font-size: 10px;
    line-height: 16px;
    border-radius: 2px;
    font-weight: bold;
}

.code {
    font-weight: 500;
    color: #0099FF;
}

.name {
    flex: 1;
}
`;

// Injecter le CSS
const style = document.createElement('style');
style.textContent = phoneInputCSS;
document.head.appendChild(style);