<?php
session_start();
include('../dbcon.php');
include('security_utils.php');
// Add Book functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
  $title = $_POST['title'];
  $author = $_POST['author'];
  $category = $_POST['category'];
  $description = $_POST['description'];
  $cover_image = '';
  if ($_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $file_ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
    $cover_image = uniqid() . '.' . $file_ext;
    $upload_dir = "../Images/";
    move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $cover_image);
  }
  $stmt = $conn->prepare("INSERT INTO books (title, author, category, cover_image, views, description, availability, created_at) VALUES (?, ?, ?, ?, 0, ?, 1, NOW())");
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  $stmt->bind_param("sssss", $title, $author, $category, $cover_image, $description);
  $stmt->execute();
  $book_id = $conn->insert_id;
  $stmt->close();
  
  // Log the book addition
  $new_data = json_encode(['title' => $title, 'author' => $author, 'category' => $category, 'cover_image' => $cover_image, 'description' => $description]);
  logAdminAction($conn, 'Add Book', 'book-add', 'book', $book_id, $title, null, $new_data);
  
  header("Location: " . $_SERVER['PHP_SELF'] . "?added=1");
  exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
  $book_id = (int)$_POST['book_id'];
  
  // Fetch full book data for audit logging
  $stmt = $conn->prepare("SELECT id, title, author, category, description, cover_image FROM books WHERE id = ?");
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  $stmt->bind_param("i", $book_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $book = $result->fetch_assoc();
  $stmt->close();
  
  if ($book) {
    // Delete cover image if exists
    if (!empty($book['cover_image'])) {
      $image_path = "../Images/" . $book['cover_image'];
      if (file_exists($image_path)) {
        unlink($image_path);
      }
    }
    
    // Delete the book
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    if (!$stmt) {
      die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
    
    // Log the book deletion
    $old_data = json_encode(['id' => $book['id'], 'title' => $book['title'], 'author' => $book['author'], 'category' => $book['category'], 'description' => $book['description']]);
    logAdminAction($conn, 'Delete Book', 'book-delete', 'book', $book_id, $book['title'], $old_data, null);
  }
  
  header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
  exit();
}

// Fetch book details for modal if ID is provided
$book_to_edit = null;
if (isset($_GET['edit_id'])) {
  $book_id = $_GET['edit_id'];
  $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
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
$genre_options = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];

// Handle success/error messages from UpdateBook.php
$success_message = '';
$error_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
  $success_message = 'Book updated successfully!';
}
if (isset($_GET['error'])) {
  $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Admin Book Management - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    /* ============================================================
       PAGE-SPECIFIC STYLES: BOOK MANAGEMENT
       ============================================================ */
    
    .content-section {
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    /* ---- PAGE HEADER & CONTROLS ---- */
    .page-header {
      padding: var(--space-24) var(--space-24) 0;
      margin-bottom: var(--space-24);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: var(--space-16);
      flex-wrap: wrap;
    }

    .page-header .section-title {
      margin: 0;
    }

    /* ---- BOOK GRID ---- */
    .book-container {
      padding: 0 var(--space-24) var(--space-24);
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .book-category-section {
      margin-bottom: var(--space-32);
    }

    .book-category-title {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      margin: 0 0 var(--space-16) 0;
      display: flex;
      align-items: center;
      gap: var(--space-12);
    }

    .book-category-title i {
      color: var(--color-primary-light);
      font-size: 20px;
    }

    .book-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: var(--space-20);
    }

    /* ---- BOOK CARDS ---- */
    .book-card {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      transition: all var(--transition-base) ease;
      background: white;
      border-radius: var(--radius-lg);
      padding: var(--space-8);
      box-shadow: var(--shadow-sm);
    }

    .book-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-4px);
    }

    .book-cover {
      width: 140px;
      height: 190px;
      border-radius: var(--radius-md);
      object-fit: cover;
      transition: all var(--transition-base) ease;
      box-shadow: var(--shadow-md);
      display: block;
    }

    .book-card:hover .book-cover {
      transform: scale(1.03);
      box-shadow: var(--shadow-lg);
    }

    .book-title-display {
      position: absolute;
      bottom: var(--space-8);
      left: var(--space-8);
      right: var(--space-8);
      background: linear-gradient(to top, rgba(14, 58, 93, 0.95), rgba(14, 58, 93, 0.8));
      color: white;
      padding: var(--space-12) var(--space-8);
      font-size: var(--font-size-xs);
      text-align: center;
      font-weight: var(--font-weight-600);
      border-radius: var(--radius-sm);
      max-height: 40px;
      overflow: hidden;
      transition: all var(--transition-base) ease;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .book-card:hover .book-title-display {
      max-height: 80px;
      -webkit-line-clamp: 3;
    }

    .book-actions {
      position: absolute;
      bottom: var(--space-12);
      left: 50%;
      transform: translateX(-50%) translateY(100%);
      display: flex;
      gap: var(--space-8);
      z-index: 10;
      opacity: 0;
      transition: all var(--transition-base) ease;
    }

    .book-card:hover .book-actions {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
    }

    .book-actions form {
      margin: 0;
    }

    .btn-sm {
      min-height: 36px;
      padding: var(--space-8) var(--space-12);
      font-size: var(--font-size-xs);
      border-radius: var(--radius-md);
    }

    /* ---- EMPTY STATE ---- */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: var(--space-40);
      background: var(--color-bg-secondary);
      border-radius: var(--radius-lg);
      border: 2px dashed var(--color-border);
    }

    .empty-state-icon {
      font-size: 48px;
      color: var(--color-text-secondary);
      margin-bottom: var(--space-12);
    }

    .empty-state-title {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      margin-bottom: var(--space-8);
    }

    .empty-state-text {
      font-size: var(--font-size-sm);
      color: var(--color-text-secondary);
    }

    /* ---- MODAL STYLES ---- */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      overflow-y: auto;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: white;
      border-radius: var(--radius-2xl);
      box-shadow: var(--shadow-2xl);
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      margin: auto;
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-24);
      border-bottom: 1px solid var(--color-border);
      background: var(--color-bg-secondary);
      border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    }

    .modal-header h3 {
      font-size: var(--font-size-xl);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      margin: 0;
      display: flex;
      align-items: center;
      gap: var(--space-12);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--color-text-secondary);
      cursor: pointer;
      transition: color var(--transition-base) ease;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close:hover {
      color: var(--color-text-primary);
    }

    /* ---- FORM STYLES ---- */
    .form-group {
      margin-bottom: var(--space-20);
    }

    .form-group label {
      display: block;
      margin-bottom: var(--space-8);
      font-weight: var(--font-weight-600);
      color: var(--color-text-primary);
      font-size: var(--font-size-sm);
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: var(--space-12);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      font-family: var(--font-family);
      font-size: var(--font-size-sm);
      color: var(--color-text-primary);
      transition: border-color var(--transition-base) ease;
      background-color: white;
      box-sizing: border-box;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="file"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--color-primary-light);
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-small-text {
      display: block;
      margin-top: var(--space-8);
      font-size: var(--font-size-xs);
      color: var(--color-text-secondary);
    }

    .form-required {
      color: var(--color-danger);
    }

    /* ---- FORM GRID (TWO COLUMNS) ---- */
    .form-two-column {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: var(--space-20);
      margin-bottom: var(--space-20);
    }

    /* ---- MODAL ACTIONS ---- */
    .modal-body {
      padding: var(--space-24);
    }

    .modal-footer {
      display: flex;
      gap: var(--space-12);
      justify-content: flex-end;
      padding: var(--space-24);
      border-top: 1px solid var(--color-border);
    }
    
    @media (max-width: 768px) {
      .book-section {
        padding: var(--space-20);
      }
      
      .book-row {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: var(--space-16);
      }
      
      .book-cover {
        width: 130px;
        height: 180px;
      }
      
      .controls-section {
        flex-direction: column;
        align-items: stretch;
      }
      
      .controls-section h2 {
        margin-bottom: var(--space-12);
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar Behavior Script -->
  <script src="includes/sidebar-behavior.js"></script>
  
  <div class="container">
    <!-- Standardized Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
      <!-- Content Section (No Header) -->
      <section class="content-section">
        <!-- Page Header with Controls -->
        <div class="page-header">
          <h1 class="section-title"><i class="fas fa-book"></i> Book Management</h1>
          <button type="button" class="btn btn-primary" onclick="openAddModal()" aria-label="Add a new book">
            <i class="fas fa-plus"></i> Add Book
          </button>
        </div>

        <!-- Books Container -->
        <div class="book-container">
          <?php 
          // Fetch all categories
          $cat_result = $conn->query("SELECT DISTINCT category FROM books ORDER BY category ASC");
          
          if ($cat_result && $cat_result->num_rows > 0):
            while ($cat = mysqli_fetch_assoc($cat_result)):
              $category = $cat['category'];
              $stmt = $conn->prepare("SELECT * FROM books WHERE category = ? ORDER BY title ASC");
              if (!$stmt) {
                echo "<p style='color: var(--color-danger);'>Error preparing query: " . $conn->error . "</p>";
                continue;
              }
              $stmt->bind_param("s", $category);
              $stmt->execute();
              $books_result = $stmt->get_result();
          ?>
            <div class="book-category-section">
              <h2 class="book-category-title">
                <i class="fas fa-folder"></i>
                <?php echo htmlspecialchars($category); ?>
              </h2>
              
              <div class="book-grid">
                <?php if ($books_result->num_rows > 0): ?>
                  <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                    <div class="book-card">
                      <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>"
                        alt="<?php echo htmlspecialchars($book['title']); ?>"
                        class="book-cover"
                        title="<?php echo htmlspecialchars($book['title']); ?>">
                      
                      <div class="book-title-display"><?php echo htmlspecialchars($book['title']); ?></div>
                      
                      <div class="book-actions">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $book['id']; ?>)" title="Edit book" aria-label="Edit book">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-title="<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>" data-id="<?php echo $book['id']; ?>" onclick="confirmArchive(this.dataset.id, this.dataset.title)" title="Archive book" aria-label="Archive book">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php else: ?>
                  <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <div class="empty-state-title">No books in this category</div>
                    <div class="empty-state-text">Books will appear here once added</div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php 
              $stmt->close();
            endwhile;
          else:
          ?>
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fas fa-book"></i></div>
              <div class="empty-state-title">No books found</div>
              <div class="empty-state-text">Click "Add Book" to get started</div>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <!-- Add Book Modal -->
  <div class="modal-overlay" id="addModal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3><i class="fas fa-plus"></i> Add New Book</h3>
        <button type="button" class="modal-close" onclick="closeAddModal()" aria-label="Close modal"><i class="fas fa-times"></i></button>
      </div>
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="form-group">
            <label for="title">Book Title <span class="form-required">*</span></label>
            <input type="text" id="title" name="title" placeholder="Enter book title" required minlength="2" maxlength="255">
          </div>
          
          <div class="form-group">
            <label for="author">Author Name <span class="form-required">*</span></label>
            <input type="text" id="author" name="author" placeholder="Enter author name" required minlength="2" maxlength="255">
          </div>
          
          <div class="form-group">
            <label for="category">Category/Genre <span class="form-required">*</span></label>
            <select id="category" name="category" required>
              <option value="">Select a genre...</option>
              <?php foreach ($genre_options as $genre_option): ?>
                <option value="<?php echo htmlspecialchars($genre_option); ?>"><?php echo htmlspecialchars($genre_option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="description">Description <span class="form-required">*</span></label>
            <textarea id="description" name="description" placeholder="Enter book description" required minlength="10" maxlength="5000"></textarea>
            <span class="form-small-text">Minimum 10 characters, maximum 5000 characters</span>
          </div>
          
          <div class="form-group">
            <label for="cover_image">Cover Image <span class="form-required">*</span></label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*" required>
            <span class="form-small-text">Supported formats: JPG, PNG, GIF (Max 5MB)</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Edit Book Modal -->
  <?php if (isset($book_to_edit)): ?>
    <div class="modal-overlay active" id="editModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3><i class="fas fa-edit"></i> Edit Book</h3>
          <button type="button" class="modal-close" onclick="closeEditModal()" aria-label="Close modal"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="updateForm" action="UpdateBook.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="book_id" value="<?php echo $book_to_edit['id']; ?>">
          
          <div class="modal-body">
            <div class="form-two-column">
              <div>
                <div class="form-group">
                  <label for="edit-title">Book Title <span class="form-required">*</span></label>
                  <input type="text" id="edit-title" name="title" value="<?php echo htmlspecialchars($book_to_edit['title']); ?>" required minlength="2" maxlength="255">
                </div>
                
                <div class="form-group">
                  <label for="edit-author">Author Name <span class="form-required">*</span></label>
                  <input type="text" id="edit-author" name="author" value="<?php echo htmlspecialchars($book_to_edit['author']); ?>" required minlength="2" maxlength="255">
                </div>
                
                <div class="form-group">
                  <label for="edit-genre">Category/Genre <span class="form-required">*</span></label>
                  <select id="edit-genre" name="genre" required>
                    <option value="">Select a category...</option>
                    <?php
                      $selected_genre = $book_to_edit['category'];
                      $edit_genres = $genre_options;
                      if (!in_array($selected_genre, $edit_genres, true)) {
                        array_unshift($edit_genres, $selected_genre);
                      }
                      foreach ($edit_genres as $genre_option):
                    ?>
                      <option value="<?php echo htmlspecialchars($genre_option); ?>" <?php echo ($selected_genre === $genre_option) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre_option); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              
              <div>
                <div class="form-group">
                  <label>Cover Image</label>
                  <div style="text-align: center; margin-bottom: var(--space-16);">
                    <img src="../Images/<?php echo htmlspecialchars($book_to_edit['cover_image']); ?>" 
                      alt="Current cover" 
                      id="coverPreview"
                      style="max-width: 100%; max-height: 200px; border-radius: var(--radius-md); box-shadow: var(--shadow-md); object-fit: cover;">
                  </div>
                  <input type="file" id="edit-cover" name="cover" accept="image/*">
                  <span class="form-small-text">Leave empty to keep current cover. JPG, PNG, GIF (Max 5MB)</span>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="edit-description">Description <span class="form-required">*</span></label>
              <textarea id="edit-description" name="description" required minlength="10" maxlength="5000"><?php echo htmlspecialchars($book_to_edit['description']); ?></textarea>
              <span class="form-small-text">Minimum 10 characters, maximum 5000 characters</span>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="validateAndSubmitEdit()">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  <script>
    console.log('[AdminBookEdit] Page loaded');

    // Validate and submit edit form with client-side validation
    function validateAndSubmitEdit() {
      const title = document.getElementById('edit-title').value.trim();
      const author = document.getElementById('edit-author').value.trim();
      const genre = document.getElementById('edit-genre').value.trim();
      const description = document.getElementById('edit-description').value.trim();
      const coverFile = document.getElementById('edit-cover').files[0];
      
      let errors = [];
      
      // Validate title
      if (!title) {
        errors.push('Book title is required');
      } else if (title.length < 2) {
        errors.push('Book title must be at least 2 characters');
      } else if (title.length > 255) {
        errors.push('Book title cannot exceed 255 characters');
      }
      
      // Validate author
      if (!author) {
        errors.push('Author name is required');
      } else if (author.length < 2) {
        errors.push('Author name must be at least 2 characters');
      } else if (author.length > 255) {
        errors.push('Author name cannot exceed 255 characters');
      }
      
      // Validate genre
      if (!genre) {
        errors.push('Category/Genre is required');
      }
      
      // Validate description
      if (!description) {
        errors.push('Description is required');
      } else if (description.length < 10) {
        errors.push('Description must be at least 10 characters');
      } else if (description.length > 5000) {
        errors.push('Description cannot exceed 5000 characters');
      }
      
      // Validate image if provided
      if (coverFile) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(coverFile.type)) {
          errors.push('Invalid image format. Allowed: JPG, PNG, GIF');
        }
        if (coverFile.size > 5000000) {
          errors.push('Image file too large (maximum 5MB)');
        }
      }
      
      // Show errors if any
      if (errors.length > 0) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: '<ul style="text-align: left; margin: 10px 0;">' + 
                errors.map(e => '<li style="margin: 5px 0;">' + e + '</li>').join('') + 
                '</ul>',
          confirmButtonColor: 'var(--color-primary-light)'
        });
        return;
      }
      
      confirmUpdate();
    }
    
    function confirmUpdate() {
      Swal.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to update this book\'s information?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--color-primary-light)',
        cancelButtonColor: 'var(--color-text-light)',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we update the book information.',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          document.getElementById('updateForm').submit();
        }
      });
    }

    function confirmArchive(bookId, bookTitle) {
      Swal.fire({
        title: 'Archive this book?',
        text: 'The book will be removed from active listings but its history will be preserved.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--color-warning)',
        cancelButtonColor: 'var(--color-text-light)',
        confirmButtonText: 'Archive',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          archiveBook(bookId, bookTitle);
        }
      });
    }

    function archiveBook(bookId, bookTitle) {
      const formData = new FormData();
      formData.append('action', 'archive');
      formData.append('book_id', bookId);
      formData.append('reason', `Archived by admin: ${bookTitle}`);

      Swal.fire({
        title: 'Archiving...',
        html: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch('archive_operations.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      })
      .then(response => {
        console.log('[AdminBookEdit] Response status:', response.status);
        return response.text().then(text => {
          console.log('[AdminBookEdit] Response text:', text);
          console.log('[AdminBookEdit] Response text length:', text.length);
          
          if (!response.ok) {
            // Log the error response
            console.error('[AdminBookEdit] HTTP Error:', response.status, text);
            throw new Error(`HTTP ${response.status}: ${text || 'No response body'}`);
          }
          
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('[AdminBookEdit] JSON parse error:', e, 'Text:', text);
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
          }
        });
      })
      .then(data => {
        console.log('[AdminBookEdit] Data:', data);
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Book Archived',
            text: 'The book was successfully archived.',
            confirmButtonColor: 'var(--color-primary-dark)',
            timer: 2000
          }).then(() => {
            window.location.href = 'ArchivedBooks.php';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to archive book',
            confirmButtonColor: 'var(--color-primary-dark)'
          });
        }
      })
      .catch(error => {
        console.error('[AdminBookEdit] Archive error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'An error occurred while archiving the book',
          confirmButtonColor: 'var(--color-primary-dark)'
        });
      });
    }

    function openAddModal() {
      const modal = document.getElementById('addModal');
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
      const modal = document.getElementById('addModal');
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    function openEditModal(bookId) {
      window.location.href = "AdminBookEdit.php?edit_id=" + bookId;
    }

    function closeEditModal() {
      window.location.href = "AdminBookEdit.php";
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
      const addModal = document.getElementById('addModal');
      const editModal = document.getElementById('editModal');
      
      if (addModal && event.target === addModal) {
        closeAddModal();
      }
      
      if (editModal && event.target === editModal) {
        closeEditModal();
      }
    });

    // Keyboard shortcut: Escape to close modals
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        
        if (addModal && addModal.classList.contains('active')) {
          closeAddModal();
        }
        
        if (editModal && editModal.classList.contains('active')) {
          closeEditModal();
        }
      }
    });

    // Prevent form resubmission
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }

    // Show success/error messages
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      
      if (urlParams.get('added') === '1') {
        Swal.fire({
          icon: 'success',
          title: 'Book Added',
          text: 'The book has been successfully added to the library.',
          confirmButtonColor: 'var(--color-primary-dark)',
          timer: 3000
        });
      }
      
      if (urlParams.get('success') === '1') {
        Swal.fire({
          icon: 'success',
          title: 'Changes Saved',
          text: 'The book information has been successfully updated.',
          confirmButtonColor: 'var(--color-primary-dark)',
          timer: 3000
        });
      }
      
      if (urlParams.get('error')) {
        const error = decodeURIComponent(urlParams.get('error'));
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error,
          confirmButtonColor: 'var(--color-primary-dark)'
        });
      }
    });
  </script>
</body>
</html>
