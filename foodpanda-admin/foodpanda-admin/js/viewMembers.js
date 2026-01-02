// API endpoint
const API_URL = 'api/viewMembers.php';

// Store members in memory
let members = [];
let currentEditId = null;
let flatpickrInstance = null;

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCustomDatePicker();
    loadMembers();
});

// Initialize Flatpickr Calendar Widget for Custom Date Range
function initializeCustomDatePicker() {
  flatpickrInstance = flatpickr("#customDateRange", {
    mode: "range",
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
      if (selectedDates.length === 2) {
        console.log('Custom date range selected:', dateStr);
        filterMembers();
      }
    }
  });
}

// Handle date filter change
function handleDateFilterChange() {
  const dateFilter = document.getElementById('dateFilter').value;
  const customDateGroup = document.getElementById('customDateGroup');
  
  if (dateFilter === 'custom') {
    customDateGroup.style.display = 'block';
  } else {
    customDateGroup.style.display = 'none';
    if (flatpickrInstance) {
      flatpickrInstance.clear();
    }
    filterMembers();
  }
}

// Reset all member filters
function resetMemberFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('typeFilter').value = 'all';
  document.getElementById('statusFilter').value = 'all';
  document.getElementById('dateFilter').value = 'all';
  document.getElementById('sortFilter').value = 'name-asc';
  
  if (flatpickrInstance) {
    flatpickrInstance.clear();
  }
  
  handleDateFilterChange();
  filterMembers();
  
  console.log('✅ Filters reset');
}

// Export members to CSV
function exportMembersCSV() {
  if (members.length === 0) {
    alert('No members to export!');
    return;
  }
  
  const headers = ['Name', 'Email', 'Phone', 'Type', 'Join Date', 'Total Orders', 'Status', 'City', 'Postal Code'];
  const rows = members.map(member => [
    member.name,
    member.email,
    member.phone,
    member.type,
    formatDate(member.joinDate),
    member.totalOrders,
    member.status,
    member.city,
    member.postal
  ]);
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `members_export_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  console.log('✅ Exported', members.length, 'members to CSV');
}

// Navigation function
function navigateTo(page) {
  window.location.href = `${page}.html`;
}

// Fetch members from database
async function loadMembers() {
  try {
    console.log('Fetching from:', `${API_URL}?action=getMembers`);
    const response = await fetch(`${API_URL}?action=getMembers`);
    console.log('Response status:', response.status);
    
    const text = await response.text();
    console.log('Response text:', text);
    
    const data = JSON.parse(text);
    console.log('Parsed data:', data);
    
    if (data.success) {
      members = data.members;
      updateStats();
      filterMembers();
    } else {
      alert('Error loading members: ' + data.message);
    }
  } catch (error) {
    console.error('Error details:', error);
    // Load dummy data for development
    loadDummyMembers();
  }
}

// Load dummy members for development
function loadDummyMembers() {
  console.log('Loading dummy members for development...');
  
  const dummyMembers = [
    {
      id: 1,
      name: 'Ahmad Ali',
      email: 'ahmad@example.com',
      phone: '+60123456789',
      type: 'customer',
      joinDate: '2024-11-15',
      totalOrders: 25,
      status: 'active',
      avatar: 'https://i.pravatar.cc/150?img=1',
      address: '123 Jalan Ampang',
      city: 'Kuala Lumpur',
      postal: '50450'
    },
    {
      id: 2,
      name: 'Siti Nurhaliza',
      email: 'siti@example.com',
      phone: '+60198765432',
      type: 'customer',
      joinDate: '2024-12-01',
      totalOrders: 12,
      status: 'active',
      avatar: 'https://i.pravatar.cc/150?img=5',
      address: '456 Jalan Bukit Bintang',
      city: 'Kuala Lumpur',
      postal: '55100'
    },
    {
      id: 3,
      name: 'Lee Wei Ming',
      email: 'lee@example.com',
      phone: '+60122334455',
      type: 'rider',
      joinDate: '2024-10-20',
      totalOrders: 150,
      status: 'active',
      avatar: 'https://i.pravatar.cc/150?img=12',
      address: '789 Jalan Petaling',
      city: 'Kuala Lumpur',
      postal: '50000'
    },
    {
      id: 4,
      name: 'Kumar Raj',
      email: 'kumar@example.com',
      phone: '+60176543210',
      type: 'customer',
      joinDate: '2024-09-10',
      totalOrders: 8,
      status: 'inactive',
      avatar: 'https://i.pravatar.cc/150?img=15',
      address: '321 Jalan Tun Razak',
      city: 'Kuala Lumpur',
      postal: '50400'
    },
    {
      id: 5,
      name: 'Fatimah Hassan',
      email: 'fatimah@example.com',
      phone: '+60134567890',
      type: 'customer',
      joinDate: '2025-01-02',
      totalOrders: 2,
      status: 'active',
      avatar: 'https://i.pravatar.cc/150?img=20',
      address: '654 Jalan Raja Chulan',
      city: 'Kuala Lumpur',
      postal: '50200'
    }
  ];
  
  members = dummyMembers;
  updateStats();
  filterMembers();
  console.log('✅ Loaded', members.length, 'dummy members');
}

// Calculate and update statistics
function updateStats() {
  const totalMembers = members.length;
  const activeMembers = members.filter(m => m.status === 'active').length;
  
  // Calculate new members this month (last 30 days)
  const thirtyDaysAgo = new Date();
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
  const newMembers = members.filter(m => new Date(m.joinDate) >= thirtyDaysAgo).length;
  
  const totalCustomers = members.filter(m => m.type === 'customer').length;

  document.getElementById('totalMembers').textContent = totalMembers;
  document.getElementById('activeMembers').textContent = activeMembers;
  document.getElementById('newMembers').textContent = newMembers;
  document.getElementById('totalCustomers').textContent = totalCustomers;
}

// Format date for display
function formatDate(dateString) {
  const date = new Date(dateString);
  const day = date.getDate();
  const month = date.toLocaleString('en-US', { month: 'short' });
  const year = date.getFullYear();
  return `${day} ${month} ${year}`;
}

// Render members table
function renderMembers(membersToRender) {
  const tbody = document.getElementById('membersTableBody');
  const memberCount = document.getElementById('memberCount');
  
  if (membersToRender.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 2rem; color: #718096;">No members found matching the filters.</td></tr>';
    if (memberCount) memberCount.textContent = 'Showing 0 members';
    return;
  }

  tbody.innerHTML = membersToRender.map(member => `
    <tr>
      <td><img src="${member.avatar}" alt="${member.name}" class="member-avatar"></td>
      <td>${member.name}</td>
      <td>${member.email}</td>
      <td>${member.phone}</td>
      <td><span class="type-badge type-${member.type}">${member.type.charAt(0).toUpperCase() + member.type.slice(1)}</span></td>
      <td>${formatDate(member.joinDate)}</td>
      <td>${member.totalOrders}</td>
      <td><span class="status-badge status-${member.status}">${member.status.charAt(0).toUpperCase() + member.status.slice(1)}</span></td>
      <td>
        <button class="btn btn-view" onclick="viewMember(${member.id})">View</button>
        <button class="btn btn-edit" onclick="editMember(${member.id})">Edit</button>
        <button class="btn btn-danger" onclick="deleteMember(${member.id})">Delete</button>
      </td>
    </tr>
  `).join('');
  
  if (memberCount) {
    memberCount.textContent = `Showing ${membersToRender.length} member${membersToRender.length !== 1 ? 's' : ''}`;
  }
}

// Filter members based on search and filters
function filterMembers() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const typeFilter = document.getElementById('typeFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  const dateFilter = document.getElementById('dateFilter').value;
  const sortFilter = document.getElementById('sortFilter').value;

  let filtered = [...members];

  // Search filter
  if (searchTerm) {
    filtered = filtered.filter(m => 
      m.name.toLowerCase().includes(searchTerm) || 
      m.email.toLowerCase().includes(searchTerm) ||
      m.phone.includes(searchTerm)
    );
  }

  // Type filter
  if (typeFilter !== 'all') {
    filtered = filtered.filter(m => m.type === typeFilter);
  }

  // Status filter
  if (statusFilter !== 'all') {
    filtered = filtered.filter(m => m.status === statusFilter);
  }

  // Date filter
  if (dateFilter !== 'all') {
    const now = new Date();
    let cutoffDate = new Date();
    
    switch(dateFilter) {
      case 'today':
        cutoffDate.setHours(0, 0, 0, 0);
        break;
      case '7days':
        cutoffDate.setDate(cutoffDate.getDate() - 7);
        break;
      case '30days':
        cutoffDate.setDate(cutoffDate.getDate() - 30);
        break;
      case '90days':
        cutoffDate.setDate(cutoffDate.getDate() - 90);
        break;
      case 'this_month':
        cutoffDate = new Date(now.getFullYear(), now.getMonth(), 1);
        break;
      case 'last_month':
        cutoffDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
        filtered = filtered.filter(m => {
          const joinDate = new Date(m.joinDate);
          return joinDate >= cutoffDate && joinDate <= lastMonthEnd;
        });
        renderMembers(filtered);
        return;
      case 'this_year':
        cutoffDate = new Date(now.getFullYear(), 0, 1);
        break;
      case 'custom':
        const customDateRange = document.getElementById('customDateRange').value;
        if (customDateRange && customDateRange.includes('to')) {
          const dates = customDateRange.split(' to ');
          const startDate = new Date(dates[0]);
          const endDate = new Date(dates[1]);
          endDate.setHours(23, 59, 59, 999);
          filtered = filtered.filter(m => {
            const joinDate = new Date(m.joinDate);
            return joinDate >= startDate && joinDate <= endDate;
          });
        }
        renderMembers(filtered);
        return;
    }
    
    if (dateFilter !== 'custom' && dateFilter !== 'last_month') {
      filtered = filtered.filter(m => new Date(m.joinDate) >= cutoffDate);
    }
  }

  // Sort
  if (sortFilter === 'name-asc') {
    filtered.sort((a, b) => a.name.localeCompare(b.name));
  } else if (sortFilter === 'name-desc') {
    filtered.sort((a, b) => b.name.localeCompare(a.name));
  } else if (sortFilter === 'date-newest') {
    filtered.sort((a, b) => new Date(b.joinDate) - new Date(a.joinDate));
  } else if (sortFilter === 'date-oldest') {
    filtered.sort((a, b) => new Date(a.joinDate) - new Date(b.joinDate));
  } else if (sortFilter === 'orders-desc') {
    filtered.sort((a, b) => b.totalOrders - a.totalOrders);
  } else if (sortFilter === 'orders-asc') {
    filtered.sort((a, b) => a.totalOrders - b.totalOrders);
  }

  renderMembers(filtered);
}

// Open modal for adding new member
function openAddModal() {
  currentEditId = null;
  document.getElementById('modalTitle').textContent = 'Add New Member';
  document.getElementById('memberForm').reset();
  document.getElementById('memberId').value = '';
  document.getElementById('memberModal').classList.add('show');
}

// Open modal for editing member
function editMember(id) {
  currentEditId = id;
  const member = members.find(m => m.id === id);
  
  if (member) {
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('memberId').value = member.id;
    document.getElementById('memberName').value = member.name;
    document.getElementById('memberEmail').value = member.email;
    document.getElementById('memberPhone').value = member.phone;
    document.getElementById('memberType').value = member.type;
    document.getElementById('memberAddress').value = member.address || '';
    document.getElementById('memberCity').value = member.city !== 'N/A' ? member.city : '';
    document.getElementById('memberPostal').value = member.postal !== 'N/A' ? member.postal : '';
    document.getElementById('memberStatus').value = member.status;
    document.getElementById('memberAvatar').value = member.avatar;
    document.getElementById('memberModal').classList.add('show');
  }
}

// View member details
function viewMember(id) {
  const member = members.find(m => m.id === id);
  
  if (member) {
    const detailsContent = document.getElementById('memberDetailsContent');
    detailsContent.innerHTML = `
      <div style="text-align: center; margin-bottom: 2rem;">
        <img src="${member.avatar}" alt="${member.name}" class="member-avatar-large">
        <h3 style="margin-top: 1rem; color: #2d3748;">${member.name}</h3>
        <span class="type-badge type-${member.type}">${member.type.charAt(0).toUpperCase() + member.type.slice(1)}</span>
        <span class="status-badge status-${member.status}" style="margin-left: 0.5rem;">${member.status.charAt(0).toUpperCase() + member.status.slice(1)}</span>
      </div>
      
      <div class="detail-section">
        <h3>Contact Information</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Email</span>
            <span class="detail-value">${member.email}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Phone</span>
            <span class="detail-value">${member.phone}</span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h3>Address</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Street Address</span>
            <span class="detail-value">${member.address || 'N/A'}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">City</span>
            <span class="detail-value">${member.city}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Postal Code</span>
            <span class="detail-value">${member.postal}</span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h3>Member Information</h3>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Member ID</span>
            <span class="detail-value">#MEM-${String(member.id).padStart(4, '0')}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Join Date</span>
            <span class="detail-value">${formatDate(member.joinDate)}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Total Orders</span>
            <span class="detail-value">${member.totalOrders} orders</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Member Type</span>
            <span class="detail-value">${member.type.charAt(0).toUpperCase() + member.type.slice(1)}</span>
          </div>
        </div>
      </div>
    `;
    
    document.getElementById('viewModal').classList.add('show');
  }
}

// Close modals
function closeModal() {
  document.getElementById('memberModal').classList.remove('show');
  document.getElementById('memberForm').reset();
  currentEditId = null;
}

function closeViewModal() {
  document.getElementById('viewModal').classList.remove('show');
}

// Save member (add or edit)
async function saveMember(event) {
  event.preventDefault();

  const memberData = {
    name: document.getElementById('memberName').value,
    email: document.getElementById('memberEmail').value,
    phone: document.getElementById('memberPhone').value,
    type: document.getElementById('memberType').value,
    address: document.getElementById('memberAddress').value,
    city: document.getElementById('memberCity').value,
    postal: document.getElementById('memberPostal').value,
    status: document.getElementById('memberStatus').value,
    avatar: document.getElementById('memberAvatar').value || 'https://i.pravatar.cc/150?img=0'
  };

  try {
    let url, method;
    
    if (currentEditId) {
      // Edit existing member
      memberData.id = currentEditId;
      url = `${API_URL}?action=updateMember`;
      method = 'POST';
    } else {
      // Add new member
      url = `${API_URL}?action=addMember`;
      method = 'POST';
    }

    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(memberData)
    });

    const data = await response.json();

    if (data.success) {
      alert(data.message);
      closeModal();
      await loadMembers(); // Reload members from database
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to save member. Using local storage for development.');
    
    // For development: save locally
    if (currentEditId) {
      const index = members.findIndex(m => m.id === currentEditId);
      if (index !== -1) {
        members[index] = { ...members[index], ...memberData };
      }
    } else {
      memberData.id = members.length + 1;
      memberData.joinDate = new Date().toISOString().split('T')[0];
      memberData.totalOrders = 0;
      members.push(memberData);
    }
    
    closeModal();
    updateStats();
    filterMembers();
  }
}

// Delete member
async function deleteMember(id) {
  if (confirm('Are you sure you want to delete this member? This will also delete all their orders and addresses.')) {
    try {
      const response = await fetch(`${API_URL}?action=deleteMember`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
      });

      const data = await response.json();

      if (data.success) {
        alert(data.message);
        await loadMembers(); // Reload members from database
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Failed to delete member. Removing locally for development.');
      
      // For development: delete locally
      members = members.filter(m => m.id !== id);
      updateStats();
      filterMembers();
    }
  }
}

// Close modals when clicking outside
window.onclick = function(event) {
  const memberModal = document.getElementById('memberModal');
  const viewModal = document.getElementById('viewModal');
  
  if (event.target === memberModal) {
    closeModal();
  }
  if (event.target === viewModal) {
    closeViewModal();
  }
}