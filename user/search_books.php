<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];

$search = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($search === '') {
    echo "<p>Please type something to search.</p>";
    exit;
}

$searchTerm = "%{$search}%";

$stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ?");
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// Get user's borrowed books
$borrowed_books = [];
$borrowed_result = mysqli_query($conn, "SELECT book_id FROM borrowed_books WHERE user_id = $user_id AND return_date IS NULL");
while ($row = mysqli_fetch_assoc($borrowed_result)) {
    $borrowed_books[] = $row['book_id'];
}

if ($result->num_rows === 0) {
    echo "<p>No books found.</p>";
    exit;
}

echo '<div class="book-row">';
while ($book = mysqli_fetch_assoc($result)) {
    echo '<div class="book-card">';
    echo '<img src="../Images/' . htmlspecialchars($book['cover_image']) . '" alt="Cover" style="width: 150px; height: 220px; object-fit: cover; border-radius: 8px;" />';
    echo '<p>' . htmlspecialchars($book['title']) . '</p>';
    
    if (in_array($book['id'], $borrowed_books)) {
        echo '<button type="button" class="btn borrowed" disabled>Borrowed</button>';
    } else {
        echo '<a class="btn borrow" href="borrow.php?book_id=' . $book['id'] . '">Borrow</a>';
    }

    echo '</div>';
}
echo '</div>';
?>
