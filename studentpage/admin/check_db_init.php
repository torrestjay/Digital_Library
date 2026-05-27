<?php
include '../dbcon.php';

echo "=== DATABASE TABLE CHECK ===\n\n";

$tables_to_check = [
    'audit_trail',
    'intrusion_log',
    'admin_roles',
    'mfa_secrets',
    'vulnerability_report',
    'archive_log'
];

$results = [];
foreach ($tables_to_check as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $results[] = "✓ $table - EXISTS";
    } else {
        $results[] = "✗ $table - MISSING";
    }
}

echo implode("\n", $results);
echo "\n\n=== USERS TABLE COLUMNS ===\n\n";

$columns_to_check = ['admin_role', 'mfa_enabled', 'mfa_secret', 'last_login', 'account_locked'];
foreach ($columns_to_check as $col) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check && $check->num_rows > 0) {
        echo "✓ $col - EXISTS\n";
    } else {
        echo "✗ $col - MISSING\n";
    }
}

echo "\n=== BOOKS TABLE COLUMNS ===\n\n";

$book_columns = ['archived_at', 'archived_by', 'archive_reason'];
foreach ($book_columns as $col) {
    $check = $conn->query("SHOW COLUMNS FROM books LIKE '$col'");
    if ($check && $check->num_rows > 0) {
        echo "✓ $col - EXISTS\n";
    } else {
        echo "✗ $col - MISSING\n";
    }
}
?>
