<?php
session_start();
include('../dbcon.php');
require_once('borrow_rules.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($book_id <= 0) {
    header('Location: homepage.php');
    exit();
}
$stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image, views FROM books WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$book) {
    header('Location: homepage.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$activeBorrow = get_active_borrow_record($conn, $user_id, $book_id);
$canReadNow = $activeBorrow !== null;

function cover_src($cover_image)
{
    $clean = trim((string)$cover_image);
    return $clean === '' ? '../Images/logo.png' : '../Images/' . rawurlencode($clean);
}
function safe_text($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function safe_excerpt($value, $length = 180)
{
    $text = trim((string)$value);
    if ($text === '') {
        return 'No description available for this title yet.';
    }
    return htmlspecialchars(mb_strimwidth($text, 0, $length, '...'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo safe_text($book['title']); ?> - Book Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html,
    body {
      min-height: 100%;
      width: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      color: #14324a;
      overflow-x: hidden;
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: transparent;
      overflow-y: auto;
    }

    .page-content {
      padding: 26px;
      max-width: 1280px;
      width: 100%;
      margin: 0 auto;
    }

    /* ========================================
       TOPBAR
       ======================================== */
    .topbar {
      padding: 18px 0 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }

    .btn-back {
      padding: 11px 20px;
      background: #e8eff7;
      color: #0e3a5d;
      border: none;
      border-radius: 14px;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.24s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-back:hover {
      background: #dfe7f2;
      transform: translateY(-1px);
    }

    .btn-back:focus {
      outline: 2px solid #0e3a5d;
      outline-offset: 2px;
    }

    /* ========================================
       HERO SECTION
       ======================================== */
    .hero {
      display: grid;
      grid-template-columns: 280px minmax(0, 1fr);
      gap: 28px;
      align-items: stretch;
      margin-bottom: 32px;
    }

    .cover-card {
      background: #ffffff;
      border: 1px solid #e5edf5;
      border-radius: 24px;
      box-shadow: 0 14px 32px rgba(14, 58, 93, 0.08);
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 18px;
      height: fit-content;
      position: sticky;
      top: 26px;
    }

    .cover-image {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      border-radius: 16px;
      background: #e8eff7;
      display: block;
    }

    .info-card {
      background: #ffffff;
      border: 1px solid #e5edf5;
      border-radius: 24px;
      box-shadow: 0 14px 32px rgba(14, 58, 93, 0.08);
      padding: 32px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* ========================================
       BOOK INFO
       ======================================== */
    .book-header {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .book-title {
      font-size: 2rem;
      font-weight: 700;
      color: #0e3a5d;
      line-height: 1.2;
    }

    .book-author {
      font-size: 1rem;
      font-weight: 600;
      color: #5f7385;
    }

    .book-meta {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 16px 0;
      border-top: 1px solid #e5edf5;
      border-bottom: 1px solid #e5edf5;
    }

    .meta-item {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .meta-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #8a9aaa;
    }

    .meta-value {
      font-size: 0.95rem;
      font-weight: 600;
      color: #14324a;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 12px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
      width: fit-content;
    }

    .badge-available {
      background: #e8f2fb;
      color: #0e3a5d;
      border: 1px solid #b5d3ef;
    }

    .badge-borrowed {
      background: #fff5e8;
      color: #8b6914;
      border: 1px solid #f4c896;
    }

    .badge-returned {
      background: #e8f5eb;
      color: #1b5e20;
      border: 1px solid #81c784;
    }

    .book-description {
      font-size: 1rem;
      line-height: 1.8;
      color: #31485b;
      padding: 0;
    }

    /* ========================================
       ACTION BUTTONS
       ======================================== */
    .action-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .btn-action {
      flex: 1;
      min-width: 140px;
      padding: 13px 20px;
      border: none;
      border-radius: 14px;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.24s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      min-height: 44px;
    }

    .btn-action:focus {
      outline: 2px solid #0e3a5d;
      outline-offset: 2px;
    }

    .btn-primary {
      background: linear-gradient(135deg, #0e3a5d, #1b678f);
      color: white;
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(14, 58, 93, 0.2);
    }

    .btn-secondary {
      background: #e8eff7;
      color: #0e3a5d;
    }

    .btn-secondary:hover:not(:disabled) {
      background: #dfe7f2;
      transform: translateY(-1px);
    }

    .btn-action:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* ========================================
       METADATA GRID
       ======================================== */
    .metadata-section {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      padding: 20px 0;
      border-top: 1px solid #e5edf5;
    }

    .metadata-item {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .metadata-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #8a9aaa;
    }

    .metadata-value {
      font-size: 0.95rem;
      font-weight: 600;
      color: #14324a;
    }

    /* ========================================
       RESPONSIVE DESIGN
       ======================================== */
    @media (max-width: 1200px) {
      .page-content {
        padding: 20px;
      }

      .hero {
        gap: 24px;
      }

      .info-card {
        padding: 28px;
      }

      .book-title {
        font-size: 1.75rem;
      }
    }

    @media (max-width: 1024px) {
      .hero {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .cover-card {
        position: static;
        flex-direction: row;
        align-items: flex-start;
        gap: 20px;
      }

      .cover-image {
        width: 180px;
        flex-shrink: 0;
      }

      .metadata-section {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .page-content {
        padding: 16px;
      }

      .hero {
        gap: 16px;
      }

      .cover-card {
        flex-direction: column;
        align-items: stretch;
      }

      .cover-image {
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
      }

      .info-card {
        padding: 20px;
        gap: 16px;
      }

      .book-title {
        font-size: 1.5rem;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-action {
        width: 100%;
      }

      .metadata-section {
        grid-template-columns: 1fr;
        gap: 12px;
      }
    }

    @media (max-width: 480px) {
      .page-content {
        padding: 12px;
      }

      .hero {
        gap: 12px;
      }

      .info-card {
        padding: 16px;
        gap: 12px;
      }

      .book-title {
        font-size: 1.25rem;
      }

      .book-author {
        font-size: 0.95rem;
      }

      .book-description {
        font-size: 0.95rem;
      }

      .action-buttons {
        gap: 8px;
      }

      .btn-action {
        padding: 11px 16px;
        font-size: 0.9rem;
      }
    }

    /* ========================================
       ACCESSIBILITY
       ======================================== */
    a:focus,
    button:focus {
      outline: 2px solid #0e3a5d;
      outline-offset: 2px;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar (from user-shell.css) -->
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()"><img src="../Images/logo.png" alt="Readly Logo"></div>
      <nav class="nav">
        <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard"><span>Dashboard</span></a>
        <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library"><span>Library</span></a>
        <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books"><span>Borrowed Books</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track and Record"><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support"><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings"><span>Account Settings</span></a>
      </nav>
      <div class="sign-out"><a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out"><span>Sign Out</span></a></div>
    </aside>

    <main class="main-content">
      <div class="page-content">
        <div class="topbar">
          <a class="btn-back" href="librarypage.php">← Back to Library</a>
        </div>

        <section class="hero">
          <div class="cover-card">
            <img class="cover-image" src="<?php echo htmlspecialchars(cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo safe_text($book['title']); ?>">
          </div>

          <div class="info-card">
            <div class="book-header">
              <h1 class="book-title"><?php echo safe_text($book['title']); ?></h1>
              <p class="book-author">by <?php echo safe_text($book['author']); ?></p>
            </div>

            <div class="book-meta">
              <div class="meta-item">
                <span class="meta-label">Category</span>
                <span class="meta-value"><?php echo safe_text($book['category']); ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Availability</span>
                <span class="status-badge badge-available">
                  <i class="fa-solid fa-check-circle"></i> Available
                </span>
              </div>
            </div>

            <p class="book-description"><?php echo safe_excerpt($book['description'], 700); ?></p>

            <div class="action-buttons">
              <?php if ($canReadNow): ?>
                <a class="btn-action btn-primary" href="read.php?id=<?php echo (int)$book['id']; ?>" aria-label="Read this book now">
                  <i class="fa-solid fa-book-open"></i> Read Now
                </a>
                <a class="btn-action btn-secondary" href="borrowed-books.php" aria-label="Go to borrowed books">
                  <i class="fa-solid fa-arrow-left"></i> Borrowed Books
                </a>
              <?php else: ?>
                <button class="btn-action btn-primary" type="button" onclick="openBorrowModal(<?php echo (int)$book['id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>')" aria-label="Borrow this book now">
                  <i class="fa-solid fa-book"></i> Borrow Book
                </button>
                <a class="btn-action btn-secondary" href="librarypage.php" aria-label="Back to library">
                  Back to Library
                </a>
              <?php endif; ?>
            </div>

            <div class="metadata-section">
              <div class="metadata-item">
                <span class="metadata-label">Category</span>
                <span class="metadata-value"><?php echo safe_text($book['category']); ?></span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Author</span>
                <span class="metadata-value"><?php echo safe_text($book['author']); ?></span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Views</span>
                <span class="metadata-value"><?php echo number_format($book['views']); ?></span>
              </div>
              <div class="metadata-item">
                <span class="metadata-label">Status</span>
                <span class="metadata-value">Available</span>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
    }

    function openBorrowModal(bookId, bookTitle) {
      Swal.fire({
        title: 'Borrow this book?',
        html: `<p style="color: #5f7385; margin-bottom: 12px;"><strong>${bookTitle}</strong></p><p style="color: #5f7385; font-size: 0.92rem;">This will add the book to your borrowed books list for 7 days.</p>`,
        icon: 'question',
        iconColor: '#0e3a5d',
        showCancelButton: true,
        confirmButtonText: 'Borrow Book',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1b678f',
        cancelButtonColor: '#e8eff7',
        reverseButtons: true,
        customClass: {
          popup: 'swal-modern',
          title: 'swal-title',
          confirmButton: 'swal-confirm',
          cancelButton: 'swal-cancel'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'borrow.php?book_id=' + encodeURIComponent(bookId);
        }
      });
    }
  </script>
</body>
</html>
