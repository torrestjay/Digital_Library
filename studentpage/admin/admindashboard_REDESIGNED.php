<?php
session_start();
include('../dbcon.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare('SELECT id, fullname FROM users WHERE id = ? LIMIT 1');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user) {
    header('Location: ../login.php');
    exit();
}

$user_name = $user['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Admin Dashboard - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- Chart.js for data visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    /* ============================================================
       DASHBOARD LAYOUT & STYLING
       ============================================================ */
    
    .dashboard-container {
      display: grid;
      gap: var(--space-24);
      padding-bottom: var(--space-32);
    }

    /* ---- SUMMARY CARDS ROW (4 columns) ---- */
    .summary-cards-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: var(--space-20);
    }

    .summary-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: var(--space-20);
      box-shadow: var(--shadow-sm);
      transition: all var(--transition-base) ease;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-left: 4px solid;
      min-height: 140px;
    }

    .summary-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .summary-card.books-total {
      border-left-color: #2196F3;
    }

    .summary-card.books-available {
      border-left-color: #4CAF50;
    }

    .summary-card.books-borrowed {
      border-left-color: #FF9800;
    }

    .summary-card.requests-pending {
      border-left-color: #FFC107;
    }

    .summary-card-label {
      font-size: var(--font-size-sm);
      color: var(--color-text-secondary);
      text-transform: uppercase;
      font-weight: var(--font-weight-600);
      letter-spacing: 0.5px;
      margin-bottom: var(--space-12);
    }

    .summary-card-value {
      font-size: 32px;
      font-weight: var(--font-weight-700);
      color: var(--color-text-primary);
      line-height: 1;
    }

    .summary-card-icon {
      font-size: 20px;
      opacity: 0.6;
      margin-bottom: var(--space-8);
    }

    /* ---- ANALYTICS ROW (2 columns) ---- */
    .analytics-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--space-20);
    }

    .analytics-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: var(--space-20);
      box-shadow: var(--shadow-sm);
    }

    .analytics-card-header {
      display: flex;
      align-items: center;
      margin-bottom: var(--space-16);
      padding-bottom: var(--space-16);
      border-bottom: 1px solid var(--color-border);
    }

    .analytics-card-title {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      display: flex;
      align-items: center;
      gap: var(--space-12);
    }

    .analytics-card-title i {
      font-size: 18px;
      opacity: 0.7;
    }

    /* ---- CHART CONTAINER ---- */
    .chart-container {
      position: relative;
      height: 250px;
      margin-bottom: var(--space-16);
    }

    /* ---- STATS GRID (within card) ---- */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--space-16);
      padding-top: var(--space-16);
    }

    .stat-item {
      text-align: center;
      padding: var(--space-12);
      border-radius: var(--radius-md);
      background-color: #f8f9fa;
    }

    .stat-label {
      display: block;
      font-size: var(--font-size-sm);
      color: var(--color-text-secondary);
      text-transform: uppercase;
      font-weight: var(--font-weight-600);
      margin-bottom: var(--space-8);
      letter-spacing: 0.5px;
    }

    .stat-value {
      display: block;
      font-size: 24px;
      font-weight: var(--font-weight-700);
      color: var(--color-text-primary);
    }

    /* ---- RECENT ACTIVITY TABLE ---- */
    .activity-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: var(--space-20);
    }

    .activity-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: var(--space-20);
      box-shadow: var(--shadow-sm);
    }

    .activity-card-header {
      display: flex;
      align-items: center;
      margin-bottom: var(--space-16);
      padding-bottom: var(--space-16);
      border-bottom: 1px solid var(--color-border);
    }

    .activity-card-title {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      display: flex;
      align-items: center;
      gap: var(--space-12);
    }

    /* ---- TABLE STYLES ---- */
    .activity-table {
      width: 100%;
      border-collapse: collapse;
    }

    .activity-table thead {
      background-color: #f8f9fa;
    }

    .activity-table th {
      padding: var(--space-12);
      text-align: left;
      font-size: var(--font-size-sm);
      font-weight: var(--font-weight-600);
      color: var(--color-text-secondary);
      text-transform: uppercase;
      border-bottom: 2px solid var(--color-border);
      letter-spacing: 0.5px;
    }

    .activity-table td {
      padding: var(--space-12);
      border-bottom: 1px solid var(--color-border);
      font-size: var(--font-size-sm);
      color: var(--color-text-primary);
    }

    .activity-table tbody tr:hover {
      background-color: #f8f9fa;
    }

    .status-badge {
      display: inline-block;
      padding: var(--space-4) var(--space-12);
      border-radius: var(--radius-full);
      font-size: var(--font-size-sm);
      font-weight: var(--font-weight-600);
      text-transform: uppercase;
    }

    .status-pending {
      background-color: #FFF3E0;
      color: #E65100;
    }

    .status-borrowed {
      background-color: #E3F2FD;
      color: #0D47A1;
    }

    .status-returned {
      background-color: #E8F5E9;
      color: #1B5E20;
    }

    .status-overdue {
      background-color: #FFEBEE;
      color: #B71C1C;
    }

    /* ---- EMPTY STATE ---- */
    .empty-state-message {
      text-align: center;
      padding: var(--space-48);
      color: var(--color-text-secondary);
    }

    .empty-state-icon {
      font-size: 48px;
      opacity: 0.3;
      margin-bottom: var(--space-16);
    }

    .empty-state-text {
      font-size: var(--font-size-base);
      color: var(--color-text-secondary);
    }

    /* ---- LOADING STATE ---- */
    .loading-state {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: var(--space-48);
    }

    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 3px solid rgba(0, 0, 0, 0.1);
      border-top-color: var(--color-primary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 768px) {
      .summary-cards-row {
        grid-template-columns: repeat(2, 1fr);
      }

      .analytics-row {
        grid-template-columns: 1fr;
      }

      .chart-container {
        height: 200px;
      }

      .summary-card {
        min-height: 120px;
      }

      .summary-card-value {
        font-size: 24px;
      }

      .activity-table {
        font-size: 12px;
      }

      .activity-table th,
      .activity-table td {
        padding: var(--space-8);
      }
    }

    @media (max-width: 480px) {
      .summary-cards-row {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .activity-card {
        padding: var(--space-12);
      }

      .summary-card-value {
        font-size: 20px;
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
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png" alt="Profile"></a>
        </div>
      </header>

      <section class="content-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-24);">
          <h2 class="section-title">Dashboard <span class="date-time" id="currentDateTime" style="font-size: 14px; font-weight: 400; margin-left: 16px;"></span></h2>
        </div>

        <!-- LOADING STATE -->
        <div id="loadingState" style="display: none;">
          <div class="analytics-card">
            <div class="loading-state">
              <div class="loading-spinner"></div>
            </div>
          </div>
        </div>

        <!-- MAIN DASHBOARD CONTENT -->
        <div id="dashboardContent" style="display: none;">
          <div class="dashboard-container">
            
            <!-- ROW 1: SUMMARY CARDS (4 columns) -->
            <div class="summary-cards-row">
              <div class="summary-card books-total">
                <div>
                  <span class="summary-card-label"><i class="fas fa-book" style="margin-right: 6px;"></i>Total Books</span>
                  <div class="summary-card-value" id="totalBooksValue">0</div>
                </div>
              </div>

              <div class="summary-card books-available">
                <div>
                  <span class="summary-card-label"><i class="fas fa-check-circle" style="margin-right: 6px;"></i>Available Books</span>
                  <div class="summary-card-value" id="availableBooksValue">0</div>
                </div>
              </div>

              <div class="summary-card books-borrowed">
                <div>
                  <span class="summary-card-label"><i class="fas fa-hand-holding" style="margin-right: 6px;"></i>Borrowed Books</span>
                  <div class="summary-card-value" id="borrowedBooksValue">0</div>
                </div>
              </div>

              <div class="summary-card requests-pending">
                <div>
                  <span class="summary-card-label"><i class="fas fa-clock" style="margin-right: 6px;"></i>Pending Requests</span>
                  <div class="summary-card-value" id="pendingRequestsValue">0</div>
                </div>
              </div>
            </div>

            <!-- ROW 2: ANALYTICS (Left: Chart, Right: Summary) -->
            <div class="analytics-row">
              <!-- LEFT: Monthly Activity Chart -->
              <div class="analytics-card">
                <div class="analytics-card-header">
                  <div class="analytics-card-title">
                    <i class="fas fa-chart-line"></i>Monthly Activity
                  </div>
                </div>
                <div class="chart-container">
                  <canvas id="monthlyActivityChart"></canvas>
                </div>
              </div>

              <!-- RIGHT: Overdue + Users Summary -->
              <div class="analytics-card">
                <div class="analytics-card-header">
                  <div class="analytics-card-title">
                    <i class="fas fa-exclamation-triangle"></i>System Overview
                  </div>
                </div>
                <div class="stats-grid">
                  <div class="stat-item">
                    <span class="stat-label">Overdue Books</span>
                    <span class="stat-value" id="overdueValue">0</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value" id="usersValue">0</span>
                  </div>
                </div>
                <div class="stats-grid" style="margin-top: var(--space-12);">
                  <div class="stat-item">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value" style="color: #FFC107;" id="pendingValue">0</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Borrowed</span>
                    <span class="stat-value" style="color: #2196F3;" id="borrowedValue">0</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- ROW 3: RECENT ACTIVITY TABLE -->
            <div class="activity-row">
              <div class="activity-card">
                <div class="activity-card-header">
                  <div class="activity-card-title">
                    <i class="fas fa-history"></i>Recent Activity
                  </div>
                </div>
                <div id="recentActivityContainer">
                  <div class="empty-state-message">
                    <div class="empty-state-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="empty-state-text">No activity yet</div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- ERROR STATE -->
        <div id="errorState" style="display: none;">
          <div class="analytics-card">
            <div class="empty-state-message">
              <div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div>
              <div class="empty-state-text" id="errorMessage">An error occurred while loading the dashboard</div>
            </div>
          </div>
        </div>

      </section>
    </main>
  </div>

  <script>
    // ============================================================
    // DASHBOARD INITIALIZATION & DATA LOADING
    // ============================================================

    // Update current date and time
    function updateDateTime() {
      const now = new Date();
      const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      };
      document.getElementById('currentDateTime').textContent = '— ' + now.toLocaleDateString('en-US', options);
    }

    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute

    // Initialize dashboard when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
      console.log('[Dashboard] Initializing...');
      loadDashboardData();
    });

    // ============================================================
    // FETCH DASHBOARD DATA
    // ============================================================

    async function loadDashboardData() {
      console.log('[Dashboard] Loading data from server...');
      
      // Show loading state
      showLoadingState();

      try {
        const response = await fetch('getDashboardData.php', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
          }
        });

        console.log('[Dashboard] Response status:', response.status);

        if (!response.ok) {
          throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
        }

        const responseText = await response.text();
        console.log('[Dashboard] Raw response:', responseText.substring(0, 200));

        let data;
        try {
          data = JSON.parse(responseText);
        } catch (parseError) {
          console.error('[Dashboard] JSON Parse Error:', parseError);
          console.error('[Dashboard] Unparseable response:', responseText);
          throw new Error('Invalid JSON response from server');
        }

        console.log('[Dashboard] Parsed data:', data);

        if (!data.success) {
          throw new Error(data.error || 'Unknown server error');
        }

        // Populate dashboard with data
        populateDashboard(data.data);
        showDashboardContent();

      } catch (error) {
        console.error('[Dashboard] Error:', error);
        showErrorState(error.message);
      }
    }

    // ============================================================
    // POPULATE DASHBOARD WITH DATA
    // ============================================================

    function populateDashboard(data) {
      console.log('[Dashboard] Populating with data:', data);

      // Update summary cards
      document.getElementById('totalBooksValue').textContent = data.totalBooks || '0';
      document.getElementById('availableBooksValue').textContent = data.availableBooks || '0';
      document.getElementById('borrowedBooksValue').textContent = data.borrowedBooks || '0';
      document.getElementById('pendingRequestsValue').textContent = data.pendingRequests || '0';

      // Update overview stats
      document.getElementById('overdueValue').textContent = data.overdueBooks || '0';
      document.getElementById('usersValue').textContent = data.totalUsers || '0';
      document.getElementById('pendingValue').textContent = data.borrowingStatus?.pending || '0';
      document.getElementById('borrowedValue').textContent = data.borrowingStatus?.borrowed || '0';

      // Create charts
      if (data.monthlyActivity) {
        createMonthlyActivityChart(data.monthlyActivity);
      }

      // Populate recent activity table
      if (data.recentActivity && data.recentActivity.length > 0) {
        populateRecentActivity(data.recentActivity);
      } else {
        document.getElementById('recentActivityContainer').innerHTML = `
          <div class="empty-state-message">
            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
            <div class="empty-state-text">No recent activity</div>
          </div>
        `;
      }
    }

    // ============================================================
    // CREATE CHARTS
    // ============================================================

    function createMonthlyActivityChart(data) {
      console.log('[Dashboard] Creating monthly activity chart');

      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const currentMonth = new Date().getMonth();
      const chartMonths = months.slice(Math.max(0, currentMonth - 5), currentMonth + 1);
      const chartData = data.slice(Math.max(0, currentMonth - 5), currentMonth + 1);

      const ctx = document.getElementById('monthlyActivityChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: chartMonths,
          datasets: [{
            label: 'Books Borrowed',
            data: chartData,
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33, 150, 243, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#2196F3',
            pointBorderColor: 'white',
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
              position: 'top',
              labels: {
                padding: 16,
                font: {
                  size: 12,
                  weight: 600,
                  family: "'Poppins', sans-serif"
                },
                color: '#2c3e50'
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });
    }

    // ============================================================
    // POPULATE RECENT ACTIVITY TABLE
    // ============================================================

    function populateRecentActivity(activities) {
      console.log('[Dashboard] Populating recent activity table');

      let tableHTML = `
        <table class="activity-table">
          <thead>
            <tr>
              <th>Book</th>
              <th>User</th>
              <th>Status</th>
              <th>Borrow Date</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
      `;

      activities.forEach(activity => {
        const statusClass = `status-${activity.status}`;
        const borrowDate = activity.borrow_date ? new Date(activity.borrow_date).toLocaleDateString() : '-';
        const dueDate = activity.due_date ? new Date(activity.due_date).toLocaleDateString() : '-';
        const bookTitle = activity.title || 'Unknown Book';
        const userName = activity.fullname || 'Unknown User';

        tableHTML += `
          <tr>
            <td><strong>${escapeHtml(bookTitle)}</strong></td>
            <td>${escapeHtml(userName)}</td>
            <td><span class="status-badge ${statusClass}">${activity.status}</span></td>
            <td>${borrowDate}</td>
            <td>${dueDate}</td>
          </tr>
        `;
      });

      tableHTML += `
          </tbody>
        </table>
      `;

      document.getElementById('recentActivityContainer').innerHTML = tableHTML;
    }

    // ============================================================
    // UI STATE MANAGEMENT
    // ============================================================

    function showLoadingState() {
      document.getElementById('loadingState').style.display = 'block';
      document.getElementById('dashboardContent').style.display = 'none';
      document.getElementById('errorState').style.display = 'none';
    }

    function showDashboardContent() {
      document.getElementById('loadingState').style.display = 'none';
      document.getElementById('dashboardContent').style.display = 'block';
      document.getElementById('errorState').style.display = 'none';
    }

    function showErrorState(errorMessage) {
      console.error('[Dashboard] Error state:', errorMessage);
      document.getElementById('errorMessage').textContent = errorMessage || 'An error occurred while loading the dashboard';
      document.getElementById('loadingState').style.display = 'none';
      document.getElementById('dashboardContent').style.display = 'none';
      document.getElementById('errorState').style.display = 'block';

      // Also show SweetAlert for better UX
      Swal.fire({
        icon: 'error',
        title: 'Dashboard Error',
        text: errorMessage || 'Failed to load dashboard data. Please try again.',
        confirmButtonColor: '#0e3a5d',
        allowOutsideClick: false
      }).then(() => {
        // Retry option
        console.log('[Dashboard] Showing retry option');
      });
    }

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Retry dashboard load from error state
    window.retryDashboard = function() {
      console.log('[Dashboard] Retrying...');
      loadDashboardData();
    };
  </script>
</body>
</html>
