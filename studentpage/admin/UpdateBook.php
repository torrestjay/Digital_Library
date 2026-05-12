<?php
include('../dbcon.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['genre'];
    $description = $_POST['description'];

    try {
        $cover_image = '';

        // Get current cover image
        $stmt = $conn->prepare("SELECT cover_image FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->bind_result($current_cover);
        $stmt->fetch();
        $stmt->close();

        // Handle image upload
        if (!empty($_FILES['cover']['name'])) {
            $target_dir = "../Images/";
            $imageFileType = strtolower(pathinfo($_FILES["cover"]["name"], PATHINFO_EXTENSION));

            // Validate image
            $check = getimagesize($_FILES["cover"]["tmp_name"]);
            if ($check === false) throw new Exception("File is not an image.");
            if ($_FILES["cover"]["size"] > 5000000) throw new Exception("File too large (max 5MB).");
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) throw new Exception("Invalid image format.");

            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["cover"]["tmp_name"], $target_file)) {
                $cover_image = $new_filename;

                // Delete old image if different
                if ($current_cover && file_exists($target_dir . $current_cover) && $current_cover !== $new_filename) {
                    unlink($target_dir . $current_cover);
                }
            } else {
                throw new Exception("Image upload failed.");
            }
        } else {
            $cover_image = $current_cover;
        }

        // Update query
        $query = "UPDATE books SET title = ?, author = ?, category = ?, description = ?";
        $types = "ssss";
        $params = [$title, $author, $category, $description];

        if (!empty($cover_image)) {
            $query .= ", cover_image = ?";
            $types .= "s";
            $params[] = $cover_image;
        }

        $query .= " WHERE id = ?";
        $types .= "i";
        $params[] = $book_id;

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            header("Location: AdminBookEdit.php?success=1&id=" . $book_id);
            exit();
        } else {
            throw new Exception("Error updating book.");
        }

    } catch (Exception $e) {
        header("Location: AdminBookEdit.php?error=" . urlencode($e->getMessage()) . "&id=" . $book_id);
        exit();
    }
} else {
    header("Location: AdminBookEdit.php");
    exit();
}
