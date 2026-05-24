<?php
session_start();
include('../dbcon.php');
require_once('borrow_rules.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid extension request.';
    header('Location: borrowed-books.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$borrow_id = isset($_POST['borrow_id']) ? (int)$_POST['borrow_id'] : 0;

if ($borrow_id <= 0) {
    $_SESSION['error'] = 'Invalid extension request.';
    header('Location: borrowed-books.php');
    exit();
}

if (request_extension($conn, $borrow_id, $user_id, $message)) {
    $_SESSION['success'] = 'Extension approved. Added 3 days to your due date.';
} else {
    $_SESSION['error'] = $message !== '' ? $message : 'Unable to process extension request.';
}

header('Location: borrowed-books.php');
exit();
