<?php
include "../dbcon.php";
header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/dashboard_errors.log');

// Initialize response
$response = [
    'success' => false,
    'data' => [],
    'error' => null,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Verify database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // ==========================================
    // 1. TOTAL ACTIVE BOOKS (only active records)
    // ==========================================
    $totalBooksQuery = "SELECT COUNT(*) as total FROM books WHERE status = 'active' OR status IS NULL";
    $totalBooksResult = $conn->query($totalBooksQuery);
    
    if (!$totalBooksResult) {
        throw new Exception("Query failed - Total Books: " . $conn->error);
    }
    
    $totalBooksData = $totalBooksResult->fetch_assoc();
    $totalBooks = (int)($totalBooksData['total'] ?? 0);

    // ==========================================
    // 2. AVAILABLE BOOKS (availability > 0)
    // ==========================================
    $availableBooksQuery = "SELECT COUNT(*) as available FROM books WHERE availability > 0 AND (status = 'active' OR status IS NULL)";
    $availableBooksResult = $conn->query($availableBooksQuery);
    
    if (!$availableBooksResult) {
        throw new Exception("Query failed - Available Books: " . $conn->error);
    }
    
    $availableBooksData = $availableBooksResult->fetch_assoc();
    $availableBooks = (int)($availableBooksData['available'] ?? 0);

    // ==========================================
    // 3. BORROWED BOOKS (availability = 0)
    // ==========================================
    $borrowedBooksQuery = "SELECT COUNT(*) as borrowed FROM books WHERE availability = 0 AND (status = 'active' OR status IS NULL)";
    $borrowedBooksResult = $conn->query($borrowedBooksQuery);
    
    if (!$borrowedBooksResult) {
        throw new Exception("Query failed - Borrowed Books: " . $conn->error);
    }
    
    $borrowedBooksData = $borrowedBooksResult->fetch_assoc();
    $borrowedBooks = (int)($borrowedBooksData['borrowed'] ?? 0);

    // ==========================================
    // 4. PENDING REQUESTS
    // ==========================================
    $pendingRequestsQuery = "SELECT COUNT(*) as pending FROM borrowed_books WHERE status = 'pending'";
    $pendingRequestsResult = $conn->query($pendingRequestsQuery);
    
    if (!$pendingRequestsResult) {
        throw new Exception("Query failed - Pending Requests: " . $conn->error);
    }
    
    $pendingRequestsData = $pendingRequestsResult->fetch_assoc();
    $pendingRequests = (int)($pendingRequestsData['pending'] ?? 0);

    // ==========================================
    // 5. OVERDUE BOOKS
    // ==========================================
    $overdueQuery = "SELECT COUNT(*) as overdue FROM borrowed_books 
                     WHERE status = 'borrowed' AND due_date < CURDATE()";
    $overdueResult = $conn->query($overdueQuery);
    
    if (!$overdueResult) {
        throw new Exception("Query failed - Overdue Books: " . $conn->error);
    }
    
    $overdueData = $overdueResult->fetch_assoc();
    $overdueBooks = (int)($overdueData['overdue'] ?? 0);

    // ==========================================
    // 6. TOTAL USERS (registered students)
    // ==========================================
    $totalUsersQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'student' OR role IS NULL";
    $totalUsersResult = $conn->query($totalUsersQuery);
    
    if (!$totalUsersResult) {
        throw new Exception("Query failed - Total Users: " . $conn->error);
    }
    
    $totalUsersData = $totalUsersResult->fetch_assoc();
    $totalUsers = (int)($totalUsersData['total'] ?? 0);

    // ==========================================
    // 7. BORROWING STATUS BREAKDOWN
    // ==========================================
    $borrowingStatusQuery = "SELECT 
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned
                            FROM borrowed_books";
    $borrowingStatusResult = $conn->query($borrowingStatusQuery);
    
    if (!$borrowingStatusResult) {
        throw new Exception("Query failed - Borrowing Status: " . $conn->error);
    }
    
    $borrowingStatusData = $borrowingStatusResult->fetch_assoc();
    $borrowingStatus = [
        'pending' => (int)($borrowingStatusData['pending'] ?? 0),
        'borrowed' => (int)($borrowingStatusData['borrowed'] ?? 0),
        'returned' => (int)($borrowingStatusData['returned'] ?? 0)
    ];

    // ==========================================
    // 8. MONTHLY ACTIVITY (Last 12 months)
    // ==========================================
    $monthlyActivityQuery = "SELECT 
                                MONTH(borrow_date) as month,
                                COUNT(*) as borrowed
                            FROM borrowed_books
                            WHERE YEAR(borrow_date) = YEAR(CURDATE())
                            GROUP BY MONTH(borrow_date)
                            ORDER BY month";
    $monthlyActivityResult = $conn->query($monthlyActivityQuery);
    
    if (!$monthlyActivityResult) {
        throw new Exception("Query failed - Monthly Activity: " . $conn->error);
    }
    
    $monthlyActivity = array_fill(0, 12, 0);
    while ($row = $monthlyActivityResult->fetch_assoc()) {
        $monthIndex = (int)$row['month'] - 1;
        $monthlyActivity[$monthIndex] = (int)$row['borrowed'];
    }

    // ==========================================
    // 9. RECENT ACTIVITY
    // ==========================================
    $recentActivityQuery = "SELECT 
                                bb.id,
                                bb.book_id,
                                bb.user_id,
                                b.title,
                                u.fullname,
                                bb.borrow_date,
                                bb.return_date,
                                bb.due_date,
                                bb.status
                            FROM borrowed_books bb
                            LEFT JOIN books b ON bb.book_id = b.id
                            LEFT JOIN users u ON bb.user_id = u.id
                            ORDER BY bb.borrow_date DESC
                            LIMIT 10";
    $recentActivityResult = $conn->query($recentActivityQuery);
    
    if (!$recentActivityResult) {
        throw new Exception("Query failed - Recent Activity: " . $conn->error);
    }
    
    $recentActivity = [];
    while ($row = $recentActivityResult->fetch_assoc()) {
        $recentActivity[] = $row;
    }

    // ==========================================
    // 10. TOP BORROWERS
    // ==========================================
    $topBorrowersQuery = "SELECT 
                            u.id,
                            u.fullname,
                            COUNT(bb.id) as borrowCount
                        FROM users u
                        LEFT JOIN borrowed_books bb ON u.id = bb.user_id
                        WHERE u.role = 'student' OR u.role IS NULL
                        GROUP BY u.id, u.fullname
                        ORDER BY borrowCount DESC
                        LIMIT 5";
    $topBorrowersResult = $conn->query($topBorrowersQuery);
    
    if (!$topBorrowersResult) {
        throw new Exception("Query failed - Top Borrowers: " . $conn->error);
    }
    
    $topBorrowers = [];
    while ($row = $topBorrowersResult->fetch_assoc()) {
        $topBorrowers[] = $row;
    }

    // ==========================================
    // BUILD SUCCESS RESPONSE
    // ==========================================
    $response['success'] = true;
    $response['data'] = [
        'totalBooks' => $totalBooks,
        'availableBooks' => $availableBooks,
        'borrowedBooks' => $borrowedBooks,
        'pendingRequests' => $pendingRequests,
        'overdueBooks' => $overdueBooks,
        'totalUsers' => $totalUsers,
        'borrowingStatus' => $borrowingStatus,
        'monthlyActivity' => $monthlyActivity,
        'recentActivity' => $recentActivity,
        'topBorrowers' => $topBorrowers
    ];

} catch (Exception $e) {
    // Log error
    error_log("Dashboard Error: " . $e->getMessage());
    
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    http_response_code(500);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Return JSON response
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
?>
