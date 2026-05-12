<?php
include '../dbcon.php';

if (isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    $sql = "UPDATE books SET views = views + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
?>
