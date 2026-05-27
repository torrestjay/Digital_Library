<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}

// Ensure reading_progress column exists
$checkColStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='borrowed_books' AND COLUMN_NAME='reading_progress'");
$checkColStmt->execute();
$colResult = $checkColStmt->get_result();
if ($colResult->num_rows === 0) {
  // Column doesn't exist, add it
  $conn->query("ALTER TABLE borrowed_books ADD COLUMN reading_progress INT DEFAULT 0");
}
$checkColStmt->close();

$user_id = (int)$_SESSION['user_id'];
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$borrowedBooks = [];
$stmt = $conn->prepare('SELECT bb.id, bb.book_id, bb.borrow_date, bb.due_date, bb.return_date, bb.status, bb.reading_progress, b.title, b.author, b.cover_image FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.user_id = ? ORDER BY bb.borrow_date DESC, bb.id DESC');
if ($stmt) {
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $borrowedBooks[] = $row;
  }
  $stmt->close();
}

function borrowed_cover_src($cover_image) {
  $clean = trim((string)$cover_image);
  return $clean === '' ? '../Images/logo.png' : '../Images/' . rawurlencode($clean);
}

function borrowed_progress_percent($borrow_date, $due_date, $return_date) {
  $start = strtotime($borrow_date);
  $end = strtotime($due_date);
  if (!$start || !$end || $end <= $start) {
    return 0;
  }

  $until = $return_date ? strtotime($return_date) : time();
  $until = max($start, min($until, $end));
  return (int)max(0, min(100, round((($until - $start) / ($end - $start)) * 100)));
}

function borrowed_days_left($due_date, $return_date) {
  if ($return_date) {
    return 'Returned';
  }

  $due = new DateTime($due_date);
  $today = new DateTime('today');
  $diff = (int)$today->diff($due)->format('%r%a');
  if ($diff > 0) {
    return $diff . ' day' . ($diff === 1 ? '' : 's') . ' left';
  }
  if ($diff === 0) {
    return 'Due today';
  }
  $late = abs($diff);
  return 'Overdue by ' . $late . ' day' . ($late === 1 ? '' : 's');
}

function borrowed_days_until_due($due_date, $return_date) {
  if ($return_date) {
    return null;
  }

  $due = new DateTime($due_date);
  $today = new DateTime('today');
  return (int)$today->diff($due)->format('%r%a');
}

$activeCount = 0;
$returnedCount = 0;
$overdueCount = 0;
$pendingCount = 0;
foreach ($borrowedBooks as $row) {
  $isReturned = !empty($row['return_date']) || $row['status'] === 'returned';
  $isActive = $row['status'] === 'borrowed' && !$isReturned;
  $isPending = $row['status'] === 'pending';
  
  if ($isPending) {
    $pendingCount++;
  } elseif ($isActive) {
    $activeCount++;
    if (strtotime($row['due_date']) < strtotime(date('Y-m-d'))) {
      $overdueCount++;
    }
  }
  if ($isReturned) {
    $returnedCount++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrowed Books</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/librarypage.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { margin: 0; background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%); color: #14324a; overflow-x: hidden; }
    .content { padding: 26px; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
    .page-header h1 { margin: 0; font-size: 2rem; font-weight: 700; }
    .page-header p { margin: 6px 0 0; color: #5f7385; font-size: 0.95rem; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-card { background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08); padding: 18px; transition: all 0.2s ease; }
    .stat-card:hover { box-shadow: 0 14px 32px rgba(14, 58, 93, 0.12); transform: translateY(-2px); }
    .stat-card strong { display: block; color: #5f7385; font-size: 0.85rem; margin-bottom: 8px; font-weight: 600; }
    .stat-card span { font-size: 1.7rem; font-weight: 700; color: #0e3a5d; }
    .borrowed-list { display: grid; gap: 16px; }
    .borrowed-item { display: grid; grid-template-columns: 92px 1fr 150px; gap: 16px; align-items: stretch; background: #fff; border: 1px solid #e5edf5; border-radius: 18px; box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08); padding: 16px; transition: all 0.2s ease; }
    .borrowed-item:hover { box-shadow: 0 14px 32px rgba(14, 58, 93, 0.12); }
    .cover { width: 92px; height: 130px; border-radius: 12px; object-fit: cover; display: block; background: #e8eff7; }
    .book-meta { display: flex; flex-direction: column; }
    .book-meta h2 { margin: 0 0 4px; font-size: 1.06rem; font-weight: 700; }
    .book-meta .author { color: #5f7385; margin: 0 0 8px; font-size: 0.92rem; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
    .badge { padding: 6px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .badge.borrowed { background: #d6ecff; color: #0e3a5d; }
    .badge.pending { background: #fff2cd; color: #8c6400; }
    .badge.returned { background: #def5e5; color: #176b37; }
    .badge.overdue { background: #fde2e1; color: #a62923; }
    .progress-wrap { margin-top: 14px; }
    .progress-head { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px; color: #5f7385; font-size: 0.84rem; font-weight: 500; }
    .progress-bar { height: 10px; background: #e8eff7; border-radius: 999px; overflow: hidden; }
    .progress-bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1b678f, #0e3a5d); transition: background 0.3s ease; }
    .progress-bar.overdue > span { background: linear-gradient(90deg, #e8744f, #c94a39); }
    .nav a.active { background: rgba(255, 255, 255, 0.12); border-left-color: #fff; }
    .empty-state { background: #fff; border: 1px dashed #c2d2e3; color: #5f7385; border-radius: 16px; padding: 32px 24px; text-align: center; font-size: 1rem; }
    .empty-state-search { background: #fff; border: 1px dashed #c2d2e3; color: #5f7385; border-radius: 16px; padding: 32px 24px; text-align: center; font-size: 1rem; display: none; }
    .empty-state-search.show { display: block; }

.toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  align-items: center;
}

.toolbar input,
.toolbar select {
  height: 44px;
  padding: 0 14px;
  border: 1px solid #d9e5f0;
  border-radius: 12px;
  background: #fff;
  font-size: 14px;
  transition: all 0.2s ease;
}

.toolbar input:focus,
.toolbar select:focus {
  outline: none;
  border-color: #0e3a5d;
  box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.1);
}

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

#searchBooks {
  flex: 1;
  max-width: 420px;
  min-width: 240px;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 24px;
  margin-bottom: 0;
}

.pagination button {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 10px;
  background: #fff;
  border: 1px solid #d9e5f0;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination button:hover:not(.active) {
  background: #f5f9fc;
  border-color: #0e3a5d;
  color: #0e3a5d;
}

.pagination button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.1);
}

.pagination button.active {
  background: #0e3a5d;
  color: #fff;
  border-color: #0e3a5d;
}

.action-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: flex-start;
  min-width: 150px;
  width: 100%;
}

.action-stack form {
  width: 100%;
}

.action-stack .btn,
.action-stack a.btn,
.action-stack button.btn {
  width: 100%;
  height: 38px;
  min-height: 38px;
  padding: 0 12px;
  border: none;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-sizing: border-box;
  white-space: nowrap;
}

.action-stack .btn.read {
  background: #ffffff;
  color: #0e3a5d;
  border: 1px solid #d9e5f0;
}

.action-stack .btn.read:hover {
  background: #f5f9fc;
  border-color: #0e3a5d;
}

.action-stack .btn.return {
  background: linear-gradient(135deg, #0e3a5d, #1b678f);
  color: #fff;
}

.action-stack .btn.return:hover {
  background: linear-gradient(135deg, #0a2a47, #15527a);
}

.action-stack .btn.disabled {
  background: #e8eff7;
  color: #8fa3b5;
  border: 1px solid #d9e5f0;
  cursor: not-allowed;
  pointer-events: none;
}

.action-stack .btn.borrowed-badge {
  background: linear-gradient(135deg, #4CAF50, #45a049);
  color: #fff;
  border: none;
  cursor: default;
  pointer-events: none;
  font-weight: 700;
}

.action-stack .btn.pending-status {
  background: linear-gradient(135deg, #ff9800, #f57c00);
  color: #fff;
  border: none;
  cursor: default;
  pointer-events: none;
  font-weight: 700;
}

.action-stack .btn.view-details {
  background: #f5f9fc;
  color: #0e3a5d;
  border: 1px solid #d9e5f0;
}

.action-stack .btn.view-details:hover {
  background: #e8eff7;
  border-color: #0e3a5d;
}

.action-stack .btn.extend {
  background: linear-gradient(135deg, #2196F3, #1976D2);
  color: #fff;
}

.action-stack .btn.extend:hover {
  background: linear-gradient(135deg, #1976D2, #1565C0);
}

.action-stack .btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.1);
}

    @media (max-width: 1100px) { 
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } 
      .borrowed-item { grid-template-columns: 92px 1fr; } 
      .action-stack { grid-column: 1 / -1; min-width: 0; flex-direction: row; gap: 12px; }
      .action-stack .btn { flex: 1; min-width: 0; }
    }
    @media (max-width: 900px) {
      .toolbar { flex-direction: column; }
      #searchBooks { max-width: 100%; }
      #sortBooks { width: 100%; }
      .status-tabs { overflow-x: auto; }
    }
    @media (max-width: 700px) { 
      .content { padding: 18px; } 
      .stats-grid { grid-template-columns: 1fr; } 
      .page-header h1 { font-size: 1.6rem; }
      .page-header p { font-size: 0.9rem; }
      .borrowed-item { grid-template-columns: 1fr; } 
      .cover { width: 100%; height: 220px; } 
      .action-stack { flex-direction: column; gap: 10px; }
      .action-stack .btn { width: 100%; }
      .toolbar { flex-direction: column; }
      #searchBooks { max-width: 100%; width: 100%; }
      #sortBooks { width: 100%; }
      .pagination { margin-top: 20px; }
      .status-tabs { overflow-x: auto; }
      .tab-btn { padding: 10px 14px; font-size: 12px; }
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
        <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="borrowed-books.php" class="active"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out Icon" /><span>Sign Out</span></a>
      </div>
    </aside>
    <main class="main-content">

      <section class="content">
        
        <div class="page-header">
          <div>
            <h1>Borrowed Books</h1>
            <p>Browse the books you are currently reading or have already borrowed.</p>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card"><strong>Active Borrowed</strong><span><?php echo $activeCount; ?></span></div>
          <div class="stat-card"><strong>Pending Approval</strong><span><?php echo $pendingCount; ?></span></div>
          <div class="stat-card"><strong>Returned</strong><span><?php echo $returnedCount; ?></span></div>
          <div class="stat-card"><strong>Total Records</strong><span><?php echo count($borrowedBooks); ?></span></div>
        </div>
        <?php if (empty($borrowedBooks)): ?>
          <div class="empty-state">You have not borrowed any books yet.</div>
        <?php else: ?>
          <!-- Status Tabs at the Top -->
          <div class="status-tabs">
            <button class="tab-btn active" data-status="all">📚 All Books</button>
            <button class="tab-btn" data-status="pending">⏳ Pending</button>
            <button class="tab-btn" data-status="borrowed">📖 Borrowed</button>
            <button class="tab-btn" data-status="returned">✓ Returned</button>
            <button class="tab-btn" data-status="overdue">⚠️ Overdue</button>
          </div>

          <!-- Search and Sort Toolbar -->
          <div class="toolbar">
            <input type="text" id="searchBooks" placeholder="Search title or author...">
            <select id="sortBooks">
              <option value="newest">Newest Borrowed</option>
              <option value="oldest">Oldest Borrowed</option>
              <option value="title">Title A-Z</option>
              <option value="due">Nearest Due Date</option>
            </select>
          </div>

          <div class="empty-state-search" id="emptyState">No books found matching your criteria.</div>

          <div class="borrowed-list" id="bookList">
            <?php foreach ($borrowedBooks as $book): ?>
              <?php
                $status = strtolower((string)$book['status']);
                $isReturned = !empty($book['return_date']) || $status === 'returned';
                $isActive = $status === 'borrowed' && !$isReturned;
                $canBorrowAgain = $isReturned || $status === 'rejected';
                $daysLeftText = borrowed_days_left($book['due_date'], $book['return_date']);
                $daysUntilDue = borrowed_days_until_due($book['due_date'], $book['return_date']);
                $canRequestExtension = $isActive && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2;
                $progress = !empty($book['reading_progress']) ? (int)$book['reading_progress'] : 0;
                $overdue = $isActive && strtotime($book['due_date']) < strtotime(date('Y-m-d'));
                $statusLabel = $isReturned ? 'Returned' : ucfirst($status);
                $dataStatus = $overdue ? 'overdue' : strtolower($statusLabel);
              ?>

              <article
                  class="borrowed-item"
                  data-status="<?php echo htmlspecialchars($dataStatus, ENT_QUOTES, 'UTF-8'); ?>"
                  data-title="<?php echo strtolower(htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8')); ?>"
                  data-author="<?php echo strtolower(htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8')); ?>"
                  data-borrow="<?php echo (int)strtotime($book['borrow_date']); ?>"
                  data-due="<?php echo (int)strtotime($book['due_date']); ?>"
              >
                <img class="cover" src="<?php echo htmlspecialchars(borrowed_cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                <div class="book-meta">
                  <h2><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                  <p class="author"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <div class="badges">
                    <span class="badge <?php echo $status === 'pending' ? 'pending' : ($isReturned ? 'returned' : ($overdue ? 'overdue' : 'borrowed')); ?>">
                      <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php if (!$isReturned): ?>
                      <span class="badge borrowed">
                        <?php echo htmlspecialchars($daysLeftText, ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if ($isActive): ?>
                    <div class="progress-wrap">
                      <div class="progress-head">
                        <span>Reading Progress</span>
                        <span><?php echo $progress; ?>%</span>
                      </div>
                      <div class="progress-bar <?php echo $overdue ? 'overdue' : ''; ?>">
                        <span style="width: <?php echo $progress; ?>%"></span>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="action-stack">
                  <?php if ($status === 'pending'): ?>
                    <!-- Pending Status - Waiting for Admin Approval -->
                    <button type="button" class="btn read disabled" disabled title="Waiting for admin approval to read this book">📖 Read (Pending)</button>
                    <button type="button" class="btn pending-status" disabled title="Waiting for admin approval">⏳ Pending Approval</button>
                    <a class="btn view-details" href="Book-Details.php?id=<?php echo (int)$book['book_id']; ?>">📋 View Details</a>
                  <?php elseif ($isActive): ?>
                    <!-- Active Borrow -->
                    <a class="btn read" href="read.php?id=<?php echo (int)$book['book_id']; ?>">📖 Read</a>
                    <button type="button" class="btn borrowed-badge" disabled title="You have borrowed this book">✓ Borrowed</button>
                    <form method="post" action="return_book.php" onsubmit="return confirmReturn(event)" style="flex: 1;">
                      <input type="hidden" name="borrow_id" value="<?php echo (int)$book['id']; ?>">
                      <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>">
                      <button type="submit" class="btn return" style="width: 100%;">↩️ Return Book</button>
                    </form>
                    <?php if ($canRequestExtension): ?>
                      <form method="post" action="request_extension.php" onsubmit="return confirmExtension(event)" style="flex: 1;">
                        <input type="hidden" name="borrow_id" value="<?php echo (int)$book['id']; ?>">
                        <button type="submit" class="btn extend" style="width: 100%;">⏱️ Extend Due</button>
                      </form>
                    <?php endif; ?>
                  <?php elseif ($isReturned): ?>
                    <!-- Returned Books -->
                    <a class="btn view-details" href="Book-Details.php?id=<?php echo (int)$book['book_id']; ?>">📋 View Details</a>
                    <a class="btn return" href="borrow.php?book_id=<?php echo (int)$book['book_id']; ?>" style="flex: 1;">📚 Borrow Again</a>
                  <?php else: ?>
                    <!-- Rejected or Unavailable -->
                    <button type="button" class="btn disabled" disabled>❌ Not Available</button>
                    <a class="btn view-details" href="Book-Details.php?id=<?php echo (int)$book['book_id']; ?>">📋 View Details</a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="pagination" id="pagination"></div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }

    function confirmReturn(event) {
      event.preventDefault();
      const form = event.currentTarget;
      Swal.fire({
        title: 'Return this book?',
        text: 'This will mark the book as returned and restore availability.',
        icon: 'question',
        iconColor: '#0e3a5d',
        showCancelButton: true,
        confirmButtonColor: '#2f8f5b',
        cancelButtonColor: '#e8eff7',
        confirmButtonText: 'Yes, return it',
        cancelButtonText: 'Cancel',
        customClass: {
          cancelButton: 'swal-secondary-btn'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
      return false;
    }

    function confirmExtension(event) {
      event.preventDefault();
      const form = event.currentTarget;
      Swal.fire({
        title: 'Request extension?',
        text: 'This will add 3 days to your due date.',
        icon: 'question',
        iconColor: '#0e3a5d',
        showCancelButton: true,
        confirmButtonColor: '#0e3a5d',
        cancelButtonColor: '#e8eff7',
        confirmButtonText: 'Yes, extend it',
        cancelButtonText: 'Cancel',
        customClass: {
          cancelButton: 'swal-secondary-btn'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
      return false;
    }

    const searchInput = document.getElementById('searchBooks');
    const sortInput = document.getElementById('sortBooks');
    const bookList = document.getElementById('bookList');
    const emptyState = document.getElementById('emptyState');
    const tabButtons = document.querySelectorAll('.tab-btn');

    let cards = Array.from(document.querySelectorAll('.borrowed-item'));
    const booksPerPage = 5;
    let currentPage = 1;
    let currentStatusFilter = 'all';

    function renderBooks() {
      let filtered = cards.filter(card => {
        const search = searchInput.value.toLowerCase().trim();
        const title = card.dataset.title || '';
        const author = card.dataset.author || '';
        const status = card.dataset.status || '';

        const matchSearch = search === '' || title.includes(search) || author.includes(search);
        const matchFilter = currentStatusFilter === 'all' || status === currentStatusFilter;

        return matchSearch && matchFilter;
      });

      // Sort filtered results
      switch (sortInput.value) {
        case 'title':
          filtered.sort((a, b) => {
            const titleA = a.dataset.title || '';
            const titleB = b.dataset.title || '';
            return titleA.localeCompare(titleB);
          });
          break;

        case 'oldest':
          filtered.sort((a, b) => {
            const borrowA = Number(a.dataset.borrow) || 0;
            const borrowB = Number(b.dataset.borrow) || 0;
            return borrowA - borrowB;
          });
          break;

        case 'due':
          filtered.sort((a, b) => {
            const dueA = Number(a.dataset.due) || 0;
            const dueB = Number(b.dataset.due) || 0;
            return dueA - dueB;
          });
          break;

        case 'newest':
        default:
          filtered.sort((a, b) => {
            const borrowA = Number(a.dataset.borrow) || 0;
            const borrowB = Number(b.dataset.borrow) || 0;
            return borrowB - borrowA;
          });
      }

      // Hide all cards
      cards.forEach(card => {
        card.style.display = 'none';
      });

      // Show empty state if no results
      if (filtered.length === 0) {
        emptyState.classList.add('show');
      } else {
        emptyState.classList.remove('show');
      }

      // Calculate pagination
      const start = (currentPage - 1) * booksPerPage;
      const end = start + booksPerPage;

      // Display paginated results
      filtered.slice(start, end).forEach(card => {
        card.style.display = '';
      });

      renderPagination(filtered.length);
    }

    function renderPagination(total) {
      const pages = Math.ceil(total / booksPerPage);
      const pagination = document.getElementById('pagination');

      pagination.innerHTML = '';

      if (pages <= 1) return;

      for (let i = 1; i <= pages; i++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = i;

        if (i === currentPage) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          currentPage = i;
          renderBooks();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        pagination.appendChild(btn);
      }
    }

    // Event listeners
    searchInput.addEventListener('input', () => {
      currentPage = 1;
      renderBooks();
    });

    // Tab filtering
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        tabButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatusFilter = btn.getAttribute('data-status');
        currentPage = 1;
        renderBooks();
      });
    });

    sortInput.addEventListener('change', () => {
      currentPage = 1;
      renderBooks();
    });

    // Initial render
    renderBooks();
  </script>
  <?php if (!empty($success)): ?>
    <script>
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: <?php echo json_encode($success); ?>, showConfirmButton: false, timer: 2800, timerProgressBar: true });
    </script>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <script>
      Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: <?php echo json_encode($error); ?>, showConfirmButton: false, timer: 3000, timerProgressBar: true });
    </script>
  <?php endif; ?>
</body>
</html>