/**
 * Checkout Location Dropdowns
 *
 * Handles dependent dropdowns for Country → Province/State → City
 *
 * @requires location_data.php
 */

(function() {
    'use strict';

    let locationData = {
        countries: [],
        provinces: {},
        cities: {}
    };

    /**
     * Initialize location dropdowns
     */
    function initLocationDropdowns() {
        const countrySelects = document.querySelectorAll('[data-location-country]');
        if (countrySelects.length === 0) return;

        // Load location data
        loadLocationData().then(data => {
            locationData = data;
            countrySelects.forEach(initCountrySelect);
        }).catch(err => {
            console.error('Failed to load location data:', err);
        });
    }

    /**
     * Load location data from server
     */
    async function loadLocationData() {
        // Use global base URL from header.php
        const baseUrl = window.APP_INCLUDES_URL || '/includes';
        const url = baseUrl + '/location_data.php?action=json';

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to load location data`);
        }

        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text.substring(0, 200));
            throw new Error('Invalid JSON response from server');
        }
    }

    /**
     * Initialize a country select element
     */
    function initCountrySelect(countrySelect) {
        const form = countrySelect.closest('form');
        const provinceSelect = form?.querySelector('[data-location-province]');
        const citySelect = form?.querySelector('[data-location-city]');
        const provinceLabel = form?.querySelector('[data-location-province-label]');

        if (!provinceSelect) return;

        // Populate countries
        populateCountries(countrySelect);

        // Add change event handler
        countrySelect.addEventListener('change', function() {
            const countryCode = this.value;
            updateProvinces(countryCode, provinceSelect, citySelect, provinceLabel);
        });

        // Initialize with selected country
        if (countrySelect.value) {
            updateProvinces(countrySelect.value, provinceSelect, citySelect, provinceLabel);
        }
    }

    /**
     * Populate country dropdown
     */
    function populateCountries(select) {
        const currentValue = select.value;
        const placeholder = select.getAttribute('data-placeholder') || 'Select Country';

        // Clear existing options
        select.innerHTML = `<option value="">${placeholder}</option>`;

        // Add country options
        locationData.countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.code;
            option.textContent = country.name;
            select.appendChild(option);
        });

        // Restore selected value
        if (currentValue) {
            select.value = currentValue;
        }
    }

    /**
     * Update province dropdown based on country selection
     */
    function updateProvinces(countryCode, provinceSelect, citySelect, provinceLabel) {
        const currentProvinceValue = provinceSelect.value;
        const currentCityValue = citySelect?.value;
        const placeholder = provinceSelect.getAttribute('data-placeholder') || 'Select Province/State';

        // Clear provinces
        provinceSelect.innerHTML = `<option value="">${placeholder}</option>`;

        // Clear cities if exists
        if (citySelect) {
            const cityPlaceholder = citySelect.getAttribute('data-placeholder') || 'Select City';
            citySelect.innerHTML = `<option value="">${cityPlaceholder}</option>`;
        }

        if (!countryCode || !locationData.provinces[countryCode]) {
            updateProvinceLabel(countryCode, provinceLabel);
            return;
        }

        // Populate provinces
        locationData.provinces[countryCode].forEach(province => {
            const option = document.createElement('option');
            option.value = province.code;
            option.textContent = province.name;
            provinceSelect.appendChild(option);
        });

        // Update province label
        updateProvinceLabel(countryCode, provinceLabel);

        // Restore selected province if valid for this country
        const isValidProvince = locationData.provinces[countryCode].some(p => p.code === currentProvinceValue);
        if (isValidProvince) {
            provinceSelect.value = currentProvinceValue;
            // Trigger province change to update cities
            provinceSelect.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Update province/state label based on country
     */
    function updateProvinceLabel(countryCode, labelElement) {
        if (!labelElement) return;

        const labels = {
            'ZA': 'Province',
            'NG': 'State',
            'US': 'State',
            'GB': 'Region'
        };

        labelElement.textContent = labels[countryCode] || 'Province/State';
    }

    /**
     * Initialize province select for cities
     */
    function initProvinceSelect(provinceSelect) {
        const form = provinceSelect.closest('form');
        const countrySelect = form?.querySelector('[data-location-country]');
        const citySelect = form?.querySelector('[data-location-city]');

        if (!citySelect || !countrySelect) return;

        provinceSelect.addEventListener('change', function() {
            const countryCode = countrySelect.value;
            const provinceCode = this.value;
            updateCities(countryCode, provinceCode, citySelect);
        });
    }

    /**
     * Update city dropdown based on province selection
     */
    function updateCities(countryCode, provinceCode, citySelect) {
        const currentCityValue = citySelect.value;
        const placeholder = citySelect.getAttribute('data-placeholder') || 'Select City';

        // Clear cities
        citySelect.innerHTML = `<option value="">${placeholder}</option>`;

        if (!countryCode || !provinceCode || !locationData.cities[countryCode] || !locationData.cities[countryCode][provinceCode]) {
            return;
        }

        // Populate cities
        locationData.cities[countryCode][provinceCode].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });

        // Restore selected city if valid
        const cities = locationData.cities[countryCode][provinceCode] || [];
        if (cities.includes(currentCityValue)) {
            citySelect.value = currentCityValue;
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLocationDropdowns);
    } else {
        initLocationDropdowns();
    }

    // Also initialize after any Alpine.js updates (for dynamic content)
    if (window.Alpine) {
        window.Alpine.effect(() => {
            // Re-initialize when Alpine updates DOM
            setTimeout(initLocationDropdowns, 100);
        });
    }

    // Expose public API
    window.CheckoutLocation = {
        init: initLocationDropdowns,
        refresh: initLocationDropdowns,
        loadLocationData: loadLocationData
    };

})();

/**
 * Helper function to get asset URL
 */
function assetUrl(path) {
    // Get base URL from current page
    const base = window.location.pathname.replace(/\/[^/]*$/, '');
    return base + '/../assets/' + path.replace(/^\/+/, '');
}
