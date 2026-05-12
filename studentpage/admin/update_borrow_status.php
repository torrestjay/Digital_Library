<?php
include "../dbcon.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$borrowId = $_POST['borrow_id'] ?? null;
$newStatus = $_POST['new_status'] ?? null;
$bookId = $_POST['book_id'] ?? null;

if (!$borrowId || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $conn->begin_transaction();

    // Update the borrow status
    $stmt = $conn->prepare("UPDATE borrowed_books SET status = ?, return_date = CASE WHEN ? = 'returned' THEN CURDATE() ELSE return_date END WHERE id = ?");
    $stmt->bind_param("ssi", $newStatus, $newStatus, $borrowId);
    $stmt->execute();

    // If marking as returned, update the book availability
    if ($newStatus === 'returned' && $bookId) {
        $stmt = $conn->prepare("UPDATE books SET availability = availability + 1 WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
    }

    // If approving a request, decrease book availability
    if ($newStatus === 'borrowed' && $bookId) {
        $stmt = $conn->prepare("UPDATE books SET availability = GREATEST(0, availability - 1) WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
