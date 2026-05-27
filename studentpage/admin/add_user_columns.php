<?php
include '../dbcon.php';

echo "=== ADDING MISSING COLUMNS TO USERS TABLE ===\n\n";

$columns_to_add = [
    'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER fullname",
    'address' => "ALTER TABLE users ADD COLUMN address TEXT AFTER phone",
    'gender' => "ALTER TABLE users ADD COLUMN gender VARCHAR(20) AFTER address"
];

foreach ($columns_to_add as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "✓ Added $col column\n";
        } else {
            echo "✗ Failed to add $col: " . $conn->error . "\n";
        }
    } else {
        echo "✓ $col column already exists\n";
    }
}

echo "\n✅ DATABASE UPDATES COMPLETE\n";
?>
