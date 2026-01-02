let restaurantData = []; // Start empty
let categories = [];

// Modern Fetch Function
async function loadPageData() {
    try {
        // 1. Fetch Categories
        const catRes = await fetch('get_categories.php');
             catData = await catRes.json();
        if(catData.success) {
            renderCategoryFilters(catData.categories);
        }

        // 2. Fetch Restaurants
        const resRes = await fetch('get_restaurants.php');
        const resData = await resRes.json();
        if(resData.success) {
            restaurantData = resData.restaurants;
            renderRestaurants(restaurantData);
        }
    } catch (error) {
        console.error("Modernization Error:", error);
    }
}

// Dynamic Rendering Function
function renderCategoryFilters(catList) {
    const container = document.getElementById('categoryContainer');
    catList.forEach(cat => {
        const label = document.createElement('label');
        label.innerHTML = `<input type="checkbox" value="${cat.Category_ID}"> ${cat.Category_Name}`;
        container.appendChild(label);
    });
}

function renderTopBrands(brands) {
    const container = document.getElementById('topBrandsContainer');
    if (!container) return;

    container.innerHTML = brands.map(brand => `
        <div class="brand-card">
            <div class="popularity-badge">#1 Popular</div>
            <img src="${brand.logo}" alt="${brand.name}">
            <p>${brand.name}</p>
            <span class="order-count">${brand.total_orders} orders recently</span>
        </div>
    `).join('');
}

// Update cart count from database on page load
async function updateCartCount() {
    try {
        const response = await fetch('get_cart_count.php');
        const data = await response.json();
        
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            el.textContent = data.count || 0;
        });
    } catch (error) {
        console.error('Error updating cart count:', error);
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

// --- FILTER FUNCTIONALITY ---
function filterRestaurants() {
    // Get sort value
    const sortBy = document.querySelector('input[name="sort"]:checked')?.parentElement.textContent.trim();
    
    // Get price range
    const minPrice = parseFloat(document.querySelector('.price-inputs input:first-child').value) || 0;
    const maxPrice = parseFloat(document.querySelector('.price-inputs input:last-child').value) || Infinity;
    
    // Get search query
    const searchQuery = searchInput?.value.toLowerCase() || '';
    
    // Get selected categories
    const selectedCategories = Array.from(document.querySelectorAll('.filter-group:last-of-type input[type="checkbox"]:checked'))
        .map(cb => cb.parentElement.textContent.trim());

    console.log('Filters Applied:', {
        sortBy,
        minPrice,
        maxPrice,
        searchQuery,
        selectedCategories
    });

    // Filter restaurants - ALL CONDITIONS
    let filtered = restaurantData.filter(restaurant => {
        // 1. Search filter
        const matchesSearch = searchQuery === '' || restaurant.name.toLowerCase().includes(searchQuery);
        
        // 2. Price filter
        const matchesPrice = restaurant.price >= minPrice && restaurant.price <= maxPrice;
        
        // 3. Category filter - check if any category matches
        const matchesCategory = selectedCategories.length === 0 || 
            selectedCategories.some(cat => restaurant.category.includes(cat));
        
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
// Replace your current DOMContentLoaded block with this:
document.addEventListener('DOMContentLoaded', async () => {
    // 1. Fetch real-time data from database first
    await loadPageData(); 
    
    // 2. Update cart count
    updateCartCount();
    
    // 3. Setup Filter button listener
    const applyBtn = document.querySelector('.apply-filters-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', filterRestaurants);
    }

    // 4. Setup Enter key listeners for price inputs
    document.querySelectorAll('.price-inputs input').forEach(input => {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                filterRestaurants();
            }
        });
    });
    
    console.log('Page initialized with dynamic database data');
});