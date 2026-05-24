<?php
session_start();
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
include('../dbcon.php');
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}
$user_id = (int)$_SESSION['user_id'];
$borrowed_books = [];
$borrowed_stmt = $conn->prepare("SELECT book_id FROM borrowed_books WHERE user_id = ? AND return_date IS NULL AND status IN ('pending', 'borrowed')");
if ($borrowed_stmt) {
  $borrowed_stmt->bind_param('i', $user_id);
  $borrowed_stmt->execute();
  $borrowed_result = $borrowed_stmt->get_result();
  while ($row = $borrowed_result->fetch_assoc()) {
    $borrowed_books[] = (int)$row['book_id'];
  }
  $borrowed_stmt->close();
}
$genres = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];
$books_by_genre = [];
$book_stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image FROM books WHERE category = ? ORDER BY title ASC');
foreach ($genres as $genre) {
  if (!$book_stmt) {
    $books_by_genre[$genre] = [];
    continue;
  }
  $book_stmt->bind_param('s', $genre);
  $book_stmt->execute();
  $result = $book_stmt->get_result();
  $books_by_genre[$genre] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
if ($book_stmt) {
  $book_stmt->close();
}
function cover_src($cover_image) {
  $clean = trim((string)$cover_image);
  if ($clean === '') {
    return '../Images/logo.png';
  }
  return '../Images/' . rawurlencode($clean);
}
function book_rating($book_id) {
  $seed = ($book_id * 37) % 13;
  return 3.8 + ($seed / 10);
}
function rating_stars($rating) {
  $filled = max(0, min(5, (int)round($rating)));
  $stars = '';
  for ($index = 1; $index <= 5; $index++) {
    $stars .= '<span class="star ' . ($index <= $filled ? 'filled' : '') . '">★</span>';
  }
  return $stars;
}
function book_description($book) {
  $description = trim((string)($book['description'] ?? ''));
  if ($description === '') {
    return 'No description available for this title yet.';
  }
  return htmlspecialchars(mb_strimwidth($description, 0, 140, '...'), ENT_QUOTES, 'UTF-8');
}
function render_book_card($book, $borrowed_books) {
  $book_id = isset($book['id']) ? (int)$book['id'] : 0;
  $title_plain = trim((string)($book['title'] ?? 'Untitled Book'));
  $title = htmlspecialchars($title_plain, ENT_QUOTES, 'UTF-8');
  $cover = htmlspecialchars(cover_src($book['cover_image'] ?? ''), ENT_QUOTES, 'UTF-8');
  $is_borrowed = in_array($book_id, $borrowed_books, true);
  $title_json = json_encode($title_plain);
  $rating = book_rating($book_id);
  $rating_label = number_format($rating, 1);
  echo '<article class="book-card">';
  echo '<a class="cover-link" href="read.php?id=' . $book_id . '">';
  echo '<div class="book-cover-wrap">';
  echo '<img class="book-cover-img" src="' . $cover . '" alt="' . $title . '">';
  echo '</div>';
  echo '</a>';
  echo '<h4 class="book-title-text">' . $title . '</h4>';
  echo '<div class="book-rating" aria-label="Rated ' . $rating_label . ' out of 5">';
  echo '<span class="rating-stars">' . rating_stars($rating) . '</span>';
  echo '<span class="rating-value">' . $rating_label . '/5</span>';
  echo '</div>';
  echo '<div class="book-description">' . book_description($book) . '</div>';
  echo '<div class="book-actions">';
  if ($is_borrowed) {
    echo '<a class="btn action-btn read-btn" href="read.php?id=' . $book_id . '">Read Now</a>';
  } else {
    echo '<a class="btn action-btn read-btn" href="Book-Details.php?id=' . $book_id . '">View</a>';
  }
  if ($is_borrowed) {
    echo '<button type="button" class="btn action-btn borrowed" disabled>Borrowed</button>';
  } else {
    echo '<button type="button" class="btn action-btn borrow" onclick="openBorrowForm(' . $title_json . ', ' . $book_id . ')">Borrow</button>';
  }
  echo '</div>';
  echo '</article>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Library</title>
  <link rel="stylesheet" href="../css/librarypage.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --brand: #0e3a5d;
      --brand-2: #1b678f;
      --bg-soft: #f4f7fb;
      --card: #ffffff;
      --text: #14324a;
      --muted: #5f7385;
      --read: #1b678f;
      --borrow: #1b678f;
      --borrow-hover: #15597c;
      --shadow: 0 10px 26px rgba(14, 58, 93, 0.12);
    }
    .content {
      padding: 26px;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      min-height: calc(100vh - 70px);
    }
    .dashboard-header h2 {
      margin: 0 0 14px 0;
      color: var(--text);
      letter-spacing: 0.2px;
      font-size: 1.85rem;
      font-weight: 700;
    }
    .search-bar {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 220px auto;
      gap: 10px;
      margin-bottom: 24px;
      background: #fff;
      padding: 8px;
      border-radius: 16px;
      box-shadow: none;
      border: 1px solid #e2ebf5;
    }
    .search-bar input {
      width: 100%;
      border: 1px solid transparent;
      border-radius: 12px;
      background: var(--bg-soft);
      padding: 12px 14px;
      font-size: 0.95rem;
      color: var(--text);
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      outline: none;
    }
    .search-bar input:hover {
      border-color: #bfd4e7;
      background: #fff;
    }
    .search-bar input:focus {
      border-color: var(--brand-2);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(27, 103, 143, 0.18);
    }
    .search-bar button {
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      color: #fff;
      padding: 0 18px;
      min-height: 44px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
      box-shadow: 0 8px 16px rgba(14, 58, 93, 0.22);
    }
    .search-bar button:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 20px rgba(14, 58, 93, 0.28);
    }
    .search-bar button:active {
      transform: translateY(0);
    }
    .search-bar select {
      width: 100%;
      border: 1px solid transparent;
      border-radius: 12px;
      background: var(--bg-soft);
      padding: 12px 14px;
      font-size: 0.95rem;
      color: var(--text);
      outline: none;
      cursor: pointer;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .search-bar select:focus {
      border-color: var(--brand-2);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(27, 103, 143, 0.18);
    }
    .book-category {
      margin: 0 0 28px 0;
      background: #fff;
      border: 1px solid #e4edf6;
      border-radius: 18px;
      padding: 16px;
      box-shadow: none;
    }
    .book-category h3 {
      color: var(--brand);
      font-size: 1.1rem;
      margin: 0 0 14px;
      font-weight: 700;
      letter-spacing: 0.2px;
    }
    .genre-carousel {
      position: relative;
      display: flex;
      align-items: center;
      padding: 0 36px;
    }
    .genre-track {
      display: flex;
      gap: 16px;
      overflow-x: hidden;
      scroll-behavior: smooth;
      padding: 6px 2px 14px;
      width: 100%;
      flex-wrap: nowrap;
    }
    .genre-track::-webkit-scrollbar {
      display: none;
    }
    .genre-btn {
      width: 46px;
      height: 46px;
      border: none;
      border-radius: 50%;
      background: var(--brand-2);
      color: #fff;
      box-shadow: 0 10px 22px rgba(27, 103, 143, 0.22);
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      transition: transform 0.2s ease, background 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
    }
    .genre-btn:hover {
      transform: translateY(-50%) scale(1.04);
      background: #15597c;
    }
    .genre-btn.left { left: 0; }
    .genre-btn.right { right: 0; }
    .book-card {
      background: var(--card);
      border: 1px solid #e5edf5;
      border-radius: 14px;
      box-shadow: 0 6px 14px rgba(14, 58, 93, 0.08);
      padding: 12px;
      display: flex;
      flex-direction: column;
      height: 100%;
      min-width: 190px;
      max-width: 190px;
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
      overflow: hidden;
      position: relative;
      z-index: 0;
    }
    .book-card:hover {
      box-shadow: 0 12px 22px rgba(14, 58, 93, 0.14);
      border-color: #c9d9e8;
    }
    .cover-link {
      display: block;
      border-radius: 10px;
      overflow: hidden;
      background: #e8eff7;
    }
    .book-cover-wrap {
      position: relative;
      overflow: hidden;
      border-radius: 10px;
    }
    .book-cover-img {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      display: block;
      transition: transform 0.35s ease, filter 0.35s ease;
    }
    .book-card:hover .book-cover-img {
      filter: brightness(0.98) saturate(1.02);
    }
    .book-title-text {
      margin: 10px 0 8px;
      font-size: 0.94rem;
      color: var(--text);
      min-height: 40px;
      line-height: 1.3;
      font-weight: 700;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .book-rating {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 0;
      color: #d18f17;
      font-size: 0.78rem;
      font-weight: 700;
    }
    .book-description {
      position: absolute;
      left: 12px;
      right: 12px;
      top: 12px;
      z-index: 3;
      color: #fff;
      font-size: 0.82rem;
      line-height: 1.5;
      padding: 12px 12px 14px;
      border-radius: 12px;
      background: linear-gradient(180deg, rgba(14, 58, 93, 0.12) 0%, rgba(14, 58, 93, 0.9) 100%);
      box-shadow: 0 12px 24px rgba(14, 58, 93, 0.16);
      opacity: 0;
      transform: translateY(10px);
      pointer-events: none;
      transition: opacity 0.28s ease, transform 0.28s ease;
    }
    .book-card:hover .book-description {
      opacity: 1;
      transform: translateY(0);
    }
    .rating-stars {
      display: inline-flex;
      gap: 2px;
      letter-spacing: 0.02em;
    }
    .star {
      color: #d1dbe6;
    }
    .star.filled {
      color: #f0b429;
    }
    .rating-value {
      color: #7a8da0;
      white-space: nowrap;
    }
    .book-actions {
      margin-top: auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .btn.action-btn {
      border: none;
      border-radius: 9px;
      padding: 10px 8px;
      font-size: 0.84rem;
      font-weight: 700;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      transition: transform 0.18s ease, filter 0.18s ease;
      color: #fff;
    }
    .btn.action-btn:hover {
      transform: translateY(-1px);
      filter: brightness(1.04);
    }
    .read-btn {
      background: var(--read);
    }
    .borrow {
      background: var(--borrow);
    }
    .borrow:hover {
      background: var(--borrow-hover);
    }
    .borrowed {
      background: #b4bcc3;
      color: #f8fbff;
      cursor: not-allowed;
      pointer-events: none;
    }
    #borrowPopup {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 18px;
    }
    .borrow-form {
      background: #fff;
      padding: 26px 24px;
      border-radius: 18px;
      width: 100%;
      max-width: 410px;
      text-align: center;
      box-shadow: 0 18px 36px rgba(0, 0, 0, 0.2);
    }
    .borrow-form img {
      width: 72px;
      margin-bottom: 12px;
    }
    .borrow-form h2 {
      font-size: 1.15rem;
      margin-bottom: 14px;
      color: var(--text);
    }
    .borrow-form input,
    .borrow-form label {
      width: 100%;
      display: block;
      text-align: left;
    }
    .borrow-form input {
      padding: 11px;
      margin: 7px 0;
      border-radius: 8px;
      border: 1px solid #c8d4e2;
      font-size: 0.92rem;
    }
    .borrow-form button {
      width: 120px;
      padding: 10px;
      margin: 10px 5px 0;
      background: var(--brand);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
    }
    .borrow-form button:hover {
      background: #12476f;
    }
    .empty-state {
      background: #fff;
      border: 1px dashed #c2d2e3;
      padding: 16px;
      border-radius: 12px;
      color: var(--muted);
      text-align: center;
    }
    @media (max-width: 900px) {
      .content {
        padding: 18px;
      }
      .genre-carousel {
        padding: 0 24px;
      }
    }
    @media (max-width: 640px) {
      .search-bar {
        grid-template-columns: 1fr;
      }
      .search-bar button {
        width: 100%;
      }
      .genre-carousel {
        padding: 0 14px;
      }
      .book-card {
        min-width: 160px;
        max-width: 160px;
      }
      .btn.action-btn {
        font-size: 0.78rem;
      }
      .book-rating {
        flex-direction: column;
        align-items: flex-start;
      }
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
        <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard" /><span>Dashboard</span></a>
        <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library" /><span>Library</span></a>
        <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books" /><span>Borrowed Books</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track and Record" /><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Page" /><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Account Settings" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out" /><span>Sign Out</span></a>
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
        <div class="dashboard-header">
          <h2>Available Books</h2>
        </div>
        <div class="search-bar">
          <input type="text" id="search-input" placeholder="Search by title, author, or category" onkeyup="searchBooks()" />
          <select id="category-filter" onchange="filterBooks()">
            <option value="">All Genres</option>
            <?php foreach ($genres as $genre): ?>
              <option value="<?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" aria-label="Search" onclick="searchBooks()">Search</button>
        </div>
        <div id="book-results">
          <?php $genre_index = 0; foreach ($books_by_genre as $genre => $book_list): ?>
            <?php $genre_id = 'genre-track-' . $genre_index++; ?>
            <section class="book-category" data-category="<?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?>">
              <h3><?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (empty($book_list)): ?>
                <div class="empty-state">No books available in this category right now.</div>
              <?php else: ?>
                <div class="genre-carousel">
                  <button class="genre-btn left" type="button" onclick="scrollGenre('<?php echo $genre_id; ?>', -1)" aria-label="Scroll left">
                    &#10094;
                  </button>
                  <div class="genre-track" id="<?php echo $genre_id; ?>">
                    <?php foreach ($book_list as $book): ?>
                      <?php render_book_card($book, $borrowed_books); ?>
                    <?php endforeach; ?>
                  </div>
                  <button class="genre-btn right" type="button" onclick="scrollGenre('<?php echo $genre_id; ?>', 1)" aria-label="Scroll right">
                    &#10095;
                  </button>
                </div>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>
  <div id="borrowPopup">
    <form class="borrow-form" action="submit_borrow.php" method="POST" onsubmit="return confirmBorrowSubmit(event)">
      <img src="../Images/logo.png" alt="Readly" />
      <h2>Fill up the following</h2>
      <input type="text" id="user_id" name="user_id" value="<?php echo $user_id; ?>" readonly>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="book_title" id="bookTitle" placeholder="Book Title" readonly>
      <label for="borrow-date" style="margin-top: 6px; font-weight: 600; color: #2d4c64;">Select return date (max 7 days)</label>
      <input type="date" id="borrow-date" name="date" required>
      <input type="text" name="contact" class="contact_num" placeholder="Contact" required>
      <input type="hidden" name="book_id" id="bookId">
      <div>
        <button type="submit">SUBMIT</button>
        <button type="button" onclick="closeBorrowForm()">CANCEL</button>
      </div>
    </form>
  </div>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
    }
    function searchBooks() {
      const query = document.getElementById('search-input').value.trim();
      const xhr = new XMLHttpRequest();
      if (query === '') {
        xhr.open('GET', 'load_default_books.php', true);
      } else {
        xhr.open('GET', 'search_books.php?query=' + encodeURIComponent(query), true);
      }
      xhr.onload = function() {
        if (xhr.status === 200) {
          document.getElementById('book-results').innerHTML = xhr.responseText;
        }
      };
      xhr.send();
    }
    function filterBooks() {
      const selectedCategory = document.getElementById('category-filter').value.trim();
      const sections = document.querySelectorAll('#book-results .book-category');
      sections.forEach((section) => {
        const sectionCategory = (section.dataset.category || '').trim();
        const matches = !selectedCategory || sectionCategory.toLowerCase() === selectedCategory.toLowerCase();
        section.style.display = matches ? '' : 'none';
      });
    }
    function closeBorrowForm() {
      document.getElementById('borrowPopup').style.display = 'none';
    }
    function scrollGenre(trackId, direction) {
      const track = document.getElementById(trackId);
      if (!track) {
        return;
      }
      track.scrollBy({
        left: direction * 360,
        behavior: 'smooth'
      });
    }
    function initializeBorrowFormConstraints() {
      document.querySelectorAll('.contact_num').forEach((input) => {
        input.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11);
        });
      });
      const dateInput = document.getElementById('borrow-date');
      if (!dateInput) {
        return;
      }
      const today = new Date();
      const maxDate = new Date();
      maxDate.setDate(today.getDate() + 7);
      const formatDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      dateInput.min = formatDate(today);
      dateInput.max = formatDate(maxDate);
    }
    function openBorrowForm(title, bookId) {
      document.getElementById('bookTitle').value = title || '';
      document.getElementById('bookId').value = bookId || '';
      document.getElementById('borrowPopup').style.display = 'flex';
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'increment_view.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send('book_id=' + encodeURIComponent(bookId));
    }
    function confirmBorrowSubmit(event) {
      event.preventDefault();
      const form = event.currentTarget;
      Swal.fire({
        title: 'Borrow this book now?',
        text: 'This will create your borrow record immediately.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0e3a5d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, borrow it',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
      return false;
    }
    document.addEventListener('DOMContentLoaded', function() {
      initializeBorrowFormConstraints();
    });
  </script>
  <?php if (!empty($success)): ?>
    <script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: <?php echo json_encode($success); ?>,
        showConfirmButton: false,
        timer: 2800,
        timerProgressBar: true
      });
    </script>
  <?php endif; ?>
</body>
</html>
