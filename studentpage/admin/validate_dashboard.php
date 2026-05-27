<?php
// Dashboard Validation Script
// Run this to verify dashboard backend is working correctly

include "../dbcon.php";

echo "=== DASHBOARD BACKEND VALIDATION ===\n\n";

// 1. Check database connection
echo "1. Database Connection: ";
if ($conn && !$conn->connect_error) {
    echo "✓ OK\n";
} else {
    echo "✗ FAILED: " . $conn->connect_error . "\n";
    exit;
}

// 2. Check tables exist
$tables = ['books', 'users', 'borrowed_books'];
echo "\n2. Required Tables:\n";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "   ✓ $table\n";
    } else {
        echo "   ✗ $table - NOT FOUND\n";
    }
}

// 3. Check books table columns
echo "\n3. Books Table Columns:\n";
$requiredCols = ['id', 'title', 'availability'];  // Removed 'status' since it doesn't exist
$result = $conn->query("DESCRIBE books");
$foundCols = [];
while ($row = $result->fetch_assoc()) {
    $foundCols[] = $row['Field'];
}
foreach ($requiredCols as $col) {
    if (in_array($col, $foundCols)) {
        echo "   ✓ $col\n";
    } else {
        echo "   ✗ $col - MISSING\n";
    }
}

// 4. Check users table columns
echo "\n4. Users Table Columns:\n";
$requiredCols = ['id', 'fullname', 'role'];
$result = $conn->query("DESCRIBE users");
$foundCols = [];
while ($row = $result->fetch_assoc()) {
    $foundCols[] = $row['Field'];
}
foreach ($requiredCols as $col) {
    if (in_array($col, $foundCols)) {
        echo "   ✓ $col\n";
    } else {
        echo "   ✗ $col - MISSING\n";
    }
}

// 5. Check borrowed_books table columns
echo "\n5. Borrowed Books Table Columns:\n";
$requiredCols = ['id', 'book_id', 'user_id', 'status', 'borrow_date', 'due_date', 'return_date'];
$result = $conn->query("DESCRIBE borrowed_books");
$foundCols = [];
while ($row = $result->fetch_assoc()) {
    $foundCols[] = $row['Field'];
}
foreach ($requiredCols as $col) {
    if (in_array($col, $foundCols)) {
        echo "   ✓ $col\n";
    } else {
        echo "   ✗ $col - MISSING\n";
    }
}

// 6. Test queries
echo "\n6. Query Tests:\n";

// Test total books
$result = $conn->query("SELECT COUNT(*) as total FROM books");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   ✓ Total Books Query: " . $row['total'] . "\n";
} else {
    echo "   ✗ Total Books Query Failed: " . $conn->error . "\n";
}

// Test available books
$result = $conn->query("SELECT COUNT(*) as available FROM books WHERE availability > 0");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   ✓ Available Books Query: " . $row['available'] . "\n";
} else {
    echo "   ✗ Available Books Query Failed: " . $conn->error . "\n";
}

// Test pending requests
$result = $conn->query("SELECT COUNT(*) as pending FROM borrowed_books WHERE status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   ✓ Pending Requests Query: " . $row['pending'] . "\n";
} else {
    echo "   ✗ Pending Requests Query Failed\n";
}

// Test total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   ✓ Total Users Query: " . $row['total'] . "\n";
} else {
    echo "   ✗ Total Users Query Failed\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";
$conn->close();
?>
