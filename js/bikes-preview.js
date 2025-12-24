/**
 * UniCycle - Bikes Preview Page JavaScript
 * Handles fetching bikes, filtering, and animations
 */

// ============================================
// DOM Ready Handler
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    fetchBikes();
    initFilterButtons();
    initScrollReveal();
});

// ============================================
// Global State
// ============================================
let allBikes = [];
let currentFilter = 'all';

// ============================================
// Fetch Bikes from API
// ============================================
async function fetchBikes() {
    const grid = document.getElementById('bikes-grid');
    const emptyState = document.getElementById('empty-state');

    try {
        const response = await fetch('api/get-bikes.php');
        const data = await response.json();

        if (data.success && data.bikes.length > 0) {
            // Only show available and maintenance bikes for public preview
            allBikes = data.bikes.filter(bike =>
                bike.status === 'available' || bike.status === 'maintenance'
            );
            renderBikes(allBikes);
        } else {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
        }
    } catch (error) {
        console.error('Error fetching bikes:', error);
        grid.innerHTML = `
            <div class="loading-spinner">
                <p>Failed to load bikes. Please refresh the page.</p>
            </div>
        `;
    }
}

// ============================================
// Render Bikes
// ============================================
function renderBikes(bikes) {
    const grid = document.getElementById('bikes-grid');
    const emptyState = document.getElementById('empty-state');
    const template = document.getElementById('bike-card-template');

    // Clear grid
    grid.innerHTML = '';

    // Filter bikes based on current filter
    const filteredBikes = currentFilter === 'all'
        ? bikes
        : bikes.filter(bike => bike.status === currentFilter);

    if (filteredBikes.length === 0) {
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    // Render each bike
    filteredBikes.forEach((bike, index) => {
        const card = template.content.cloneNode(true);
        const cardElement = card.querySelector('.bike-card');

        // Set data attribute for filtering
        cardElement.setAttribute('data-status', bike.status);

        // Add staggered animation delay
        cardElement.setAttribute('data-delay', index * 100);

        // Set bike image based on type
        const bikeImg = card.querySelector('.bike-img');
        const bikeType = getBikeType(bike.bike_name);
        bikeImg.src = `assets/${bikeType}-bike.png`;
        bikeImg.alt = bike.bike_name;

        // Populate card data
        card.querySelector('.bike-name').textContent = bike.bike_name;
        card.querySelector('.location-text').textContent = bike.location || 'Main Bike Area';
        card.querySelector('.maintenance-date').textContent = bike.last_maintained_date || 'N/A';

        // Set status badge
        const statusBadge = card.querySelector('.status-badge');
        statusBadge.textContent = getStatusText(bike.status);
        statusBadge.classList.add(bike.status);

        // Update rent button based on status
        const rentBtn = card.querySelector('.rent-btn');
        if (bike.status === 'available') {
            rentBtn.href = 'login.php?redirect=available-bikes.php';
            rentBtn.textContent = 'Login to Rent';
        } else {
            rentBtn.classList.add('disabled');
            rentBtn.removeAttribute('href');
            rentBtn.textContent = getUnavailableText(bike.status);
        }

        grid.appendChild(card);
    });

    // Re-initialize scroll reveal for new elements
    initScrollReveal();
}

// ============================================
// Get Bike Type from Name
// ============================================
function getBikeType(bikeName) {
    const name = bikeName.toLowerCase();
    if (name.includes('mountain')) {
        return 'mountain';
    } else if (name.includes('city')) {
        return 'city';
    } else {
        // Default to city bike
        return 'city';
    }
}

// ============================================
// Get Status Text
// ============================================
function getStatusText(status) {
    const statusMap = {
        'available': 'Available',
        'pending': 'Pending',
        'maintenance': 'Maintenance',
        'rented': 'Rented'
    };
    return statusMap[status] || 'Unknown';
}

// ============================================
// Get Unavailable Button Text
// ============================================
function getUnavailableText(status) {
    const textMap = {
        'pending': 'Pending Approval',
        'maintenance': 'Under Maintenance',
        'rented': 'Currently Rented'
    };
    return textMap[status] || 'Unavailable';
}

// ============================================
// Filter Buttons
// ============================================
function initFilterButtons() {
    const filterButtons = document.querySelectorAll('.filter-btn');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update filter and re-render
            currentFilter = btn.dataset.filter;
            renderBikes(allBikes);
        });
    });
}

// ============================================
// Mobile Menu
// ============================================
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinksContainer = document.querySelector('.nav-links');

    if (mobileMenuBtn && navLinksContainer) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuBtn.classList.toggle('active');
            navLinksContainer.classList.toggle('active');
        });
    }
}

// ============================================
// Scroll Reveal Animation
// ============================================
function initScrollReveal() {
    const revealElements = document.querySelectorAll('.reveal:not(.active)');

    function reveal() {
        revealElements.forEach(element => {
            const windowHeight = window.innerHeight;
            const elementTop = element.getBoundingClientRect().top;
            const revealPoint = 150;

            if (elementTop < windowHeight - revealPoint) {
                const delay = element.dataset.delay || 0;

                setTimeout(() => {
                    element.classList.add('active');
                }, delay);
            }
        });
    }

    // Initial check
    reveal();

    // Throttled scroll listener
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                reveal();
                ticking = false;
            });
            ticking = true;
        }
    });
}
