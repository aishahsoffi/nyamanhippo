// API endpoint
const API_URL = 'api/report_admin.php';

// Store current data
let allOrders = [];
let currentDateRange = {};
let flatpickrInstance = null;
let revenueChart = null;
let statusChart = null;
let sortDirection = {};

// Generate dummy data if no real data exists
function generateDummyData() {
  const dummyOrders = [];
  const customers = ['Ahmad Ali', 'Siti Nurhaliza', 'Lee Wei Ming', 'Kumar Raj', 'Fatimah Hassan', 'Chen Xiao', 'Ravi Kumar', 'Nurul Aina'];
  const paymentMethods = ['Credit Card', 'Online Banking', 'E-Wallet', 'Cash on Delivery'];
  const paymentStatuses = ['paid', 'pending', 'paid', 'paid'];
  const deliveryStatuses = ['delivered', 'processing', 'delivered', 'pending'];
  
  // Generate data for the last 30 days
  for (let i = 0; i < 50; i++) {
    const daysAgo = Math.floor(Math.random() * 30);
    const date = new Date();
    date.setDate(date.getDate() - daysAgo);
    date.setHours(Math.floor(Math.random() * 24), Math.floor(Math.random() * 60), 0, 0);
    
    const amount = (Math.random() * 150 + 20).toFixed(2);
    const paymentStatusIndex = Math.floor(Math.random() * paymentStatuses.length);
    
    dummyOrders.push({
      date: date.toISOString(),
      orderId: `#ORD-${1000 + i}`,
      customer: customers[Math.floor(Math.random() * customers.length)],
      paymentMethod: paymentMethods[Math.floor(Math.random() * paymentMethods.length)],
      amount: parseFloat(amount),
      paymentStatus: paymentStatuses[paymentStatusIndex],
      deliveryStatus: deliveryStatuses[paymentStatusIndex]
    });
  }
  
  return dummyOrders.sort((a, b) => new Date(b.date) - new Date(a.date));
}

// Navigation function
function navigateTo(page) {
  window.location.href = `${page}.html`;
}

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
  
  if (timeFilter === 'custom') {
    dateRangeGroup.style.display = 'block';
  } else {
    dateRangeGroup.style.display = 'none';
    if (flatpickrInstance) {
      flatpickrInstance.clear();
    }
  }
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
    case 'all':
      return null;
    default:
      return null;
  }
  
  return { start, end };
}

// Format date for display
function formatDate(dateString) {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = date.toLocaleString('en-US', { month: 'short' });
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${day} ${month} ${year}, ${hours}:${minutes}`;
}

// Format date range for display
function formatDateRange(start, end, filter) {
  if (!start || !end) {
    return 'All Time Report';
  }
  
  const formatSimpleDate = (d) => {
    const day = d.getDate();
    const month = d.toLocaleString('en-US', { month: 'short' });
    const year = d.getFullYear();
    return `${day} ${month} ${year}`;
  };
  
  const filterNames = {
    'today': 'Today',
    'yesterday': 'Yesterday',
    'this_week': 'This Week',
    'last_week': 'Last Week',
    'this_month': 'This Month',
    'last_month': 'Last Month',
    'this_year': 'This Year',
    'custom': 'Custom Range'
  };
  
  return `${filterNames[filter] || 'Custom'} - ${formatSimpleDate(new Date(start))} to ${formatSimpleDate(new Date(end))}`;
}

// Filter orders by date range and status
function filterOrders(orders, dateRange, status) {
  let filtered = [...orders];
  
  // Filter by date range
  if (dateRange && dateRange.start && dateRange.end) {
    const startTime = new Date(dateRange.start).getTime();
    const endTime = new Date(dateRange.end).getTime();
    
    filtered = filtered.filter(order => {
      const orderTime = new Date(order.date).getTime();
      return orderTime >= startTime && orderTime <= endTime;
    });
  }
  
  // Filter by status
  if (status && status !== 'all') {
    if (status === 'paid') {
      filtered = filtered.filter(o => o.paymentStatus === 'paid');
    } else if (status === 'pending') {
      filtered = filtered.filter(o => o.paymentStatus === 'pending');
    } else if (status === 'delivered') {
      filtered = filtered.filter(o => o.deliveryStatus === 'delivered');
    } else if (status === 'processing') {
      filtered = filtered.filter(o => o.deliveryStatus === 'processing');
    }
  }
  
  return filtered;
}

// Sort table by column
function sortTable(column) {
  if (!allOrders.length) return;
  
  const currentDirection = sortDirection[column] || 'desc';
  const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
  sortDirection = { [column]: newDirection };
  
  const sorted = [...allOrders].sort((a, b) => {
    let valA = a[column];
    let valB = b[column];
    
    if (column === 'date') {
      valA = new Date(valA).getTime();
      valB = new Date(valB).getTime();
    } else if (column === 'amount') {
      valA = parseFloat(valA);
      valB = parseFloat(valB);
    }
    
    if (valA < valB) return newDirection === 'asc' ? -1 : 1;
    if (valA > valB) return newDirection === 'asc' ? 1 : -1;
    return 0;
  });
  
  renderTable(sorted);
}

// Render table rows
function renderTable(orders) {
  const tbody = document.getElementById('reportTableBody');
  const recordCount = document.getElementById('recordCount');
  
  if (orders.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">No transaction records found matching the selected filters.</td></tr>';
    recordCount.textContent = 'Showing 0 records';
    return;
  }

  tbody.innerHTML = orders.map(order => `
    <tr>
      <td>${formatDate(order.date)}</td>
      <td><strong>${order.orderId}</strong></td>
      <td>${order.customer}</td>
      <td>${order.paymentMethod || 'N/A'}</td>
      <td><strong>RM ${parseFloat(order.amount).toFixed(2)}</strong></td>
      <td><span class="status-badge status-${order.paymentStatus}">${order.paymentStatus.charAt(0).toUpperCase() + order.paymentStatus.slice(1)}</span></td>
      <td><span class="status-badge status-${order.deliveryStatus}">${order.deliveryStatus.charAt(0).toUpperCase() + order.deliveryStatus.slice(1)}</span></td>
    </tr>
  `).join('');
  
  recordCount.textContent = `Showing ${orders.length} record${orders.length !== 1 ? 's' : ''}`;
}

// Calculate statistics
function calculateStats(orders) {
  const totalRevenue = orders.reduce((sum, o) => sum + o.amount, 0);
  const totalOrders = orders.length;
  const completedTransactions = orders.filter(o => o.paymentStatus === 'paid').length;
  const completedRevenue = orders.filter(o => o.paymentStatus === 'paid').reduce((sum, o) => sum + o.amount, 0);
  const pendingTransactions = orders.filter(o => o.paymentStatus === 'pending').length;
  const pendingAmount = orders.filter(o => o.paymentStatus === 'pending').reduce((sum, o) => sum + o.amount, 0);
  
  return {
    totalRevenue,
    totalOrders,
    completedTransactions,
    completedRevenue,
    pendingTransactions,
    pendingAmount
  };
}

// Display statistics
function displayStats(stats) {
  document.getElementById('totalRevenue').textContent = `RM ${stats.totalRevenue.toFixed(2)}`;
  document.getElementById('totalOrders').textContent = stats.totalOrders;
  document.getElementById('completedTransactions').textContent = stats.completedTransactions;
  document.getElementById('completedRevenue').textContent = `RM ${stats.completedRevenue.toFixed(2)}`;
  document.getElementById('pendingTransactions').textContent = stats.pendingTransactions;
  document.getElementById('pendingAmount').textContent = `RM ${stats.pendingAmount.toFixed(2)}`;
}

// Update report summary
function updateReportSummary(dateRange, filter) {
  const summaryBanner = document.getElementById('reportSummary');
  summaryBanner.style.display = 'block';
  
  const sectionHeader = summaryBanner.querySelector('.section-header h2');
  sectionHeader.innerHTML = `📋 Report Summary <span style="font-size: 16px; font-weight: 500; color: #d70f64; margin-left: 15px;">${formatDateRange(dateRange?.start, dateRange?.end, filter)}</span>`;
}

// Create charts
function createCharts(orders) {
  const viewType = document.getElementById('viewType').value;
  const chartsSection = document.getElementById('chartsSection');
  const tableSection = document.getElementById('tableSection');
  
  if (viewType === 'table') {
    chartsSection.style.display = 'none';
    tableSection.style.display = 'block';
    return;
  } else if (viewType === 'chart') {
    chartsSection.style.display = 'block';
    tableSection.style.display = 'none';
  } else {
    chartsSection.style.display = 'block';
    tableSection.style.display = 'block';
  }
  
  // Destroy existing charts
  if (revenueChart) revenueChart.destroy();
  if (statusChart) statusChart.destroy();
  
  // Group orders by date for revenue trend
  const revenueByDate = {};
  orders.forEach(order => {
    const date = new Date(order.date).toISOString().split('T')[0];
    if (!revenueByDate[date]) {
      revenueByDate[date] = 0;
    }
    revenueByDate[date] += order.amount;
  });
  
  const dates = Object.keys(revenueByDate).sort();
  const revenues = dates.map(date => revenueByDate[date]);
  
  // Revenue Trend Chart
  const revenueCtx = document.getElementById('revenueChart').getContext('2d');
  revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
      labels: dates.map(d => new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'short' })),
      datasets: [{
        label: 'Revenue (RM)',
        data: revenues,
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
        legend: {
          labels: { color: '#2d3748' }
        }
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
    paid: orders.filter(o => o.paymentStatus === 'paid').length,
    pending: orders.filter(o => o.paymentStatus === 'pending').length,
    delivered: orders.filter(o => o.deliveryStatus === 'delivered').length,
    processing: orders.filter(o => o.deliveryStatus === 'processing').length
  };
  
  const statusCtx = document.getElementById('statusChart').getContext('2d');
  statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Paid', 'Pending', 'Delivered', 'Processing'],
      datasets: [{
        data: [statusCounts.paid, statusCounts.pending, statusCounts.delivered, statusCounts.processing],
        backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6']
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

// Generate Report function
async function generateReport() {
  const timeFilter = document.getElementById('timeFilter').value;
  const status = document.getElementById('status').value;
  
  let dateRange = null;
  let startDate = null;
  let endDate = null;
  
  // Handle custom date range
  if (timeFilter === 'custom') {
    const dateRangeValue = document.getElementById('dateRange').value;
    if (!dateRangeValue || !dateRangeValue.includes('to')) {
      alert('Please select a date range using the calendar!');
      return;
    }
    const dates = dateRangeValue.split(' to ');
    startDate = dates[0];
    endDate = dates[1];
    dateRange = {
      start: startDate,
      end: endDate
    };
  } else if (timeFilter !== 'all') {
    const range = calculateDateRange(timeFilter);
    if (range) {
      dateRange = range;
      startDate = range.start.toISOString().split('T')[0];
      endDate = range.end.toISOString().split('T')[0];
    }
  }

  try {
    console.log('Generating report with filters:', { timeFilter, startDate, endDate, status });
    
    // Show loading state
    const tbody = document.getElementById('reportTableBody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">⏳ Loading report data...</td></tr>';
    
    // Try to fetch from API first
    let orders = [];
    let useDummyData = false;
    
    try {
      let url = `${API_URL}?action=generateReport&timeFilter=${timeFilter}`;
      if (startDate) url += `&startDate=${startDate}`;
      if (endDate) url += `&endDate=${endDate}`;
      if (status && status !== 'all') url += `&status=${status}`;
      
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.success && data.orders && data.orders.length > 0) {
        orders = data.orders;
      } else {
        useDummyData = true;
      }
    } catch (error) {
      console.log('API not available, using dummy data');
      useDummyData = true;
    }
    
    // Use dummy data if API failed or returned no data
    if (useDummyData) {
      orders = generateDummyData();
    }
    
    // Filter orders
    const filteredOrders = filterOrders(orders, dateRange, status);
    allOrders = filteredOrders;
    currentDateRange = dateRange;
    
    // Calculate and display stats
    const stats = calculateStats(filteredOrders);
    displayStats(stats);
    
    // Update summary
    updateReportSummary(dateRange, timeFilter);
    
    // Render table
    renderTable(filteredOrders);
    
    // Create charts
    createCharts(filteredOrders);
    
    console.log(`✅ Generated report with ${filteredOrders.length} transactions`);
    
    if (useDummyData) {
      console.log('ℹ️ Using dummy data for demonstration');
    }
  } catch (error) {
    console.error('Error generating report:', error);
    alert('Failed to generate report: ' + error.message);
  }
}

// Reset Filters
function resetFilters() {
  document.getElementById('timeFilter').value = 'all';
  document.getElementById('status').value = 'all';
  document.getElementById('viewType').value = 'table';
  
  if (flatpickrInstance) {
    flatpickrInstance.clear();
  }
  
  handleTimeFilterChange();
  
  document.getElementById('reportSummary').style.display = 'none';
  document.getElementById('chartsSection').style.display = 'none';
  document.getElementById('tableSection').style.display = 'block';
  
  // Reset stats
  document.getElementById('totalRevenue').textContent = 'RM 0.00';
  document.getElementById('totalOrders').textContent = '0';
  document.getElementById('completedTransactions').textContent = '0';
  document.getElementById('completedRevenue').textContent = 'RM 0.00';
  document.getElementById('pendingTransactions').textContent = '0';
  document.getElementById('pendingAmount').textContent = 'RM 0.00';
  
  const tbody = document.getElementById('reportTableBody');
  tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">Select filters and click "Generate Report" to view transactions</td></tr>';
  
  document.getElementById('recordCount').textContent = 'Showing 0 records';
  
  allOrders = [];
  currentDateRange = {};
  sortDirection = {};
  
  if (revenueChart) revenueChart.destroy();
  if (statusChart) statusChart.destroy();
  
  console.log('✅ Filters reset');
}

// Export to CSV
function exportToCSV() {
  if (allOrders.length === 0) {
    alert('Please generate a report first!');
    return;
  }
  
  const headers = ['Date & Time', 'Order ID', 'Customer', 'Payment Method', 'Amount (RM)', 'Payment Status', 'Delivery Status'];
  const rows = allOrders.map(order => [
    formatDate(order.date),
    order.orderId,
    order.customer,
    order.paymentMethod || 'N/A',
    order.amount.toFixed(2),
    order.paymentStatus,
    order.deliveryStatus
  ]);
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `transaction_report_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
}

// Print Report
function printReport() {
  if (allOrders.length === 0) {
    alert('Please generate a report first!');
    return;
  }
  
  const printWindow = window.open('', '_blank');
  const reportTitle = formatDateRange(currentDateRange?.start, currentDateRange?.end, document.getElementById('timeFilter').value);
  const stats = calculateStats(allOrders);
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Transaction Report - ${reportTitle}</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #d70f64; text-align: center; }
        .report-header { text-align: center; margin-bottom: 30px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-box { border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-label { font-size: 12px; color: #666; margin-bottom: 8px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #d70f64; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #d70f64; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        @media print { .no-print { display: none; } }
      </style>
    </head>
    <body>
      <div class="report-header">
        <h1>🍔 Foodpanda Transaction Report</h1>
        <p><strong>${reportTitle}</strong></p>
        <p>Generated on: ${new Date().toLocaleString()}</p>
      </div>
      
      <h2>Summary Statistics</h2>
      <div class="stats">
        <div class="stat-box">
          <div class="stat-label">Total Revenue</div>
          <div class="stat-value">RM ${stats.totalRevenue.toFixed(2)}</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Total Orders</div>
          <div class="stat-value">${stats.totalOrders}</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Completed</div>
          <div class="stat-value">${stats.completedTransactions}</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Pending</div>
          <div class="stat-value">${stats.pendingTransactions}</div>
        </div>
      </div>
      
      <h2>Transaction Details</h2>
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Payment Method</th>
            <th>Amount</th>
            <th>Payment Status</th>
            <th>Delivery Status</th>
          </tr>
        </thead>
        <tbody>
          ${allOrders.map(order => `
            <tr>
              <td>${formatDate(order.date)}</td>
              <td>${order.orderId}</td>
              <td>${order.customer}</td>
              <td>${order.paymentMethod || 'N/A'}</td>
              <td>RM ${order.amount.toFixed(2)}</td>
              <td>${order.paymentStatus.toUpperCase()}</td>
              <td>${order.deliveryStatus.toUpperCase()}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      
      <script>
        window.onload = function() { window.print(); }
      </script>
    </body>
    </html>
  `);
  
  printWindow.document.close();
}

// Initialize on page load
window.addEventListener('load', () => {
  initializeCalendar();
  handleTimeFilterChange();
  console.log('📊 Transaction Report page loaded successfully');
  console.log('💡 Select time period and click "Generate Report" to view data');
});