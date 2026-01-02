// API endpoint
const API_URL = 'api/manageProduct.php';

// Store products in memory
let products = [];
let currentEditId = null;

// Navigation function
function navigateTo(page) {
  window.location.href = `${page}.html`;
}

// Fetch products from database
async function loadProducts() {
  try {
    console.log('Fetching from:', `${API_URL}?action=getProducts`);
    const response = await fetch(`${API_URL}?action=getProducts`);
    console.log('Response status:', response.status);
    
    const text = await response.text();
    console.log('Response text:', text);
    
    const data = JSON.parse(text);
    console.log('Parsed data:', data);
    
    if (data.success) {
      products = data.products;
      updateStats();
      filterProducts();
    } else {
      alert('Error loading products: ' + data.message);
    }
  } catch (error) {
    console.error('Error details:', error);
    alert('Failed to load products from database. Check console for details.');
  }
}

// Calculate and update statistics
function updateStats() {
  const totalProducts = products.length;
  const availableProducts = products.filter(p => p.status === 'available').length;
  const lowStockProducts = products.filter(p => p.stock > 0 && p.stock <= 10).length;
  const totalCategories = new Set(products.map(p => p.category)).size;

  document.getElementById('totalProducts').textContent = totalProducts;
  document.getElementById('availableProducts').textContent = availableProducts;
  document.getElementById('lowStockProducts').textContent = lowStockProducts;
  document.getElementById('totalCategories').textContent = totalCategories;
}

// Render products table
function renderProducts(productsToRender) {
  const tbody = document.getElementById('productsTableBody');
  
  if (productsToRender.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">No products found matching the filters.</td></tr>';
    return;
  }

  tbody.innerHTML = productsToRender.map(product => {
    const stockClass = product.stock === 0 ? 'status-unavailable' : product.stock <= 10 ? 'status-low-stock' : '';
    const stockBadge = product.stock === 0 ? '<span class="status-badge status-unavailable">Out of Stock</span>' : 
                       product.stock <= 10 ? '<span class="status-badge status-low-stock">Low Stock</span>' : 
                       product.stock;

    return `
      <tr>
        <td><img src="${product.image}" alt="${product.name}" class="product-img"></td>
        <td>${product.name}</td>
        <td>${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</td>
        <td>RM ${parseFloat(product.price).toFixed(2)}</td>
        <td>${stockBadge}</td>
        <td><span class="status-badge status-${product.status}">${product.status.charAt(0).toUpperCase() + product.status.slice(1)}</span></td>
        <td>
          <button class="btn btn-edit" onclick="editProduct(${product.id})">Edit</button>
          <button class="btn btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
        </td>
      </tr>
    `;
  }).join('');
}

// Filter products based on search and filters
function filterProducts() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const categoryFilter = document.getElementById('categoryFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  const priceFilter = document.getElementById('priceFilter').value;
  const sortFilter = document.getElementById('sortFilter').value;

  let filtered = [...products];

  // Search filter
  if (searchTerm) {
    filtered = filtered.filter(p => 
      p.name.toLowerCase().includes(searchTerm) || 
      p.category.toLowerCase().includes(searchTerm)
    );
  }

  // Category filter
  if (categoryFilter !== 'all') {
    filtered = filtered.filter(p => p.category === categoryFilter);
  }

  // Status filter
  if (statusFilter !== 'all') {
    filtered = filtered.filter(p => p.status === statusFilter);
  }

  // Price filter
  if (priceFilter !== 'all') {
    if (priceFilter === '0-20') {
      filtered = filtered.filter(p => p.price >= 0 && p.price <= 20);
    } else if (priceFilter === '20-50') {
      filtered = filtered.filter(p => p.price > 20 && p.price <= 50);
    } else if (priceFilter === '50+') {
      filtered = filtered.filter(p => p.price > 50);
    }
  }

  // Sort
  if (sortFilter === 'name-asc') {
    filtered.sort((a, b) => a.name.localeCompare(b.name));
  } else if (sortFilter === 'name-desc') {
    filtered.sort((a, b) => b.name.localeCompare(a.name));
  } else if (sortFilter === 'price-asc') {
    filtered.sort((a, b) => a.price - b.price);
  } else if (sortFilter === 'price-desc') {
    filtered.sort((a, b) => b.price - a.price);
  } else if (sortFilter === 'stock-asc') {
    filtered.sort((a, b) => a.stock - b.stock);
  } else if (sortFilter === 'stock-desc') {
    filtered.sort((a, b) => b.stock - a.stock);
  }

  renderProducts(filtered);
}

// Open modal for adding new product
function openAddModal() {
  currentEditId = null;
  document.getElementById('modalTitle').textContent = 'Add New Product';
  document.getElementById('productForm').reset();
  document.getElementById('productId').value = '';
  document.getElementById('productModal').classList.add('show');
}

// Open modal for editing product
function editProduct(id) {
  currentEditId = id;
  const product = products.find(p => p.id === id);
  
  if (product) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productCategory').value = product.category;
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productStock').value = product.stock;
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productImage').value = product.image;
    document.getElementById('productStatus').value = product.status;
    document.getElementById('productModal').classList.add('show');
  }
}

// Close modal
function closeModal() {
  document.getElementById('productModal').classList.remove('show');
  document.getElementById('productForm').reset();
  currentEditId = null;
}

// Save product (add or edit)
async function saveProduct(event) {
  event.preventDefault();

  const productData = {
    name: document.getElementById('productName').value,
    category: document.getElementById('productCategory').value,
    price: parseFloat(document.getElementById('productPrice').value),
    stock: parseInt(document.getElementById('productStock').value),
    description: document.getElementById('productDescription').value,
    image: document.getElementById('productImage').value || 'https://via.placeholder.com/50?text=Product',
    status: document.getElementById('productStatus').value
  };

  try {
    let url, method;
    
    if (currentEditId) {
      // Edit existing product
      productData.id = currentEditId;
      url = `${API_URL}?action=updateProduct`;
      method = 'POST';
    } else {
      // Add new product
      url = `${API_URL}?action=addProduct`;
      method = 'POST';
    }

    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(productData)
    });

    const data = await response.json();

    if (data.success) {
      alert(data.message);
      closeModal();
      await loadProducts(); // Reload products from database
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to save product');
  }
}

// Delete product
async function deleteProduct(id) {
  if (confirm('Are you sure you want to delete this product?')) {
    try {
      const response = await fetch(`${API_URL}?action=deleteProduct`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
      });

      const data = await response.json();

      if (data.success) {
        alert(data.message);
        await loadProducts(); // Reload products from database
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Failed to delete product');
    }
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('productModal');
  if (event.target === modal) {
    closeModal();
  }
}

// Initialize page
window.addEventListener('load', () => {
  loadProducts();
});