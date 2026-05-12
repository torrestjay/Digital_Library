<?php
session_start();
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch borrowed books
$borrowed_books = [];
$borrowed_result = mysqli_query($conn, "SELECT book_id FROM borrowed_books WHERE user_id = $user_id AND return_date IS NULL");
while ($row = mysqli_fetch_assoc($borrowed_result)) {
    $borrowed_books[] = $row['book_id'];
}

// Fetch books by genre
// Fetch books by genre
$genres = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];
$books_by_genre = [];

foreach ($genres as $genre) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE category = ?");
    $stmt->bind_param("s", $genre);
    $stmt->execute();
    $result = $stmt->get_result();
    $books_by_genre[$genre] = $result;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Library</title>
  <link rel="stylesheet" href="../css/librarypage.css" />
  <style>
    .btn {
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .borrow {
      background-color: #4CAF50;
      color: white;
    }
    .borrow:hover {
      background-color: #45a049;
    }
    .borrowed {
      background-color: #ccc;
      color: #555;
      cursor: not-allowed;
    }
    /* Popup styling */
    #borrowPopup {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      font-family: 'Inter', sans-serif;
    }

    .borrow-form {
      background: white;
      padding: 40px 30px;
      border-radius: 20px;
      width: 400px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .borrow-form img {
      width: 80px;
      margin-bottom: 15px;
    }

    .borrow-form h2 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .borrow-form input {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .borrow-form button {
      width: 120px;
      padding: 10px;
      margin: 10px 5px 0;
      background-color: #0e3a5d;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .borrow-form button:hover {
      background-color: #12476f;
    }

    /* Layout improvements */
    .search-bar { display:flex; gap:8px; align-items:center; margin-bottom: 18px; }
    .search-bar input { flex:1; padding:10px; border-radius:8px; border:1px solid #ccc; }
    .search-bar button { padding:9px 12px; border-radius:8px; background:#0e3a5d; border:none; color:#fff }

    .book-row { display:flex; gap:20px; flex-wrap:wrap; }
    .book-card { width:170px; text-align:center; display:flex; flex-direction:column; gap:8px; align-items:center }
    .book-card p { margin:0; font-size:14px; font-weight:600 }

  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
        <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" /><span>Dashboard</span></a>
        <a href="librarypage.php"><img class="icon" src="../Images/Library.png" /><span>Library</span></a>
        <a href="Book-Details.php"><img class="icon" src="../Images/Details.png" /><span>Book Details</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" /><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" /><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" /><span>Sign Out</span></a>
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
          <h2>Available Books</h2>
        </div>

        <div class="search-bar">
          <input type="text" id="search-input" placeholder="Search by title or author" onkeyup="searchBooks()" />
          <button type="button" aria-label="Search" onclick="searchBooks()"><img src="../Images/Search.jpg" alt="Search" /></button>
        </div>

        <div id="book-results">
          <?php foreach ($books_by_genre as $genre => $book_list): ?>
            <div class="book-category">
              <h3><?php echo htmlspecialchars($genre); ?></h3>
              <div class="book-row">
                <?php while ($book = mysqli_fetch_assoc($book_list)): ?>
                  <div class="book-card">
                    <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover" style="width: 150px; height: 220px; object-fit: cover; border-radius: 8px;" />
                    <p><?php echo htmlspecialchars($book['title']); ?></p>
                    <?php if (in_array($book['id'], $borrowed_books)): ?>
                      <button type="button" class="btn borrowed" disabled>Borrowed</button>
                    <?php else: ?>
                      <button type="button" class="btn borrow" onclick="openBorrowForm('<?php echo htmlspecialchars($book['title']); ?>', '<?php echo $book['id']; ?>')">Borrow</button>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>

  <!-- Borrow Popup -->
  <div id="borrowPopup" style="display:none;">
    <form class="borrow-form" action="submit_borrow.php" method="POST">
      <img src="../Images/logo.png" alt="Readly" />
      <h2>Fill up the following</h2>
      <input type="text" id="user_id" name="user_id" value="<?= $_SESSION['user_id'] ?>" readonly>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="book_title" id="bookTitle" placeholder="Book Title" readonly>
      <label for="borrow-date" style="display:block; margin-top: 10px; font-weight: bold;">Select return date (max 7 days)</label>
      <input type="date" id="borrow-date" name="date" required>
      <input type="text" name="contact" class="contact_num" placeholder="Contact" required>
      <input type="hidden" name="book_id" id="bookId">
      <div>
        <button type="submit">SUBMIT</button>
        <button type="button" onclick="closeBorrowForm()">CANCEL</button>
      </div>
    </form>
  </div>

  <!-- Success Popup -->
  <?php if (isset($_GET['borrowed']) && $_GET['borrowed'] == 1): ?>
    <div id="successPopup" style="display:flex;">
      <div class="success-box">
        <img src="../Images/success-icon.png" alt="Success" />
        <h2>SUCCESSFUL</h2>
        <p>Your request is under review. Kindly wait for approval.</p>
        <button type="button" onclick="closeSuccessPopup()">Continue</button>
      </div>
    </div>
  <?php endif; ?>


  <script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }

  function searchBooks() {
    const query = document.getElementById("search-input").value.trim();
    const xhr = new XMLHttpRequest();

    if (query === "") {
      // Load full layout again
      xhr.open("GET", "load_default_books.php", true);
    } else {
      // Do normal search
      xhr.open("GET", "search_books.php?query=" + encodeURIComponent(query), true);
    }

    xhr.onload = function () {
      if (xhr.status === 200) {
        document.getElementById("book-results").innerHTML = xhr.responseText;
      }
    };
    xhr.send();
  }
  

  function closeBorrowForm() {
    document.getElementById('borrowPopup').style.display = 'none';
  }

  function closeSuccessPopup() {
    document.getElementById('successPopup').style.display = 'none';
  }

  // Allow numeric contact only
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.contact_num').forEach(input => {
      input.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11);
      });
    });

    const dateInput = document.getElementById('borrow-date');
    const today = new Date();
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + 7);

    const formatDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    
    dateInput.min = formatDate(today);
    dateInput.max = formatDate(maxDate);
  });

  function openBorrowForm(title, bookId) {
    // Fill the modal form
    document.getElementById('bookTitle').value = title;
    document.getElementById('bookId').value = bookId;
    document.getElementById('borrowPopup').style.display = 'flex';

    // Send AJAX request to increment view count
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "increment_view.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("book_id=" + encodeURIComponent(bookId));
  }

  
</script>
  <!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (!empty($success)): ?>
  <script>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: <?php echo json_encode($success); ?>,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });
  </script>
<?php endif; ?>


</body>
</html>
