// Global variable to store restaurant data from database
let restaurantData = [];
let categories = [];

// Fetch restaurants from database
async function fetchRestaurants() {
    try {
        const response = await fetch('get_restaurants.php');
        const data = await response.json();
        
        if (data.success) {
            restaurantData = data.restaurants;
            console.log('Loaded', restaurantData.length, 'restaurants from database');
            console.log('Restaurant data:', restaurantData); // Debug log
            renderRestaurants(restaurantData);
        } else {
            console.error('Error loading restaurants:', data.error);
            showError('Failed to load restaurants. Please refresh the page.');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showError('Failed to connect to server. Please check your connection.');
    }
}

// Fetch categories from database
async function fetchCategories() {
    try {
        const response = await fetch('get_categories.php');
        const data = await response.json();
        
        if (data.success) {
            categories = data.categories;
            renderCategoryFilters();
        } else {
            console.error('Error loading categories:', data.error);
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

// Render category filters dynamically
function renderCategoryFilters() {
    const categoryContainer = document.querySelector('.filter-group:last-of-type');
    if (!categoryContainer) return;
    
    // Keep the label, remove old checkboxes
    const labels = categoryContainer.querySelectorAll('label');
    labels.forEach(label => label.remove());
    
    // Add new checkboxes from database
    categories.forEach(cat => {
        const label = document.createElement('label');
        label.innerHTML = `<input type="checkbox" value="${cat.Category_ID}"> ${cat.Category_Name}`;
        categoryContainer.appendChild(label);
    });
}

// Show error message
function showError(message) {
    const grid = document.getElementById('allRestaurants');
    if (grid) {
        grid.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:#d70f64; padding:40px; font-size:16px;">${message}</p>`;
    }
}

// Simple Search Bar Feedback
const searchInput = document.querySelector('.search-container input');
if (searchInput) {
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            filterRestaurants();
        }
    });
}

// Your Favorites - click handlers
document.addEventListener('DOMContentLoaded', () => {
    // Favorites already have onclick in HTML, but let's add a backup
    const favoriteCards = {
        '.sushiking-card': 'Sushiking.html',
        '.unclebob-card': 'Unclebob.html',
        '.tealive-card': 'Tealive.html',
        '.topglobal-card': 'Topglobal.html',
        '.chagee-card': 'Chagee.html'
    };
    
    Object.entries(favoriteCards).forEach(([selector, link]) => {
        const card = document.querySelector(selector);
        if (card && !card.onclick) {
            card.addEventListener('click', () => window.location.href = link);
        }
    });
});

// Add this to your existing fetch calls in the DOMContentLoaded listener
async function fetchTopBrands() {
    try {
        const response = await fetch('get_top_brands.php');
        const data = await response.json();
        
        if (data.success) {
            renderTopBrands(data.brands);
        }
    } catch (error) {
        console.error('Error fetching top brands:', error);
    }
}

function renderTopBrands(brands) {
    const container = document.getElementById('topBrandsContainer');
    if (!container) return;

    if (brands.length === 0) {
        container.innerHTML = '<p style="color:#999;">No top brands available yet.</p>';
        return;
    }

    container.innerHTML = brands.map(brand => `
        <div class="brand-card" onclick="window.location.href='${brand.link || 'comingsoon.html'}'">
            <img src="${brand.logo || 'placeholder.jpg'}" alt="${brand.name}" 
                 onerror="this.src='https://via.placeholder.com/90?text=${encodeURIComponent(brand.name)}'">
        </div>
    `).join('');
}

// Update your existing initialization block
document.addEventListener('DOMContentLoaded', async () => {
    await fetchCategories();
    await fetchRestaurants();
    await fetchTopBrands(); // Add this line
});

// Top Brands - click handlers
document.addEventListener('DOMContentLoaded', () => {
    const topBrandCards = document.querySelectorAll('.brand-grid:nth-of-type(2) .brand-card');
    topBrandCards.forEach(card => {
        if (!card.onclick) {
            card.addEventListener('click', () => window.location.href = 'comingsoon.html');
        }
    });
});

// --- FILTER FUNCTIONALITY ---
function filterRestaurants() {
    // Get sort value
    const sortBy = document.querySelector('input[name="sort"]:checked')?.parentElement.textContent.trim();
    
    // Get price range
    const minPrice = parseFloat(document.querySelector('.price-inputs input:first-child').value) || 0;
    const maxPrice = parseFloat(document.querySelector('.price-inputs input:last-child').value) || Infinity;
    
    // Get search query
    const searchQuery = searchInput?.value.toLowerCase() || '';
    
    // Get selected category IDs
    const selectedCategoryIds = Array.from(document.querySelectorAll('.filter-group:last-of-type input[type="checkbox"]:checked'))
        .map(cb => parseInt(cb.value));

    console.log('Filters Applied:', {
        sortBy,
        minPrice,
        maxPrice,
        searchQuery,
        selectedCategoryIds
    });

    // Filter restaurants - ALL CONDITIONS
    let filtered = restaurantData.filter(restaurant => {
        // 1. Search filter
        const matchesSearch = searchQuery === '' || restaurant.name.toLowerCase().includes(searchQuery);
        
        // 2. Price filter
        const matchesPrice = restaurant.price >= minPrice && restaurant.price <= maxPrice;
        
        // 3. Category filter - handle null category_id
        const matchesCategory = selectedCategoryIds.length === 0 || 
                               (restaurant.category_id && selectedCategoryIds.includes(parseInt(restaurant.category_id)));
        
        // Must match ALL filters
        return matchesSearch && matchesPrice && matchesCategory;
    });

    // Apply sorting
    if (sortBy === 'Popularity') {
        filtered.sort((a, b) => b.rating - a.rating);
    } else if (sortBy === 'Distance') {
        filtered.sort((a, b) => a.distance - b.distance);
    }
    // 'Relevance' keeps original order

    console.log('Filtered results:', filtered.length, 'restaurants');

    // Render filtered results
    renderRestaurants(filtered);
}

function renderRestaurants(restaurants) {
    const grid = document.getElementById('allRestaurants');
    if (!grid) return;

    grid.innerHTML = restaurants.map(r => {
        // Use 'logo' from your SQL database
        const image = r.logo || 'placeholder.jpg';
        const rating = parseFloat(r.rating || 0).toFixed(1);
        
        // Ensure the link matches your DB 'link' column
        const link = r.link || 'userComingSoon.html';

        return `
            <div class="restaurant-item" onclick="window.location.href='${link}'">
                <div class="res-image-wrapper">
                    <img src="${image}" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                </div>
                <div class="res-details">
                    <h3>${r.name}</h3>
                    <p class="res-info">$$ • ${r.category || 'Restaurant'}</p>
                    <div class="res-rating">⭐ ${rating}</div>
                </div>
            </div>
        `;
    }).join('');
}
    
    console.log('Rendered', restaurants.length, 'restaurants');


// Initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Page loaded, initializing...');
    
    // Fetch data from database
    await fetchCategories();
    await fetchRestaurants();
    
    // Apply Filters button
    const applyBtn = document.querySelector('.apply-filters-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', filterRestaurants);
    }

    // Also allow Enter key in price inputs
    document.querySelectorAll('.price-inputs input').forEach(input => {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                filterRestaurants();
            }
        });
    });
    
    console.log('Filter system initialized with database connection');
});