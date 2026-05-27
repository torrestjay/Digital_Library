<?php
include '../dbcon.php';

echo "=== USERS TABLE STRUCTURE ===\n\n";

$result = $conn->query("DESCRIBE users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ") - Null: " . $row['Null'] . "\n";
    }
}
?>
