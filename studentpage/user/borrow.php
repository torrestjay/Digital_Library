<?php
session_start();
include('../dbcon.php');
require_once('borrow_rules.php');
$user_id = $_SESSION['user_id'] ?? null;
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : (isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0);
$due_input = $_POST['date'] ?? null;
if (!$user_id || $book_id <= 0) {
    $_SESSION['error'] = 'Invalid borrow request.';
    header("Location: ../login.php");
    exit();
}
$user_id = (int)$user_id;
$due_date = parse_due_date($due_input, 7);
if ($due_date === null) {
    $_SESSION['error'] = 'Invalid return date. Please choose within the next 7 days.';
    header('Location: borrowed-books.php');
    exit();
}
if (create_borrow_record($conn, $user_id, $book_id, $due_date, $message)) {
    $_SESSION['success'] = 'Book borrowed successfully. Return date: ' . date('F j, Y', strtotime($due_date));
} else {
    $_SESSION['error'] = $message !== '' ? $message : 'Unable to borrow this book right now.';
}
header("Location: borrowed-books.php");
exit();
