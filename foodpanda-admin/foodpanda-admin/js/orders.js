// API endpoint
const API_URL = 'api/orders.php';

// Store orders in memory
let orders = [];
let filteredOrders = [];
let flatpickrInstance = null;
let revenueChart = null;
let statusChart = null;
let statsData = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    loadOrders();
});

// Initialize Flatpickr Calendar Widget
function initializeCalendar() {
  flatpickrInstance = flatpickr("#customDateRange", {
    mode: "range",
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        console.log('Custom date range selected:', dateStr);
        filterOrders();
      }
    }
  });
}

// Handle time period filter change
function handleTimePeriodChange() {
  const timePeriod = document.getElementById('timePeriodFilter').value;
  const customDateGroup = document.getElementById('customDateGroup');
  const yearFilterGroup = document.getElementById('yearFilterGroup');
  const monthFilterGroup = document.getElementById('monthFilterGroup');
  
  if (timePeriod === 'custom') {
    customDateGroup.style.display = 'block';
    yearFilterGroup.style.display = 'none';
    monthFilterGroup.style.display = 'none';
  } else {
    customDateGroup.style.display = 'none';
    yearFilterGroup.style.display = 'block';
    monthFilterGroup.style.display = 'block';
    if (flatpickrInstance) {
      flatpickrInstance.clear();
    }
  }
  
  filterOrders();
}

// Navigation function
function navigateTo(page) {
  window.location.href = `${page}.html`;
}

// Generate dummy orders for development
function generateDummyOrders() {
  const dummyOrders = [];
  const customers = ['Ahmad Ali', 'Siti Nurhaliza', 'Lee Wei Ming', 'Kumar Raj', 'Fatimah Hassan', 'Chen Xiao'];
  const statuses = ['delivered', 'pending', 'confirmed', 'preparing', 'out-for-delivery'];
  const paymentStatuses = ['paid', 'pending', 'paid', 'paid'];
  const paymentMethods = ['Credit Card', 'Online Banking', 'E-Wallet', 'Cash on Delivery'];
  
  for (let i = 1; i <= 100; i++) {
    const daysAgo = Math.floor(Math.random() * 365);
    const date = new Date();
    date.setDate(date.getDate() - daysAgo);
    date.setHours(Math.floor(Math.random() * 24), Math.floor(Math.random() * 60), 0, 0);
    
    const total = (Math.random() * 100 + 20).toFixed(2);
    const itemCount = Math.floor(Math.random() * 5) + 1;
    const statusIndex = Math.floor(Math.random() * statuses.length);
    
    dummyOrders.push({
      id: 1000 + i,
      customer: customers[Math.floor(Math.random() * customers.length)],
      customerId: `CUST-${String(Math.floor(Math.random() * 100)).padStart(3, '0')}`,
      email: `user${i}@example.com`,
      phone: `+6012345${String(i).padStart(4, '0')}`,
      total: parseFloat(total),
      orderStatus: statuses[statusIndex],
      orderDate: date.toISOString(),
      paymentMethod: paymentMethods[Math.floor(Math.random() * paymentMethods.length)],
      paymentStatus: statuses[statusIndex] === 'delivered' ? 'paid' : paymentStatuses[Math.floor(Math.random() * paymentStatuses.length)],
      deliveryAddress: '123 Jalan Ampang, Kuala Lumpur, 50450',
      items: Array.from({ length: itemCount }, (_, idx) => ({
        name: `Food Item ${idx + 1}`,
        quantity: Math.floor(Math.random() * 3) + 1,
        price: parseFloat((Math.random() * 30 + 10).toFixed(2))
      })),
      subtotal: parseFloat(total) * 0.85,
      deliveryFee: 5.00,
      tax: parseFloat(total) - (parseFloat(total) * 0.85) - 5.00
    });
  }
  
  return dummyOrders.sort((a, b) => new Date(b.orderDate) - new Date(a.orderDate));
}

// Fetch orders from database
async function loadOrders() {
  try {
    console.log('Fetching from:', `${API_URL}?action=getOrders`);
    const response = await fetch(`${API_URL}?action=getOrders`);
    console.log('Response status:', response.status);
    
    const text = await response.text();
    console.log('Response text:', text);
    
    const data = JSON.parse(text);
    console.log('Parsed data:', data);
    
    if (data.success) {
      orders = data.orders;
      filteredOrders = [...orders];
      updateStats();
      updateStatisticsTable();
      filterOrders();
      createCharts();
    } else {
      console.log('API returned error, using dummy data');
      loadDummyOrders();
    }
  } catch (error) {
    console.error('Error details:', error);
    loadDummyOrders();
  }
}

// Load dummy orders for development
function loadDummyOrders() {
  console.log('Loading dummy orders for development...');
  orders = generateDummyOrders();
  filteredOrders = [...orders];
  updateStats();
  updateStatisticsTable();
  filterOrders();
  createCharts();
  console.log('✅ Loaded', orders.length, 'dummy orders');
}

// Calculate date range based on time period filter
function calculateDateRange(timePeriod) {
  const today = new Date();
  const start = new Date();
  const end = new Date();
  
  switch(timePeriod) {
    case 'today':
      start.setHours(0, 0, 0, 0);
      end.setHours(23, 59, 59, 999);
      break;
    case 'yesterday':
      start.setDate(today.getDate() - 1);
      start.setHours(0, 0, 0, 0);
      end.setDate(today.getDate() - 1);
      end.setHours(23, 59, 59, 999);
      break;
    case 'this_week':
      const dayOfWeek = today.getDay();
      const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
      start.setDate(diff);
      start.setHours(0, 0, 0, 0);
      end.setHours(23, 59, 59, 999);
      break;
    case 'last_week':
      const lastWeekStart = new Date(today);
      lastWeekStart.setDate(today.getDate() - today.getDay() - 6);
      lastWeekStart.setHours(0, 0, 0, 0);
      const lastWeekEnd = new Date(lastWeekStart);
      lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
      lastWeekEnd.setHours(23, 59, 59, 999);
      return { start: lastWeekStart, end: lastWeekEnd };
    case 'this_month':
      start.setDate(1);
      start.setHours(0, 0, 0, 0);
      end.setHours(23, 59, 59, 999);
      break;
    case 'last_month':
      start.setMonth(today.getMonth() - 1);
      start.setDate(1);
      start.setHours(0, 0, 0, 0);
      end.setMonth(today.getMonth());
      end.setDate(0);
      end.setHours(23, 59, 59, 999);
      break;
    case 'this_year':
      start.setMonth(0);
      start.setDate(1);
      start.setHours(0, 0, 0, 0);
      end.setHours(23, 59, 59, 999);
      break;
    case 'last_year':
      start.setFullYear(today.getFullYear() - 1);
      start.setMonth(0);
      start.setDate(1);
      start.setHours(0, 0, 0, 0);
      end.setFullYear(today.getFullYear() - 1);
      end.setMonth(11);
      end.setDate(31);
      end.setHours(23, 59, 59, 999);
      break;
    default:
      return null;
  }
  
  return { start, end };
}

// Calculate and update statistics
function updateStats() {
  const totalOrders = filteredOrders.length;
  const pendingOrders = filteredOrders.filter(o => 
    o.orderStatus === 'pending' || 
    o.orderStatus === 'confirmed' || 
    o.orderStatus === 'preparing'
  ).length;
  const totalRevenue = filteredOrders
    .filter(o => o.paymentStatus === 'completed' || o.paymentStatus === 'paid')
    .reduce((sum, o) => sum + o.total, 0);
  const avgOrderValue = filteredOrders.length > 0 
    ? filteredOrders.reduce((sum, o) => sum + o.total, 0) / filteredOrders.length 
    : 0;

  document.getElementById('totalOrders').textContent = totalOrders;
  document.getElementById('pendingOrders').textContent = pendingOrders;
  document.getElementById('totalRevenue').textContent = 'RM ' + totalRevenue.toFixed(2);
  document.getElementById('avgOrderValue').textContent = 'RM ' + avgOrderValue.toFixed(2);
  
  // Update order count in table header
  const orderCount = document.getElementById('orderCount');
  if (orderCount) {
    orderCount.textContent = `Showing ${filteredOrders.length} order${filteredOrders.length !== 1 ? 's' : ''}`;
  }
}

// Generate statistics table by month
function updateStatisticsTable() {
  const tbody = document.getElementById('statsTableBody');
  const year = document.getElementById('yearFilter').value;
  
  // Group orders by year-month
  const monthlyStats = {};
  
  filteredOrders.forEach(order => {
    const date = new Date(order.orderDate);
    const yearMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    
    if (!monthlyStats[yearMonth]) {
      monthlyStats[yearMonth] = {
        total: 0,
        completed: 0,
        pending: 0,
        cancelled: 0,
        revenue: 0
      };
    }
    
    monthlyStats[yearMonth].total++;
    
    if (order.orderStatus === 'delivered') {
      monthlyStats[yearMonth].completed++;
    } else if (order.orderStatus === 'pending' || order.orderStatus === 'confirmed' || order.orderStatus === 'preparing') {
      monthlyStats[yearMonth].pending++;
    } else if (order.orderStatus === 'cancelled') {
      monthlyStats[yearMonth].cancelled++;
    }
    
    if (order.paymentStatus === 'completed' || order.paymentStatus === 'paid') {
      monthlyStats[yearMonth].revenue += order.total;
    }
  });
  
  // Sort by year-month descending
  const sortedMonths = Object.keys(monthlyStats).sort().reverse();
  
  if (sortedMonths.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #718096;">No data available for selected period</td></tr>';
    statsData = [];
    return;
  }
  
  // Store stats data for sorting
  statsData = sortedMonths.map((yearMonth, index) => {
    const stats = monthlyStats[yearMonth];
    const avgOrder = stats.total > 0 ? stats.revenue / stats.total : 0;
    const [y, m] = yearMonth.split('-');
    const monthName = new Date(y, m - 1, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' });
    
    // Calculate growth vs previous month
    let growth = 0;
    if (index < sortedMonths.length - 1) {
      const prevStats = monthlyStats[sortedMonths[index + 1]];
      if (prevStats && prevStats.revenue > 0) {
        growth = ((stats.revenue - prevStats.revenue) / prevStats.revenue) * 100;
      }
    }
    
    return {
      period: monthName,
      yearMonth,
      ...stats,
      avgOrder,
      growth
    };
  });
  
  renderStatsTable(statsData);
}

// Render statistics table
function renderStatsTable(data) {
  const tbody = document.getElementById('statsTableBody');
  
  tbody.innerHTML = data.map(stats => {
    const growthClass = stats.growth > 0 ? 'growth-positive' : stats.growth < 0 ? 'growth-negative' : '';
    const growthIcon = stats.growth > 0 ? '↑' : stats.growth < 0 ? '↓' : '→';
    
    return `
      <tr>
        <td><strong>${stats.period}</strong></td>
        <td>${stats.total}</td>
        <td><span class="status-badge status-delivered">${stats.completed}</span></td>
        <td><span class="status-badge status-pending">${stats.pending}</span></td>
        <td><span class="status-badge status-cancelled">${stats.cancelled}</span></td>
        <td><strong style="color: #d70f64;">RM ${stats.revenue.toFixed(2)}</strong></td>
        <td>RM ${stats.avgOrder.toFixed(2)}</td>
        <td>${stats.growth !== 0 ? `<span class="growth-badge ${growthClass}">${growthIcon} ${Math.abs(stats.growth).toFixed(1)}%</span>` : '-'}</td>
      </tr>
    `;
  }).join('');
}

// Sort statistics table
let statsSortDirection = {};
function sortStatsTable(column) {
  if (statsData.length === 0) return;
  
  const currentDirection = statsSortDirection[column] || 'desc';
  const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
  statsSortDirection = { [column]: newDirection };
  
  const sorted = [...statsData].sort((a, b) => {
    let valA = a[column];
    let valB = b[column];
    
    if (column === 'period') {
      valA = a.yearMonth;
      valB = b.yearMonth;
    }
    
    if (typeof valA === 'string') {
      return newDirection === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
    } else {
      return newDirection === 'asc' ? valA - valB : valB - valA;
    }
  });
  
  renderStatsTable(sorted);
}

// Create charts
function createCharts() {
  // Destroy existing charts
  if (revenueChart) revenueChart.destroy();
  if (statusChart) statusChart.destroy();
  
  // Prepare revenue trend data (last 7 days or months)
  const revenueData = {};
  const last7Days = [];
  for (let i = 6; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateStr = date.toISOString().split('T')[0];
    last7Days.push(dateStr);
    revenueData[dateStr] = 0;
  }
  
  filteredOrders.forEach(order => {
    const dateStr = new Date(order.orderDate).toISOString().split('T')[0];
    if (revenueData[dateStr] !== undefined && (order.paymentStatus === 'paid' || order.paymentStatus === 'completed')) {
      revenueData[dateStr] += order.total;
    }
  });
  
  const revenueValues = last7Days.map(date => revenueData[date]);
  const revenueLabels = last7Days.map(date => new Date(date).toLocaleDateString('en-MY', { day: 'numeric', month: 'short' }));
  
  // Revenue Trend Chart
  const revenueCtx = document.getElementById('revenueChart').getContext('2d');
  revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
      labels: revenueLabels,
      datasets: [{
        label: 'Revenue (RM)',
        data: revenueValues,
        borderColor: '#d70f64',
        backgroundColor: 'rgba(215, 15, 100, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: '#2d3748' } }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#4a5568' },
          grid: { color: '#e2e8f0' }
        },
        x: {
          ticks: { color: '#4a5568' },
          grid: { color: '#e2e8f0' }
        }
      }
    }
  });
  
  // Status Distribution Chart
  const statusCounts = {
    delivered: filteredOrders.filter(o => o.orderStatus === 'delivered').length,
    pending: filteredOrders.filter(o => o.orderStatus === 'pending').length,
    confirmed: filteredOrders.filter(o => o.orderStatus === 'confirmed').length,
    preparing: filteredOrders.filter(o => o.orderStatus === 'preparing').length,
    cancelled: filteredOrders.filter(o => o.orderStatus === 'cancelled').length
  };
  
  const statusCtx = document.getElementById('statusChart').getContext('2d');
  statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Delivered', 'Pending', 'Confirmed', 'Preparing', 'Cancelled'],
      datasets: [{
        data: [statusCounts.delivered, statusCounts.pending, statusCounts.confirmed, statusCounts.preparing, statusCounts.cancelled],
        backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#2d3748', padding: 15 }
        }
      }
    }
  });
}

// Format date and time for display
function formatDateTime(dateString) {
  const date = new Date(dateString);
  const day = date.getDate();
  const month = date.toLocaleString('en-US', { month: 'short' });
  const year = date.getFullYear();
  const time = date.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
  return `${day} ${month} ${year}<br><small style="color: #718096;">${time}</small>`;
}

// Render orders table
function renderOrders(ordersToRender) {
  const tbody = document.getElementById('ordersTableBody');
  
  if (ordersToRender.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #718096;">No orders found matching the filters.</td></tr>';
    return;
  }

  tbody.innerHTML = ordersToRender.map(order => {
    const itemCount = order.items ? order.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
    const displayPaymentStatus = order.paymentStatus === 'completed' ? 'paid' : order.paymentStatus;
    
    return `
      <tr>
        <td><strong>#ORD-${order.id}</strong></td>
        <td>${order.customer}</td>
        <td>${itemCount > 0 ? itemCount : '-'} item${itemCount !== 1 ? 's' : ''}</td>
        <td><strong style="color: #d70f64;">RM ${parseFloat(order.total).toFixed(2)}</strong></td>
        <td><span class="status-badge payment-${displayPaymentStatus}">${displayPaymentStatus.charAt(0).toUpperCase() + displayPaymentStatus.slice(1)}</span></td>
        <td><span class="status-badge status-${order.orderStatus}">${formatStatus(order.orderStatus)}</span></td>
        <td>${formatDateTime(order.orderDate)}</td>
        <td>
          <button class="btn btn-view" onclick="viewOrder(${order.id})">View</button>
          <button class="btn btn-update" onclick="openStatusModal(${order.id})">Update</button>
        </td>
      </tr>
    `;
  }).join('');
}

// Format status for display
function formatStatus(status) {
  return status.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

// Filter orders based on all criteria
function filterOrders() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const timePeriodFilter = document.getElementById('timePeriodFilter').value;
  const yearFilter = document.getElementById('yearFilter').value;
  const monthFilter = document.getElementById('monthFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  const paymentFilter = document.getElementById('paymentFilter').value;
  const sortFilter = document.getElementById('sortFilter').value;

  let filtered = [...orders];

  // Time period filter
  if (timePeriodFilter !== 'all') {
    if (timePeriodFilter === 'custom') {
      const customDateRange = document.getElementById('customDateRange').value;
      if (customDateRange && customDateRange.includes('to')) {
        const dates = customDateRange.split(' to ');
        const startDate = new Date(dates[0]);
        const endDate = new Date(dates[1]);
        endDate.setHours(23, 59, 59, 999);
        
        filtered = filtered.filter(o => {
          const orderDate = new Date(o.orderDate);
          return orderDate >= startDate && orderDate <= endDate;
        });
      }
    } else {
      const dateRange = calculateDateRange(timePeriodFilter);
      if (dateRange) {
        filtered = filtered.filter(o => {
          const orderDate = new Date(o.orderDate);
          return orderDate >= dateRange.start && orderDate <= dateRange.end;
        });
      }
    }
  }

  // Year filter (only if not using time period filter)
  if (timePeriodFilter === 'all' && yearFilter !== 'all') {
    filtered = filtered.filter(o => {
      const orderYear = new Date(o.orderDate).getFullYear().toString();
      return orderYear === yearFilter;
    });
  }

  // Month filter (only if not using time period filter)
  if (timePeriodFilter === 'all' && monthFilter !== 'all') {
    filtered = filtered.filter(o => {
      const orderMonth = (new Date(o.orderDate).getMonth() + 1).toString();
      return orderMonth === monthFilter;
    });
  }

  // Search filter
  if (searchTerm) {
    filtered = filtered.filter(o => 
      o.id.toString().includes(searchTerm) || 
      o.customer.toLowerCase().includes(searchTerm)
    );
  }

  // Status filter
  if (statusFilter !== 'all') {
    filtered = filtered.filter(o => o.orderStatus === statusFilter);
  }

  // Payment filter
  if (paymentFilter !== 'all') {
    filtered = filtered.filter(o => {
      const status = o.paymentStatus === 'completed' ? 'paid' : o.paymentStatus;
      return status === paymentFilter;
    });
  }

  // Sort
  if (sortFilter === 'date-newest') {
    filtered.sort((a, b) => new Date(b.orderDate) - new Date(a.orderDate));
  } else if (sortFilter === 'date-oldest') {
    filtered.sort((a, b) => new Date(a.orderDate) - new Date(b.orderDate));
  } else if (sortFilter === 'amount-high') {
    filtered.sort((a, b) => b.total - a.total);
  } else if (sortFilter === 'amount-low') {
    filtered.sort((a, b) => a.total - b.total);
  }

  filteredOrders = filtered;
  updateStats();
  updateStatisticsTable();
  renderOrders(filtered);
  createCharts();
}

// Reset all filters
function resetFilters() {
  document.getElementById('timePeriodFilter').value = 'this_month';
  document.getElementById('yearFilter').value = '2025';
  document.getElementById('monthFilter').value = 'all';
  document.getElementById('statusFilter').value = 'all';
  document.getElementById('paymentFilter').value = 'all';
  document.getElementById('sortFilter').value = 'date-newest';
  document.getElementById('searchInput').value = '';
  
  if (flatpickrInstance) {
    flatpickrInstance.clear();
  }
  
  handleTimePeriodChange();
  
  filteredOrders = [...orders];
  updateStats();
  updateStatisticsTable();
  renderOrders(orders);
  createCharts();
  
  console.log('✅ Filters reset');
}

// View order details
function viewOrder(id) {
  const order = orders.find(o => o.id === id);
  
  if (order) {
    const detailsContent = document.getElementById('orderDetailsContent');
    const displayPaymentStatus = order.paymentStatus === 'completed' ? 'paid' : order.paymentStatus;
    
    let itemsHTML = '';
    if (order.items && order.items.length > 0) {
      itemsHTML = order.items.map(item => `
        <tr>
          <td>${item.name}</td>
          <td>${item.quantity}</td>
          <td>RM ${item.price.toFixed(2)}</td>
          <td><strong>RM ${(item.quantity * item.price).toFixed(2)}</strong></td>
        </tr>
      `).join('');
    } else {
      itemsHTML = '<tr><td colspan="4" style="text-align: center; color: #718096;">No item details available</td></tr>';
    }

    detailsContent.innerHTML = `
      <div class="detail-section">
        <h3>Order Information</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Order ID</span>
            <span class="detail-value">#ORD-${order.id}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Order Date</span>
            <span class="detail-value">${new Date(order.orderDate).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Order Status</span>
            <span class="detail-value"><span class="status-badge status-${order.orderStatus}">${formatStatus(order.orderStatus)}</span></span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Status</span>
            <span class="detail-value"><span class="status-badge payment-${displayPaymentStatus}">${displayPaymentStatus.charAt(0).toUpperCase() + displayPaymentStatus.slice(1)}</span></span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h3>Customer Information</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Customer Name</span>
            <span class="detail-value">${order.customer}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Customer ID</span>
            <span class="detail-value">${order.customerId}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Email</span>
            <span class="detail-value">${order.email}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Phone</span>
            <span class="detail-value">${order.phone}</span>
          </div>
          <div class="detail-item" style="grid-column: 1 / -1;">
            <span class="detail-label">Delivery Address</span>
            <span class="detail-value">${order.deliveryAddress}</span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h3>Order Items</h3>
        <table class="items-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${itemsHTML}
          </tbody>
        </table>

        <div class="order-summary">
          <div class="summary-row">
            <span>Subtotal:</span>
            <span>RM ${order.subtotal.toFixed(2)}</span>
          </div>
          <div class="summary-row">
            <span>Delivery Fee:</span>
            <span>RM ${order.deliveryFee.toFixed(2)}</span>
          </div>
          <div class="summary-row">
            <span>Tax:</span>
            <span>RM ${order.tax.toFixed(2)}</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span>RM ${order.total.toFixed(2)}</span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h3>Payment Information</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value">${order.paymentMethod || 'N/A'}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Status</span>
            <span class="detail-value"><span class="status-badge payment-${displayPaymentStatus}">${displayPaymentStatus.charAt(0).toUpperCase() + displayPaymentStatus.slice(1)}</span></span>
          </div>
        </div>
      </div>
    `;
    
    document.getElementById('orderModal').classList.add('show');
  }
}

// Open status update modal
function openStatusModal(id) {
  const order = orders.find(o => o.id === id);
  
  if (order) {
    document.getElementById('statusOrderId').value = `#ORD-${order.id}`;
    document.getElementById('currentStatus').value = formatStatus(order.orderStatus);
    document.getElementById('newStatus').value = '';
    document.getElementById('statusNotes').value = '';
    document.getElementById('updateOrderId').value = order.id;
    document.getElementById('statusModal').classList.add('show');
  }
}

// Update order status
async function updateOrderStatus(event) {
  event.preventDefault();
  
  const orderId = parseInt(document.getElementById('updateOrderId').value);
  const newStatus = document.getElementById('newStatus').value;
  const notes = document.getElementById('statusNotes').value;
  
  try {
    const response = await fetch(`${API_URL}?action=updateStatus`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: orderId,
        status: newStatus,
        notes: notes
      })
    });

    const data = await response.json();

    if (data.success) {
      alert(`Order #ORD-${orderId} status updated to: ${formatStatus(newStatus)}`);
      closeStatusModal();
      await loadOrders();
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    // Update locally for development
    const orderIndex = orders.findIndex(o => o.id === orderId);
    if (orderIndex !== -1) {
      orders[orderIndex].orderStatus = newStatus;
      if (newStatus === 'delivered') {
        orders[orderIndex].paymentStatus = 'paid';
      }
      alert(`Order #ORD-${orderId} status updated locally (development mode)`);
      closeStatusModal();
      filteredOrders = [...orders];
      updateStats();
      updateStatisticsTable();
      filterOrders();
    }
  }
}

// Export statistics to CSV
function exportStatistics() {
  if (statsData.length === 0) {
    alert('No statistics to export!');
    return;
  }
  
  const headers = ['Period', 'Total Orders', 'Completed', 'Pending', 'Cancelled', 'Revenue (RM)', 'Avg Order (RM)', 'Growth (%)'];
  const rows = statsData.map(stats => [
    stats.period,
    stats.total,
    stats.completed,
    stats.pending,
    stats.cancelled,
    stats.revenue.toFixed(2),
    stats.avgOrder.toFixed(2),
    stats.growth.toFixed(1)
  ]);
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `order_statistics_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  console.log('✅ Exported statistics to CSV');
}

// Export orders to CSV
function exportOrders() {
  if (filteredOrders.length === 0) {
    alert('No orders to export!');
    return;
  }
  
  const headers = ['Order ID', 'Customer', 'Email', 'Phone', 'Total Amount (RM)', 'Payment Status', 'Order Status', 'Order Date'];
  const rows = filteredOrders.map(order => [
    `#ORD-${order.id}`,
    order.customer,
    order.email,
    order.phone,
    order.total.toFixed(2),
    order.paymentStatus,
    order.orderStatus,
    new Date(order.orderDate).toLocaleString()
  ]);
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `orders_export_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  console.log('✅ Exported', filteredOrders.length, 'orders to CSV');
}

// Refresh orders
async function refreshOrders() {
  await loadOrders();
  alert('Orders refreshed successfully!');
}

// Close modals
function closeModal() {
  document.getElementById('orderModal').classList.remove('show');
}

function closeStatusModal() {
  document.getElementById('statusModal').classList.remove('show');
  document.getElementById('statusForm').reset();
}

// Close modals when clicking outside
window.onclick = function(event) {
  const orderModal = document.getElementById('orderModal');
  const statusModal = document.getElementById('statusModal');
  
  if (event.target === orderModal) {
    closeModal();
  }
  if (event.target === statusModal) {
    closeStatusModal();
  }
}