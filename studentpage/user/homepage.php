<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('..\dbcon.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ..\login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare('SELECT id, fullname FROM users WHERE id = ? LIMIT 1');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();
if (!$user) {
  header('Location: ../login.php');
  exit();
}
$user_name = $user['fullname'];
// Statistics
$borrowed_count = 0;
$overdue_count = 0;
$read_count = 0;
$borrowed_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrowed_books WHERE user_id = ? AND status = 'borrowed' AND return_date IS NULL");
$borrowed_stmt->bind_param('i', $user_id);
$borrowed_stmt->execute();
$borrowed_count = (int)($borrowed_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$borrowed_stmt->close();
$overdue_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrowed_books WHERE user_id = ? AND status = 'borrowed' AND return_date IS NULL AND due_date < NOW()");
$overdue_stmt->bind_param('i', $user_id);
$overdue_stmt->execute();
$overdue_count = (int)($overdue_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$overdue_stmt->close();
$read_stmt = $conn->prepare('SELECT COUNT(*) AS total FROM reading_history WHERE user_id = ?');
$read_stmt->bind_param('i', $user_id);
$read_stmt->execute();
$read_count = (int)($read_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$read_stmt->close();
// Featured Books
$featured_books = [];
$featured_query = mysqli_query($conn,
"SELECT * FROM books WHERE archived_at IS NULL ORDER BY views DESC LIMIT 5");
while($row = mysqli_fetch_assoc($featured_query)){
    $featured_books[] = $row;
}
$featured_book = $featured_books[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Homepage</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/homepage.css?v=20260523" />
  <link rel="stylesheet" href="../css/user-shell.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
  <div id="toastBox"></div>
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo" onclick="toggleSidebar()">
      <img src="../Images/logo.png" alt="Readly Logo" />
    </div>
    <nav class="nav">
      <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
      <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
      <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
      <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
      <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Page" /><span>Support Page</span></a>
      <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
    </nav>
    <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
    </div>
  </aside>
  <main class="main-content">
    <section class="content">
      <div class="dashboard-header">
        <h2>Hello, <?php echo ucwords(htmlspecialchars($user['fullname'])); ?>!</h2>
        <p><?php echo date("F j, Y | l, h:i A"); ?></p>
      </div>
      <div class="stats-cards">
        <div class="card">
          <div class="card-label"><i class="fa-solid fa-book"></i><span>Borrowed Books</span></div>
          <div class="card-value"><?php echo $borrowed_count; ?></div>
        </div>
        <div class="card">
          <div class="card-label"><i class="fa-solid fa-triangle-exclamation"></i><span>Overdue Books</span></div>
          <div class="card-value"><?php echo $overdue_count; ?></div>
        </div>
        <div class="card">
          <div class="card-label"><i class="fa-solid fa-book-open-reader"></i><span>Total Books Read</span></div>
          <div class="card-value"><?php echo $read_count; ?></div>
        </div>
      </div>

      <div class="featured-wrapper">
        <button class="featured-nav-btn prev" onclick="prevFeatured()" title="Previous">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="featured-book-v2">

          <?php if ($featured_book): ?>

            <img
              class="featured-img"
              src="../Images/<?php echo htmlspecialchars($featured_book['cover_image']); ?>"
              alt="<?php echo htmlspecialchars($featured_book['title']); ?>"
            >

            <div class="featured-content">

              <span class="featured-label">
                Featured Book
              </span>

              <h2 class="featured-title">
                <?php echo htmlspecialchars($featured_book['title']); ?>
              </h2>

              <h4 class="featured-author">
                <?php echo htmlspecialchars($featured_book['author']); ?>
              </h4>

              <p class="featured-desc">
                <?php echo htmlspecialchars(mb_strimwidth($featured_book['description'], 0, 140, '...')); ?>
              </p>

              <div class="featured-actions">

                <a
                  href="Book-Details.php?id=<?php echo $featured_book['id']; ?>"
                  class="featured-btn secondary featured-view-link"
                >
                  View Details
                </a>

                <button
                  type="button"
                  class="featured-btn primary featured-borrow-btn"
                  onclick="openBorrowModal(<?php echo $featured_book['id']; ?>, '<?php echo htmlspecialchars($featured_book['title'], ENT_QUOTES, 'UTF-8'); ?>')"
                >
                  Borrow Book
                </button>

              </div>

            </div>

          <?php endif; ?>

        </div>

        <button class="featured-nav-btn next" onclick="nextFeatured()" title="Next">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </section>
  </main>
</div>
<!-- Book Modal -->
<div class="modal" id="bookModal" style="display: none;">
  <div class="modal-content">
    <button class="close-btn" onclick="closeModal()">�</button>
    <div class="book-image">
      <img id="modalImage" src="" alt="Book Cover">
      <div class="tags"><span class="tag" id="modalGenre">Genre</span></div>
    </div>
    <div class="book-info">
      <h1 id="modalTitle">Title</h1>
      <p class="author" id="modalAuthor">Author</p>
      <div class="stats">
        <div><span>??</span> Reads<br><strong>�</strong></div>
        <div><span>?</span> Votes<br><strong>�</strong></div>
        <div><span>??</span> Parts<br><strong>�</strong></div>
      </div>
      <div class="buttons">
        <button class="btn btn-primary">READ</button>
        <button class="btn btn-secondary">FAVORITE</button>
      </div>
      <div class="description">
        <h4>Publisher's Description:</h4>
        <p id="modalDescription">Description here...</p>
      </div>
    </div>
  </div>
</div>
<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("collapsed");
  const main = document.querySelector(".main-content");
  main.style.marginLeft = sidebar.classList.contains("collapsed") ? "70px" : "250px";
}
function fetchBookDetails(bookId) {
  fetch('get-book-details.php?book_id=' + bookId)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById("modalTitle").innerText = data.title;
        document.getElementById("modalAuthor").innerText = data.author;
        document.getElementById("modalGenre").innerText = data.category || data.genre;
        document.getElementById("modalDescription").innerText = data.description;
        document.getElementById("modalImage").src = "../Images/" + data.cover_image;
        document.getElementById("bookModal").style.display = "flex";
      } else {
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: data.message, showConfirmButton: false, timer: 3000, timerProgressBar: true });
      }
    })
    .catch(error => console.error('Error:', error));
}
function closeModal() {
  document.getElementById("bookModal").style.display = "none";
}
  let userr_name = <?php echo json_encode($user_name); ?>;
  let toastBox = document.getElementById('toastBox');
  let successMess = '<i class="fa-solid fa-circle-check"></i> Welcome ' + userr_name + '!';
  function showToast(msg) {
      let toast = document.createElement('div'); 
      toast.classList.add('toast');
      toast.innerHTML = msg;
      toastBox.appendChild(toast); 
      if (msg.includes('error')) {
          toast.classList.add('error');
      }
      // Play notification sound
      const sound = document.getElementById('notifySound');
      if (sound) sound.play();
      setTimeout(() => {
          toast.remove();
      }, 3000);
  }
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('status') === 'success') {
      showToast(successMess);
      window.history.replaceState(null, null, window.location.pathname);
  }
/* Featured Book Slideshow */
const featuredBooks = <?php echo json_encode($featured_books); ?>;
let currentFeatured = 0;

function updateFeaturedDisplay() {
  const book = featuredBooks[currentFeatured];
  document.querySelector(".featured-img").src = "../Images/" + book.cover_image;
  document.querySelector(".featured-img").alt = book.title;
  document.querySelector(".featured-title").innerText = book.title;
  document.querySelector(".featured-author").innerText = book.author;
  document.querySelector(".featured-desc").innerText = book.description.substring(0, 140) + "...";
  document.querySelector(".featured-view-link").href = "Book-Details.php?id=" + book.id;
  document.querySelector(".featured-borrow-btn").onclick = function() {
    openBorrowModal(book.id, book.title);
  };
}

function nextFeatured() {
  currentFeatured++;
  if(currentFeatured >= featuredBooks.length){
    currentFeatured = 0;
  }
  updateFeaturedDisplay();
}

function prevFeatured() {
  currentFeatured--;
  if(currentFeatured < 0){
    currentFeatured = featuredBooks.length - 1;
  }
  updateFeaturedDisplay();
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

// Auto-rotate every 20 seconds
setInterval(() => {
  nextFeatured();
}, 20000);
</script>
</body>
</html>
