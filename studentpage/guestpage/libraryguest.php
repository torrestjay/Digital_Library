<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Guest Library</title>
  <link rel="stylesheet" href="css/libraryguest.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
        <a href="guestpage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="libraryguest.php"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="../login.php"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="../login.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="../login.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="../login.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Settings</span></a>       
      </nav>
      <div class="sign-out">
        <a href="../login.php"><img class="icon" src="../Images/signout.png" alt="Sign Out Icon" /><span>Sign In</span></a>
      </div>
    </aside>
    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <img class="icon" src="../Images/notif.png" alt="Notification Icon">
          <img class="icon" src="../Images/profile.png" alt="Profile Icon">
        </div>
      </header>
      <!-- Content -->
      <section class="content">
        <!-- Guest Banner -->
        <div class="guest-banner">
          <img src="../Images/lock.png" alt="Lock Icon" />
          <p>You’re viewing as a guest. Some content is locked. <a href="../login.php">Login</a> to unlock full access.</p>
        </div>
        <div class="dashboard-header">
          <h2>Available Books</h2>
        </div>
        <div class="search-bar">
          <input type="text" placeholder="Search" />
          <button type="button" aria-label="Search" onclick="searchBooks()"><img src="../Images/Search.jpg" alt="Search" /></button>
        </div>
        <!-- Science Fiction -->
        <div class="book-category">
          <h3>Science Fiction</h3>
          <div class="book-row">
            <div class="locked-book"><img src="../Images/Book4.png"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
            <div class="locked-book"><img src="../Images/Book3.jpg"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
            <div class="locked-book"><img src="../Images/Book4.png"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
          </div>
        </div>
        <!-- Thriller -->
        <div class="book-category">
          <h3>Thriller</h3>
          <div class="book-row">
            <div class="locked-book"><img src="../Images/Book3.jpg"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
            <div class="locked-book"><img src="../Images/Book4.png"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
            <div class="locked-book"><img src="../Images/Book3.jpg"><div class="lock-overlay"><img src="../Images/lock.png"></div></div>
          </div>
        </div>
        <!-- Romance -->
        <div class="book-category">
          <h3>Romance</h3>
          <div class="book-row">
            <div class="locked-book"><img src="Images/Book4.png"><div class="lock-overlay"><img src="Images/lock.png"></div></div>
            <div class="locked-book"><img src="Images/Book3.jpg"><div class="lock-overlay"><img src="Images/lock.png"></div></div>
            <div class="locked-book"><img src="Images/Book4.png"><div class="lock-overlay"><img src="Images/lock.png"></div></div>
          </div>
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
