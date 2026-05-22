<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Setting Timeline</title>
  <link rel="stylesheet" href="../css/timeline.css" />
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
        <a href="homepage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="Book-Details.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="track&record.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Settings</span></a>      
      </nav>
      <div class="sign-out">
        <a href="../logout.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Sign out Icon" /><span>Sign Out</span></a>
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

            <section class="track-record-section">
  <h2>Track & Record</h2>
  <p class="subtext">Keep track of the books you've Borrowed and Read.</p>

  <div class="stats-cards">
    <div class="stat-card">
      <img src="../Images/borrowed.png" alt="Borrowed Books" />
      <div>
        <strong>Books Borrowed</strong>
        <p>8 Books</p>
      </div>
    </div>
    <div class="stat-card">
      <img src="../Images/read.png" alt="Read Books" />
      <div>
        <strong>Read Books</strong>
        <p>15 Books</p>
      </div>
    </div>
    <div class="stat-card">
      <img src="../Images/currently.png" alt="Currently Reading" />
      <div>
        <strong>Currently Reading</strong>
        <p>8 Books</p>
      </div>
    </div>
    <div class="reading-progress">
      <div class="circle">
        <span>85%</span>
      </div>
      <p>You’ve read 15 out of 20 books this month.</p>
    </div>
  </div>

  <div class="tabs">
    <button type="button" class="active">Borrowed Book</button>
    <button type="button" class="active">Timeline</button>
  </div>

  <div class="content-grid">
    <table class="borrowed-table">
      <thead>
        <tr>
          <th>Book Title</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>The Book 1</td>
          <td>April 2, 2025</td>
          <td>April 2, 2025</td>
          <td>In Progress</td>
        </tr>
        <tr>
          <td>The Book 1</td>
          <td>April 2, 2025</td>
          <td>April 2, 2025</td>
          <td>In Progress</td>
        </tr>
        <tr>
          <td>The Book 2</td>
          <td>April 2, 2025</td>
          <td>April 2, 2025</td>
          <td>Completed</td>
        </tr>
      </tbody>
    </table>

    <div class="borrow-log">
      <h4>Borrowed Book</h4>
      <p>Rated “The Night Circus”<br><span>04/15/2025</span></p>
      <p>Borrowed “The Book 2”<br><span>04/14/2025</span></p>
      <p>Finished “The Book 1”<br><span>04/13/2025</span></p>
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


