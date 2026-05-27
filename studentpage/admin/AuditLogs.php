<?php
session_start();
include('../dbcon.php');
include('security_utils.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check permission to view audit logs
if (!hasPermission($conn, $user_id, 'view_audit_logs')) {
    http_response_code(403);
    die("Access Denied: You do not have permission to view audit logs.");
}

// Get filter parameters
$filter_admin = $_GET['admin'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_resource = $_GET['resource'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$query = "SELECT * FROM audit_trail WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_admin)) {
    $query .= " AND (admin_name LIKE ? OR admin_email LIKE ?)";
    $search = "%$filter_admin%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

if (!empty($filter_action)) {
    $query .= " AND action = ?";
    $params[] = $filter_action;
    $types .= "s";
}

if (!empty($filter_resource)) {
    $query .= " AND resource_type = ?";
    $params[] = $filter_resource;
    $types .= "s";
}

if (!empty($filter_date)) {
    $query .= " AND DATE(action_date) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$query .= " ORDER BY action_date DESC LIMIT 500";

$stmt = $conn->prepare($query);
if ($types && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

// Get unique actions for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM audit_trail ORDER BY action");
$unique_actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $unique_actions[] = $row['action'];
}

// Get unique resource types for filter dropdown
$resources_result = $conn->query("SELECT DISTINCT resource_type FROM audit_trail ORDER BY resource_type");
$unique_resources = [];
while ($row = $resources_result->fetch_assoc()) {
    $unique_resources[] = $row['resource_type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Audit Logs - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    .filters-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      align-items: flex-end;
    }
    
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .filter-group label {
      font-weight: 600;
      color: #0e3a5d;
      font-size: 12px;
      text-transform: uppercase;
    }
    
    .filter-group input,
    .filter-group select {
      padding: 10px;
      border: 1px solid #bdc3c7;
      border-radius: 6px;
      font-family: Poppins, sans-serif;
      font-size: 13px;
    }
    
    .filter-buttons {
      display: flex;
      gap: 10px;
      height: 40px;
    }
    
    .logs-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .logs-table thead {
      background: #0e3a5d;
      color: white;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    
    .logs-table th {
      padding: 16px;
      text-align: left;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
    }
    
    .logs-table td {
      padding: 14px 16px;
      border-top: 1px solid #ecf0f1;
      font-size: 13px;
    }
    
    .logs-table tbody tr:hover {
      background: #f8fbff;
    }
    
    .action-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .action-badge.login { background: #c8e6c9; color: #2e7d32; }
    .action-badge.logout { background: #ffccbc; color: #d84315; }
    .action-badge.create { background: #bbdefb; color: #1565c0; }
    .action-badge.update { background: #fff9c4; color: #f57f17; }
    .action-badge.delete { background: #ffcdd2; color: #c62828; }
    
    .empty-state {
      text-align: center;
      padding: 40px;
      color: #7f8c8d;
    }
    
    .empty-state i {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.5;
    }
    
    @media (max-width: 768px) {
      .filters-container {
        grid-template-columns: 1fr;
      }
      
      .logs-table {
        font-size: 12px;
      }
      
      .logs-table th,
      .logs-table td {
        padding: 10px;
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
        <h2 class="section-title"><i class="fas fa-file-alt" style="margin-right: 12px;"></i>Audit Logs</h2>
        
        <!-- Filters -->
        <div class="filters-container">
          <div class="filter-group">
            <label>Admin Name/Email</label>
            <input type="text" id="filterAdmin" value="<?php echo htmlspecialchars($filter_admin); ?>" placeholder="Search admin...">
          </div>
          
          <div class="filter-group">
            <label>Action</label>
            <select id="filterAction">
              <option value="">All Actions</option>
              <?php foreach ($unique_actions as $action): ?>
                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo ($filter_action === $action ? 'selected' : ''); ?>>
                  <?php echo htmlspecialchars($action); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Resource Type</label>
            <select id="filterResource">
              <option value="">All Resources</option>
              <?php foreach ($unique_resources as $resource): ?>
                <option value="<?php echo htmlspecialchars($resource); ?>" <?php echo ($filter_resource === $resource ? 'selected' : ''); ?>>
                  <?php echo htmlspecialchars($resource); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Date</label>
            <input type="date" id="filterDate" value="<?php echo htmlspecialchars($filter_date); ?>">
          </div>
          
          <div class="filter-buttons">
            <button onclick="applyFilters()" class="btn btn-primary" style="flex: 1;">
              <i class="fas fa-search" style="margin-right: 8px;"></i>Search
            </button>
            <button onclick="resetFilters()" class="btn btn-secondary" style="flex: 1;">
              <i class="fas fa-redo" style="margin-right: 8px;"></i>Reset
            </button>
          </div>
        </div>
        
        <!-- Logs Table -->
        <?php if (!empty($logs)): ?>
          <table class="logs-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Resource</th>
                <th>Details</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?php echo date('M d, Y h:i A', strtotime($log['action_date'])); ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong><br>
                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($log['admin_email']); ?></small>
                  </td>
                  <td>
                    <span class="action-badge <?php echo strtolower(substr($log['action'], 0, 6)); ?>">
                      <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($log['resource_type']); ?>
                    <?php if ($log['resource_name']): ?>
                      <br><small style="color: #7f8c8d;"><?php echo htmlspecialchars($log['resource_name']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($log['notes']): ?>
                      <?php echo htmlspecialchars(substr($log['notes'], 0, 50)); ?>...
                    <?php else: ?>
                      <span style="color: #95a5a6;">-</span>
                    <?php endif; ?>
                  </td>
                  <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No audit logs found</p>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    function applyFilters() {
      const params = new URLSearchParams();
      
      const admin = document.getElementById('filterAdmin').value;
      if (admin) params.append('admin', admin);
      
      const action = document.getElementById('filterAction').value;
      if (action) params.append('action', action);
      
      const resource = document.getElementById('filterResource').value;
      if (resource) params.append('resource', resource);
      
      const date = document.getElementById('filterDate').value;
      if (date) params.append('date', date);
      
      window.location.search = params.toString();
    }
    
    function resetFilters() {
      window.location.href = window.location.pathname;
    }
    
    // Allow Enter key to apply filters
    ['filterAdmin', 'filterDate'].forEach(id => {
      document.getElementById(id).addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyFilters();
      });
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>
