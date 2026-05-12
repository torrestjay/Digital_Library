<?php
include '../dbcon.php';

if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $query = "SELECT * FROM books WHERE id = $book_id LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($book = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'title' => $book['title'],
            'author' => $book['author'],
            'genre' => $book['genre'],
            'description' => $book['description'],
            'cover_image' => $book['cover_image']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No book ID provided.']);
}
?>
