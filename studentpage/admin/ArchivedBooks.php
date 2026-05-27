<?php
session_start();
include("../dbcon.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get all genres
$genre_query = "SELECT DISTINCT category FROM books ORDER BY category ASC";
$genre_result = $conn->query($genre_query);
$genre_options = [];
while ($row = $genre_result->fetch_assoc()) {
    $genre_options[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Archived Books - Digital Library</title>
  
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
    .book-section {
      padding: var(--space-20);
    }
    
    .controls-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--space-32);
      gap: var(--space-16);
    }
    
    .controls-section h2 {
      margin: 0;
      font-size: var(--font-size-xl);
      font-weight: var(--font-weight-700);
      color: var(--color-text-primary);
    }
    
    .book-row {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: var(--space-24);
      margin-bottom: var(--space-40);
    }
    
    .book-item {
      position: relative;
      overflow: hidden;
      border-radius: var(--radius-lg);
      background-color: var(--color-bg-primary);
      box-shadow: var(--shadow-sm);
      transition: box-shadow var(--transition-base);
    }
    
    .book-item:hover {
      box-shadow: var(--shadow-lg);
    }
    
    .book-cover {
      width: 100%;
      height: 240px;
      object-fit: cover;
      display: block;
      cursor: pointer;
      transition: transform var(--transition-base);
    }
    
    .book-item:hover .book-cover {
      transform: translateY(-8px) scale(1.05);
      box-shadow: var(--shadow-lg);
    }
    
    .book-title-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
      color: white;
      padding: var(--space-12);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-600);
      text-align: center;
      min-height: 60px;
      display: flex;
      align-items: flex-end;
      justify-content: center;
    }
    
    .book-item-actions {
      position: absolute;
      bottom: var(--space-12);
      left: 50%;
      transform: translateX(-50%) translateY(100%);
      display: flex;
      gap: var(--space-8);
      opacity: 0;
      transition: all var(--transition-base) ease;
      z-index: 10;
    }
    
    .book-item:hover .book-item-actions {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
    }
    
    .archived-badge {
      position: absolute;
      top: var(--space-8);
      right: var(--space-8);
      background-color: rgba(255, 152, 0, 0.9);
      color: white;
      padding: var(--space-6) var(--space-12);
      border-radius: var(--radius-full);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-600);
      display: flex;
      align-items: center;
      gap: var(--space-6);
      z-index: 5;
    }
    
    @media (max-width: 768px) {
      .book-row {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: var(--space-16);
      }
      
      .controls-section {
        flex-direction: column;
        align-items: flex-start;
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
    
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <section class="book-section">
        <div class="controls-section">
          <h2><i class="fas fa-archive" style="margin-right: 12px;"></i>Archived Books</h2>
          <a href="AdminBookEdit.php" class="btn btn-secondary" style="text-decoration: none;">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Active Books
          </a>
        </div>
        
        <div class="book-row">
          <?php
          // Fetch archived books
          $query = "SELECT * FROM books WHERE archived_at IS NOT NULL ORDER BY archived_at DESC";
          $result = $conn->query($query);
          
          if ($result && $result->num_rows > 0) {
            while ($book = $result->fetch_assoc()) {
              $archived_date = new DateTime($book['archived_at']);
              $formatted_date = $archived_date->format('M d, Y');
              
              echo "<div class='book-item'>
                <img src='../Images/" . htmlspecialchars($book['cover_image']) . "'
                  alt='" . htmlspecialchars($book['title']) . "'
                  class='book-cover'
                  title='" . htmlspecialchars($book['title']) . "'>
                
                <div class='archived-badge'>
                  <i class='fas fa-archive'></i> Archived
                </div>
                
                <div class='book-title-overlay'>" . htmlspecialchars($book['title']) . "</div>
                
                <div class='book-item-actions'>
                  <button type='button' class='btn btn-sm btn-success' 
                    onclick='confirmRestore(" . $book['id'] . ", \"" . htmlspecialchars($book['title'], ENT_QUOTES) . "\")' 
                    title='Restore book'>
                    <i class='fas fa-undo'></i>
                  </button>
                </div>
              </div>";
            }
          } else {
            echo "<div style='grid-column: 1/-1; text-align: center; padding: 60px 20px;'>
              <div style='font-size: 64px; color: #bdc3c7; margin-bottom: 16px;'><i class='fas fa-inbox'></i></div>
              <div style='font-size: 18px; color: #7f8c8d; margin-bottom: 8px;'>No archived books</div>
              <div style='font-size: 14px; color: #bdc3c7;'>Archived books will appear here</div>
            </div>";
          }
          ?>
        </div>
      </section>
    </main>
  </div>

  <script>
    function confirmRestore(bookId, bookTitle) {
      Swal.fire({
        title: 'Restore this book?',
        text: 'The book will become available again.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4CAF50',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Restore',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          restoreBook(bookId, bookTitle);
        }
      });
    }

    function restoreBook(bookId, bookTitle) {
      const formData = new FormData();
      formData.append('action', 'restore');
      formData.append('book_id', bookId);

      Swal.fire({
        title: 'Restoring...',
        html: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch('archive_operations.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Book Restored',
            text: 'The book is active again.',
            confirmButtonColor: '#0e3a5d',
            timer: 2000
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to restore book',
            confirmButtonColor: '#0e3a5d'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while restoring the book',
          confirmButtonColor: '#0e3a5d'
        });
      });
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>
