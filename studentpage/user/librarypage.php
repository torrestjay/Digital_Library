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
$pending_books = [];
$borrowed_stmt = $conn->prepare("SELECT book_id, status FROM borrowed_books WHERE user_id = ? AND return_date IS NULL AND status IN ('pending', 'borrowed')");
if ($borrowed_stmt) {
  $borrowed_stmt->bind_param('i', $user_id);
  $borrowed_stmt->execute();
  $borrowed_result = $borrowed_stmt->get_result();
  while ($row = $borrowed_result->fetch_assoc()) {
    $book_id = (int)$row['book_id'];
    $borrowed_books[] = $book_id;
    if ($row['status'] === 'pending') {
      $pending_books[] = $book_id;
    }
  }
  $borrowed_stmt->close();
}
$genres = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];
$books_by_genre = [];
$book_stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image FROM books WHERE category = ? AND archived_at IS NULL ORDER BY title ASC');
foreach ($genres as $genre) {
  if (!$book_stmt) {
    $books_by_genre[$genre] = [];
    continue;
  }
  $book_stmt->bind_param('s', $genre);
  $book_stmt->execute();
  $result = $book_stmt->get_result();
  $books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
  
  // Sort books: non-borrowed first, then borrowed
  usort($books, function($a, $b) use ($borrowed_books) {
    $a_borrowed = in_array((int)$a['id'], $borrowed_books, true) ? 1 : 0;
    $b_borrowed = in_array((int)$b['id'], $borrowed_books, true) ? 1 : 0;
    return $a_borrowed <=> $b_borrowed;
  });
  
  $books_by_genre[$genre] = $books;
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
function render_book_card($book, $borrowed_books, $pending_books = []) {
  $book_id = isset($book['id']) ? (int)$book['id'] : 0;
  $title_plain = trim((string)($book['title'] ?? 'Untitled Book'));
  $title = htmlspecialchars($title_plain, ENT_QUOTES, 'UTF-8');
  $cover = htmlspecialchars(cover_src($book['cover_image'] ?? ''), ENT_QUOTES, 'UTF-8');
  $is_borrowed = in_array($book_id, $borrowed_books, true);
  $is_pending = in_array($book_id, $pending_books, true);
  $title_json = json_encode($title_plain);
  $rating = book_rating($book_id);
  $rating_label = number_format($rating, 1);
  echo '<article class="book-card">';
  echo '<a class="cover-link" href="read.php?id=' . $book_id . '">';
  echo '<div class="book-cover-wrap">';
  if ($is_pending) {
    echo '<div class="book-pending-badge">⏳ Pending</div>';
  }
  echo '<img class="book-cover-img" src="' . $cover . '" alt="' . $title . '">';
  echo '</div>';
  echo '</a>';
  echo '<h4 class="book-title-text">' . $title . '</h4>';
  echo '<p class="book-author-text">' . htmlspecialchars((string)($book['author'] ?? 'Unknown Author'), ENT_QUOTES, 'UTF-8') . '</p>';
  echo '<div class="book-rating" aria-label="Rated ' . $rating_label . ' out of 5">';
  echo '<span class="rating-stars">' . rating_stars($rating) . '</span>';
  echo '<span class="rating-value">' . $rating_label . '/5</span>';
  echo '</div>';
  echo '<div class="book-description">' . book_description($book) . '</div>';
  echo '<div class="book-actions">';
  if ($is_borrowed) {
    echo '<a class="featured-btn secondary" href="read.php?id=' . $book_id . '">Read</a>';
  } else {
    echo '<a class="featured-btn secondary" href="Book-Details.php?id=' . $book_id . '">View</a>';
  }
  if ($is_borrowed) {
    echo '<button type="button" class="featured-btn secondary" disabled style="opacity: 0.6; cursor: not-allowed;">Borrowed</button>';
  } else {
    echo '<button type="button" class="featured-btn primary borrow-btn" data-book-id="' . $book_id . '" data-book-title="' . htmlspecialchars($title_plain, ENT_QUOTES, 'UTF-8') . '">Borrow Book</button>';
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
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/librarypage.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
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
      transform: translateY(-50%);
      background: #15597c;
    }
    .genre-btn.left { left: 0; }
    .genre-btn.right { right: 0; }
    .book-card {
      background: transparent;
      border: none;
      border-radius: 14px;
      box-shadow: none;
      padding: 0;
      display: flex;
      flex-direction: column;
      height: 100%;
      min-width: 190px;
      max-width: 190px;
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
      overflow: visible;
      position: relative;
      z-index: 0;
    }
    .book-card:hover {
      box-shadow: none;
      border-color: transparent;
    }
    .cover-link {
      display: block;
      border-radius: 10px;
      overflow: hidden;
      background: #e8eff7;
      margin-bottom: 8px;
    }
    .book-cover-wrap {
      position: relative;
      overflow: hidden;
      border-radius: 10px;
    }
    .book-pending-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: linear-gradient(135deg, #ffa500, #ff8c00);
      color: #fff;
      font-size: 0.7rem;
      padding: 4px 8px;
      border-radius: 6px;
      font-weight: 700;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(255, 140, 0, 0.3);
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
      margin: 0 0 6px 0;
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
    .book-author-text {
      margin: 0 0 8px 0;
      font-size: 0.8rem;
      line-height: 1.2;
      color: #5f7385;
      font-weight: 500;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .book-rating {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 8px;
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
.book-actions{
  margin-top: auto;

  display: flex;
  align-items: center;
  gap: 10px;

  width: 100%;
}
.featured-btn{
  flex: 1;

  height: 42px;
  min-width: 0;

  padding: 0 14px;

  border-radius: 12px;
  border: none;

  display: flex;
  align-items: center;
  justify-content: center;

  text-decoration: none;

  font-size: 0.82rem;
  font-weight: 600;

  transition: all 0.25s ease;
  cursor: pointer;

  white-space: nowrap;
  box-sizing: border-box;
}
.featured-btn.primary{
  background: linear-gradient(
    135deg,
    #0e3a5d,
    #1b678f
  );

  color: white;

  box-shadow:
    0 8px 18px rgba(14,58,93,0.18);
}
.featured-btn.secondary{
  border: 1px solid #dbe7f2;
  background: white;
  color: #0e3a5d;
}
    .featured-btn:hover:not(:disabled) {
      transform: translateY(-2px);
    }
    .book-actions .btn {
      width: auto;
      min-height: 38px;
      border-radius: 14px;
      font-size: 0.82rem;
      padding: 0 12px;
      flex: 1 1 0;
    }
    .book-actions .btn:hover:not(.btn-disabled) {
      transform: translateY(-1px);
    }
    .book-actions .btn-disabled {
      background: #d1d1d1;
      color: #6b7a8f;
      cursor: not-allowed;
      opacity: 0.75;
      transform: none;
    }
    .empty-state {
      background: #fff;
      border: 1px dashed #c2d2e3;
      padding: 16px;
      border-radius: 12px;
      color: var(--muted);
      text-align: center;
    }

    .book-actions .featured-btn:hover:not(:disabled){
  transform: translateY(-2px);
}

.book-actions .featured-btn:disabled{
  opacity: 0.65;
  cursor: not-allowed;
}

.book-actions .featured-btn{
  width: 100%;
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
      .book-actions .btn {
        font-size: 0.78rem;
      }
      .book-actions {
        flex-direction: column;
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
      <section class="content">
        <div class="dashboard-header">
          <h2>Available Books</h2>
        </div>
        <div class="search-bar">
          <input class="input-field" type="text" id="search-input" placeholder="Search by title, author, or category" onkeyup="searchBooks()" />
          <select class="select-field" id="category-filter" onchange="filterBooks()">
            <option value="">All Genres</option>
            <?php foreach ($genres as $genre): ?>
              <option value="<?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-primary" aria-label="Search" onclick="searchBooks()">Search</button>
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
                      <?php render_book_card($book, $borrowed_books, $pending_books); ?>
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
    
    document.addEventListener('click', function(event) {
      const borrowBtn = event.target.closest('.borrow-btn');
      if (borrowBtn) {
        event.preventDefault();
        event.stopPropagation();
        
        const bookId = borrowBtn.getAttribute('data-book-id');
        const bookTitle = borrowBtn.getAttribute('data-book-title');
        
        if (bookId && bookTitle) {
          openBorrowModal(parseInt(bookId), bookTitle);
        }
      }
    });
    
    function openBorrowModal(bookId, bookTitle) {
      Swal.fire({
        title: 'Borrow this book?',
        html: '<p style="color: #5f7385; margin-bottom: 12px;"><strong>' + escapeHtml(bookTitle) + '</strong></p><p style="color: #5f7385; font-size: 0.92rem;">This will add the book to your borrowed books list for 7 days.</p>',
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
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'borrow.php';
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'book_id';
          input.value = bookId;
          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }
      });
    }
    
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Library page initialization
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
