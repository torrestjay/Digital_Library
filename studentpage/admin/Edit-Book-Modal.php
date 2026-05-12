<?php
include('../dbcon.php');

if (!isset($_GET['id'])) {
  echo "No book ID provided.";
  exit();
}

$book_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
  echo "Book not found.";
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Book</title>
  <link rel="stylesheet" href="../css/Edit-Book-Modal.css">
</head>
<body>
  <div class="modal-overlay">
    <div class="modal-box">
      <!-- Modal Header -->
      <div class="modal-header">
        <img src="../Images/book-icon.png" alt="Icon" class="modal-icon">
        <h2>Edit Book</h2>
      </div>

      <!-- Manage Chapter Button -->
      <div style="text-align: right; margin-bottom: 15px;">
        <a class="chapter-btn" href="manage-chapter.php?book_id=<?= $book_id ?>">Manage Chapter</a>
      </div>

      <!-- Form Layout -->
      <form class="modal-form" action="UpdateBook.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">

        <div class="form-grid">
          <!-- Left Column -->
          <div class="left-column">
            <div class="form-group">
              <label for="title">Book Title</label>
              <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>

            <div class="form-group">
              <label for="author">Author's Name</label>
              <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            </div>

            <div class="form-group">
              <label for="genre">Category/Genre</label>
              <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['category']); ?>" required>
            </div>
          </div>

          <!-- Right Column -->
          <div class="right-column">
            <div class="form-group icon-input">
              <label for="cover">Cover Photo</label>
              <input type="file" id="cover" name="cover">
              <img src="../Images/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current Cover" class="cover-icon" style="width: 80px; margin-top: 8px;">
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div class="button-group">
          <a href="AdminBookEdit.php" class="cancel-btn">Cancel</a>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
