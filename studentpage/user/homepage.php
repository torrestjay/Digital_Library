<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('..\dbcon.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ..\login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id LIMIT 1";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$user_name = $user['fullname'];

// Statistics
$borrowed_count = 0;
$overdue_count = 0;
$read_count = 0;

$borrowed_query = "SELECT COUNT(*) AS total FROM borrowed_books WHERE user_id = $user_id";
if ($borrowed_result = mysqli_query($conn, $borrowed_query)) {
    $row = mysqli_fetch_assoc($borrowed_result);
    $borrowed_count = $row['total'];
}

$overdue_query = "SELECT COUNT(*) AS total FROM borrowed_books WHERE user_id = $user_id AND due_date < NOW()";
if ($overdue_result = mysqli_query($conn, $overdue_query)) {
    $row = mysqli_fetch_assoc($overdue_result);
    $overdue_count = $row['total'];
}

$read_query = "SELECT COUNT(*) AS total FROM reading_history WHERE user_id = $user_id";
if ($read_result = mysqli_query($conn, $read_query)) {
    $row = mysqli_fetch_assoc($read_result);
    $read_count = $row['total'];
}

// Featured Book
$featured_query = "SELECT * FROM books ORDER BY RAND() LIMIT 1";
$featured_result = mysqli_query($conn, $featured_query);
$featured_book = mysqli_fetch_assoc($featured_result);

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
  <link rel="stylesheet" href="../css/homepage.css" />
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
      margin-top: 20px;
      font-size: 14px;
      background-color: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .borrowed-table thead {
      background-color: #368DB8;
      color: white;
    }

    .borrowed-table th{
      background-color: #3498db;
    }

    .borrowed-table th, .borrowed-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e0e0e0;
      color: #333;
    }

    .borrowed-table tbody tr {
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    .borrowed-table tbody tr:hover {
      background-color: #e9f5ff;
      box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.06);
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
      <a href="Book-Details.php"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
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
        <h1>Hello, <?php echo ucwords(htmlspecialchars($user['fullname'])); ?>!</h1>
        <p><?php echo date("F j, Y | l, h:i A"); ?></p>
      </div>

      <div class="stats-cards">
        <div class="card"><?php echo $borrowed_count; ?><br><span>Borrowed Books</span></div>
        <div class="card"><?php echo $overdue_count; ?><br><span>Overdue Books</span></div>
        <div class="card"><?php echo $read_count; ?><br><span>Total Book Read</span></div>
      </div>

      <!-- Top Books Table (Dynamic) -->
      <div class="top-books">
        <table class="borrowed-table">
          <thead>
      
            <tr><th style="color: #ffff;">#</th><th style="color: #ffff;">Title</th style="color: #ffff;"><th style="color: #ffff;">Author</th><th style="color: #ffff;">Views</th></tr>
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
          <img class="featured-img" src="../Images/<?php echo htmlspecialchars($featured_book['cover_image']); ?>" alt="<?php echo htmlspecialchars($featured_book['title']); ?>">
          <div class="book-desc">
            <h3><?php echo htmlspecialchars($featured_book['title']); ?></h3>
            <p><?php echo htmlspecialchars(mb_strimwidth($featured_book['description'], 0, 150, '...')); ?></p>
            <a href="read-book.php?id=<?php echo $featured_book['id']; ?>"><button>READ</button></a>
          </div>
        </div>
      </div>

      <h2 class="section-title">Book Recommendation</h2>
      <div class="book-recommendations">
        <?php while ($book = mysqli_fetch_assoc($recommended)): ?>
          <div class="book-card" onclick="fetchBookDetails(<?php echo $book['id']; ?>)">
            <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <p><?php echo htmlspecialchars($book['title']); ?></p>
          </div>
        <?php endwhile; ?>
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
        document.getElementById("modalGenre").innerText = data.genre;
        document.getElementById("modalDescription").innerText = data.description;
        document.getElementById("modalImage").src = "Images/" + data.cover_image;
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
</script>

</body>
</html>
