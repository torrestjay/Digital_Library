<?php
include '../dbcon.php';
header('Content-Type: application/json; charset=UTF-8');
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image FROM books WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($book = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'title' => $book['title'],
            'author' => $book['author'],
            'category' => $book['category'],
            'genre' => $book['category'],
            'description' => $book['description'],
            'cover_image' => $book['cover_image']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No book ID provided.']);
}
?>
