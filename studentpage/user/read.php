<?php
include('..\dbcon.php');


$isbn = $_GET['id'] ?? '';
$isbn = mysqli_real_escape_string($conn, $isbn);

// Fetch book by ISBN
$query = "SELECT * FROM books WHERE id = '$isbn'";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

// Check if book exists
if (!$book) {
    echo "Book not found.";
    exit;
}

// Increment view count
mysqli_query($conn, "UPDATE books SET views = views + 1 WHERE id = '$isbn'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($book['title']) ?> - Read</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    html, body {
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background-color: #fdfaf7;
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

    .nav, .sign-out {
      display: flex;
      flex-direction: column;
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

    .nav .icon,
    .sign-out .icon {
      width: 25px;
      height: 25px;
      margin-right: 10px;
    }

    .nav span,
    .sign-out span {
      margin-left: 10px;
      white-space: nowrap;
    }

    .sidebar.collapsed span {
      display: none;
    }

    .sign-out {
      margin-top: auto;
    }

    .main-content {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .topbar {
      background-color: #ffffff;
      padding: 10px 20px;
      border-bottom: 1px solid #ccc;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .topbar .left {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: bold;
    }

    .topbar .right img {
      width: 30px;
      height: 30px;
      margin-left: 15px;
      cursor: pointer;
    }

    .reader {
      flex-grow: 1;
      padding: 20px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .header {
      margin-bottom: 15px;
    }

    .book-title {
      font-size: 24px;
      font-weight: bold;
    }

    .author {
      font-size: 16px;
      color: #666;
    }

    iframe {
      flex: 1;
      width: 100%;
      border: none;
    }

    button {
      background-color: #002f4b;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    button:hover {
      background-color: #01406d;
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

    /* Success Popup */
    #successPopup {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 99999;
      align-items: center;
      justify-content: center;
    }

    .success-box {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      width: 400px;
    }

    .success-box img {
      width: 80px;
      margin-bottom: 20px;
    }

    .success-box h2 {
      color: green;
      margin-bottom: 10px;
    }

    .success-box p {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="container">
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
      <a href="setting.php"><img class="icon" src="../Images/Settings.png" alt="Settings Icon" /><span>Account Settings</span></a>       
    </nav>
    <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out Icon" /><span>Sign Out</span></a>
    </div>
  </aside>

  <!-- Main content -->
  <div class="main-content">
    <div class="topbar">
      <div class="left"><span>READLY</span></div>
      <div class="right">
        <a href="setting.php"><img class="icon" src="../Images/profile.png"></a> 
      </div>
    </div>

    <div class="reader">
      <div class="header">
        <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
        <div class="author">Author: <?= htmlspecialchars($book['author']) ?></div>
        <br>
        <a class="btn" href="Book-Details.php">Back to Book Details</a>
        <button type="button" onclick="openBorrowForm('<?= htmlspecialchars($book['title']) ?>')">Borrow Book</button>
    </div>
  </div>
</div>

<!-- Borrow Form Popup -->
<div id="borrowPopup">
  <form class="borrow-form" action="submit_borrow.php" method="POST">
    <img src="../Images/logo.png" alt="Readly">
    <h2>Fill up the following</h2>
    <input type="text" id="user_id" name="user_id" placeholder="User ID" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="book_title" id="bookTitle" placeholder="Book Title" readonly>
    <label for="borrow-date" style="display:block; margin-top: 10px; font-weight: bold;">
      Select the return date (up to 7 days from today)
    </label>
    <input type="date" id="borrow-date" name="date" required title="Choose the return date – maximum of 7 days from today">
    <input type="text" name="contact" class="contact_num" placeholder="Contact" required>
    <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
    <div>
      <button type="submit">SUBMIT</button>
      <button type="button" onclick="closeBorrowForm()">CANCEL</button>
    </div>
  </form>
</div>

<!-- Success Popup -->
<div id="successPopup">
  <div class="success-box">
    <img src="../Images/success-icon.png" alt="Success">
    <h2>SUCCESSFUL</h2>
    <p>Your request is now under review. Kindly wait for approval.</p>
    <button onclick="closeSuccessPopup()">Continue</button>
  </div>
</div>

<script>
  // Allow only numeric input and max 6 digits for 'days_input'
  document.getElementById('user_id').addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
  });

  // Apply the same logic to all 'other deduction' value inputs
  document.querySelectorAll('.contact_num').forEach(input => {
      input.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11);
      });
  });

  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }

  function openBorrowForm(title) {
    document.getElementById('bookTitle').value = title;
    document.getElementById('borrowPopup').style.display = 'flex';
  }

  function closeBorrowForm() {
    document.getElementById('borrowPopup').style.display = 'none';
  }

  function closeSuccessPopup() {
    document.getElementById('successPopup').style.display = 'none';
  }

  window.onload = function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('borrowed') === '1') {
      document.getElementById('successPopup').style.display = 'flex';
    }
  };

  window.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.querySelector('input[name="date"]');
    const today = new Date();
    const maxDate = new Date();
    maxDate.setDate(today.getDate() + 7);

    const formatDate = (d) => {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    dateInput.min = formatDate(today);
    dateInput.max = formatDate(maxDate);
  });
</script>

</body>
</html>