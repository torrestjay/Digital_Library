<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch books that are not currently borrowed (no return date yet)
$query = "
    SELECT b.* 
    FROM books b
    WHERE b.id NOT IN (
        SELECT book_id 
        FROM borrowed_books 
        WHERE return_date IS NULL
    )
";

$result = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Book Details</title>
  <link rel="stylesheet" href="../css/Book-Details.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    *{
      
    font-family: 'Poppins', sans-serif;

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

      <section class="book-section">
        <?php while ($book = $result->fetch_assoc()): ?>
          <div class="book-card">
            <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover" />
            <div class="book-info">
              <h2><?php echo htmlspecialchars($book['title']); ?></h2>
              <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
              <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
              <p class="description"><?php echo htmlspecialchars($book['description']); ?></p>
              <div class="action-buttons">
                <form method="post" action="borrow.php">
                  <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                  <button type="submit" class="borrow-btn">Borrow</button>
                </form>
                <a href="read.php?id=<?php echo $book['id']; ?>" class="read-btn">Read</a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </section>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }
  </script>
</body>
</html>
