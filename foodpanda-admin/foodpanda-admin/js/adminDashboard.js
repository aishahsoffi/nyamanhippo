// Global variables
let currentPeriodStart = getMonday(new Date());
let salesChart = null;
let flatpickrInstance = null;
let currentFilter = 'this_week';

// Check authentication on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    checkAuthentication();
});

// Initialize Flatpickr Calendar Widget
function initializeCalendar() {
  flatpickrInstance = flatpickr("#dateRange", {
    mode: "range",
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        console.log('Date range selected:', dateStr);
      }
    }
  });
}

// Handle time filter change
function handleTimeFilterChange() {
  const timeFilter = document.getElementById('timeFilter').value;
  const dateRangeGroup = document.getElementById('dateRangeGroup');
  
  currentFilter = timeFilter;
  
  if (timeFilter === 'custom') {
    dateRangeGroup.style.display = 'block';
  } else {
    dateRangeGroup.style.display = 'none';
    if (flatpickrInstance) {
      flatpickrInstance.clear();
    }
  }
}

// Apply dashboard filters
function applyDashboardFilters() {
  const timeFilter = document.getElementById('timeFilter').value;
  
  // Validate custom date range
  if (timeFilter === 'custom') {
    const dateRangeValue = document.getElementById('dateRange').value;
    if (!dateRangeValue || !dateRangeValue.includes('to')) {
      alert('Please select a date range using the calendar!');
      return;
    }
  }
  
  // Reload all dashboard data with filters
  loadDashboardData();
}

// Reset dashboard filters
function resetDashboardFilters() {
  document.getElementById('timeFilter').value = 'this_week';
  document.getElementById('orderStatus').value = 'all';
  
  if (flatpickrInstance) {
    flatpickrInstance.clear();
  }
  
  handleTimeFilterChange();
  currentFilter = 'this_week';
  currentPeriodStart = getMonday(new Date());
  
  loadDashboardData();
}

// Check if admin is authenticated
async function checkAuthentication() {
    try {
        console.log('Checking authentication...');
        
        const response = await fetch('check_session.php');
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Session check response:', data);
        
        if (!data.success) {
            console.log('Not authenticated, redirecting to login...');
            window.location.href = 'login_admin.html';
            return;
        }
        
        // Update UI with admin information
        updateAdminInfo(data.admin);
        
        // Load dashboard data
        loadDashboardData();
    } catch (error) {
        console.error('Authentication check failed:', error);
        // Comment out redirect for development
        // window.location.href = 'login_admin.html';
        
        // Load dummy data for development
        loadDummyData();
    }
}

// Update admin information in the UI
function updateAdminInfo(admin) {
    console.log('Updating admin info:', admin);
    
    const adminNameElement = document.querySelector('.admin-name');
    if (adminNameElement) {
        adminNameElement.textContent = admin.name || 'Admin';
    }
    
    const adminEmailElement = document.querySelector('.admin-email');
    if (adminEmailElement) {
        adminEmailElement.textContent = admin.email || '';
    }
    
    const profileIcon = document.querySelector('.profile-icon');
    if (profileIcon && admin.name) {
        profileIcon.textContent = admin.name.charAt(0).toUpperCase();
    }
}

// Load all dashboard data
function loadDashboardData() {
    loadStats();
    loadSalesChart();
    loadRecentActivity();
}

// Calculate date range based on filter
function calculateDateRange(filter) {
  const today = new Date();
  const start = new Date();
  const end = new Date();
  
  switch(filter) {
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
    default:
      return null;
  }
  
  return { start, end };
}

// Load dummy data for development
function loadDummyData() {
    // Set dummy admin info
    document.querySelector('.admin-name').textContent = 'Admin User';
    document.querySelector('.admin-email').textContent = 'admin@foodpanda.com';
    document.querySelector('.profile-icon').textContent = 'A';
    
    // Set dummy stats
    document.getElementById('totalRevenue').textContent = 'RM 15,450';
    document.getElementById('revenueChange').innerHTML = '↑ 12.5% from last period';
    document.getElementById('totalOrders').textContent = '248';
    document.getElementById('ordersChange').innerHTML = '↑ 8.1% from last period';
    document.getElementById('activeMembers').textContent = '156';
    document.getElementById('membersChange').innerHTML = '↑ 16.3% from last period';
    
    // Render dummy chart
    const dummySalesData = {
        Mon: 1200, Tue: 1800, Wed: 1500, Thu: 2200, Fri: 2800, Sat: 3200, Sun: 2700
    };
    renderChart(dummySalesData);
    updateDateRange(currentPeriodStart);
    
    // Render dummy activity
    const dummyActivities = [
        { icon: '🛒', iconClass: 'order-icon', title: 'New order #ORD-1025 received', time: '5 minutes ago' },
        { icon: '👤', iconClass: 'member-icon', title: 'New member registration: Ahmad Ali', time: '15 minutes ago' },
        { icon: '🛒', iconClass: 'order-icon', title: 'Order #ORD-1024 completed', time: '30 minutes ago' },
        { icon: '📦', iconClass: 'product-icon', title: 'Product "Nasi Lemak" stock low (8 remaining)', time: '1 hour ago' },
        { icon: '👤', iconClass: 'member-icon', title: 'New member registration: Siti Nurhaliza', time: '2 hours ago' }
    ];
    renderActivity(dummyActivities);
}

// Load dashboard statistics
async function loadStats() {
    try {
        console.log('Loading stats...');
        const response = await fetch('api/adminDashboard.php?action=getStats');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Stats response:', data);
        
        if (data.success) {
            const stats = data.stats;
            
            document.getElementById('totalRevenue').textContent = 
                'RM ' + formatNumber(stats.totalRevenue);
            document.getElementById('revenueChange').innerHTML = 
                `↑ ${stats.revenueChange}% from last period`;
            document.getElementById('revenueChange').className = 
                'stat-change ' + (stats.revenueChange >= 0 ? 'positive' : 'negative');
            
            document.getElementById('totalOrders').textContent = 
                formatNumber(stats.totalOrders);
            document.getElementById('ordersChange').innerHTML = 
                `↑ ${stats.ordersChange}% from last period`;
            document.getElementById('ordersChange').className = 
                'stat-change ' + (stats.ordersChange >= 0 ? 'positive' : 'negative');
            
            document.getElementById('activeMembers').textContent = 
                formatNumber(stats.activeMembers);
            document.getElementById('membersChange').innerHTML = 
                `↑ ${stats.membersChange}% from last period`;
            document.getElementById('membersChange').className = 
                'stat-change ' + (stats.membersChange >= 0 ? 'positive' : 'negative');
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        // Use dummy data on error
        loadDummyData();
    }
}

// Load sales chart
async function loadSalesChart() {
    try {
        console.log('Loading sales chart...');
        const weekStartStr = formatDate(currentPeriodStart);
        const response = await fetch(`api/adminDashboard.php?action=getSalesData&weekStart=${weekStartStr}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Sales data response:', data);
        
        if (data.success) {
            updateDateRange(currentPeriodStart);
            renderChart(data.salesData);
        }
    } catch (error) {
        console.error('Error loading sales chart:', error);
        // Use dummy data on error
        const dummySalesData = {
            Mon: 1200, Tue: 1800, Wed: 1500, Thu: 2200, Fri: 2800, Sat: 3200, Sun: 2700
        };
        renderChart(dummySalesData);
        updateDateRange(currentPeriodStart);
    }
}

// Render the sales chart
function renderChart(salesData) {
    const ctx = document.getElementById('salesChart');
    
    if (!ctx) return;
    
    if (salesChart) {
        salesChart.destroy();
    }
    
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const dataValues = labels.map(day => salesData[day] || 0);
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (RM)',
                data: dataValues,
                borderColor: '#d70f64',
                backgroundColor: 'rgba(215, 15, 100, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#d70f64',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#2d3748',
                    bodyColor: '#2d3748',
                    borderColor: '#d70f64',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'RM ' + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#718096',
                        callback: function(value) {
                            return 'RM ' + value;
                        }
                    },
                    grid: {
                        color: '#e2e8f0'
                    }
                },
                x: {
                    ticks: {
                        color: '#718096'
                    },
                    grid: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

// Load recent activity
async function loadRecentActivity() {
    try {
        console.log('Loading recent activity...');
        const response = await fetch('api/adminDashboard.php?action=getRecentActivity');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Activity response:', data);
        
        if (data.success) {
            renderActivity(data.activities);
        }
    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

// Render activity list
function renderActivity(activities) {
    const activityList = document.getElementById('activityList');
    
    if (!activityList || !activities || activities.length === 0) return;
    
    activityList.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon ${activity.iconClass}">${activity.icon}</div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <div class="activity-time">${activity.time}</div>
            </div>
        </div>
    `).join('');
}

// Navigation functions
function previousPeriod() {
    if (currentFilter === 'this_week' || currentFilter === 'last_week') {
        currentPeriodStart.setDate(currentPeriodStart.getDate() - 7);
    } else if (currentFilter === 'this_month') {
        currentPeriodStart.setMonth(currentPeriodStart.getMonth() - 1);
    } else {
        currentPeriodStart.setDate(currentPeriodStart.getDate() - 1);
    }
    loadSalesChart();
}

function nextPeriod() {
    const today = new Date();
    const nextPeriod = new Date(currentPeriodStart);
    
    if (currentFilter === 'this_week' || currentFilter === 'last_week') {
        nextPeriod.setDate(nextPeriod.getDate() + 7);
    } else if (currentFilter === 'this_month') {
        nextPeriod.setMonth(nextPeriod.getMonth() + 1);
    } else {
        nextPeriod.setDate(nextPeriod.getDate() + 1);
    }
    
    if (nextPeriod <= today) {
        currentPeriodStart = nextPeriod;
        loadSalesChart();
    }
}

function navigateTo(page) {
    if (page === 'logout_admin') {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout_admin.php';
        }
        return;
    }
    
    if (page === 'adminDashboard') {
        window.location.reload();
        return;
    }
    
    window.location.href = page + '.html';
}

// Utility functions
function getMonday(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateDisplay(date) {
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('en-GB', options);
}

function updateDateRange(weekStart) {
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const dateRangeElement = document.getElementById('chartDateRange');
    if (dateRangeElement) {
        dateRangeElement.textContent = 
            `${formatDateDisplay(weekStart)} - ${formatDateDisplay(weekEnd)}`;
    }
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}