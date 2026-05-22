<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Profile</title>
  <link rel="stylesheet" href="../css/profile.css" />
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
       <a href="../logout.php"><img class="icon" src="../Images\signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <img class="icon" src="../Images/notif.png" />
          <img class="icon" src="../Images/profile.png" />
        </div>
      </header>

      <section class="profile-container">
        <div class="profile-header">
          <img src="../Images/Photo.png" alt="Profile" class="avatar" />
          <div class="profile-info">
            <h2>Daniel John Ford Padilla</h2>
            <p>Reader since 2024</p>
            <p>ID: K3456778909K</p>
          </div>
        </div>

        <!-- ✅ UPDATED SECTION BELOW -->
        <div class="profile-tabs">
          <div class="left-tabs">
            <button class="tab active">ABOUT</button>
            <button class="tab">CONVERSATION</button>
          </div>
          <button class="edit-btn">Edit Profile</button>
        </div>

        <div class="profile-card">
          <h3>Joined</h3>
          <p>August 14, 2024</p>
        </div>
      </section>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("collapsed");
    }
  </script>
</body>
</html>
