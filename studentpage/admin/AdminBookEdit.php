<?php
session_start();
include('../dbcon.php');

// Add Book functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
  // Generate a unique token for this upload
  $upload_token = uniqid();

  // Check if this is a duplicate submission
  if (!isset($_SESSION['last_upload_token']) || $_SESSION['last_upload_token'] !== $upload_token) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $description = $_POST['description'];

    // Handle file upload
    $cover_image = '';
    if ($_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $file_ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
      $cover_image = uniqid() . '.' . $file_ext;
      $upload_dir = "../Images/";
      move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $cover_image);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO books (title, author, category, cover_image, views, description, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())");

    if (!$stmt) {
      die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $title, $author, $category, $cover_image, $description);
    $stmt->execute();
    $stmt->close();

    // Store the upload token to prevent duplicates
    $_SESSION['last_upload_token'] = $upload_token;

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }
}

// Fetch book details for modal if ID is provided
$book_to_edit = null;
if (isset($_GET['edit_id'])) {
  $book_id = $_GET['edit_id'];
  $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
  $stmt->bind_param("i", $book_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $book_to_edit = $result->fetch_assoc();
  $stmt->close();
}

// Fetch all unique categories
$category_query = "SELECT DISTINCT category FROM books";
$category_result = mysqli_query($conn, $category_query);
if (!$category_result) {
  die("Error fetching categories: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Admin Book Edit</title>
  <link rel="stylesheet" href="../css/AdminBookEdit.css" />
  <!-- SweetAlert2 CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    *{
      font-family: 'Poppins', sans-serif;

    }
    /* Form Styles */
    .add-book-form {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
      max-width: 600px;
      transition: box-shadow 0.3s ease;
    }

    .add-book-form:hover {
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .add-book-form h3 {
      margin-top: 0;
      color: #005b7f;
      font-size: 1.5rem;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .add-book-form input[type="text"],
    .add-book-form textarea {
      width: 100%;
      padding: 12px 15px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .add-book-form input[type="text"]:focus,
    .add-book-form textarea:focus {
      border-color: #005b7f;
      box-shadow: 0 0 0 2px rgba(0, 91, 127, 0.2);
      outline: none;
    }

    .add-book-form textarea {
      height: 120px;
      resize: vertical;
      min-height: 100px;
    }

    .add-book-form input[type="file"] {
      margin-bottom: 15px;
      width: 100%;
      padding: 10px;
      background: #f8f9fa;
      border: 1px dashed #ddd;
      border-radius: 6px;
      transition: border-color 0.3s;
    }

    .add-book-form input[type="file"]:hover {
      border-color: #005b7f;
    }

    /* Button Styles */
    .add-book-form button {
      background-color: #005b7f;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 500;
      transition: all 0.3s;
      width: 100%;
    }

    .add-book-form button:hover {
      background-color: #0078a5;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 91, 127, 0.3);
    }

    .add-book-form button:active {
      transform: translateY(0);
    }

    /* Message Styles */
    .success-message {
      color: #28a745;
      margin-bottom: 15px;
      font-weight: 500;
      padding: 10px;
      background-color: #e8f5e9;
      border-radius: 5px;
      border-left: 4px solid #28a745;
    }

    .error-message {
      color: #dc3545;
      margin-bottom: 15px;
      font-weight: 500;
      padding: 10px;
      background-color: #f8e8e8;
      border-radius: 5px;
      border-left: 4px solid #dc3545;
    }

    /* Book Item Styles */
    .book-item {
      position: relative;
      display: inline-block;
      margin: 15px;
      border-radius: 8px;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .book-item:hover {
      transform: translateY(-5px);
    }

    .book-cover {
      border-radius: 8px;
      transition: all 0.3s ease;
      width: 150px;
      height: 200px;
      object-fit: cover;
      display: block;
    }

    .book-item:hover .book-cover {
      transform: scale(1.03);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    /* Book Title Overlay */
    .book-title-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
      color: white;
      padding: 15px 10px 5px;
      font-size: 13px;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
      box-sizing: border-box;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .book-item:hover .book-title-overlay {
      white-space: normal;
      overflow: visible;
      background: rgba(0, 0, 0, 0.85);
      padding: 15px 10px;
      backdrop-filter: blur(2px);
    }

    /* Modal Styles */
    .modal-overlay {
      display: <?php echo isset($book_to_edit) ? 'flex' : 'none'; ?>;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-box {
      background: white;
      border-radius: 10px;
      width: 80%;
      max-width: 700px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 25px;
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }

    .modal-icon {
      width: 40px;
      height: 40px;
      margin-right: 15px;
    }

    .modal-header h2 {
      margin: 0;
      color: #005b7f;
    }

    .chapter-btn {
      background: #005b7f;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .chapter-btn:hover {
      background: #0078a5;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #555;
    }

    .form-group input[type="text"],
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .cover-icon {
      display: block;
      border-radius: 5px;
      margin-top: 10px;
    }

    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 20px;
    }

    .cancel-btn,
    .save-btn {
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .cancel-btn {
      background: #f0f0f0;
      color: #333;
      text-decoration: none;
      border: 1px solid #ddd;
    }

    .cancel-btn:hover {
      background: #e0e0e0;
    }

    .save-btn {
      background: #005b7f;
      color: white;
      border: none;
    }

    .save-btn:hover {
      background: #0078a5;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .add-book-form {
        padding: 20px;
        max-width: 100%;
      }

      .book-cover {
        width: 120px;
        height: 160px;
      }

      .book-title-overlay {
        font-size: 11px;
        padding: 10px 5px 3px;
      }

      .modal-box {
        width: 95%;
        padding: 15px;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
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
        <a href="admindashboard.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="AdminBookEdit.php"><img class="icon" src="../Images/BookDetails.png" alt="Book Edit Icon" /><span>Book Edit</span></a>
        <a href="AdminUserPage.php"><img class="icon" src="../Images/userpage.png" alt="User Page Icon" /><span>User Page</span></a>
        <a href="SettingAdmin.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Header with Icons -->
      <header class="header">
        <div class="header-icons">
          <!-- <img class="icon" src="../Images/notif.png" alt="Notification Icon"> -->
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <!-- Title & Search Bar Section -->
      <section class="book-controls">
        <h2 class="book-title">Available Books</h2>
        <!-- <div class="search-bar">
          <input type="text" placeholder="Search" />
          <button><img src="../Images/search-icon.png" alt="Search Icon"></button>
        </div> -->
      </section>

      <!-- Add Book Form -->
      <section class="add-book-form">
        <form action="" method="POST" enctype="multipart/form-data">
          <h3>Add New Book</h3>
          <?php if (isset($_SESSION['last_upload_token'])): ?>
            <div class="success-message">Book added successfully!</div>
            <?php unset($_SESSION['last_upload_token']); ?>
          <?php endif; ?>

          <input type="text" name="title" placeholder="Title" required>
          <input type="text" name="author" placeholder="Author" required>
          <input type="text" name="category" placeholder="Category" required>
          <textarea name="description" placeholder="Description" required></textarea>
          <input type="file" name="cover_image" accept="image/*" required>
          <input type="hidden" name="upload_token" value="<?php echo uniqid(); ?>">
          <button type="submit" name="add_book">Add Book</button>
        </form>
      </section>

      <!-- Book Display Section -->
      <div class="book-section">
        <?php while ($cat = mysqli_fetch_assoc($category_result)):
          $category = $cat['category'];
          $stmt = $conn->prepare("SELECT * FROM books WHERE category = ?");
          if (!$stmt) {
            echo "<p>Error preparing query: " . $conn->error . "</p>";
            continue;
          }
          $stmt->bind_param("s", $category);
          $stmt->execute();
          $books_result = $stmt->get_result();
        ?>

          <h3><?php echo htmlspecialchars($category); ?></h3>
          <div class="book-row">
            <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
              <div class="book-item">
                <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>"
                  alt="<?php echo htmlspecialchars($book['title']); ?>"
                  class="book-cover"
                  onclick="openEditModal(<?php echo $book['id']; ?>)">
                <div class="book-title-overlay"><?php echo htmlspecialchars($book['title']); ?></div>
              </div>
            <?php endwhile; ?>
          </div>

        <?php endwhile; ?>
      </div>
    </main>
  </div>

  <!-- Edit Book Modal -->
  <?php if (isset($book_to_edit)): ?>
    <div class="modal-overlay" id="editModal">
      <div class="modal-box">
        <!-- Modal Header -->
        <div class="modal-header">
          <img src="../Images/currently.png" alt="Icon" class="modal-icon">
          <h2>Edit Book</h2>
        </div>

        <!-- Manage Chapter Button
        <div style="text-align: right; margin-bottom: 15px;">
          <a href="manage-chapter.php?book_id="><button class="chapter-btn">Manage Chapter</button></a>
        </div> -->
<!-- //$book_to_edit['id'] -->
        <!-- Form Layout -->
        <form id="updateForm" class="modal-form" action="UpdateBook.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="book_id" value="<?php echo $book_to_edit['id']; ?>">

          <div class="form-grid">
            <!-- Left Column -->
            <div class="left-column">
              <div class="form-group">
                <label for="title">Book Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book_to_edit['title']); ?>" required>
              </div>

              <div class="form-group">
                <label for="author">Author's Name</label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book_to_edit['author']); ?>" required>
              </div>

              <div class="form-group">
                <label for="genre">Category/Genre</label>
                <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book_to_edit['category']); ?>" required>
              </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
              <div class="form-group icon-input">
                <label for="cover">Cover Photo</label>
                <input type="file" id="cover" name="cover">
                <img src="../Images/<?php echo htmlspecialchars($book_to_edit['cover_image']); ?>" alt="Current Cover" class="cover-icon" style="width: 80px; margin-top: 8px;">
              </div>

              <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($book_to_edit['description']); ?></textarea>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="button-group">
            <a href="AdminBookEdit.php" class="cancel-btn">Cancel</a>
            <button type="button" class="save-btn" onclick="confirmUpdate()">Update Book</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  <script>
    function confirmUpdate() {
      Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to save the changes?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'No, cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          // Submit the form
          document.getElementById('updateForm').submit();
        }
      });
    }
  </script>
  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Book updated successfully!',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        });
      });
    </script>
  <?php endif; ?>



  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

    function openEditModal(bookId) {
      // Redirect to the same page with edit_id parameter
      window.location.href = "AdminBookEdit.php?edit_id=" + bookId;
    }

    function closeModal() {
      // Remove the edit_id parameter from URL
      window.location.href = "AdminBookEdit.php";
    }

    // Close modal when clicking outside of it
    document.addEventListener('click', function(event) {
      const modal = document.getElementById('editModal');
      if (modal && event.target === modal) {
        closeModal();
      }
    });

    // Prevent form resubmission when page is refreshed
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
  </script>

</body>

</html>