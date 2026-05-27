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
      overflow: hidden;
    }
    
    .status-badge-cell {
      padding: var(--space-8) 0 !important;
    }
    
    .action-buttons {
      display: flex;
      gap: var(--space-8);
      flex-wrap: wrap;
    }
    
    .action-cell {
      white-space: nowrap;
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
      }
      
      td {
        padding: var(--space-10) var(--space-12) !important;
        font-size: var(--font-size-xs);
      }
      
      .action-buttons {
        gap: var(--space-4);
      }
      
      .btn-sm {
        padding: var(--space-6) var(--space-8);
        font-size: var(--font-size-xs);
        min-height: auto;
      }
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
          
          <div class="filter-group">
            <label for="status-filter"><i class="fas fa-filter" style="margin-right: 6px;"></i>Status</label>
            <select id="status-filter">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="borrowed">Borrowed</option>
              <option value="returned">Returned</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          
          <div class="filter-group" style="align-self: flex-end;">
            <button id="reset-filters" class="btn btn-secondary btn-sm" style="width: 100%;">
              <i class="fas fa-redo" style="margin-right: 6px;"></i>Reset Filters
            </button>
          </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="min-width: 150px;">Name</th>
                <th style="min-width: 180px;">Email</th>
                <th style="min-width: 150px;">Book Title</th>
                <th style="min-width: 110px;">Borrow Date</th>
                <th style="min-width: 110px;">Due Date</th>
                <th style="min-width: 110px;">Return Date</th>
                <th style="min-width: 110px;">Status</th>
                <th style="min-width: 140px;">Action</th>
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
                  
                  echo "<tr data-borrow-id='" . $row['borrow_id'] . "' data-status='" . $status . "'>
                    <td>" . htmlspecialchars($row['fullname']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td>" . htmlspecialchars($row['book_title']) . "</td>
                    <td>" . htmlspecialchars($row['borrow_date']) . "</td>
                    <td>" . htmlspecialchars($row['due_date']) . "</td>
                    <td>" . htmlspecialchars($row['return_date'] ? $row['return_date'] : '-') . "</td>
                    <td class='status-badge-cell'><span class='badge " . $badgeClass . "'>" . htmlspecialchars(ucfirst($row['borrow_status'])) . "</span></td>
                    <td class='action-cell'>";
                  
                  // Action buttons based on status
                  if ($row['borrow_status'] == 'pending') {
                    echo "<div class='action-buttons'>
                      <button type='button' class='btn btn-sm btn-success' onclick='approveRequest(" . $row['borrow_id'] . ")' title='Approve'><i class='fas fa-check'></i></button>
                      <button type='button' class='btn btn-sm btn-danger' onclick='rejectRequest(" . $row['borrow_id'] . ")' title='Reject'><i class='fas fa-times'></i></button>
                    </div>";
                  } elseif ($row['borrow_status'] == 'borrowed' && !$row['return_date']) {
                    echo "<button type='button' class='btn btn-sm btn-info' onclick='markAsReturned(" . $row['borrow_id'] . ", " . $row['book_id'] . ")'><i class='fas fa-undo'></i> Return</button>";
                  } else {
                    echo "<span style='color: #bdc3c7;'>—</span>";
                  }
                  echo "</td></tr>";
                }
              } else {
                echo "<tr><td colspan='8' class='no-records'><div class='no-records-icon'><i class='fas fa-inbox'></i></div><div>No borrowing records found</div></td></tr>";
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
    // Filter functionality
    document.getElementById('reset-filters').addEventListener('click', function() {
      document.getElementById('name-filter').value = '';
      document.getElementById('book-filter').value = '';
      document.getElementById('status-filter').value = '';
      applyFilters();
    });

    // Real-time filtering
    ['name-filter', 'book-filter', 'status-filter'].forEach(id => {
      document.getElementById(id).addEventListener('input', applyFilters);
      document.getElementById(id).addEventListener('change', applyFilters);
    });

    function applyFilters() {
      const nameFilter = document.getElementById('name-filter').value.toLowerCase();
      const bookFilter = document.getElementById('book-filter').value.toLowerCase();
      const statusFilter = document.getElementById('status-filter').value.toLowerCase();
      
      const rows = document.querySelectorAll('tbody tr');
      let visibleCount = 0;
      
      rows.forEach(row => {
        if (row.querySelector('.no-records')) return;
        
        const name = row.cells[0].textContent.toLowerCase();
        const book = row.cells[2].textContent.toLowerCase();
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
      if (visibleCount === 0 && tbody.querySelector('.no-records')) {
        tbody.querySelector('.no-records').style.display = '';
      }
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
          updateStatus(borrowId, 'approved');
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
      formData.append('status', status);
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
            const statusCell = row.cells[6];
            const statusText = status === 'approved' ? 'borrowed' : status;
            
            // Remove old badge class
            const badge = statusCell.querySelector('.badge');
            badge.className = 'badge badge-' + statusText;
            badge.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
            
            // Update row data attribute
            row.dataset.status = statusText;
            
            // Update action cell
            const actionCell = row.cells[7];
            if (statusText === 'borrowed') {
              actionCell.innerHTML = `<button type="button" class="btn btn-sm btn-info" onclick="markAsReturned(${borrowId}, ${bookId ? bookId : ''})"><i class="fas fa-undo"></i> Return</button>`;
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
  </script>
</body>
</html>
