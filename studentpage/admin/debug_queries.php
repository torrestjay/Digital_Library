<?php
include "../dbcon.php";

echo "Testing direct queries:\n\n";

// Test 1
echo "Test 1: SELECT COUNT(*) FROM books\n";
$result = $conn->query("SELECT COUNT(*) as total FROM books");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Result: " . $row['total'] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test 2
echo "\nTest 2: SELECT COUNT(*) FROM books WHERE availability > 0\n";
$result = $conn->query("SELECT COUNT(*) as available FROM books WHERE availability > 0");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Result: " . $row['available'] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test 3 - Check some book data
echo "\nTest 3: Sample books data\n";
$result = $conn->query("SELECT id, title, availability FROM books LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Title: {$row['title']}, Availability: {$row['availability']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test 4 - Check database being used
echo "\nTest 4: Current Database\n";
$result = $conn->query("SELECT DATABASE()");
if ($result) {
    $row = $result->fetch_row();
    echo "Database: " . $row[0] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
