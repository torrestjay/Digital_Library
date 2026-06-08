<?php

function parse_due_date(?string $inputDueDate, int $maxDays = 7): ?string
{
    $today = new DateTime('today');
    $maxDate = (clone $today)->modify('+' . $maxDays . ' days');

    if ($inputDueDate === null || trim($inputDueDate) === '') {
        return $maxDate->format('Y-m-d');
    }

    $due = DateTime::createFromFormat('Y-m-d', trim($inputDueDate));
    if (!$due) {
        return null;
    }

    $dueText = $due->format('Y-m-d');
    if ($dueText < $today->format('Y-m-d') || $dueText > $maxDate->format('Y-m-d')) {
        return null;
    }

    return $dueText;
}

function has_open_borrow(mysqli $conn, int $userId, int $bookId): bool
{
    $stmt = $conn->prepare(
        "SELECT id FROM borrowed_books WHERE user_id = ? AND book_id = ? AND return_date IS NULL AND status IN ('pending', 'borrowed') LIMIT 1"
    );
    if (!$stmt) {
        return true;
    }

    $stmt->bind_param('ii', $userId, $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function get_active_borrow_record(mysqli $conn, int $userId, int $bookId): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, borrow_date, due_date, return_date, status
         FROM borrowed_books
         WHERE user_id = ? AND book_id = ? AND status = 'borrowed' AND return_date IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $userId, $bookId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function create_borrow_record(mysqli $conn, int $userId, int $bookId, ?string $dueDate, ?string &$message = null): bool
{
    $message = '';

    if ($userId <= 0 || $bookId <= 0) {
        $message = 'Invalid borrow request.';
        return false;
    }

    $dueDate = trim((string)$dueDate);
    if ($dueDate === '') {
        $message = 'A valid return date is required.';
        return false;
    }

    $conn->begin_transaction();
    try {
        $checkBook = $conn->prepare('SELECT availability FROM books WHERE id = ? AND archived_at IS NULL FOR UPDATE');
        if (!$checkBook) {
            throw new RuntimeException('Unable to check book availability.');
        }

        $checkBook->bind_param('i', $bookId);
        $checkBook->execute();
        $bookRow = $checkBook->get_result()->fetch_assoc();
        $checkBook->close();

        if (!$bookRow) {
            throw new RuntimeException('Book not found.');
        }

        if ((int)$bookRow['availability'] <= 0) {
            throw new RuntimeException('Book is currently unavailable.');
        }

        $existingBorrow = $conn->prepare(
            "SELECT id FROM borrowed_books WHERE user_id = ? AND book_id = ? AND return_date IS NULL AND status IN ('pending', 'borrowed') LIMIT 1 FOR UPDATE"
        );
        if (!$existingBorrow) {
            throw new RuntimeException('Unable to verify existing borrow requests.');
        }

        $existingBorrow->bind_param('ii', $userId, $bookId);
        $existingBorrow->execute();
        $hasExistingBorrow = $existingBorrow->get_result()->num_rows > 0;
        $existingBorrow->close();

        if ($hasExistingBorrow) {
            throw new RuntimeException('You already have an active borrow request for this book.');
        }

        $borrowDate = date('Y-m-d');
        $status = 'pending';  // Changed from 'borrowed' to 'pending' - requires admin approval

        $insert = $conn->prepare(
            'INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, return_date, status) VALUES (?, ?, ?, ?, NULL, ?)'
        );
        if (!$insert) {
            throw new RuntimeException('Unable to create borrow request.');
        }

        $insert->bind_param('iisss', $userId, $bookId, $borrowDate, $dueDate, $status);
        $insert->execute();
        $insert->close();

        // Do NOT decrease availability here - wait for admin approval
        // Availability will be decreased when admin approves the request

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        $message = $e->getMessage();
        return false;
    }
}

function request_extension(mysqli $conn, int $borrowId, int $userId, string &$message): bool
{
    $message = '';

    $stmt = $conn->prepare(
        "UPDATE borrowed_books
         SET due_date = DATE_ADD(due_date, INTERVAL 3 DAY)
         WHERE id = ?
           AND user_id = ?
           AND status = 'borrowed'
           AND return_date IS NULL
           AND DATEDIFF(due_date, CURDATE()) BETWEEN 0 AND 2"
    );

    if (!$stmt) {
        $message = 'Unable to request extension right now.';
        return false;
    }

    $stmt->bind_param('ii', $borrowId, $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        $message = 'Extension request is only available when due date is within 2 days.';
        return false;
    }

    return true;
}
