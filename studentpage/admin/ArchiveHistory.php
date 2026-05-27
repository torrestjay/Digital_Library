<?php
session_start();
include("../dbcon.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Archive History - Digital Library</title>
  
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
    .archive-log-table {
      background-color: var(--color-bg-primary);
      border-radius: var(--radius-2xl);
      box-shadow: var(--shadow-md);
      margin: var(--space-20);
      margin-top: 0;
      overflow: hidden;
    }
    
    .archive-filters {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--space-16);
      padding: var(--space-20);
      background-color: var(--color-bg-primary);
      border-bottom: 1px solid var(--color-border);
      margin: var(--space-20);
      margin-bottom: 0;
      border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    }
    
    .archive-filters input,
    .archive-filters select {
      padding: var(--space-10) var(--space-12);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      font-family: var(--font-family);
      font-size: var(--font-size-sm);
      transition: border-color var(--transition-base);
    }
    
    .archive-filters input:focus,
    .archive-filters select:focus {
      outline: none;
      border-color: var(--color-primary-light);
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .action-badge {
      display: inline-block;
      padding: var(--space-6) var(--space-12);
      border-radius: var(--radius-full);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-600);
      text-transform: uppercase;
    }
    
    .action-badge.archived {
      background-color: rgba(255, 152, 0, 0.2);
      color: #FF9800;
    }
    
    .action-badge.restored {
      background-color: rgba(76, 175, 80, 0.2);
      color: #4CAF50;
    }
    
    .book-info-cell {
      display: flex;
      align-items: center;
      gap: var(--space-12);
    }
    
    .book-cover-thumb {
      width: 40px;
      height: 60px;
      border-radius: var(--radius-md);
      object-fit: cover;
      box-shadow: var(--shadow-sm);
    }
    
    @media (max-width: 768px) {
      .archive-filters {
        margin: var(--space-16);
      }
      
      .archive-log-table {
        margin: var(--space-16);
      }
      
      td {
        padding: var(--space-10) var(--space-12) !important;
        font-size: var(--font-size-xs);
      }
      
      .book-cover-thumb {
        width: 30px;
        height: 45px;
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
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <section class="content-section">
        <h2 class="section-title"><i class="fas fa-history" style="margin-right: 12px;"></i>Book Archive History</h2>
        
        <!-- Filter Section -->
        <div class="archive-filters">
          <div>
            <label style="font-weight: 600; font-size: 12px; color: var(--color-text-secondary); text-transform: uppercase; display: block; margin-bottom: 8px;">
              <i class="fas fa-book" style="margin-right: 6px;"></i>Book Title
            </label>
            <input type="text" id="title-filter" placeholder="Search by title...">
          </div>
          
          <div>
            <label style="font-weight: 600; font-size: 12px; color: var(--color-text-secondary); text-transform: uppercase; display: block; margin-bottom: 8px;">
              <i class="fas fa-user" style="margin-right: 6px;"></i>Admin Email
            </label>
            <input type="text" id="admin-filter" placeholder="Search by admin...">
          </div>
          
          <div>
            <label style="font-weight: 600; font-size: 12px; color: var(--color-text-secondary); text-transform: uppercase; display: block; margin-bottom: 8px;">
              <i class="fas fa-filter" style="margin-right: 6px;"></i>Action
            </label>
            <select id="action-filter">
              <option value="">All Actions</option>
              <option value="Archived">Archived</option>
              <option value="Restored">Restored</option>
            </select>
          </div>
          
          <div style="align-self: flex-end;">
            <button id="reset-filters" class="btn btn-secondary btn-sm" style="width: 100%;">
              <i class="fas fa-redo" style="margin-right: 6px;"></i>Reset
            </button>
          </div>
        </div>
        
        <!-- Archive Log Table -->
        <div class="archive-log-table">
          <table>
            <thead>
              <tr>
                <th style="min-width: 200px;">Book</th>
                <th style="min-width: 150px;">Admin</th>
                <th style="min-width: 100px;">Action</th>
                <th style="min-width: 150px;">Date & Time</th>
                <th style="min-width: 250px;">Reason</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch archive logs
              $query = "SELECT 
                          al.id,
                          al.book_id,
                          al.book_title,
                          al.admin_email,
                          al.action,
                          al.reason,
                          al.action_date
                        FROM archive_log al
                        ORDER BY al.action_date DESC
                        LIMIT 200";
              
              $result = $conn->query($query);
              
              if ($result && $result->num_rows > 0) {
                while ($log = $result->fetch_assoc()) {
                  $actionClass = strtolower($log['action']);
                  $actionIcon = $log['action'] === 'Archived' ? 'fa-archive' : 'fa-undo';
                  $timestamp = new DateTime($log['action_date']);
                  $formatted_date = $timestamp->format('M d, Y');
                  $formatted_time = $timestamp->format('h:i A');
                  
                  echo "<tr data-action='" . htmlspecialchars($log['action']) . "'>
                    <td>
                      <strong>" . htmlspecialchars($log['book_title']) . "</strong>
                      <br>
                      <small style='color: #7f8c8d;'>ID: " . $log['book_id'] . "</small>
                    </td>
                    <td>" . htmlspecialchars($log['admin_email'] ?? 'N/A') . "</td>
                    <td>
                      <span class='action-badge " . $actionClass . "'>
                        <i class='fas " . $actionIcon . "' style='margin-right: 4px;'></i>" . htmlspecialchars($log['action']) . "
                      </span>
                    </td>
                    <td>
                      <div>" . $formatted_date . "</div>
                      <small style='color: #7f8c8d;'>" . $formatted_time . "</small>
                    </td>
                    <td>" . htmlspecialchars($log['reason'] ?? 'No reason provided') . "</td>
                  </tr>";
                }
              } else {
                echo "<tr><td colspan='5' style='text-align: center; padding: 40px;'>
                  <div style='font-size: 48px; margin-bottom: 16px; opacity: 0.5;'><i class='fas fa-inbox'></i></div>
                  <div>No archive history yet</div>
                </td></tr>";
              }
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
      document.getElementById('title-filter').value = '';
      document.getElementById('admin-filter').value = '';
      document.getElementById('action-filter').value = '';
      applyFilters();
    });

    // Real-time filtering
    ['title-filter', 'admin-filter', 'action-filter'].forEach(id => {
      document.getElementById(id).addEventListener('input', applyFilters);
      document.getElementById(id).addEventListener('change', applyFilters);
    });

    function applyFilters() {
      const titleFilter = document.getElementById('title-filter').value.toLowerCase();
      const adminFilter = document.getElementById('admin-filter').value.toLowerCase();
      const actionFilter = document.getElementById('action-filter').value;
      
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase();
        const admin = row.cells[1].textContent.toLowerCase();
        const action = row.dataset.action;
        
        const matches = 
          title.includes(titleFilter) &&
          admin.includes(adminFilter) &&
          (!actionFilter || action === actionFilter);
        
        row.style.display = matches ? '' : 'none';
      });
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>
