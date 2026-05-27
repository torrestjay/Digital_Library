<?php
include '../dbcon.php';

echo "=== DETAILED BOOKS TABLE CHECK ===\n\n";

// Get all columns for books table
$result = $conn->query("DESCRIBE books");
if ($result) {
    echo "Books table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
