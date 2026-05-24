<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$borrowedBooks = [];
$stmt = $conn->prepare('SELECT bb.id, bb.book_id, bb.borrow_date, bb.due_date, bb.return_date, bb.status, b.title, b.author, b.cover_image FROM borrowed_books bb JOIN books b ON bb.book_id = b.id WHERE bb.user_id = ? ORDER BY bb.borrow_date DESC, bb.id DESC');
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
foreach ($borrowedBooks as $row) {
  $isReturned = !empty($row['return_date']) || $row['status'] === 'returned';
  $isActive = $row['status'] === 'borrowed' && !$isReturned;
  if ($isActive) {
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
  <link rel="stylesheet" href="../css/librarypage.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { margin: 0; background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%); color: #14324a; overflow-x: hidden; }
    .content { padding: 26px; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
    .page-header h1 { margin: 0; font-size: 2rem; }
    .page-header p { margin: 6px 0 0; color: #5f7385; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
    .stat-card { background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08); padding: 18px; }
    .stat-card strong { display: block; color: #5f7385; font-size: 0.85rem; margin-bottom: 8px; }
    .stat-card span { font-size: 1.7rem; font-weight: 700; color: #0e3a5d; }
    .borrowed-list { display: grid; gap: 16px; }
    .borrowed-item { display: grid; grid-template-columns: 92px 1fr auto; gap: 16px; align-items: stretch; background: #fff; border: 1px solid #e5edf5; border-radius: 18px; box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08); padding: 14px; }
    .cover { width: 92px; height: 130px; border-radius: 12px; object-fit: cover; display: block; background: #e8eff7; }
    .book-meta h2 { margin: 0 0 4px; font-size: 1.06rem; }
    .book-meta .author { color: #5f7385; margin: 0 0 8px; font-size: 0.92rem; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
    .badge { padding: 5px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .badge.borrowed { background: #d6ecff; color: #0e3a5d; }
    .badge.pending { background: #fff2cd; color: #8c6400; }
    .badge.returned { background: #def5e5; color: #176b37; }
    .badge.overdue { background: #fde2e1; color: #a62923; }
    .progress-wrap { margin-top: 12px; }
    .progress-head { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px; color: #5f7385; font-size: 0.84rem; }
    .progress-bar { height: 10px; background: #e8eff7; border-radius: 999px; overflow: hidden; }
    .progress-bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1b678f, #0e3a5d); }
    .action-stack { display: flex; flex-direction: column; gap: 10px; justify-content: center; min-width: 145px; }
    .btn { display: inline-flex; justify-content: center; align-items: center; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .btn.read { background: #1b678f; color: #fff; }
    .btn.return { background: #1b678f; color: #fff; }
    .btn.disabled { background: #b7c1cb; color: #fff; cursor: not-allowed; pointer-events: none; }
    .empty-state { background: #fff; border: 1px dashed #c2d2e3; color: #5f7385; border-radius: 16px; padding: 24px; text-align: center; }
    .back-link { display: inline-flex; margin-bottom: 18px; text-decoration: none; color: #0e3a5d; font-weight: 700; }
    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .borrowed-item { grid-template-columns: 92px 1fr; } .action-stack { grid-column: 1 / -1; min-width: 0; flex-direction: row; } }
    @media (max-width: 700px) { .content { padding: 18px; } .stats-grid { grid-template-columns: 1fr; } .borrowed-item { grid-template-columns: 1fr; } .cover { width: 100%; height: 220px; } .action-stack { flex-direction: column; } }
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
        <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out Icon" /><span>Sign Out</span></a>
      </div>
    </aside>
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="setting.php"><img class="icon" src="../Images/profile.png" alt="Profile"></a>
        </div>
      </header>
      <section class="content">
        <a class="back-link" href="homepage.php">Back to dashboard</a>
        <div class="page-header">
          <div>
            <h1>Borrowed Books</h1>
            <p>Browse the books you are currently reading or have already borrowed.</p>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card"><strong>Active Borrowed</strong><span><?php echo $activeCount; ?></span></div>
          <div class="stat-card"><strong>Returned</strong><span><?php echo $returnedCount; ?></span></div>
          <div class="stat-card"><strong>Overdue</strong><span><?php echo $overdueCount; ?></span></div>
          <div class="stat-card"><strong>Total Records</strong><span><?php echo count($borrowedBooks); ?></span></div>
        </div>
        <?php if (empty($borrowedBooks)): ?>
          <div class="empty-state">You have not borrowed any books yet.</div>
        <?php else: ?>
          <div class="borrowed-list">
            <?php foreach ($borrowedBooks as $book): ?>
              <?php
                $status = strtolower((string)$book['status']);
                $isReturned = !empty($book['return_date']) || $status === 'returned';
                $isActive = $status === 'borrowed' && !$isReturned;
                $canBorrowAgain = $isReturned || $status === 'rejected';
                $daysLeftText = borrowed_days_left($book['due_date'], $book['return_date']);
                $daysUntilDue = borrowed_days_until_due($book['due_date'], $book['return_date']);
                $canRequestExtension = $isActive && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2;
                $progress = borrowed_progress_percent($book['borrow_date'], $book['due_date'], $book['return_date']);
                $overdue = $isActive && strtotime($book['due_date']) < strtotime(date('Y-m-d'));
                $statusLabel = $isReturned ? 'Returned' : ucfirst($status);
              ?>
              <article class="borrowed-item">
                <img class="cover" src="<?php echo htmlspecialchars(borrowed_cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="book-meta">
                  <h2><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                  <p class="author"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <div class="badges">
                    <span class="badge <?php echo $status === 'pending' ? 'pending' : ($isReturned ? 'returned' : ($overdue ? 'overdue' : 'borrowed')); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="badge borrowed"><?php echo htmlspecialchars($daysLeftText, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="progress-wrap">
                    <div class="progress-head"><span>Reading progress</span><span><?php echo $progress; ?>%</span></div>
                    <div class="progress-bar"><span style="width: <?php echo $progress; ?>%"></span></div>
                  </div>
                </div>
                <div class="action-stack">
                  <?php if ($isActive): ?>
                    <a class="btn read" href="read.php?id=<?php echo (int)$book['book_id']; ?>">Read</a>
                  <?php else: ?>
                    <button type="button" class="btn disabled" disabled>Read Unavailable</button>
                  <?php endif; ?>
                  <?php if ($isActive): ?>
                    <form method="post" action="return_book.php" onsubmit="return confirmReturn(event)">
                      <input type="hidden" name="borrow_id" value="<?php echo (int)$book['id']; ?>">
                      <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>">
                      <button type="submit" class="btn return">Return Book</button>
                    </form>
                    <?php if ($canRequestExtension): ?>
                      <form method="post" action="request_extension.php" onsubmit="return confirmExtension(event)">
                        <input type="hidden" name="borrow_id" value="<?php echo (int)$book['id']; ?>">
                        <button type="submit" class="btn return">Request Extension</button>
                      </form>
                    <?php endif; ?>
                  <?php else: ?>
                    <?php if ($canBorrowAgain): ?>
                      <a class="btn return" href="borrow.php?book_id=<?php echo (int)$book['book_id']; ?>">Borrow Again</a>
                    <?php else: ?>
                      <button type="button" class="btn disabled" disabled>Unavailable</button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
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
        showCancelButton: true,
        confirmButtonColor: '#2f8f5b',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, return it'
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
        showCancelButton: true,
        confirmButtonColor: '#0e3a5d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, extend it'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
      return false;
    }
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