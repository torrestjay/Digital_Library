<?php
session_start();
include('../dbcon.php');
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}
$user_id = (int)$_SESSION['user_id'];
$borrowed_books = [];
$borrowed_stmt = $conn->prepare('SELECT book_id FROM borrowed_books WHERE user_id = ? AND return_date IS NULL');
if ($borrowed_stmt) {
    $borrowed_stmt->bind_param('i', $user_id);
    $borrowed_stmt->execute();
    $borrowed_result = $borrowed_stmt->get_result();
    while ($row = $borrowed_result->fetch_assoc()) {
        $borrowed_books[] = (int)$row['book_id'];
    }
    $borrowed_stmt->close();
}
$genres = ['Fantasy', 'Fiction', 'Literary Fiction', 'Romance', 'Children', 'Health', 'Self-help', 'Motivational'];
$book_stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image FROM books WHERE category = ? ORDER BY title ASC');
function cover_src($cover_image) {
    $clean = trim((string)$cover_image);
    if ($clean === '') {
        return '../Images/logo.png';
    }
    return '../Images/' . rawurlencode($clean);
}
function book_rating($book_id) {
    $seed = ($book_id * 37) % 13;
    return 3.8 + ($seed / 10);
}
function rating_stars($rating) {
    $filled = max(0, min(5, (int)round($rating)));
    $stars = '';
    for ($index = 1; $index <= 5; $index++) {
        $stars .= '<span class="star ' . ($index <= $filled ? 'filled' : '') . '">★</span>';
    }
    return $stars;
}
function book_description($book) {
    $description = trim((string)($book['description'] ?? ''));
    if ($description === '') {
        return 'No description available for this title yet.';
    }
    return htmlspecialchars(mb_strimwidth($description, 0, 140, '...'), ENT_QUOTES, 'UTF-8');
}
function render_book_card($book, $borrowed_books) {
    $book_id = isset($book['id']) ? (int)$book['id'] : 0;
    $title_plain = trim((string)($book['title'] ?? 'Untitled Book'));
    $title = htmlspecialchars($title_plain, ENT_QUOTES, 'UTF-8');
    $cover = htmlspecialchars(cover_src($book['cover_image'] ?? ''), ENT_QUOTES, 'UTF-8');
    $is_borrowed = in_array($book_id, $borrowed_books, true);
    $title_json = json_encode($title_plain);
    $rating = book_rating($book_id);
    $rating_label = number_format($rating, 1);
    echo '<article class="book-card">';
    echo '<a class="cover-link" href="read.php?id=' . $book_id . '">';
    echo '<div class="book-cover-wrap">';
    echo '<img class="book-cover-img" src="' . $cover . '" alt="' . $title . '">';
    echo '</div>';
    echo '</a>';
    echo '<h4 class="book-title-text">' . $title . '</h4>';
    echo '<div class="book-rating" aria-label="Rated ' . $rating_label . ' out of 5">';
    echo '<span class="rating-stars">' . rating_stars($rating) . '</span>';
    echo '<span class="rating-value">' . $rating_label . '/5</span>';
    echo '</div>';
    echo '<div class="book-description">' . book_description($book) . '</div>';
    echo '<div class="book-actions">';
    echo '<a class="btn action-btn read-btn" href="read.php?id=' . $book_id . '">Read</a>';
    if ($is_borrowed) {
        echo '<button type="button" class="btn action-btn borrowed" disabled>Borrowed</button>';
    } else {
        echo '<button type="button" class="btn action-btn borrow" onclick="openBorrowForm(' . $title_json . ', ' . $book_id . ')">Borrow</button>';
    }
    echo '</div>';
    echo '</article>';
}
foreach ($genres as $index => $genre) {
    $genre_id = 'genre-track-default-' . $index;
    echo '<section class="book-category" data-category="' . htmlspecialchars($genre, ENT_QUOTES, 'UTF-8') . '">';
    echo '<h3>' . htmlspecialchars($genre, ENT_QUOTES, 'UTF-8') . '</h3>';
    if (!$book_stmt) {
        echo '<div class="empty-state">Unable to load this category right now.</div>';
        echo '</section>';
        continue;
    }
    $book_stmt->bind_param('s', $genre);
    $book_stmt->execute();
    $result = $book_stmt->get_result();
    $books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if (empty($books)) {
        echo '<div class="empty-state">No books available in this category right now.</div>';
    } else {
        echo '<div class="genre-carousel">';
        echo '<button class="genre-btn left" type="button" onclick="scrollGenre(\'' . $genre_id . '\', -1)" aria-label="Scroll left">&#10094;</button>';
        echo '<div class="genre-track" id="' . $genre_id . '">';
        foreach ($books as $book) {
            render_book_card($book, $borrowed_books);
        }
        echo '</div>';
        echo '<button class="genre-btn right" type="button" onclick="scrollGenre(\'' . $genre_id . '\', 1)" aria-label="Scroll right">&#10095;</button>';
        echo '</div>';
    }
    echo '</section>';
}
if ($book_stmt) {
    $book_stmt->close();
}
?>
