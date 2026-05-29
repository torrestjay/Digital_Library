<?php
// Must start session before any output
ini_set('display_errors', 0);
session_start();

include("../dbcon.php");

// Check if user is logged in via session
$user_id = $_SESSION['user_id'] ?? null;

// Fallback: if no session, check referer
if (empty($user_id)) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // Allow if coming from AdminBookEdit.php or admindashboard
    if (stripos($referer, 'AdminBookEdit.php') !== false || stripos($referer, 'admin') !== false) {
        // Use a system user ID for audit purposes
        $user_id = -1;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'archive') {
        $book_id = (int)$_POST['book_id'] ?? 0;
        $reason = $_POST['reason'] ?? '';
        
        if ($book_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
            exit();
        }
        
        try {
            // Get book details
            $stmt = $conn->prepare("SELECT id, title FROM books WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
            $stmt->close();
            
            if (!$book) {
                throw new Exception("Book not found");
            }
            
            // Mark book as archived (soft archive)
            $stmt = $conn->prepare("UPDATE books SET archived_at = NOW(), archived_by = ?, archive_reason = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("isi", $user_id, $reason, $book_id);
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
            $stmt->close();
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Book archived successfully',
                'book_id' => $book_id
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    } elseif ($action === 'restore') {
        // Restore archived book
        $book_id = (int)$_POST['book_id'] ?? 0;
        
        if ($book_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
            exit();
        }
        
        try {
            // Get book details
            $stmt = $conn->prepare("SELECT id, title FROM books WHERE id = ? AND archived_at IS NOT NULL");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
            $stmt->close();
            
            if (!$book) {
                throw new Exception("Archived book not found");
            }
            
            // Restore book (clear archive fields)
            $stmt = $conn->prepare("UPDATE books SET archived_at = NULL, archived_by = NULL, archive_reason = NULL WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $book_id);
            if (!$stmt->execute()) {
                throw new Exception("Restore failed: " . $stmt->error);
            }
            $stmt->close();
            
            // Return success
            echo json_encode([
                'success' => true,
                'message' => 'Book restored successfully',
                'book_id' => $book_id
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

