<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Settting Notification</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      min-height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      color: #14324a;
      overflow-x: hidden;
    }
    .container {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      background-color: #0e3a5d;
      width: 250px;
      color: white;
      display: flex;
      flex-direction: column;
      transition: width 0.3s ease;
    }
    .sidebar.collapsed {
      width: 70px;
    }
    .logo {
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      cursor: pointer;
      padding: 10px;
    }
    .logo img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
    }
    .nav {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      padding-top: 20px;
    }
    .nav a,
    .sign-out a {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      color: white;
      text-decoration: none;
      transition: background 0.2s ease;
    }
    .nav a:hover,
    .sign-out a:hover {
      background-color: #12476f;
    }
    .icon {
      width: 25px;
      height: 25px;
    }
    .nav span,
    .sign-out span {
      margin-left: 10px;
      white-space: nowrap;
    }
    .sign-out {
      margin-top: auto;
    }
    .header {
      position: fixed;
      top: 0;
      left: 250px;
      width: calc(100% - 250px);
      z-index: 1000;
      padding: 12px 20px;
      background-color: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #e0e0e0;
      height: 60px;
      box-sizing: border-box;
      transition: left 0.3s ease, width 0.3s ease;
    }
    .header-icons .icon {
      margin-left: 10px;
      cursor: pointer;
      width: 30px;
      height: 30px;
    }
  </style>
  <link rel="stylesheet" href="../css/user-shell.css" />
    .main-content {
      flex: 1;
      padding: 80px 24px 24px;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      overflow-y: auto;
      transition: padding-left 0.3s ease;
    }
    h2 {
      font-size: 24px;
      margin-bottom: 10px;
    }
    p {
      font-size: 14px;
      color: #333;
      margin-bottom: 20px;
    }
    .tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 14px 0 24px;
    }
    .tabs button {
      background: #0e3a5d;
      color: white;
      padding: 10px 18px;
      border: none;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 700;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .tabs button:hover {
      background: #15597c;
      transform: translateY(-1px);
    }
    .box {
      border: 1px solid #e5edf5;
      padding: 22px;
      margin-bottom: 20px;
      border-radius: 24px;
      background: white;
      box-shadow: 0 14px 28px rgba(14, 58, 93, 0.08);
    }
    .box h4 {
      margin-bottom: 16px;
      color: #0e3a5d;
    }
    .setting-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #eef4f7;
    }
    .setting-row:last-child {
      border-bottom: none;
    }
    .setting-row span {
      color: #14324a;
      font-size: 14px;
    }
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        height: 100%;
        z-index: 1001;
        left: 0;
        top: 0;
      }
      .header {
        left: 70px;
        width: calc(100% - 70px);
      }
      .sidebar:not(.collapsed) ~ .main-content .header {
        left: 250px;
        width: calc(100% - 250px);
      }
      .main-content {
        padding-left: 70px;
      }
      .sidebar:not(.collapsed) ~ .main-content {
        padding-left: 250px;
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
        <a href="homepage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="borrowed-books.php" onclick="toggleSidebar()"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
        <a href="track&record.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Settings</span></a>       
      </nav>
      <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <img class="icon" src="../Images/notif.png" alt="Notifications">
          <img class="icon" src="../Images/profile.png" alt="Profile">
        </div>
      </header>
      <h2>Settings</h2>
      <p>Manage your personal information and account preferences</p>
      <div class="tabs">
        <button class="btn btn-secondary">Account</button>
        <button class="btn btn-secondary">Notification</button>
        <button class="btn btn-secondary">Content Preferences</button>
      </div>
      <div class="box">
        <h4>Story Updates & Recommendations</h4>
        <div class="setting-row"><span>Part published</span><span>Push, Feed, Email ›</span></div>
        <div class="setting-row"><span>Story recommendations</span><span>Push, Feed, Email ›</span></div>
        <div class="setting-row"><span>Story Published</span><span>Push, Feed, Email ›</span></div>
      </div>
      <div class="box">
        <h4>Comments & Messages</h4>
        <div class="setting-row"><span>Conversation Replies</span><span>Push, Feed, Email ›</span></div>
        <div class="setting-row"><span>Direct Messages</span><span>Push, Feed, Email ›</span></div>
        <div class="setting-row"><span>Comment Replies</span><span>Push, Feed, Email ›</span></div>
      </div>
    </main>
  </div>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const header = document.querySelector('.header');
      sidebar.classList.toggle('collapsed');
      if (sidebar.classList.contains('collapsed')) {
        header.style.left = '70px';
        header.style.width = 'calc(100% - 70px)';
      } else {
        header.style.left = '250px';
        header.style.width = 'calc(100% - 250px)';
      }
    }
    window.addEventListener('DOMContentLoaded', () => {
      if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.add('collapsed');
        const header = document.querySelector('.header');
        header.style.left = '70px';
        header.style.width = 'calc(100% - 70px)';
      }
    });
  </script>
</body>
</html>
