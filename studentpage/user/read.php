<?php
session_start();
include('../dbcon.php');
require_once('borrow_rules.php');
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}
$user_id = (int)$_SESSION['user_id'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($book_id <= 0) {
  echo 'Book not found.';
  exit;
}
$stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image, views FROM books WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$book) {
  echo 'Book not found.';
  exit;
}
$viewStmt = $conn->prepare('UPDATE books SET views = views + 1 WHERE id = ?');
$viewStmt->bind_param('i', $book_id);
$viewStmt->execute();
$viewStmt->close();
$borrowRow = get_active_borrow_record($conn, $user_id, $book_id);
if (!$borrowRow) {
  $_SESSION['error'] = 'You can only read books that are currently borrowed and not returned.';
  header('Location: borrowed-books.php');
  exit();
}
$lastReadStmt = $conn->prepare('SELECT read_date FROM reading_history WHERE user_id = ? AND book_id = ? ORDER BY read_date DESC LIMIT 1');
$lastReadStmt->bind_param('ii', $user_id, $book_id);
$lastReadStmt->execute();
$lastReadRow = $lastReadStmt->get_result()->fetch_assoc();
$lastReadStmt->close();
if (!$lastReadRow || $lastReadRow['read_date'] !== date('Y-m-d')) {
  $insertRead = $conn->prepare('INSERT INTO reading_history (user_id, book_id, read_date) VALUES (?, ?, CURDATE())');
  $insertRead->bind_param('ii', $user_id, $book_id);
  $insertRead->execute();
  $insertRead->close();
}
$episodes = [
  [
    'title' => 'Episode 1',
    'heading' => 'Opening Scene',
    'body' => [
      'The first quiet pages of the story set the tone and introduce the world around the main character.',
      'Small details matter here: a place, a mood, a habit, or a moment that shapes the rest of the read.',
      'Keep reading to see how the central conflict starts to move.'
    ]
  ],
  [
    'title' => 'Episode 2',
    'heading' => 'The Turning Point',
    'body' => [
      'Something changes. The pace picks up and the character has to react instead of observe.',
      'This section is where the story starts to feel urgent and the path forward becomes less certain.',
      'New information appears and the reader gets a clearer sense of the stakes.'
    ]
  ],
  [
    'title' => 'Episode 3',
    'heading' => 'Deep Conflict',
    'body' => [
      'The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.',
      'Choices start to matter more, and the consequences of earlier decisions become visible.',
      'This is the point where the story should feel like it is moving toward a result.'
    ]
  ],
  [
    'title' => 'Episode 4',
    'heading' => 'Final Push',
    'body' => [
      'The pace tightens again as the story heads toward its final stretch.',
      'Loose threads begin to connect, and the important themes become easier to see.',
      'The reader should feel the ending getting closer with each paragraph.'
    ]
  ],
  [
    'title' => 'Episode 5',
    'heading' => 'Closing Scene',
    'body' => [
      'The last episode wraps up the reading experience and gives space for the ending to settle.',
      'What happened before now informs the final shape of the story and its emotional result.',
      'You can return to the borrowed-books page to continue with your other books.'
    ]
  ]
];
$episodeIndex = isset($_GET['episode']) ? max(1, min(count($episodes), (int)$_GET['episode'])) : 1;
$currentEpisode = $episodes[$episodeIndex - 1];
$previousEpisode = max(1, $episodeIndex - 1);
$nextEpisode = min(count($episodes), $episodeIndex + 1);
$progress = (int)round(($episodeIndex / count($episodes)) * 100);
function cover_src($cover_image) {
  $clean = trim((string)$cover_image);
  return $clean === '' ? '../Images/logo.png' : '../Images/' . rawurlencode($clean);
}
function status_label($borrowRow) {
  return $borrowRow ? 'Borrowed' : 'Not borrowed';
}
function days_left($borrowRow) {
  if (!$borrowRow || !empty($borrowRow['return_date']) || $borrowRow['status'] === 'returned') {
    return 'Returned';
  }
  $due = new DateTime($borrowRow['due_date']);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?> - Read</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
    body { min-height: 100vh; background: linear-gradient(180deg, #f7fbff 0%, #eef4fa 100%); color: #14324a; }
    .container { display: flex; min-height: 100vh; }
    .sidebar { width: 250px; background: #0e3a5d; color: #fff; display: flex; flex-direction: column; }
    .logo { height: 70px; display: flex; align-items: center; padding: 10px; cursor: pointer; }
    .logo img { width: 50px; height: 50px; border-radius: 50%; }
    .nav, .sign-out { display: flex; flex-direction: column; }
    .nav a, .sign-out a { display: flex; align-items: center; gap: 10px; padding: 15px 20px; color: #fff; text-decoration: none; }
    .nav a:hover, .sign-out a:hover { background: #12476f; }
    .icon { width: 25px; height: 25px; }
    .sign-out { margin-top: auto; }
    .main-content { flex: 1; display: flex; flex-direction: column; }
    .topbar { background: #fff; border-bottom: 1px solid #dbe6f1; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
    .topbar .brand { font-weight: 800; color: #0e3a5d; letter-spacing: 0.6px; }
    .reader-shell { padding: 22px; display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 18px; }
    .book-panel, .story-panel { background: #fff; border: 1px solid #e3ebf3; border-radius: 20px; box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08); }
    .book-panel { padding: 20px; display: grid; gap: 14px; align-content: start; }
    .cover { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 16px; background: #e8eff7; }
    .book-title { font-size: 1.4rem; font-weight: 800; color: #0e3a5d; line-height: 1.2; }
    .meta { color: #5f7385; font-size: 0.95rem; }
    .badge-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .badge { padding: 6px 10px; border-radius: 999px; background: #e8eff7; color: #0e3a5d; font-size: 0.76rem; font-weight: 700; }
    .story-panel { padding: 22px; display: grid; gap: 16px; }
    .story-head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: end; }
    .story-head h1 { font-size: 1.8rem; color: #0e3a5d; margin-bottom: 6px; }
    .story-head p { color: #5f7385; }
    .progress-wrap { display: grid; gap: 8px; min-width: 220px; }
    .progress-bar { width: 100%; height: 10px; background: #e8eff7; border-radius: 999px; overflow: hidden; }
    .progress-bar span { display: block; height: 100%; background: linear-gradient(90deg, #1b678f, #2f8f5b); border-radius: inherit; }
    .episode-card { background: linear-gradient(180deg, #fbfdff 0%, #f7fbff 100%); border: 1px solid #e5edf5; border-radius: 18px; padding: 20px; }
    .episode-label { text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; color: #1b678f; font-weight: 800; margin-bottom: 8px; }
    .episode-title { font-size: 1.4rem; font-weight: 800; color: #14324a; margin-bottom: 14px; }
    .episode-body { display: grid; gap: 14px; color: #31485b; line-height: 1.85; font-size: 1.02rem; }
    .chapter-nav { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .nav-btn, .borrow-btn { border: none; border-radius: 12px; padding: 12px 18px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .nav-btn { background: #e8eff7; color: #0e3a5d; }
    .nav-btn.primary, .borrow-btn { background: linear-gradient(135deg, #0e3a5d, #1b678f); color: #fff; }
    .nav-btn.disabled { pointer-events: none; opacity: 0.5; }
    .top-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .top-actions a { text-decoration: none; }
    .bottom-note { color: #5f7385; font-size: 0.9rem; }
    @media (max-width: 1024px) { .reader-shell { grid-template-columns: 1fr; } }
    @media (max-width: 720px) { .reader-shell { padding: 14px; } .story-panel, .book-panel { padding: 16px; } .story-head h1 { font-size: 1.45rem; } }
  </style>
</head>
<body>
  <div class="container">
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
      <div class="topbar">
        <div class="brand">READLY READER</div>
        <div class="top-actions">
          <a class="nav-btn" href="borrowed-books.php">Borrowed Books</a>
          <a class="nav-btn" href="librarypage.php">Back to Library</a>
        </div>
      </div>
      <section class="reader-shell">
        <aside class="book-panel">
          <img class="cover" src="<?php echo htmlspecialchars(cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="meta">Author: <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="meta">Genre: <?php echo htmlspecialchars($book['category'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="badge-row">
            <span class="badge"><?php echo htmlspecialchars(status_label($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="badge"><?php echo htmlspecialchars(days_left($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <p class="bottom-note"><?php echo htmlspecialchars(mb_strimwidth((string)$book['description'], 0, 240, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
        </aside>
        <article class="story-panel">
          <div class="story-head">
            <div>
              <h1><?php echo htmlspecialchars($currentEpisode['heading'], ENT_QUOTES, 'UTF-8'); ?></h1>
              <p><?php echo htmlspecialchars($currentEpisode['title'], ENT_QUOTES, 'UTF-8'); ?> of <?php echo count($episodes); ?></p>
            </div>
            <div class="progress-wrap">
              <div class="meta">Episode progress: <?php echo $progress; ?>%</div>
              <div class="progress-bar"><span style="width: <?php echo $progress; ?>%"></span></div>
            </div>
          </div>
          <section class="episode-card">
            <div class="episode-label">Episode Reader</div>
            <div class="episode-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="episode-body">
              <?php foreach ($currentEpisode['body'] as $paragraph): ?>
                <p><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endforeach; ?>
            </div>
          </section>
          <div class="chapter-nav">
            <a class="nav-btn <?php echo $episodeIndex <= 1 ? 'disabled' : ''; ?>" href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $previousEpisode; ?>">Back</a>
            <a class="nav-btn primary <?php echo $episodeIndex >= count($episodes) ? 'disabled' : ''; ?>" href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $nextEpisode; ?>">Next</a>
          </div>
          <div class="chapter-nav">
            <a class="borrow-btn" href="borrowed-books.php">Return to Borrowed Books</a>
            <a class="nav-btn" href="librarypage.php">Browse More Books</a>
          </div>
        </article>
      </section>
    </main>
  </div>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
    }
  </script>
</body>
</html>
