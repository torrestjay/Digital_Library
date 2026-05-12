<?php
session_start(); // ✅ Required to use $_SESSION
include('../dbcon.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $book_id = mysqli_real_escape_string($conn, $_POST['book_id']);
    $due_date = mysqli_real_escape_string($conn, $_POST['date']);
    $borrow_date = date('Y-m-d');
    $return_date = NULL;
    $status = 'Pending';

    // Validate due date (server-side)
    $max_due_date = date('Y-m-d', strtotime('+7 days'));
    if ($due_date > $max_due_date || $due_date < $borrow_date) {
        echo "<script>alert('Invalid return date. Must be within 7 days from today.'); history.back();</script>";
        exit;
    }

    // Prevent duplicate borrow
    $check = mysqli_query($conn, "SELECT * FROM borrowed_books 
                                  WHERE user_id = '$user_id' 
                                  AND book_id = '$book_id' 
                                  AND status IN ('Pending', 'approved')");

    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('You already requested or borrowed this book.'); history.back();</script>";
        exit;
    }

    // Insert request
    $insert = "INSERT INTO borrowed_books 
        (user_id, book_id, borrow_date, due_date, return_date, status)
        VALUES ('$user_id', '$book_id', '$borrow_date', '$due_date', NULL, '$status')";

    if (mysqli_query($conn, $insert)) {
        $success = "Borrow request submitted!";
        $_SESSION['success'] = $success;
        header("Location: ../user/librarypage.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request method.";
}
?>
