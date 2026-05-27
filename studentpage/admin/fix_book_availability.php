<?php
/**
 * Fix Book Availability
 * This script sets availability to 1 for all books that have NULL or 0 availability
 * This is a one-time fix to make books readable by users
 */

session_start();
include("../dbcon.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'librarian')) {
    echo "Not authorized";
    exit();
}

// Check if this is a POST request (to prevent accidental execution)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("This action requires POST request");
}

// First, let's check how many books have no availability set
$count_query = "SELECT COUNT(*) as total FROM books WHERE availability IS NULL OR availability = 0";
$count_result = $conn->query($count_query);
$count_data = $count_result->fetch_assoc();
$books_to_fix = $count_data['total'];

if ($books_to_fix > 0) {
    // Set availability to 1 for all books that are currently unavailable
    $update_query = "UPDATE books SET availability = 1 WHERE availability IS NULL OR availability = 0";
    
    if ($conn->query($update_query)) {
        echo json_encode([
            'success' => true,
            'message' => "Fixed $books_to_fix books - set availability to 1",
            'fixed' => $books_to_fix
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Error updating books: " . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'message' => "All books already have proper availability values",
        'fixed' => 0
    ]);
}

$conn->close();
?>
