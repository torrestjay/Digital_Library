<?php
// Test the getDashboardData.php API endpoint directly
include "../dbcon.php";
header('Content-Type: application/json');

echo "Testing getDashboardData endpoint...\n\n";

// Test 1: Total Books
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$result = $conn->query($totalBooksQuery);
if (!$result) {
    echo "Total Books Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Total Books: " . $row['total'] . "\n";
}

// Test 2: Available Books
$availableBooksQuery = "SELECT COUNT(*) as available FROM books WHERE availability > 0";
$result = $conn->query($availableBooksQuery);
if (!$result) {
    echo "Available Books Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Available Books: " . $row['available'] . "\n";
}

// Test 3: Borrowed Books
$borrowedBooksQuery = "SELECT COUNT(*) as borrowed FROM books WHERE availability = 0";
$result = $conn->query($borrowedBooksQuery);
if (!$result) {
    echo "Borrowed Books Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Borrowed Books: " . $row['borrowed'] . "\n";
}

// Test 4: Pending Requests
$pendingQuery = "SELECT COUNT(*) as pending FROM borrowed_books WHERE status = 'pending'";
$result = $conn->query($pendingQuery);
if (!$result) {
    echo "Pending Requests Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Pending Requests: " . $row['pending'] . "\n";
}

// Test 5: Overdue Books
$overdueQuery = "SELECT COUNT(*) as overdue FROM borrowed_books WHERE status = 'borrowed' AND due_date < CURDATE()";
$result = $conn->query($overdueQuery);
if (!$result) {
    echo "Overdue Books Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Overdue Books: " . $row['overdue'] . "\n";
}

// Test 6: Total Users
$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
$result = $conn->query($totalUsersQuery);
if (!$result) {
    echo "Total Users Query FAILED: " . $conn->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "Total Users: " . $row['total'] . "\n";
}

// Test 7: Recent Activity
$recentActivityQuery = "SELECT 
                            bb.id,
                            bb.book_id,
                            bb.user_id,
                            b.title,
                            u.fullname,
                            bb.borrow_date,
                            bb.return_date,
                            bb.due_date,
                            bb.status
                        FROM borrowed_books bb
                        LEFT JOIN books b ON bb.book_id = b.id
                        LEFT JOIN users u ON bb.user_id = u.id
                        ORDER BY bb.borrow_date DESC
                        LIMIT 10";
$result = $conn->query($recentActivityQuery);
if (!$result) {
    echo "Recent Activity Query FAILED: " . $conn->error . "\n";
} else {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
    }
    echo "Recent Activity Records: " . $count . "\n";
}

echo "\n=== All tests completed ===\n";
$conn->close();
?>
