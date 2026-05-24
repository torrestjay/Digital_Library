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
"SELECT * FROM books ORDER BY views DESC LIMIT 5");
while($row = mysqli_fetch_assoc($featured_query)){
    $featured_books[] = $row;
}
$featured_book = $featured_books[0] ?? null;
// Recommended Books
$recommended = mysqli_query($conn, "SELECT * FROM books ORDER BY RAND() LIMIT 6");
// Top Books (Dynamic Table)
$top_books_query = "SELECT * FROM books ORDER BY views DESC LIMIT 6";
$top_books_result = mysqli_query($conn, $top_books_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Homepage</title>
  <link rel="stylesheet" href="../css/homepage.css?v=20260523" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    *{
      font-family: 'Poppins', sans-serif;
    }
    .featured-book .featured-img {
      width: 180px;
      height: 280px;
      object-fit: cover;
      border-radius: 12px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .featured-book .featured-img:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      cursor: pointer;
    }
    .borrowed-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0;
      font-size: 14px;
      background-color: #fff;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid #e5edf5;
      box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08);
    }
    .borrowed-table thead {
      background-color: #0e3a5d;
      color: white;
    }
    .borrowed-table th{
      background-color: #0e3a5d;
      color: #fff;
      font-weight: 600;
    }
    .borrowed-table th, .borrowed-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e0e0e0;
      color: #14324a;
    }
    .borrowed-table tbody tr {
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .borrowed-table tbody tr:hover {
      background-color: #e9f5ff;
      box-shadow: inset 0 0 10px rgba(14, 58, 93, 0.06);
    }
    #toastBox {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast {
        background-color: #0e3a5d;
        color: white;
        padding: 12px 18px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        min-width: 200px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
    }
    .toast.error {
        background-color: #e74c3c;
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
  </style>
</head>
<!-- =========================
BORROW MODAL
========================= -->
<div class="borrow-modal" id="borrowModal">
  <div class="borrow-modal-content">
    <button class="close-borrow" onclick="closeBorrowModal()">×</button>
    <h2>Borrow Book</h2>
    <p id="borrowBookTitle"></p>
    <form action="borrow.php" method="POST">
      <input type="hidden" name="book_id" id="borrowBookId">
      <div class="form-group">
        <label>Selected Book</label>
        <input type="text" id="borrowBookTitleField" readonly>
      </div>
      <p style="margin: 6px 0 14px; color: #5f7385; font-size: 0.92rem;">This will create a 7-day borrow record for your account.</p>
      <button type="submit" class="confirm-borrow-btn">
        Confirm Borrow
      </button>
    </form>
  </div>
</div>
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
      <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
      <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
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
      <!-- Top Books Table (Dynamic) -->
      <div class="top-books">
        <table class="borrowed-table">
          <thead>
      
            <tr><th style="color: #ffff;">#</th><th style="color: #ffff;">Title</th><th style="color: #ffff;">Author</th><th style="color: #ffff;">Views</th></tr>
          </thead>
          <tbody>
            <?php $i = 1; while ($book = mysqli_fetch_assoc($top_books_result)): ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($book['title']); ?></td>
                <td><?php echo htmlspecialchars($book['author']); ?></td>
                <td><?php echo $book['views']; ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <!-- Featured Book Section -->
        <div class="featured-book">
          <?php if ($featured_book): ?>
            <img class="featured-img" src="../Images/<?php echo htmlspecialchars($featured_book['cover_image']); ?>" alt="<?php echo htmlspecialchars($featured_book['title']); ?>">
            <div class="book-desc">
              <h3><?php echo htmlspecialchars($featured_book['title']); ?></h3>
              <p><?php echo htmlspecialchars(mb_strimwidth($featured_book['description'], 0, 150, '...')); ?></p>
              <a href="read.php?id=<?php echo $featured_book['id']; ?>"><button>READ</button></a>
            </div>
          <?php else: ?>
            <div class="book-desc">
              <h3>No featured books yet</h3>
              <p>Add books to the catalog to populate this section.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
<h2 class="section-title">Book Recommendation</h2>
<div class="recommend-wrapper">
  <button class="carousel-btn left" onclick="scrollBooks(-1)">
    <i class="fa-solid fa-chevron-left"></i>
  </button>
  <div class="book-recommendations" id="recommendCarousel">
    <?php while ($book = mysqli_fetch_assoc($recommended)): ?>
      <?php
        $book_id = $book['id'];
        $checkBorrowed = $conn->prepare("SELECT id FROM borrowed_books WHERE user_id = ? AND book_id = ? AND return_date IS NULL AND status IN ('pending', 'borrowed') LIMIT 1");
        $checkBorrowed->bind_param('ii', $user_id, $book_id);
        $checkBorrowed->execute();
        $isBorrowed = (bool)$checkBorrowed->get_result()->fetch_assoc();
        $checkBorrowed->close();
      ?>
      <div class="book-card">
        <img
          src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>"
          alt="<?php echo htmlspecialchars($book['title']); ?>"
        >
        <div class="book-overlay">
<a href="Book-Details.php?id=<?php echo $book['id']; ?>">
  <button class="view-btn">
    VIEW
  </button>
</a>
          <?php if($isBorrowed): ?>
            <a href="read.php?id=<?php echo $book['id']; ?>">
              <button class="borrowed-btn">READ NOW</button>
            </a>
          <?php else: ?>
            <button
              class="borrow-btn"
              onclick="openBorrowModal(
                <?php echo $book['id']; ?>,
                '<?php echo htmlspecialchars(addslashes($book['title'])); ?>'
              )"
            >
              BORROW
            </button>
          <?php endif; ?>
        </div>
        <p><?php echo htmlspecialchars($book['title']); ?></p>
      </div>
    <?php endwhile; ?>
  </div>
  <button class="carousel-btn right" onclick="scrollBooks(1)">
    <i class="fa-solid fa-chevron-right"></i>
  </button>
</div>
    </section>
  </main>
</div>
<!-- Book Modal -->
<div class="modal" id="bookModal" style="display: none;">
  <div class="modal-content">
    <button class="close-btn" onclick="closeModal()">×</button>
    <div class="book-image">
      <img id="modalImage" src="" alt="Book Cover">
      <div class="tags"><span class="tag" id="modalGenre">Genre</span></div>
    </div>
    <div class="book-info">
      <h1 id="modalTitle">Title</h1>
      <p class="author" id="modalAuthor">Author</p>
      <div class="stats">
        <div><span>👁</span> Reads<br><strong>—</strong></div>
        <div><span>⭐</span> Votes<br><strong>—</strong></div>
        <div><span>📄</span> Parts<br><strong>—</strong></div>
      </div>
      <div class="buttons">
        <button class="read-btn">READ</button>
        <button class="fav-btn">FAVORITE</button>
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
        alert(data.message);
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
  /* Recommendation Carousel */
function scrollBooks(direction){
  const container = document.getElementById("recommendCarousel");
  container.scrollBy({
    left: direction * 300,
    behavior: "smooth"
  });
}
/* Featured Book Slideshow */
const featuredBooks = <?php echo json_encode($featured_books); ?>;
let currentFeatured = 0;
setInterval(() => {
  currentFeatured++;
  if(currentFeatured >= featuredBooks.length){
    currentFeatured = 0;
  }
  const book = featuredBooks[currentFeatured];
  document.querySelector(".featured-img").src =
    "../Images/" + book.cover_image;
  document.querySelector(".featured-img").alt =
    book.title;
  document.querySelector(".book-desc h3").innerText =
    book.title;
  document.querySelector(".book-desc p").innerText =
    book.description.substring(0, 150) + "...";
  document.querySelector(".book-desc a").href =
    "read.php?id=" + book.id;
}, 20000);
/* =========================
BORROW MODAL
========================= */
function openBorrowModal(bookId, title){
  document.getElementById("borrowModal").style.display = "flex";
  document.getElementById("borrowBookId").value = bookId;
  document.getElementById("borrowBookTitleField").value = title;
  document.getElementById("borrowBookTitle").innerText =
    "You are borrowing: " + title;
}
function closeBorrowModal(){
  document.getElementById("borrowModal").style.display = "none";
}
/* =========================
CONFIRMATION POPUP
========================= */
document.addEventListener("DOMContentLoaded", () => {
  const borrowForm = document.querySelector(".borrow-modal form");
  borrowForm.addEventListener("submit", function(e){
    const confirmBorrow = confirm(
      "Are you sure you want to borrow this book?"
    );
    if(!confirmBorrow){
      e.preventDefault();
    }
  });
});
</script>
</body>
</html>
