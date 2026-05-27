<?php
session_start();
include('../dbcon.php');
include('security_utils.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Input validation
        $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : null;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $author = isset($_POST['author']) ? trim($_POST['author']) : '';
        $category = isset($_POST['genre']) ? trim($_POST['genre']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        // Validate required fields
        if (!$book_id) throw new Exception("Book ID is required");
        if (empty($title) || strlen($title) < 2) throw new Exception("Book title must be at least 2 characters");
        if (strlen($title) > 255) throw new Exception("Book title cannot exceed 255 characters");
        if (empty($author) || strlen($author) < 2) throw new Exception("Author name must be at least 2 characters");
        if (strlen($author) > 255) throw new Exception("Author name cannot exceed 255 characters");
        if (empty($category)) throw new Exception("Category/Genre is required");
        if (empty($description) || strlen($description) < 10) throw new Exception("Description must be at least 10 characters");
        if (strlen($description) > 5000) throw new Exception("Description cannot exceed 5000 characters");
        
        // Get current book data for audit logging
        $stmt = $conn->prepare("SELECT id, title, author, category, description, cover_image FROM books WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->bind_result($db_id, $db_title, $db_author, $db_category, $db_description, $current_cover);
        if (!$stmt->fetch()) {
            throw new Exception("Book not found");
        }
        $stmt->close();
        
        // Store old data for audit logging
        $old_data = json_encode([
            'title' => $db_title,
            'author' => $db_author,
            'category' => $db_category,
            'description' => $db_description
        ]);
        
        // Handle image upload
        $cover_image = $current_cover;
        if (!empty($_FILES['cover']['name'])) {
            $target_dir = "../Images/";
            $imageFileType = strtolower(pathinfo($_FILES["cover"]["name"], PATHINFO_EXTENSION));
            
            // Validate image
            if ($_FILES["cover"]["error"] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error");
            }
            
            $check = getimagesize($_FILES["cover"]["tmp_name"]);
            if ($check === false) throw new Exception("File is not a valid image");
            if ($_FILES["cover"]["size"] > 5000000) throw new Exception("File too large (maximum 5MB)");
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Invalid image format. Allowed: JPG, PNG, GIF");
            }
            
            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["cover"]["tmp_name"], $target_file)) {
                $cover_image = $new_filename;
                // Delete old image if it exists and is different
                if ($current_cover && file_exists($target_dir . $current_cover) && $current_cover !== $new_filename) {
                    unlink($target_dir . $current_cover);
                }
            } else {
                throw new Exception("Failed to save image. Please try again");
            }
        }
        
        // Prepare update query
        $update_query = "UPDATE books SET title = ?, author = ?, category = ?, description = ?";
        $param_types = "ssss";
        $params = [$title, $author, $category, $description];
        
        if (!empty($cover_image) && $cover_image !== $current_cover) {
            $update_query .= ", cover_image = ?";
            $param_types .= "s";
            $params[] = $cover_image;
        }
        
        $update_query .= " WHERE id = ?";
        $param_types .= "i";
        $params[] = $book_id;
        
        // Execute update
        $stmt = $conn->prepare($update_query);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param($param_types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update book: " . $stmt->error);
        }
        $stmt->close();
        
        // Log the update action
        $new_data = json_encode([
            'title' => $title,
            'author' => $author,
            'category' => $category,
            'description' => $description,
            'cover_updated' => $cover_image !== $current_cover
        ]);
        logAdminAction($conn, 'Update Book', 'book-edit', 'book', $book_id, $title, $old_data, $new_data);
        
        // Return success response and redirect
        header("Location: AdminBookEdit.php?success=1&id=" . $book_id);
        exit();
        
    } catch (Exception $e) {
        // Log error and redirect with error message
        header("Location: AdminBookEdit.php?error=" . urlencode($e->getMessage()) . "&id=" . (isset($book_id) ? $book_id : ''));
        exit();
    }
} else {
    header("Location: AdminBookEdit.php");
    exit();
}

