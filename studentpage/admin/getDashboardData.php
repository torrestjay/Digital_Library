<?php
include "../dbcon.php";

header('Content-Type: application/json');

try {
    // Get books statistics
    $booksQuery = "SELECT 
                    SUM(CASE WHEN availability > 0 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN availability = 0 THEN 1 ELSE 0 END) as borrowed,
                    COUNT(*) as total
                  FROM books";
    $booksResult = $conn->query($booksQuery);
    $booksData = $booksResult->fetch_assoc();

    // Get borrowing statistics
    $borrowingQuery = "SELECT 
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
                        SUM(CASE WHEN status = 'borrowed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
                      FROM borrowed_books";
    $borrowingResult = $conn->query($borrowingQuery);
    $borrowingData = $borrowingResult->fetch_assoc();

    // Get monthly activity
    $monthlyActivity = [
        'borrowed' => array_fill(0, 12, 0),
        'returned' => array_fill(0, 12, 0)
    ];
    
    $activityQuery = "SELECT 
                        MONTH(borrow_date) as month, 
                        COUNT(*) as count,
                        'borrowed' as type
                      FROM borrowed_books
                      WHERE YEAR(borrow_date) = YEAR(CURDATE())
                      GROUP BY MONTH(borrow_date)
                      
                      UNION ALL
                      
                      SELECT 
                        MONTH(return_date) as month, 
                        COUNT(*) as count,
                        'returned' as type
                      FROM borrowed_books
                      WHERE YEAR(return_date) = YEAR(CURDATE()) 
                      AND return_date IS NOT NULL
                      GROUP BY MONTH(return_date)";
    
    $activityResult = $conn->query($activityQuery);
    while ($row = $activityResult->fetch_assoc()) {
        $monthIndex = $row['month'] - 1;
        if ($row['type'] === 'borrowed') {
            $monthlyActivity['borrowed'][$monthIndex] = (int)$row['count'];
        } else {
            $monthlyActivity['returned'][$monthIndex] = (int)$row['count'];
        }
    }

    // Get top users
    $topUsersQuery = "SELECT 
                        u.id, u.fullname, 
                        COUNT(bb.id) as booksBorrowed
                      FROM users u
                      LEFT JOIN borrowed_books bb ON u.id = bb.user_id
                      WHERE u.role = 'student'
                      GROUP BY u.id
                      ORDER BY booksBorrowed DESC
                      LIMIT 6";
    $topUsersResult = $conn->query($topUsersQuery);
    $topUsers = [];
    while ($row = $topUsersResult->fetch_assoc()) {
        $topUsers[] = $row;
    }

    echo json_encode([
        'success' => true,
        'booksData' => [
            'available' => (int)$booksData['available'],
            'borrowed' => (int)$booksData['borrowed'],
            'reserved' => 0 // Add if you have reserved status
        ],
        'totalBooks' => (int)$booksData['total'],
        'availableBooks' => (int)$booksData['available'],
        'borrowingData' => $borrowingData,
        'pendingRequests' => (int)$borrowingData['pending'],
        'overdueReturns' => (int)$borrowingData['overdue'],
        'monthlyActivity' => $monthlyActivity,
        'topUsers' => $topUsers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>