<?php
session_start();
include('../dbcon.php');
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}
$user_id = (int)$_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
// Borrowed, Read, Currently Reading Counts
$borrowedQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND status = 'borrowed' AND return_date IS NULL");
$borrowedQuery->bind_param("i", $user_id);
$borrowedQuery->execute();
$borrowedQuery->bind_result($borrowedCount);
$borrowedQuery->fetch();
$borrowedQuery->close();
$readQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND (return_date IS NOT NULL OR status = 'returned')");
$readQuery->bind_param("i", $user_id);
$readQuery->execute();
$readQuery->bind_result($readCount);
$readQuery->fetch();
$readQuery->close();
$currentQuery = $conn->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND status = 'borrowed' AND return_date IS NULL");
$currentQuery->bind_param("i", $user_id);
$currentQuery->execute();
$currentQuery->bind_result($currentCount);
$currentQuery->fetch();
$currentQuery->close();
// Progress
$booksRead = $readCount;
$totalGoal = max($readCount + $currentCount, 1);
$percentRead = ($totalGoal > 0) ? round(($booksRead / $totalGoal) * 100) : 0;
// Pagination
$records_per_page = 2;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;
// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM borrowed_books WHERE user_id = ?";
if ($status_filter === 'overdue') {
  $countSql .= " AND status = 'borrowed' AND return_date IS NULL AND due_date < CURDATE()";
  $countStmt = $conn->prepare($countSql);
  $countStmt->bind_param("i", $user_id);
} elseif (!empty($status_filter)) {
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
  if ($status_filter === 'overdue') {
    $sql .= " AND borrowed_books.status = 'borrowed' AND borrowed_books.return_date IS NULL AND borrowed_books.due_date < CURDATE()";
  } else {
    $sql .= " AND borrowed_books.status = ?";
  }
}
$sql .= " ORDER BY borrowed_books.borrow_date DESC LIMIT ?, ?";
if ($status_filter === 'overdue') {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iii", $user_id, $start_from, $records_per_page);
} elseif (!empty($status_filter)) {
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
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/track&record.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
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
.pagination-buttons a {
  margin-left: 10px;
  padding: 0 20px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  border: none;
  background: linear-gradient(135deg, #0e3a5d, #1b678f);
  color: #fff;
  border-radius: 16px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  text-decoration: none;
  font-weight: 600;
}
.pagination-buttons a:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 32px rgba(14, 58, 93, 0.12);
}
/* Page Info Text */
.admin-icon {
  font-weight: bold;
  color: #0e3a5d;
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
  background-color: #1b678f; /* Blue */
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
/* Table styling handled in track&record.css */
/* Table styling handled in track&record.css */
/* Table styling handled in track&record.css */
/* Table styling handled in track&record.css */
/* Table styling handled in track&record.css */
/* Table styling handled in track&record.css */
  .filter-form {
    margin: 16px 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: 'Poppins', sans-serif;
  }
  .filter-form label {
    font-weight: 600;
    font-size: 0.95rem;
    color: #0e3a5d;
  }
  .filter-form select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #c8d4e2;
    background-color: #fff;
    font-size: 0.95rem;
    transition: all 0.3s ease;
  }
  .filter-form select:hover,
  .filter-form select:focus {
    border-color: #1b678f;
    outline: none;
    box-shadow: 0 0 0 3px rgba(27, 103, 143, 0.18);
  }
  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
      <a href="homepage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="borrowed-books.php" onclick="toggleSidebar()"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
        <a href="track&record.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>       
      </nav>
      <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
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
    <p>You’ve completed <?= $booksRead ?> borrowed books out of <?= $totalGoal ?> tracked items.</p>
  </div>
</div>
  <form method="GET" class="filter-form">
    <label for="status">Filter by Status:</label>
    <select name="status" id="status" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="borrowed" <?= ($status_filter == 'borrowed') ? 'selected' : '' ?>>Borrowed</option>
      <option value="returned" <?= ($status_filter == 'returned') ? 'selected' : '' ?>>Returned</option>
      <option value="pending" <?= ($status_filter == 'pending') ? 'selected' : '' ?>>Pending</option>
      <option value="overdue" <?= ($status_filter == 'overdue') ? 'selected' : '' ?>>Overdue</option>
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
                    case 'borrowed':
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
          <a href="?page=<?= $current_page - 1 ?>">&laquo; Previous</a>
        <?php endif; ?>
        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>">Next &raquo;</a>
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
