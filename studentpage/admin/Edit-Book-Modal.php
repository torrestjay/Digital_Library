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
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="editBookTitle" aria-describedby="editBookDesc">
    <section class="modal-box">
      <div class="modal-header">
        <img src="../Images/book-icon.png" alt="Icon" class="modal-icon">
        <div>
          <p style="letter-spacing: 0.12em; text-transform: uppercase; color: #1b678f; font-weight: 700; margin-bottom: 6px;">Library Management</p>
          <h2 id="editBookTitle" style="margin: 0; color: #0e3a5d;">Edit Book</h2>
        </div>
      </div>
      <p id="editBookDesc" style="margin: 10px 0 18px; color: #5f7385; line-height: 1.7;">Update the book profile, cover image, or description before publishing the changes.</p>
      <div style="text-align: right; margin-bottom: 15px;">
        <a class="chapter-btn" href="manage-chapter.php?book_id=<?= $book_id ?>">Manage Chapters</a>
      </div>
      <form class="modal-form" action="UpdateBook.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
        <div class="form-grid">
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
        <div class="button-group">
          <a href="AdminBookEdit.php" class="cancel-btn">Cancel</a>
          <button type="submit" class="save-btn">Save Changes</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
