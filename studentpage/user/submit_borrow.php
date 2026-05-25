<?php
session_start(); // ✅ Required to use $_SESSION
include('../dbcon.php');
require_once('borrow_rules.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Please sign in first.';
        header('Location: ../login.php');
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $due_date = parse_due_date($_POST['date'] ?? null, 7);
    if ($book_id <= 0 || $due_date === null) {
        $_SESSION['error'] = 'Invalid request. Pick a return date within 7 days.';
        header('Location: ../user/librarypage.php');
        exit;
    }
    if (create_borrow_record($conn, $user_id, $book_id, $due_date, $message)) {
        $_SESSION['success'] = 'Book borrowed successfully. Return date: ' . date('F j, Y', strtotime($due_date));
        header('Location: ../user/borrowed-books.php');
        exit;
    }
    $_SESSION['error'] = $message !== '' ? $message : 'Unable to borrow this book right now.';
    header('Location: ../user/librarypage.php');
    exit;
} else {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../user/librarypage.php');
    exit;
}
?>
