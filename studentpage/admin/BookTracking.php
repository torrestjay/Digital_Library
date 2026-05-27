<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Manage Users & Borrowing - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    /* Additional table styles */
    .table-wrapper {
      background-color: var(--color-bg-primary);
      border-radius: var(--radius-2xl);
      box-shadow: var(--shadow-md);
      margin: 0 var(--space-20) var(--space-20) var(--space-20);
      overflow-x: auto;
      overflow-y: visible;
    }
    
    .status-badge-cell {
      padding: var(--space-8) 0 !important;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    
    .action-buttons {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
    }
    
    .action-cell {
      white-space: normal;
      padding: 12px !important;
      text-align: center;
      background: linear-gradient(to bottom, rgba(247, 248, 250, 0.5), transparent);
    }

    /* Action Button Styles */
    .action-btn {
      padding: 8px 10px;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: all 0.2s ease;
      text-decoration: none;
      flex-shrink: 0;
      min-width: 36px;
      height: 36px;
    }

    .action-btn-approve {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: #fff;
    }

    .action-btn-approve:hover {
      background: linear-gradient(135deg, #45a049, #3d8b40);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    .action-btn-reject {
      background: linear-gradient(135deg, #f44336, #da190b);
      color: #fff;
    }

    .action-btn-reject:hover {
      background: linear-gradient(135deg, #da190b, #ba0605);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
    }

    .action-btn-return {
      background: linear-gradient(135deg, #2196F3, #1976D2);
      color: #fff;
    }

    .action-btn-return:hover {
      background: linear-gradient(135deg, #1976D2, #1565C0);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .action-btn-view {
      background: #f5f9fc;
      color: #0e3a5d;
      border: 1px solid #d9e5f0;
    }

    .action-btn-view:hover {
      background: #e8eff7;
      border-color: #0e3a5d;
    }

    .action-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* Status specific styling */
    .action-buttons .action-btn-approve + .action-btn-reject {
      margin-left: 4px;
    }

    /* Completed state styling */
    .action-cell span {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      background: #f0f4f8;
      border-radius: 6px;
      color: #7a8fa3;
      font-size: 12px;
      font-weight: 600;
    }
    
    .filter-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--space-16);
      padding: var(--space-20);
      background-color: var(--color-bg-primary);
      border-bottom: 1px solid var(--color-border);
      margin: 0 var(--space-20) 0 var(--space-20);
      border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    }
    
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: var(--space-8);
    }
    
    .filter-group label {
      font-weight: var(--font-weight-600);
      font-size: var(--font-size-sm);
      color: var(--color-text-primary);
    }
    
    .filter-group input,
    .filter-group select {
      padding: var(--space-10) var(--space-12);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      font-family: var(--font-family);
      font-size: var(--font-size-sm);
      transition: border-color var(--transition-base);
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: var(--color-primary-light);
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }

    /* Status Tabs */
    .status-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      margin-top: 20px;
      flex-wrap: wrap;
      border-bottom: 3px solid #e8eef7;
      padding-bottom: 12px;
      background: #ffffff;
      padding: 12px;
      border-radius: 12px;
      border: 1px solid #e5edf5;
      box-shadow: 0 2px 8px rgba(14, 58, 93, 0.06);
      margin-left: 20px;
      margin-right: 20px;
    }

    .tab-btn {
      padding: 12px 18px;
      border: none;
      background: transparent;
      color: #7a8e9f;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      position: relative;
      transition: all 0.3s ease;
      border-bottom: 3px solid transparent;
      margin-bottom: 0;
      border-radius: 8px;
      white-space: nowrap;
    }

    .tab-btn:hover {
      color: #0e3a5d;
      background: #f5f9fc;
    }

    .tab-btn.active {
      color: #fff;
      background: #0e3a5d;
      border-bottom-color: #0e3a5d;
    }
    
    .no-records {
      text-align: center;
      padding: var(--space-40);
      color: var(--color-text-secondary);
    }
    
    .no-records-icon {
      font-size: 48px;
      margin-bottom: var(--space-16);
      opacity: 0.5;
    }
    
    @media (max-width: 768px) {
      .filter-section {
        margin: var(--space-16);
      }
      
      .table-wrapper {
        margin: var(--space-16);
        overflow-x: auto;
      }
      
      table {
        min-width: 900px;
      }
      
      td {
        padding: var(--space-10) var(--space-12) !important;
        font-size: var(--font-size-xs);
      }
      
      .action-buttons {
        gap: 4px;
        flex-direction: column;
      }
      
      .action-btn {
        padding: 6px 10px;
        font-size: 11px;
        width: 100%;
      }
      
      .btn-sm {
        padding: var(--space-6) var(--space-8);
        font-size: var(--font-size-xs);
        min-height: auto;
      }
    }

    /* Sorting Styles */
    .sort-dropdown {
      transition: all 0.2s ease;
    }

    .sort-dropdown:hover {
      border-color: #2196F3;
      box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1) !important;
    }

    .sort-dropdown:focus {
      outline: none;
      border-color: #2196F3 !important;
      box-shadow: 0 2px 12px rgba(33, 150, 243, 0.2) !important;
    }
  </style>
</head>
<body>
  <!-- Sidebar Behavior Script -->
  <script src="includes/sidebar-behavior.js"></script>
  
  <div class="container">
    <!-- Standardized Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <!-- <img class="icon" src="../Images/notif.png"> -->
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>
      <section class="content-section">
        <h2 class="section-title"><i class="fas fa-users" style="margin-right: 12px;"></i>Borrowing Records</h2>
        
        <!-- Filter Section -->
        <div class="filter-section">
          <div class="filter-group">
            <label for="name-filter"><i class="fas fa-user" style="margin-right: 6px;"></i>Name</label>
            <input type="text" id="name-filter" placeholder="Search by name...">
          </div>
          
          <div class="filter-group">
            <label for="book-filter"><i class="fas fa-book" style="margin-right: 6px;"></i>Book Title</label>
            <input type="text" id="book-filter" placeholder="Search by book...">
          </div>
          
          <div class="filter-group" style="align-self: flex-end;">
            <button id="reset-filters" class="btn btn-secondary btn-sm" style="width: 100%;">
              <i class="fas fa-redo" style="margin-right: 6px;"></i>Reset Filters
            </button>
          </div>
        </div>

        <!-- Sort Dropdown -->
        <div class="sort-section" style="margin-bottom: 16px; margin-left: 20px; margin-right: 20px;">
          <label for="sort-dropdown" style="font-weight: 600; color: #0e3a5d; margin-right: 12px;"><i class="fas fa-sort" style="margin-right: 6px;"></i>Sort By:</label>
          <select id="sort-dropdown" class="sort-dropdown" style="padding: 8px 12px; border: 1px solid #c5d3e0; border-radius: 6px; background: white; color: #0e3a5d; font-size: 14px; font-weight: 500; cursor: pointer; min-width: 200px;">
            <option value="borrow-desc">Newest to Lowest</option>
            <option value="borrow-asc">Oldest to Newest</option>
            <option value="name-asc">Name (A-Z)</option>
            <option value="name-desc">Name (Z-A)</option>
            <option value="book-asc">Book Title (A-Z)</option>
            <option value="book-desc">Book Title (Z-A)</option>
          </select>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
          <button class="tab-btn active" data-status="">📚 All Records</button>
          <button class="tab-btn" data-status="pending">⏳ Pending</button>
          <button class="tab-btn" data-status="borrowed">📖 Borrowed</button>
          <button class="tab-btn" data-status="returned">✓ Returned</button>
          <button class="tab-btn" data-status="rejected">❌ Rejected</button>
        </div>
        
        <!-- Table Section -->
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="min-width: 140px;">Name</th>
                <th style="min-width: 140px;">Book Title</th>
                <th style="min-width: 95px;">Borrow Date</th>
                <th style="min-width: 95px;">Due Date</th>
                <th style="min-width: 95px;">Return Date</th>
                <th style="min-width: 85px;">Status</th>
                <th style="min-width: 200px;"><i class="fas fa-cogs" style="margin-right: 6px;"></i>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Database connection
              include "../dbcon.php";
              // Join query to fetch users who borrowed books
              $query = "SELECT 
                          u.id, 
                          u.fullname, 
                          u.email, 
                          u.role, 
                          b.title AS book_title,
                          b.id AS book_id,
                          bb.id AS borrow_id,
                          bb.borrow_date,
                          bb.due_date,
                          bb.return_date,
                          bb.status AS borrow_status
                        FROM users u
                        JOIN borrowed_books bb ON u.id = bb.user_id
                        JOIN books b ON bb.book_id = b.id
                        WHERE u.role = 'student'
                        ORDER BY bb.borrow_date DESC";
              $result = $conn->query($query);
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $status = strtolower($row['borrow_status']);
                  $badgeClass = 'badge-' . $status;
                  if ($status === 'borrowed') $badgeClass = 'badge-borrowed';
                  elseif ($status === 'returned') $badgeClass = 'badge-returned';
                  elseif ($status === 'rejected') $badgeClass = 'badge-rejected';
                  
                  echo "<tr data-borrow-id='" . $row['borrow_id'] . "' data-status='" . $status . "' data-borrower-name='" . htmlspecialchars($row['fullname']) . "' data-borrower-email='" . htmlspecialchars($row['email']) . "' data-book-title='" . htmlspecialchars($row['book_title']) . "'>
                    <td>" . htmlspecialchars($row['fullname']) . "</td>
                    <td>" . htmlspecialchars($row['book_title']) . "</td>
                    <td>" . htmlspecialchars($row['borrow_date']) . "</td>
                    <td>" . htmlspecialchars($row['due_date']) . "</td>
                    <td>" . htmlspecialchars($row['return_date'] ? $row['return_date'] : '-') . "</td>
                    <td class='status-badge-cell'><span class='badge " . $badgeClass . "'>" . htmlspecialchars(ucfirst($row['borrow_status'])) . "</span></td>
                    <td class='action-cell'>";
                  
                  // Action buttons based on status
                  echo "<div class='action-buttons'>";
                  
                  // View button for all statuses
                  echo "<button type='button' class='action-btn action-btn-view' onclick='viewDetails(" . $row['borrow_id'] . ")' title='View Details'><i class='fas fa-eye'></i></button>";
                  
                  if ($row['borrow_status'] == 'pending') {
                    echo "<button type='button' class='action-btn action-btn-approve' onclick='approveRequest(" . $row['borrow_id'] . ")' title='Approve Request'><i class='fas fa-check'></i></button>";
                    echo "<button type='button' class='action-btn action-btn-reject' onclick='rejectRequest(" . $row['borrow_id'] . ")' title='Reject Request'><i class='fas fa-times'></i></button>";
                  } elseif ($row['borrow_status'] == 'borrowed' && !$row['return_date']) {
                    echo "<button type='button' class='action-btn action-btn-return' onclick='markAsReturned(" . $row['borrow_id'] . ", " . $row['book_id'] . ")' title='Mark as Returned'><i class='fas fa-undo'></i></button>";
                  } elseif ($row['borrow_status'] == 'returned' || $row['borrow_status'] == 'rejected') {
                    echo "<span style='color: #bdc3c7;'><i class='fas fa-check-circle'></i> Completed</span>";
                  } else {
                    echo "<span style='color: #bdc3c7;'>—</span>";
                  }
                  
                  echo "</div>";
                  echo "</td></tr>";
                }
              } else {
                echo "<tr><td colspan='7' class='no-records'><div class='no-records-icon'><i class='fas fa-inbox'></i></div><div>No borrowing records found</div></td></tr>";
              }
              $conn->close();
              ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
  <script>
    // Status filter variable
    let currentStatusFilter = '';

    // Tab button functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        tabButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatusFilter = btn.getAttribute('data-status');
        applyFilters();
      });
    });

    // Filter functionality
    document.getElementById('reset-filters').addEventListener('click', function() {
      document.getElementById('name-filter').value = '';
      document.getElementById('book-filter').value = '';
      currentStatusFilter = '';
      tabButtons.forEach(btn => {
        if (btn.getAttribute('data-status') === '') {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });
      applyFilters();
    });

    // Real-time filtering
    ['name-filter', 'book-filter'].forEach(id => {
      document.getElementById(id).addEventListener('input', applyFilters);
      document.getElementById(id).addEventListener('change', applyFilters);
    });

    function applyFilters() {
      const nameFilter = document.getElementById('name-filter').value.toLowerCase();
      const bookFilter = document.getElementById('book-filter').value.toLowerCase();
      const statusFilter = currentStatusFilter.toLowerCase();
      
      const rows = document.querySelectorAll('tbody tr');
      let visibleCount = 0;
      
      rows.forEach(row => {
        if (row.querySelector('.no-records')) return;
        
        const name = row.cells[0].textContent.toLowerCase();
        const book = row.cells[1].textContent.toLowerCase();
        const status = row.dataset.status.toLowerCase();
        
        const matches = 
          name.includes(nameFilter) &&
          book.includes(bookFilter) &&
          (!statusFilter || status === statusFilter);
        
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
      });
      
      // Show no records message if all filtered out
      const tbody = document.querySelector('tbody');
      if (visibleCount === 0) {
        let noRecordsRow = tbody.querySelector('.no-records');
        if (noRecordsRow) {
          noRecordsRow.style.display = '';
        }
      } else {
        let noRecordsRow = tbody.querySelector('.no-records');
        if (noRecordsRow) {
          noRecordsRow.style.display = 'none';
        }
      }
    }

    // Sorting functionality
    function sortTable(sortType) {
      const table = document.querySelector('table tbody');
      const rows = Array.from(table.querySelectorAll('tr:not(.no-records)'));
      
      // Parse sort type (e.g., 'borrow-desc' -> column: 'borrow', direction: 'desc')
      const [column, direction] = sortType.split('-');

      // Sort rows
      rows.sort((a, b) => {
        let aVal, bVal;

        switch(column) {
          case 'name':
            aVal = a.cells[0].textContent.trim();
            bVal = b.cells[0].textContent.trim();
            return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
          
          case 'book':
            aVal = a.cells[1].textContent.trim();
            bVal = b.cells[1].textContent.trim();
            return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
          
          case 'borrow':
            aVal = new Date(a.cells[2].textContent.trim());
            bVal = new Date(b.cells[2].textContent.trim());
            return direction === 'asc' ? aVal - bVal : bVal - aVal;
          
          case 'due':
            aVal = new Date(a.cells[3].textContent.trim());
            bVal = new Date(b.cells[3].textContent.trim());
            return direction === 'asc' ? aVal - bVal : bVal - aVal;
          
          case 'return':
            aVal = a.cells[4].textContent.trim();
            bVal = b.cells[4].textContent.trim();
            // Handle '-' for empty return dates
            if (aVal === '-') aVal = '9999-12-31';
            if (bVal === '-') bVal = '9999-12-31';
            aVal = new Date(aVal);
            bVal = new Date(bVal);
            return direction === 'asc' ? aVal - bVal : bVal - aVal;
          
          default:
            return 0;
        }
      });

      // Reorder rows in table
      rows.forEach(row => {
        table.appendChild(row);
      });

      // Reapply current filters
      applyFilters();
    }

    // Approve Request
    function approveRequest(borrowId) {
      Swal.fire({
        title: 'Approve Request?',
        text: 'This book will be marked as borrowed by the student.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4CAF50',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          updateStatus(borrowId, 'borrowed');
        }
      });
    }

    // Reject Request
    function rejectRequest(borrowId) {
      Swal.fire({
        title: 'Reject Request?',
        text: 'The borrow request will be permanently rejected.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F44336',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          updateStatus(borrowId, 'rejected');
        }
      });
    }

    // Mark as Returned
    function markAsReturned(borrowId, bookId) {
      Swal.fire({
        title: 'Mark as Returned?',
        text: 'This book will be marked as returned.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Mark Returned',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          updateStatus(borrowId, 'returned', bookId);
        }
      });
    }

    // Update Status via API
    function updateStatus(borrowId, status, bookId = null) {
      const formData = new FormData();
      formData.append('borrow_id', borrowId);
      formData.append('new_status', status);
      if (bookId) formData.append('book_id', bookId);

      Swal.fire({
        title: 'Processing...',
        html: 'Updating status...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch('update_borrow_status.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const row = document.querySelector(`tr[data-borrow-id="${borrowId}"]`);
          if (row) {
            // Update status column
            const statusCell = row.cells[5];
            const statusText = status;
            
            // Remove old badge class
            const badge = statusCell.querySelector('.badge');
            badge.className = 'badge badge-' + statusText;
            badge.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
            
            // Update row data attribute
            row.dataset.status = statusText;
            
            // Update action cell
            const actionCell = row.cells[6];
            if (statusText === 'borrowed') {
              actionCell.innerHTML = `<div class="action-buttons"><button type="button" class="action-btn action-btn-view" onclick="viewDetails(${borrowId})" title="View Details"><i class="fas fa-eye"></i></button><button type="button" class="action-btn action-btn-return" onclick="markAsReturned(${borrowId}, ${bookId ? bookId : ''})"><i class="fas fa-undo"></i></button></div>`;
            } else if (statusText === 'returned' || statusText === 'rejected') {
              actionCell.innerHTML = '<span style="color: #bdc3c7;">—</span>';
            }
            
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: data.message || 'Status updated successfully',
              confirmButtonColor: '#0e3a5d',
              timer: 2000
            });
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to update status',
            confirmButtonColor: '#0e3a5d'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while updating status',
          confirmButtonColor: '#0e3a5d'
        });
      });
    }

    // View Details function
    function viewDetails(borrowId) {
      const row = document.querySelector(`tr[data-borrow-id="${borrowId}"]`);
      if (!row) return;

      const borrowerName = row.getAttribute('data-borrower-name');
      const borrowerEmail = row.getAttribute('data-borrower-email');
      const bookTitle = row.getAttribute('data-book-title');
      const borrowDate = row.cells[2].textContent;
      const dueDate = row.cells[3].textContent;
      const returnDate = row.cells[4].textContent;
      const status = row.cells[5].textContent.trim();

      Swal.fire({
        title: 'Borrowing Details',
        html: `
          <div style="text-align: left; padding: 20px; background: #f5f9fc; border-radius: 8px;">
            <div style="margin-bottom: 15px;">
              <strong style="color: #0e3a5d; font-size: 14px;">Borrower Information</strong>
              <div style="margin-top: 8px; padding: 10px; background: white; border-radius: 6px;">
                <p style="margin: 5px 0;"><strong>Name:</strong> ${borrowerName}</p>
                <p style="margin: 5px 0;"><strong>Email:</strong> ${borrowerEmail}</p>
              </div>
            </div>
            <div style="margin-bottom: 15px;">
              <strong style="color: #0e3a5d; font-size: 14px;">Book Information</strong>
              <div style="margin-top: 8px; padding: 10px; background: white; border-radius: 6px;">
                <p style="margin: 5px 0;"><strong>Title:</strong> ${bookTitle}</p>
              </div>
            </div>
            <div>
              <strong style="color: #0e3a5d; font-size: 14px;">Transaction Timeline</strong>
              <div style="margin-top: 8px; padding: 10px; background: white; border-radius: 6px;">
                <p style="margin: 5px 0;"><strong>Borrow Date:</strong> ${borrowDate}</p>
                <p style="margin: 5px 0;"><strong>Due Date:</strong> ${dueDate}</p>
                <p style="margin: 5px 0;"><strong>Return Date:</strong> ${returnDate}</p>
                <p style="margin: 5px 0;"><strong>Current Status:</strong> <span style="background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-weight: 600;">${status}</span></p>
              </div>
            </div>
          </div>
        `,
        icon: 'info',
        confirmButtonColor: '#0e3a5d',
        confirmButtonText: 'Close',
        width: '500px'
      });
    }

    // Initialize sort dropdown on page load
    document.addEventListener('DOMContentLoaded', function() {
      // Apply default sort (newest to lowest)
      sortTable('borrow-desc');
      
      // Add event listener to sort dropdown
      const sortDropdown = document.getElementById('sort-dropdown');
      if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
          sortTable(this.value);
        });
      }
    });
  </script>
</body>
</html>
