<?php
session_start();
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($book_id > 0) {
  header('Location: read.php?id=' . $book_id);
} else {
  header('Location: borrowed-books.php');
}
exit();
