<?php
session_start();
include('../dbcon.php'); // Safe include pathway hook to digital_library base schema

$book_id = $_GET['id'] ?? '';
$book_id = mysqli_real_escape_string($conn, $book_id);

// Target specific book items out of books master table
$query = "SELECT * FROM books WHERE id = '$book_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    echo "Book content unavailable or entry path missing.";
    exit;
}

// Safely update view tracker increments inside master table schema log context
mysqli_query($conn, "UPDATE books SET views = views + 1 WHERE id = '$book_id'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($book['title']) ?> - Reader Mode</title>
  
  <link rel="stylesheet" href="/digital_library/css/accessibility.css">
  <script src="/digital_library/js/accessibility.js"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: 'Poppins', sans-serif; background-color: #fdfaf7; color: #333; }
    .reader-container { max-width: 800px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .chapter-text p { margin-bottom: 20px; font-size: 18px; line-height: 1.8; text-align: justify; }
    .book-meta { border-bottom: 2px solid #0e3a5d; padding-bottom: 15px; margin-bottom: 25px; }
  </style>
</head>
<body>

  <div class="reader-container">
    <div class="book-meta">
      <h1 style="color: #0e3a5d; font-size: 28px; font-weight: 700;"><?= htmlspecialchars($book['title']) ?></h1>
      <p style="font-style: italic; color: #666; margin-top: 5px;">Written by: <?= htmlspecialchars($book['author']) ?></p>
    </div>

    <div class="chapter-text">
       <?php 
          $paragraphs = explode("\n", $book['description']);
          foreach($paragraphs as $para) {
              if(trim($para) !== "") {
                  echo "<p>" . htmlspecialchars(trim($para)) . "</p>";
              }
          }
       ?>
    </div>
  </div>

  <?php include '../accessibility.php'; ?>

</body>
</html>