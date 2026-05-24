<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: borrowed-books.php');
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$borrow_id = isset($_POST['borrow_id']) ? (int)$_POST['borrow_id'] : 0;
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if ($borrow_id <= 0 || $book_id <= 0) {
  $_SESSION['error'] = 'Invalid return request.';
  header('Location: borrowed-books.php');
  exit();
}

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("UPDATE borrowed_books SET status = 'returned', return_date = CURDATE() WHERE id = ? AND user_id = ? AND status = 'borrowed' AND return_date IS NULL");
  $stmt->bind_param('ii', $borrow_id, $user_id);
  $stmt->execute();

  if ($stmt->affected_rows <= 0) {
    throw new Exception('Book could not be returned.');
  }

  $stmt = $conn->prepare('UPDATE books SET availability = availability + 1 WHERE id = ?');
  $stmt->bind_param('i', $book_id);
  $stmt->execute();

  $conn->commit();
  $_SESSION['success'] = 'Book returned successfully.';
} catch (Throwable $e) {
  $conn->rollback();
  $_SESSION['error'] = $e->getMessage();
}

header('Location: borrowed-books.php');
exit();