<?php
/**
 * Archive System Database Initialization
 * This script adds archive support to the books table
 * Run once to initialize the archive system
 */

include "../dbcon.php";

// Check if archived_at column exists
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='books' AND COLUMN_NAME='archived_at'");

if ($result->num_rows === 0) {
    // Add archived_at column
    $alter_sql_1 = "ALTER TABLE books ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER description";
    if ($conn->query($alter_sql_1)) {
        echo "✓ Added archived_at column<br>";
    } else {
        echo "✗ Error adding archived_at column: " . $conn->error . "<br>";
    }
} else {
    echo "✓ archived_at column already exists<br>";
}

// Check if archived_by column exists
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='books' AND COLUMN_NAME='archived_by'");

if ($result->num_rows === 0) {
    // Add archived_by column
    $alter_sql_2 = "ALTER TABLE books ADD COLUMN archived_by INT NULL DEFAULT NULL AFTER archived_at";
    if ($conn->query($alter_sql_2)) {
        echo "✓ Added archived_by column<br>";
    } else {
        echo "✗ Error adding archived_by column: " . $conn->error . "<br>";
    }
} else {
    echo "✓ archived_by column already exists<br>";
}

// Check if archive_reason column exists
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='books' AND COLUMN_NAME='archive_reason'");

if ($result->num_rows === 0) {
    // Add archive_reason column
    $alter_sql_3 = "ALTER TABLE books ADD COLUMN archive_reason VARCHAR(500) NULL DEFAULT NULL AFTER archived_by";
    if ($conn->query($alter_sql_3)) {
        echo "✓ Added archive_reason column<br>";
    } else {
        echo "✗ Error adding archive_reason column: " . $conn->error . "<br>";
    }
} else {
    echo "✓ archive_reason column already exists<br>";
}

// Create archive_log table if it doesn't exist
$create_log_table = "CREATE TABLE IF NOT EXISTS archive_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    admin_id INT NOT NULL,
    admin_email VARCHAR(100),
    action VARCHAR(50) NOT NULL COMMENT 'Archived, Restored',
    reason VARCHAR(500),
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_book_id (book_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_date (action_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_log_table)) {
    echo "✓ Archive log table ready<br>";
} else {
    echo "✗ Error creating archive log table: " . $conn->error . "<br>";
}

echo "<br><strong>Archive system initialized successfully!</strong>";
$conn->close();
?>
