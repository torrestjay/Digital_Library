<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Get borrowed books
$borrowed_books = [];
$borrowed_result = mysqli_query($conn, "SELECT book_id FROM borrowed_books WHERE user_id = $user_id AND return_date IS NULL");
while ($row = mysqli_fetch_assoc($borrowed_result)) {
    $borrowed_books[] = $row['book_id'];
}

$genres = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];

foreach ($genres as $genre) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE category = ?");
    $stmt->bind_param("s", $genre);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<div class="book-category">';
    echo '<h3>' . htmlspecialchars($genre) . '</h3>';
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
    echo '</div>';
}
?>
