<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Borrowed, Read, Currently Reading Counts
$borrowedQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ?");
$borrowedQuery->bind_param("i", $user_id);
$borrowedQuery->execute();
$borrowedQuery->bind_result($borrowedCount);
$borrowedQuery->fetch();
$borrowedQuery->close();

$readQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND return_date IS NOT NULL");
$readQuery->bind_param("i", $user_id);
$readQuery->execute();
$readQuery->bind_result($readCount);
$readQuery->fetch();
$readQuery->close();

$currentQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND return_date IS NULL");
$currentQuery->bind_param("i", $user_id);
$currentQuery->execute();
$currentQuery->bind_result($currentCount);
$currentQuery->fetch();
$currentQuery->close();

// Progress
$month = date('m-Y');
$booksRead = 0;
$totalGoal = 20;

$progressQuery = $conn->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND due_date = ?");
$progressQuery->bind_param("is", $user_id, $month);
$progressQuery->execute();
$progressResult = $progressQuery->get_result();
if ($progressResult->num_rows > 0) {
  $progress = $progressResult->fetch_assoc();
  $booksRead = $progress['books_read'];
  $totalGoal = $progress['total_books_goal'];
}
$percentRead = ($totalGoal > 0) ? round(($booksRead / $totalGoal) * 100) : 0;

// Pagination
$records_per_page = 2;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM borrowed_books WHERE user_id = ?";
if (!empty($status_filter)) {
  $countSql .= " AND status = ?";
  $countStmt = $conn->prepare($countSql);
  $countStmt->bind_param("is", $user_id, $status_filter);
} else {
  $countStmt = $conn->prepare($countSql);
  $countStmt->bind_param("i", $user_id);
}
$countStmt->execute();
$countStmt->bind_result($total_records);
$countStmt->fetch();
$countStmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Fetch filtered & paginated borrowed books
$sql = "
  SELECT books.title, borrowed_books.borrow_date, borrowed_books.due_date, borrowed_books.return_date, borrowed_books.status
  FROM borrowed_books
  JOIN books ON borrowed_books.book_id = books.id
  WHERE borrowed_books.user_id = ?
";
if (!empty($status_filter)) {
  $sql .= " AND borrowed_books.status = ?";
}
$sql .= " ORDER BY borrowed_books.borrow_date DESC LIMIT ?, ?";

if (!empty($status_filter)) {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isii", $user_id, $status_filter, $start_from, $records_per_page);
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iii", $user_id, $start_from, $records_per_page);
}
$stmt->execute();
$borrowedBooksResult = $stmt->get_result();
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Track and Record</title>
  <link rel="stylesheet" href="../css/track&record.css" />
  <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  *{
    font-family: 'Poppins', sans-serif;

  }
  
   /* Pagination Container */
.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 10px;
  border-top: 1px solid #ccc;
  font-size: 14px;
  color: #333;
}

/* Pagination Buttons */
.pagination-buttons button {
  margin-left: 10px;
  padding: 6px 12px;
  border: none;
  background-color: black;
  color: #fff;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.pagination-buttons button:hover {
  background-color: #0056b3;
}

/* Page Info Text */
.admin-icon {
  font-weight: bold;
  color: black;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-align: center;
  display: inline-block;
}

.status-pending {
  background-color: #FFA500; /* Orange */
  color: white;
}

.status-returned {
  background-color: #28a745; /* Green */
  color: white;
}

.status-overdue {
  background-color: #dc3545; /* Red */
  color: white;
}

.status-reading {
  background-color: #007bff; /* Blue */
  color: white;
}

/* Borrowed Table Styles */
.borrowed-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  font-size: 14px;
  background-color: #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  border-radius: 10px;
  overflow: hidden;
}

.borrowed-table thead {
  background-color: #368DB8;
  color: black;
}

.borrowed-table th,
.borrowed-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.borrowed-table tbody tr:hover {
  background-color: #f5faff;
  transition: 0.3s;
}

.borrowed-table td {
  color: #333;
}

/* Optional: Add rounded corners to first and last cells */
.borrowed-table thead th:first-child {
  border-top-left-radius: 10px;
}

.borrowed-table thead th:last-child {
  border-top-right-radius: 10px;
}

 .filter-form {
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: Arial, sans-serif;
  }

  .filter-form label {
    font-weight: bold;
    font-size: 16px;
  }

  .filter-form select {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    background-color: #f9f9f9;
    font-size: 15px;
    transition: all 0.3s ease;
  }

  .filter-form select:hover,
  .filter-form select:focus {
    border-color: #007bff;
    outline: none;
    background-color: #fff;
  }
  .borrowed-table th {
    background-color: #368DB8;
    color: white;
  }

  .stat-card {
    flex: 1;
    background-color: #ffffff;
    border: 1px solid #ccc;
    padding: 20px;
    text-align: center; /* center text horizontally */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center; /* center vertically */
    border-radius: 8px;
    min-height: 120px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images\logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
     <a href="homepage.php" onclick="toggleSidebar()"><img class="icon" src="../Images\dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="Book-Details.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="track&record.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php" onclick="toggleSidebar()"><img class="icon" src="../Images\settings.png" alt="Settings Icon" /><span>Account Settings</span></a>       
      </nav>
      <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images\signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>


       <main class="main-content">
            <header class="header">
                <div class="spacer"></div>
                <div class="header-icons">
                <a href="setting.php"><img class="icon" src="../Images/profile.png"></a> 
                </div>
            </header>

            <section class="track-record-section">
  <h2>Track & Record</h2>
  <p class="subtext">Keep track of the books you've Borrowed and Read.</p>

  <div class="stats-cards">
  <div class="stat-card">
    <img src="../Images/borrowed.png" alt="Borrowed Books" />
    <div>
      <strong>Books Borrowed</strong>
      <p><?= $borrowedCount ?> Books</p>
    </div>
  </div>
  <div class="stat-card">
    <img src="../Images/read.png" alt="Read Books" />
    <div>
      <strong>Read Books</strong>
      <p><?= $readCount ?> Books</p>
    </div>
  </div>
  <div class="stat-card">
    <img src="../Images/currently.png" alt="Currently Reading" />
    <div>
      <strong>Currently Reading</strong>
      <p><?= $currentCount ?> Books</p>
    </div>
  </div>
  <div class="reading-progress">
    <div class="circle">
      <span><?= $percentRead ?>%</span>
    </div>
    <p>You’ve read <?= $booksRead ?> out of <?= $totalGoal ?> books this month.</p>
  </div>
</div>

  <form method="GET" class="filter-form">
    <label for="status">Filter by Status:</label>
    <select name="status" id="status" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
      <option value="reading" <?= (isset($_GET['status']) && $_GET['status'] == 'reading') ? 'selected' : '' ?>>Returned</option>
      <option value="returned" <?= (isset($_GET['status']) && $_GET['status'] == 'returned') ? 'selected' : '' ?>>Borrowed</option>
      <option value="overdue" <?= (isset($_GET['status']) && $_GET['status'] == 'overdue') ? 'selected' : '' ?>>Reject</option>
    </select>
  </form>


  <div class="content-grid">
    <table class="borrowed-table">
      <thead>
        <tr>
          <th>Book Title</th>
          <th>Borrow Date</th>
          <th>Due Date</th>
          <th>Return</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
          <?php while($row = $borrowedBooksResult->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= date("F j, Y", strtotime($row['borrow_date'])) ?></td>
              <td><?= date("F j, Y", strtotime($row['due_date'])) ?></td>
              <td><?= $row['return_date'] ? date("F j, Y", strtotime($row['return_date'])) : 'Not Returned' ?></td>
              <td>
                <?php
                  $status = strtolower($row['status']);
                  $badgeClass = 'status-badge ';

                  switch ($status) {
                    case 'pending':
                      $badgeClass .= 'status-pending';
                      break;
                    case 'returned':
                      $badgeClass .= 'status-returned';
                      break;
                    case 'overdue':
                      $badgeClass .= 'status-overdue';
                      break;
                    case 'reading':
                      $badgeClass .= 'status-reading';
                      break;
                    default:
                      $badgeClass .= 'status-pending'; // fallback
                  }
                ?>
                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
    </table>
  </div>
   <!-- Pagination Section -->
    <div class="pagination">
      <div class="admin-icon">Page <?= $current_page ?> of <?= $total_pages ?></div>

      <div class="pagination-buttons">
        <?php if ($current_page > 1): ?>
          <a href="?page=<?= $current_page - 1 ?>" style="border-radius:4px;background-color:#368DB8;color:white;margin-bottom:13px; padding: 10px; display:inline-block; text-decoration:none;">&laquo; Previous</a>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>" style="border-radius:4px;background-color:#368DB8;color:white;margin-bottom:13px; padding: 10px; display:inline-block; text-decoration:none;">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
</section>

    </main>
  </div>
   <script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("collapsed");
    }
    </script>
</body>
</html>


