<?php
include('../dbcon.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
  $id = $_POST['id'];
  $title = $_POST['title'];
  $author = $_POST['author'];
  $category = $_POST['category'];
  $description = $_POST['description'];

  // Optional cover image
  if (!empty($_FILES['cover_image']['name'])) {
    $cover_image = $_FILES['cover_image']['name'];
    $temp = $_FILES['cover_image']['tmp_name'];
    move_uploaded_file($temp, "../Images/" . $cover_image);

    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, description=?, cover_image=? WHERE id=?");
    $stmt->bind_param("sssssi", $title, $author, $category, $description, $cover_image, $id);
  } else {
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, description=? WHERE id=?");
    $stmt->bind_param("ssssi", $title, $author, $category, $description, $id);
  }

  if ($stmt->execute()) {
    header("Location: AdminBookEdit.php");
  } else {
    echo "Error updating book.";
  }
}
?>
