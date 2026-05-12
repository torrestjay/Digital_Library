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

// Add Chapter Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_chapter'])) {
  $chapter_title = $_POST['chapter_title'];
  $content = $_POST['content'];

  // Get the next chapter number
  $count_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM chapters WHERE book_id = ?");
  $count_stmt->bind_param("i", $book_id);
  $count_stmt->execute();
  $count_result = $count_stmt->get_result();
  $count_data = $count_result->fetch_assoc();
  $chapter_number = $count_data['count'] + 1;

  $insert_stmt = $conn->prepare("INSERT INTO chapters (book_id, chapter_number, chapter_title, content) VALUES (?, ?, ?, ?)");
  $insert_stmt->bind_param("iiss", $book_id, $chapter_number, $chapter_title, $content);
  $insert_stmt->execute();
}

// Fetch chapters
$chapter_stmt = $conn->prepare("SELECT * FROM chapters WHERE book_id = ? ORDER BY chapter_number ASC");
$chapter_stmt->bind_param("i", $book_id);
$chapter_stmt->execute();
$chapters = $chapter_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Chapters</title>
  <link rel="stylesheet" href="../css/Edit-Book-Modal.css">
</head>
<body>
  <div class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <img src="../Images/book-icon.png" alt="Icon" class="modal-icon">
        <h2>Manage Chapters for "<?php echo htmlspecialchars($book['title']); ?>"</h2>
      </div>

      <!-- Chapter Form -->
      <form method="POST">
        <div class="form-group">
          <label for="chapter_title">Chapter Title</label>
          <input type="text" id="chapter_title" name="chapter_title" required>
        </div>
        <div class="form-group">
          <label for="content">Chapter Content</label>
          <textarea id="content" name="content" rows="5" required></textarea>
        </div>
        <button type="submit" name="add_chapter">Add Chapter</button>
      </form>

      <hr style="margin: 30px 0;">

      <!-- Chapter List -->
      <h3>Chapters</h3>
      <div class="chapter-list">
        <?php while ($chapter = $chapters->fetch_assoc()): ?>
          <div class="form-group" style="border-bottom: 1px solid #ddd; padding: 10px 0;">
            <strong>Chapter <?php echo $chapter['chapter_number']; ?>:</strong>
            <?php echo htmlspecialchars($chapter['chapter_title']); ?>
            <div style="float: right;">
              <a href="edit-chapter.php?id=<?php echo $chapter['id']; ?>" class="chapter-button">Edit</a>
              <a href="delete-chapter.php?id=<?php echo $chapter['id']; ?>&book_id=<?php echo $book_id; ?>" class="cancel-button" onclick="return confirm('Are you sure?')">Delete</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="button-group">
        <a href="AdminBookEdit.php" class="cancel-btn">Back</a>
      </div>
    </div>
  </div>
</body>
</html>
