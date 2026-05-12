<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Settings Admin Rules</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f7f4;
    }

    .container {
      display: flex;
      height: 100vh;
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

    .sidebar.collapsed span {
      display: none;
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
      transition: background 0.2s;
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

    .main-content {
      flex: 1;
      padding: 60px 20px 20px;
      background-color: #f5f5f5;
      overflow-y: auto;
      transition: padding-left 0.3s ease;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 5px;
    }

    p {
      font-size: 14px;
      color: #333;
      margin-bottom: 20px;
    }

    .tabs {
      margin: 10px 0 20px;
    }

    .tabs button {
      background: #003d5c;
      color: white;
      padding: 10px 20px;
      border: none;
      margin-right: 10px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border: 1px solid #ccc;
      border-radius: 10px;
      overflow: hidden;
    }

    table th,
    table td {
      padding: 12px;
      border-top: 1px solid #ccc;
    }

    table th {
      background-color: #f1e9df;
      text-align: left;
    }

    table th:last-child,
    table td:last-child {
      text-align: center;
    }

    .apply-btn {
      background: #003d5c;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
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
        <a href="admindashboard.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="AdminBookEdit.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="AdminBookEdit.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="AdminUserPage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="AdminNotif.php" onclick="toggleSidebar()"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="SettingAdmin.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Settings</span></a>       
      </nav>
      <div class="sign-out">
        <a href="../logout.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Sign out Icon" /><span>Sign Out</span></a>
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
        <button type="button">Account</button>
        <button type="button">Notification</button>
        <button type="button" style="background: #003d5c;">Roles and Permissions</button>
      </div>

      <h3 style="margin: 20px 0 10px;">Users Roles</h3>
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Registered</th>
              <th>Roles</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Daniel Padilla</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>USER</td>
            </tr>
            <tr>
              <td>Kathryn Bernardo</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>ADMIN</td>
            </tr>
            <tr>
              <td>Jackie Serrano</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>USER</td>
            </tr>
            <tr>
              <td>Mia Dela Torre</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>USER</td>
            </tr>
            <tr>
              <td>Gab Dela Cuesta</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>USER</td>
            </tr>
            <tr>
              <td>Primo Alvarez</td>
              <td>shesdatingthegangster@gmail.com</td>
              <td>04/25/22</td>
              <td>USER</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
        <button class="apply-btn">Apply Change</button>
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
