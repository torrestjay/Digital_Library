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

// Check permission to view security dashboard
if (!hasPermission($conn, $user_id, 'view_audit_logs')) {
    http_response_code(403);
    die("Access Denied: You do not have permission to view security information.");
}

// Get intrusion summary
$summary = getIntrustionSummary($conn);

// Get recent intrusion events
$intrusion_result = $conn->query("
    SELECT * FROM intrusion_log 
    WHERE status = 'open' 
    ORDER BY created_at DESC 
    LIMIT 50
");
$intrusions = [];
while ($row = $intrusion_result->fetch_assoc()) {
    $intrusions[] = $row;
}

// Get locked accounts
$locked_result = $conn->query("
    SELECT id, name, email, failed_login_attempts, account_locked 
    FROM users 
    WHERE account_locked = 1
");
$locked_accounts = [];
while ($row = $locked_result->fetch_assoc()) {
    $locked_accounts[] = $row;
}

// Get admin vulnerability summary
$vulnerabilities = scanAdminVulnerabilities($conn);
$vuln_count = count($vulnerabilities);

// Count by severity
$severity_counts = ['high' => 0, 'medium' => 0, 'low' => 0];
foreach ($vulnerabilities as $vuln) {
    $severity = $vuln['severity'];
    if (isset($severity_counts[$severity])) {
        $severity_counts[$severity]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Security Dashboard - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    .security-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 24px;
      margin-bottom: 32px;
    }
    
    .stat-card {
      background: white;
      padding: 24px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: white;
      flex-shrink: 0;
    }
    
    .stat-icon.alert { background: #F44336; }
    .stat-icon.warning { background: #FF9800; }
    .stat-icon.info { background: #2196F3; }
    .stat-icon.success { background: #4CAF50; }
    
    .stat-content h3 {
      font-size: 14px;
      font-weight: 500;
      color: #7f8c8d;
      margin: 0 0 8px 0;
      text-transform: uppercase;
    }
    
    .stat-content .value {
      font-size: 32px;
      font-weight: 700;
      color: #0e3a5d;
      margin: 0;
    }
    
    .section-card {
      background: white;
      padding: 24px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 24px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #0e3a5d;
      margin: 0 0 16px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .event-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .event-item {
      padding: 12px;
      border-left: 4px solid #FF9800;
      background: #fff3e0;
      margin-bottom: 8px;
      border-radius: 4px;
    }
    
    .event-item.critical {
      border-left-color: #F44336;
      background: #ffebee;
    }
    
    .event-item.medium {
      border-left-color: #FF9800;
      background: #fff3e0;
    }
    
    .event-time {
      font-size: 12px;
      color: #7f8c8d;
      margin-bottom: 4px;
    }
    
    .event-message {
      font-size: 13px;
      color: #2c3e50;
      margin-bottom: 4px;
    }
    
    .event-detail {
      font-size: 12px;
      color: #7f8c8d;
    }
    
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
    
    .account-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
    }
    
    .account-table thead {
      background: #f0f0f0;
    }
    
    .account-table th {
      padding: 12px;
      text-align: left;
      font-weight: 600;
      font-size: 12px;
      color: #0e3a5d;
      text-transform: uppercase;
      border-bottom: 2px solid #e0e0e0;
    }
    
    .account-table td {
      padding: 12px;
      border-bottom: 1px solid #ecf0f1;
      font-size: 13px;
    }
    
    .unlock-btn {
      padding: 6px 12px;
      background: #2196F3;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .unlock-btn:hover {
      background: #1976D2;
    }
    
    @media (max-width: 768px) {
      .security-grid {
        grid-template-columns: 1fr;
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
        <h2 class="section-title"><i class="fas fa-shield-alt" style="margin-right: 12px;"></i>Security Dashboard</h2>
        
        <!-- Security Summary Cards -->
        <div class="security-grid">
          <div class="stat-card">
            <div class="stat-icon alert">
              <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
              <h3>Failed Login Attempts</h3>
              <p class="value"><?php echo $summary['failed_logins_24h']; ?></p>
              <small style="color: #7f8c8d;">Last 24 hours</small>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon alert">
              <i class="fas fa-lock"></i>
            </div>
            <div class="stat-content">
              <h3>Locked Accounts</h3>
              <p class="value"><?php echo $summary['locked_accounts']; ?></p>
              <small style="color: #7f8c8d;">Active locks</small>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
              <h3>Open Events</h3>
              <p class="value"><?php echo $summary['open_events']; ?></p>
              <small style="color: #7f8c8d;">Unresolved</small>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fas fa-bug"></i>
            </div>
            <div class="stat-content">
              <h3>Vulnerabilities</h3>
              <p class="value"><?php echo $vuln_count; ?></p>
              <small style="color: #7f8c8d;"><?php echo $severity_counts['high']; ?> high severity</small>
            </div>
          </div>
        </div>
        
        <!-- Recent Intrusion Events -->
        <div class="section-card">
          <h3 class="section-title">
            <i class="fas fa-triangle-exclamation"></i> Recent Suspicious Activity
          </h3>
          
          <?php if (!empty($intrusions)): ?>
            <ul class="event-list">
              <?php foreach (array_slice($intrusions, 0, 10) as $event): ?>
                <li class="event-item <?php echo strtolower($event['severity'] ?? 'medium'); ?>">
                  <div class="event-time">
                    <?php echo date('M d, Y h:i A', strtotime($event['created_at'])); ?>
                  </div>
                  <div class="event-message">
                    <strong><?php echo htmlspecialchars($event['event_type']); ?></strong>
                    <?php if ($event['user_id']): ?>
                      - User ID: <?php echo htmlspecialchars($event['user_id']); ?>
                    <?php endif; ?>
                  </div>
                  <div class="event-detail">
                    IP: <?php echo htmlspecialchars($event['ip_address']); ?> | 
                    Status: <span style="text-transform: uppercase; font-weight: 600;"><?php echo htmlspecialchars($event['status']); ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="empty-state">
              <i class="fas fa-check-circle"></i>
              <p>No suspicious activity detected</p>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Locked Accounts -->
        <?php if (!empty($locked_accounts)): ?>
          <div class="section-card">
            <h3 class="section-title">
              <i class="fas fa-ban"></i> Locked Accounts
            </h3>
            
            <table class="account-table">
              <thead>
                <tr>
                  <th>Account Name</th>
                  <th>Email</th>
                  <th>Failed Attempts</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($locked_accounts as $account): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($account['name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($account['email']); ?></td>
                    <td>
                      <span style="background: #ffcdd2; color: #c62828; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                        <?php echo $account['failed_login_attempts']; ?>
                      </span>
                    </td>
                    <td>
                      <button class="unlock-btn" onclick="unlockAccount(<?php echo $account['id']; ?>)">
                        <i class="fas fa-unlock"></i> Unlock
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        
        <!-- Admin Vulnerabilities -->
        <?php if (!empty($vulnerabilities)): ?>
          <div class="section-card">
            <h3 class="section-title">
              <i class="fas fa-exclamation-circle"></i> Admin Account Vulnerabilities
            </h3>
            
            <ul class="event-list">
              <?php foreach (array_slice($vulnerabilities, 0, 15) as $vuln): ?>
                <li class="event-item <?php echo strtolower($vuln['severity'] ?? 'medium'); ?>">
                  <div class="event-message">
                    <strong><?php echo htmlspecialchars($vuln['description']); ?></strong>
                  </div>
                  <div class="event-detail">
                    User: <?php echo htmlspecialchars($vuln['user_email']); ?> | 
                    Recommendation: <?php echo htmlspecialchars($vuln['recommended_action']); ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    function unlockAccount(userId) {
      if (confirm('Are you sure you want to unlock this account? It will reset the failed login counter.')) {
        // This would require an AJAX endpoint to handle account unlocking
        alert('Account unlock feature coming soon');
      }
    }
    
    // Auto-refresh every 30 seconds
    setTimeout(function() {
      location.reload();
    }, 30000);
  </script>
</body>
</html>
<?php $conn->close(); ?>
