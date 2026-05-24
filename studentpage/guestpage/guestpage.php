<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Guest Dashboard</title>
  <link rel="stylesheet" href="css/guestpage.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
        <a href="guestpage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="libraryguest.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="../login.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="../login.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="../login.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="../login.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Settings</span></a>       
      </nav>
      <div class="sign-out">
        <a href="../login.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Sign out Icon" /><span>Sign In</span></a>
      </div>
    </aside>
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <img class="icon" src="../Images/notif.png">
          <img class="icon" src="../Images/profile.png">
        </div>
      </header>
      <section class="content">
        <!-- Guest View Banner -->
        <div class="guest-banner">
          <img src="../Images/lock.png" alt="Lock Icon" />
          <p>You’re viewing as a guest. Some content is locked. <a href="../login.php">Login</a> to unlock full access.</p>
        </div>
        <div class="dashboard-header">
          <h1>WELCOME GUEST!</h1>
          <p>April 5, 2025 | Saturday, 10:00 AM</p>
        </div>
        <!-- Locked Stats Cards -->
        <div class="stats-cards">
          <div class="card"><img src="Images/lock.png" class="lock-icon"/><br><span>Borrowed Books</span></div>
          <div class="card"><img src="Images/lock.png" class="lock-icon"/><br><span>Overdue Books</span></div>
          <div class="card"><img src="Images/lock.png" class="lock-icon"/><br><span>Total Book Read</span></div>
        </div>
        <div class="top-books">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Author</th>
                <th>Views</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>1</td><td>************</td><td>*******</td><td>****</td></tr>
              <tr><td>2</td><td>***************</td><td>************</td><td>****</td></tr>
            </tbody>
          </table>
          <div class="featured-book">
            <img src="Images/book1.jpg" alt="Emilia Book Cover">
            <div class="book-desc">
              <h3>Emilia: Finding My Forever</h3>
              <p>Emilia, a mixture of Enzo and Elianna. She isn’t afraid of anyone and is a headstrong force to be reckoned with...</p>
              <button type="button">READ</button>
            </div>
          </div>
        </div>
        <h2 class="section-title">Book Recommendation</h2>
        <div class="book-recommendations">
          <div><img src="Images/Book2.jpg"><p>The Night Circus</p></div>
          <div><img src="Images/Book3.jpg"><p>The Great Gatsby</p></div>
          <div><img src="Images/Book2.jpg"><p>The Alchemist</p></div>
          <div><img src="Images/Book3.jpg"><p>The Night Circus</p></div>
          <div><img src="Images/Book2.jpg"><p>The Great Gatsby</p></div>
          <div><img src="Images/Book3.jpg"><p>The Night Circus</p></div>
        </div>
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
