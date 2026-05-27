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
      'In the summer of 1922, a young man named Nick Carraway moved to West Egg, Long Island, to start a career in the bond business. Nick rented a small house next to a huge mansion owned by a mysterious millionaire named Jay Gatsby.',
      'The central conflict starts to moveKeep reading to see how the central conflict starts to moveKeep reading to see how the central conflict starts to moveKeep reading to see how the central conflict starts to move.',
      'Something changes. The pace picks up and the character has to react instead of observe.',
      'The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.',
      'Choices start to matter more, and the consequences of earlier decisions become visible New information appears and the reader gets a clearer sense of the stakes.',
      'This is the point where the story should feel like it is moving toward a result.The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.'
      
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
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/Read-Book.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
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
        <a class="btn-back" href="borrowed-books.php">← Back to Borrowed Books</a>
      </div>
      <section class="reader-shell">
        <aside class="book-panel">
          <img class="cover" src="<?php echo htmlspecialchars(cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
          
          <div class="book-info">
            <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="book-author"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>

          <div class="book-meta">
            <div class="meta-item">
              <span class="meta-label">Category</span>
              <span class="meta-value"><?php echo htmlspecialchars($book['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Status</span>
              <span class="meta-value"><?php echo htmlspecialchars(status_label($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Due Date</span>
              <span class="meta-value"><?php echo htmlspecialchars(days_left($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>

          <p class="book-description"><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></p>

          <div class="episodes-section">
            <h3 class="episodes-title">Episodes</h3>
            <div class="episodes-list">
              <?php foreach ($episodes as $idx => $episode): $ep_num = $idx + 1; ?>
                <a href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $ep_num; ?>" class="episode-item <?php echo $episodeIndex === $ep_num ? 'active' : ''; ?>">
                  <span class="episode-number">EP <?php echo $ep_num; ?></span>
                  <span class="episode-name"><?php echo htmlspecialchars($episode['heading'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>
        <article class="story-panel">
          <div class="story-header">
            <div>
              <h1 class="story-title"><?php echo htmlspecialchars($currentEpisode['heading'], ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="story-episode"><?php echo htmlspecialchars($currentEpisode['title'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $episodeIndex; ?>/<?php echo count($episodes); ?>)</p>
            </div>
            <div class="progress-container">
              <div class="progress-label">Reading Progress</div>
              <div class="progress-bar"><span style="width: <?php echo $progress; ?>%"></span></div>
              <div class="progress-text"><?php echo $progress; ?>%</div>
            </div>
          </div>

          <div class="story-content">
            <?php foreach ($currentEpisode['body'] as $paragraph): ?>
              <p><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
          </div>

          <div class="story-nav">
            <a class="btn-nav <?php echo $episodeIndex <= 1 ? 'disabled' : ''; ?>" href="<?php echo $episodeIndex > 1 ? 'read.php?id=' . $book_id . '&episode=' . $previousEpisode : '#'; ?>" <?php echo $episodeIndex <= 1 ? 'disabled aria-disabled="true"' : ''; ?>>← Previous</a>
            
            <?php if ($episodeIndex >= count($episodes)): ?>
              <a class="btn-nav btn-finish" href="borrowed-books.php">Finished ✓</a>
            <?php else: ?>
              <a class="btn-nav" href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $nextEpisode; ?>">Next →</a>
            <?php endif; ?>
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
