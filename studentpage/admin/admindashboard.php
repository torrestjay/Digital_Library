<?php
session_start();
include('..\dbcon.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ..\login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id LIMIT 1";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$user_name = $user['fullname'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admindashboard.css" />
  <!-- Add Chart.js for data visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Add SweetAlert for notifications -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    *{
      font-family: 'Poppins', sans-serif;

    }
    #toastBox {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast {
        background-color: #0e3a5d;
        color: white;
        padding: 12px 18px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        min-width: 200px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
    }

    .toast.error {
        background-color: #e74c3c;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
  </style>
</head>
<body>
  <div id="toastBox"></div>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
       <nav class="nav">
        <a href="admindashboard.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="AdminBookEdit.php" onclick="toggleSidebar()"><img class="icon" src="../Images/BookDetails.png" alt="Book Edit Icon" /><span>Book Edit</span></a>
        <a href="AdminUserPage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/userpage.png" alt="User Page Icon" /><span>User Page</span></a>
        <a href="SettingAdmin.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>      
      </nav>
      <div class="sign-out">
        <a href="../logout.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <section class="dashboard">
        <h2 class="dashboard-title">Hello, Admin! <span class="date-time" id="currentDateTime"></span></h2>

        <div class="dashboard-cards">
          <div class="card">
            <h3>Books Statistics</h3>
            <canvas id="booksChart"></canvas>
            <p class="card-footer" id="booksFooter"></p>
          </div>

          <div class="card">
            <h3>Borrowing Status</h3>
            <canvas id="borrowingChart"></canvas>
            <div id="borrowingStats"></div>
          </div>

          <div class="card wide">
            <h3>Monthly Activity</h3>
            <canvas id="activityChart" height="200"></canvas>
          </div>

          <div class="card users">
            <h3>Top Users</h3>
            <ul id="topUsersList">
              <!-- Will be populated by JavaScript -->
            </ul>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

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
      document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute

    // Fetch data and initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      fetchDashboardData();
    });

    async function fetchDashboardData() {
      try {
        const response = await fetch('getDashboardData.php');
        const data = await response.json();
        
        if (data.success) {
          // Update books statistics
          createBooksChart(data.booksData);
          document.getElementById('booksFooter').innerHTML = 
            `Total books: ${data.totalBooks} &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp; 
             Available: ${data.availableBooks}`;

          // Update borrowing stats
          createBorrowingChart(data.borrowingData);
          document.getElementById('borrowingStats').innerHTML = `
            <p><strong>${data.pendingRequests}</strong> Pending Borrow Request</p>
            <p><strong class="overdue">${data.overdueReturns}</strong> Overdue Returns</p>
          `;

          // Update monthly activity
          createActivityChart(data.monthlyActivity);

          // Update top users
          const topUsersList = document.getElementById('topUsersList');
            topUsersList.innerHTML = data.topUsers.map((user, index) => `
              <li>
                <img src="../Images/user-profile.png" alt="${user.fullname}">
                <span class="name">${user.fullname}</span>
                <span class="count">${user.booksBorrowed} books</span>
              </li>
            `).join('');
        } else {
          Swal.fire('Error', 'Failed to load dashboard data', 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred while loading data', 'error');
      }
    }

    function createBooksChart(data) {
      const ctx = document.getElementById('booksChart').getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Available', 'Borrowed', 'Reserved'],
          datasets: [{
            data: [data.available, data.borrowed, data.reserved],
            backgroundColor: [
              '#4CAF50',
              '#FF9800',
              '#9C27B0'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    }

    function createBorrowingChart(data) {
      const ctx = document.getElementById('borrowingChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Pending', 'Borrowed', 'Returned', 'Overdue'],
          datasets: [{
            label: 'Borrowing Status',
            data: [data.pending, data.borrowed, data.returned, data.overdue],
            backgroundColor: [
              '#FFC107',
              '#2196F3',
              '#4CAF50',
              '#F44336'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }

    function createActivityChart(data) {
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const currentMonth = new Date().getMonth();
      const last6Months = months.slice(Math.max(0, currentMonth - 5), currentMonth + 1);
      
      const ctx = document.getElementById('activityChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: last6Months,
          datasets: [
            {
              label: 'Books Borrowed',
              data: data.borrowed,
              borderColor: '#2196F3',
              backgroundColor: 'rgba(33, 150, 243, 0.1)',
              tension: 0.3,
              fill: true
            },
            {
              label: 'Books Returned',
              data: data.returned,
              borderColor: '#4CAF50',
              backgroundColor: 'rgba(76, 175, 80, 0.1)',
              tension: 0.3,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top'
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }
  </script>
  <script>
    let userr_name = <?php echo json_encode($user_name); ?>;
    let toastBox = document.getElementById('toastBox');
    let successMess = '<i class="fa-solid fa-circle-check"></i> Welcome ' + userr_name + '!';

      function showToast(msg) {
          let toast = document.createElement('div'); 
          toast.classList.add('toast');
          toast.innerHTML = msg;
          toastBox.appendChild(toast); 

          if (msg.includes('error')) {
              toast.classList.add('error');
          }

          // Play notification sound
          const sound = document.getElementById('notifySound');
          if (sound) sound.play();

          setTimeout(() => {
              toast.remove();
          }, 3000);
      }

      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('status') === 'success') {
          showToast(successMess);
          window.history.replaceState(null, null, window.location.pathname);
      }
  </script>
</body>
</html>