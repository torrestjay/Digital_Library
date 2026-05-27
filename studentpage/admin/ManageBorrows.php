<?php
session_start();
include("../dbcon.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || $user['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle approval/rejection
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = (int)($_POST['request_id'] ?? 0);
    
    if ($action === 'approve' && $request_id > 0) {
        // Get the request details
        $req_query = "SELECT bb.id, bb.user_id, bb.book_id, b.title FROM borrowed_books bb 
                     JOIN books b ON bb.book_id = b.id WHERE bb.id = ?";
        $stmt = $conn->prepare($req_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $req_result = $stmt->get_result();
        $request = $req_result->fetch_assoc();
        $stmt->close();
        
        if ($request) {
            // Update status to borrowed
            $update_query = "UPDATE borrowed_books SET status = 'borrowed' WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
            
            // Decrease book availability
            $book_update = "UPDATE books SET availability = GREATEST(1, availability - 1) WHERE id = ?";
            $stmt = $conn->prepare($book_update);
            $stmt->bind_param("i", $request['book_id']);
            $stmt->execute();
            $stmt->close();
            
            $message = "✓ Approved borrow request for: " . htmlspecialchars($request['title']);
        }
    } elseif ($action === 'reject' && $request_id > 0) {
        // Delete the request
        $delete_query = "DELETE FROM borrowed_books WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
        
        $message = "✓ Rejected borrow request";
    }
}

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM books WHERE availability > 0) as available,
    (SELECT COUNT(*) FROM books WHERE availability <= 0) as unavailable,
    (SELECT COUNT(*) FROM borrowed_books WHERE status='pending') as pending,
    (SELECT COUNT(*) FROM borrowed_books WHERE status='borrowed') as active";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get pending requests
$pending_query = "SELECT bb.id, bb.user_id, bb.book_id, bb.borrow_date,
                         b.title, b.author, b.cover_image,
                         u.fullname, u.email
                  FROM borrowed_books bb
                  JOIN books b ON bb.book_id = b.id
                  JOIN users u ON bb.user_id = u.id
                  WHERE bb.status = 'pending'
                  ORDER BY bb.borrow_date ASC";
$pending_result = $conn->query($pending_query);
$pending_requests = [];
while ($row = $pending_result->fetch_assoc()) {
    $pending_requests[] = $row;
}

// Get active borrows
$active_query = "SELECT bb.id, bb.user_id, bb.book_id, bb.borrow_date, bb.due_date,
                        b.title, b.availability,
                        u.fullname
                 FROM borrowed_books bb
                 JOIN books b ON bb.book_id = b.id
                 JOIN users u ON bb.user_id = u.id
                 WHERE bb.status = 'borrowed' AND bb.return_date IS NULL
                 ORDER BY bb.due_date ASC
                 LIMIT 10";
$active_result = $conn->query($active_query);
$active_borrows = [];
while ($row = $active_result->fetch_assoc()) {
    $active_borrows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Images/logo.png" type="image/png">
    <title>Manage Borrows - Digital Library</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://kit.fontawesome.com/3b07bc6295.js">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        h1 {
            color: #0e3a5d;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #7a8e9f;
            font-size: 14px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
            background: #f1f8f4;
            color: #2e7d32;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1b678f;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 13px;
            color: #7a8e9f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section h2 {
            color: #0e3a5d;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #f0f4f8;
            padding-bottom: 15px;
        }
        
        .request-item {
            background: #f9fbfd;
            border: 1px solid #e8eef7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .request-item:hover {
            border-color: #1b678f;
            background: #f5f9fc;
            box-shadow: 0 4px 12px rgba(27, 103, 143, 0.1);
        }
        
        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
            background: #e8eef7;
        }
        
        .request-info {
            flex: 1;
        }
        
        .request-info h3 {
            margin: 0 0 8px 0;
            color: #0e3a5d;
            font-size: 16px;
        }
        
        .request-meta {
            font-size: 13px;
            color: #7a8e9f;
            margin: 4px 0;
            line-height: 1.5;
        }
        
        .user-info {
            background: #f0f4f8;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 12px;
        }
        
        .user-info strong {
            color: #0e3a5d;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-approve {
            background: #4CAF50;
            color: white;
        }
        
        .btn-approve:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7a8e9f;
        }
        
        .empty-icon {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th {
            background: #f0f4f8;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #0e3a5d;
            font-size: 12px;
            border-bottom: 2px solid #e8eef7;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e8eef7;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f9fbfd;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-borrowed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1b678f;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        
        .back-link:hover {
            color: #0e3a5d;
        }
        
        @media (max-width: 768px) {
            .request-item {
                flex-direction: column;
                text-align: center;
            }
            
            .actions {
                justify-content: center;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admindashboard.php" class="back-link">← Back to Dashboard</a>
        
        <header>
            <h1><i class="fas fa-tasks"></i> Manage Book Borrows</h1>
            <p class="subtitle">Approve/reject borrow requests and monitor active loans</p>
        </header>
        
        <?php if ($message): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-book" style="font-size: 24px; color: #4CAF50;"></i>
                <div class="stat-value"><?php echo $stats['available']; ?></div>
                <div class="stat-label">Books Available</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-lock" style="font-size: 24px; color: #f44336;"></i>
                <div class="stat-value"><?php echo $stats['unavailable']; ?></div>
                <div class="stat-label">Books Unavailable</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 24px; color: #ff9800;"></i>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 24px; color: #2196F3;"></i>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active Loans</div>
            </div>
        </div>
        
        <!-- Pending Requests Section -->
        <div class="section">
            <h2><i class="fas fa-hourglass-half"></i> Pending Requests (<?php echo count($pending_requests); ?>)</h2>
            
            <?php if (count($pending_requests) > 0): ?>
                <?php foreach ($pending_requests as $req): ?>
                    <div class="request-item">
                        <img src="<?php echo file_exists("../Images/" . $req['cover_image']) ? "../Images/" . htmlspecialchars($req['cover_image']) : "../Images/logo.png"; ?>" 
                             alt="Book cover" class="book-cover">
                        
                        <div class="request-info">
                            <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                            <div class="request-meta">
                                <strong>Author:</strong> <?php echo htmlspecialchars($req['author'] ?? 'Unknown'); ?>
                            </div>
                            <div class="request-meta">
                                <strong>Requested:</strong> <?php echo date('M d, Y h:i A', strtotime($req['borrow_date'])); ?>
                            </div>
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($req['fullname']); ?></strong>
                                (<?php echo htmlspecialchars($req['email']); ?>)
                            </div>
                        </div>
                        
                        <div class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this borrow request?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this request?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                    <p>No pending requests - All borrows are up to date!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Active Borrows Section -->
        <div class="section">
            <h2><i class="fas fa-book-reader"></i> Active Loans (<?php echo count($active_borrows); ?>)</h2>
            
            <?php if (count($active_borrows) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Borrowed By</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_borrows as $borrow): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($borrow['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($borrow['fullname']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($borrow['borrow_date'])); ?></td>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($borrow['due_date'])); ?></strong>
                                    <?php 
                                    $days_left = (strtotime($borrow['due_date']) - time()) / 86400;
                                    if ($days_left < 0) {
                                        echo '<span class="status-badge badge-overdue">OVERDUE</span>';
                                    } elseif ($days_left < 3) {
                                        echo '<span class="status-badge badge-pending">DUE SOON</span>';
                                    } else {
                                        echo '<span class="status-badge badge-borrowed">ACTIVE</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge badge-borrowed">Borrowed</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-book"></i></div>
                    <p>No active loans currently</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        <?php if ($message): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo addslashes($message); ?>',
            confirmButtonColor: '#1b678f',
            timer: 3000
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
