<?php
session_start();
include('../dbcon.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../login.php');
  exit();
}

try {
  // Check if column exists
  $checkStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='borrowed_books' AND COLUMN_NAME='reading_progress'");
  $checkStmt->execute();
  $result = $checkStmt->get_result();
  
  if ($result->num_rows === 0) {
    // Column doesn't exist, add it
    $alterStmt = $conn->prepare("ALTER TABLE borrowed_books ADD COLUMN reading_progress INT DEFAULT 0");
    $alterStmt->execute();
    echo json_encode(['success' => true, 'message' => 'reading_progress column added successfully']);
  } else {
    echo json_encode(['success' => true, 'message' => 'reading_progress column already exists']);
  }
  $checkStmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
