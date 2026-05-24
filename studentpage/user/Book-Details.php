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
$recommendedStmt = $conn->prepare('SELECT id, title, author, category, description, cover_image FROM books WHERE id != ? ORDER BY RAND() LIMIT 6');
$recommendedStmt->bind_param('i', $book_id);
$recommendedStmt->execute();
$recommended = $recommendedStmt->get_result();
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      min-height: 100vh;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      color: #14324a;
      overflow-x: hidden;
    }
    .page {
      max-width: 1280px;
      margin: 0 auto;
      padding: 24px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 22px;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      color: #0e3a5d;
      letter-spacing: 0.5px;
    }
    .brand img {
      width: 42px;
      height: 42px;
      border-radius: 50%;
    }
    .top-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .top-actions a,
    .action-btn {
      text-decoration: none;
      border: none;
      border-radius: 999px;
      padding: 12px 18px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
    }
    .top-actions a {
      background: #e8eff7;
      color: #0e3a5d;
    }
    .top-actions a.primary,
    .action-btn.primary {
      background: linear-gradient(135deg, #0e3a5d, #1b678f);
      color: #fff;
      box-shadow: 0 8px 18px rgba(14, 58, 93, 0.18);
    }
    .top-actions a:hover,
    .action-btn:hover {
      transform: translateY(-1px);
    }
    .hero {
      display: grid;
      grid-template-columns: 320px minmax(0, 1fr);
      gap: 22px;
      align-items: stretch;
      margin-bottom: 28px;
    }
    .cover-card,
    .info-card,
    .recommend-card {
      background: #fff;
      border: 1px solid #e5edf5;
      border-radius: 22px;
      box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08);
    }
    .cover-card {
      padding: 18px;
    }
    .cover-card img {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      border-radius: 16px;
      display: block;
      background: #e8eff7;
    }
    .info-card {
      padding: 22px;
      display: grid;
      gap: 16px;
      align-content: start;
    }
    .title {
      font-size: clamp(1.8rem, 4vw, 3rem);
      color: #0e3a5d;
      line-height: 1.1;
    }
    .meta-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .pill {
      background: #eef4fa;
      color: #0e3a5d;
      border-radius: 999px;
      padding: 8px 14px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .description {
      color: #5f7385;
      line-height: 1.85;
      font-size: 0.98rem;
    }
    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 4px;
    }
    .action-note {
      color: #5f7385;
      font-size: 0.92rem;
      line-height: 1.6;
      margin-top: -2px;
    }
    .action-btn.read {
      background: #1b678f;
      color: #fff;
      box-shadow: 0 8px 18px rgba(27, 103, 143, 0.18);
    }
    .action-btn.borrow {
      background: linear-gradient(135deg, #0e3a5d, #1b678f);
      color: #fff;
      box-shadow: 0 8px 18px rgba(14, 58, 93, 0.18);
    }
    .section-title {
      font-size: 1.3rem;
      color: #0e3a5d;
      margin: 8px 0 16px;
      font-weight: 800;
    }
    .recommend-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 16px;
    }
    .recommend-card {
      text-decoration: none;
      color: inherit;
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .recommend-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 14px 28px rgba(14, 58, 93, 0.12);
    }
    .recommend-card img {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      display: block;
      background: #e8eff7;
    }
    .recommend-card .content {
      padding: 12px 14px 14px;
    }
    .recommend-card h4 {
      font-size: 0.95rem;
      color: #14324a;
      margin-bottom: 6px;
      line-height: 1.35;
    }
    .recommend-card p {
      color: #5f7385;
      font-size: 0.86rem;
      line-height: 1.6;
    }
    @media (max-width: 900px) {
      .page { padding: 16px; }
      .hero { grid-template-columns: 1fr; }
      .cover-card { max-width: 420px; }
    }
    @media (max-width: 640px) {
      .info-card { padding: 18px; }
      .cover-card { padding: 14px; }
      .top-actions a,
      .action-btn { width: 100%; }
      .top-actions,
      .actions { width: 100%; }
      .recommend-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
      .recommend-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div class="brand">
        <img src="../Images/logo.png" alt="Readly Logo">
        <span>READLY</span>
      </div>
      <div class="top-actions">
        <a href="homepage.php">Dashboard</a>
        <a href="librarypage.php">Library</a>
        <a class="primary" href="borrowed-books.php">Borrowed Books</a>
      </div>
    </div>
    <section class="hero">
      <div class="cover-card">
        <img src="<?php echo htmlspecialchars(cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo safe_text($book['title']); ?>">
      </div>
      <div class="info-card">
        <div>
          <h1 class="title"><?php echo safe_text($book['title']); ?></h1>
        </div>
        <div class="meta-row">
          <span class="pill"><i class="fa-solid fa-user"></i> <?php echo safe_text($book['author']); ?></span>
          <span class="pill"><i class="fa-solid fa-layer-group"></i> <?php echo safe_text($book['category']); ?></span>
          <span class="pill">⭐ 4.8 Rating</span>
        </div>
        <div class="description">
          <?php echo safe_excerpt($book['description'], 700); ?>
        </div>
        <p class="action-note">
          <?php if ($canReadNow): ?>
            Your borrow is active. You can read now, return later, or request an extension when the due date gets close.
          <?php else: ?>
            Borrow this book to unlock reading access. Returned books can be borrowed again when you need them.
          <?php endif; ?>
        </p>
        <div class="actions">
          <?php if ($canReadNow): ?>
            <a class="action-btn read" href="read.php?id=<?php echo (int)$book['id']; ?>" aria-label="Read this book now">Read Now</a>
          <?php else: ?>
            <button class="action-btn borrow" type="button" onclick="confirmBorrow(<?php echo (int)$book['id']; ?>)" aria-label="Borrow this book now">Borrow Book</button>
          <?php endif; ?>
          <a class="action-btn" href="librarypage.php" aria-label="Back to the library">Back to Library</a>
        </div>
      </div>
    </section>
    <h2 class="section-title">Recommended Books</h2>
    <section class="recommend-grid">
      <?php while ($rec = $recommended->fetch_assoc()): ?>
        <a class="recommend-card" href="Book-Details.php?id=<?php echo (int)$rec['id']; ?>">
          <img src="<?php echo htmlspecialchars(cover_src($rec['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo safe_text($rec['title']); ?>">
          <div class="content">
            <h4><?php echo safe_text($rec['title']); ?></h4>
            <p><?php echo safe_excerpt($rec['description'], 120); ?></p>
          </div>
        </a>
      <?php endwhile; ?>
    </section>
  </div>
  <script>
    function confirmBorrow(bookId) {
      const proceed = confirm('Do you want to borrow this book?');
      if (proceed) {
        window.location.href = 'borrow.php?book_id=' + encodeURIComponent(bookId);
      }
    }
  </script>
</body>
</html>
