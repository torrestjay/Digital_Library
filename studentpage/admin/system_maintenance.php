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

if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'librarian')) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$fixed_count = 0;

// Handle the fix request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_availability') {
        // Update all books with NULL or 0 availability to 1
        $update_query = "UPDATE books SET availability = 1 WHERE availability IS NULL OR availability = 0";
        
        if ($conn->query($update_query)) {
            $message = "✓ Successfully updated all books to have availability = 1";
            // Get the count of updated books
            $check_query = "SELECT COUNT(*) as total FROM books WHERE availability > 0";
            $result = $conn->query($check_query);
            $data = $result->fetch_assoc();
            $fixed_count = $data['total'];
        } else {
            $message = "✗ Error: " . $conn->error;
        }
    }
}

// Get current statistics
$total_books = 0;
$available_books = 0;
$borrowed_books = 0;
$pending_requests = 0;

$queries = [
    'total' => "SELECT COUNT(*) as count FROM books",
    'available' => "SELECT COUNT(*) as count FROM books WHERE availability > 0",
    'borrowed' => "SELECT COUNT(*) as count FROM books WHERE availability = 0",
    'pending' => "SELECT COUNT(*) as count FROM borrowed_books WHERE status = 'pending'"
];

foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $data = $result->fetch_assoc();
        ${$key . '_books'} = $data['count'];
    }
}

// Get pending requests
$pending_query = "SELECT bb.*, b.title, u.fullname, u.email FROM borrowed_books bb 
                  LEFT JOIN books b ON bb.book_id = b.id 
                  LEFT JOIN users u ON bb.user_id = u.id 
                  WHERE bb.status = 'pending'
                  ORDER BY bb.borrow_date DESC
                  LIMIT 20";
$pending_result = $conn->query($pending_query);
$pending_list = [];
if ($pending_result) {
    while ($row = $pending_result->fetch_assoc()) {
        $pending_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>System Maintenance - Digital Library</title>
    <link rel="stylesheet" href="../css/admin-design-system.css" />
    <link rel="stylesheet" href="../css/admin-utilities.css" />
    <style>
        .maintenance-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box.available {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .stat-box.borrowed {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        }
        
        .stat-box.pending {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        button:hover {
            background-color: #1976D2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-btn {
            background-color: #4CAF50;
            padding: 6px 12px;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .action-btn:hover {
            background-color: #45a049;
        }
        
        .action-btn.reject {
            background-color: #f44336;
        }
        
        .action-btn.reject:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <h1>📚 System Maintenance & Diagnostics</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✓') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Card -->
        <div class="card">
            <h2>📊 Current Statistics</h2>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Total Books</div>
                    <div class="stat-value"><?php echo $total_books; ?></div>
                </div>
                <div class="stat-box available">
                    <div class="stat-label">Available Books</div>
                    <div class="stat-value"><?php echo $available_books; ?></div>
                </div>
                <div class="stat-box borrowed">
                    <div class="stat-label">Borrowed Books</div>
                    <div class="stat-value"><?php echo $borrowed_books; ?></div>
                </div>
                <div class="stat-box pending">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value"><?php echo $pending_requests; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Fix Availability Card -->
        <div class="card">
            <h2>🔧 Fix Book Availability</h2>
            <p>If most books show as "unavailable" to users, run this fix to set all books to availability = 1.</p>
            <p><strong>Current Status:</strong> <?php echo $available_books; ?> available, <?php echo $total_books - $available_books; ?> unavailable</p>
            
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="fix_availability">
                <button type="submit" onclick="return confirm('This will set all books to availability = 1. Continue?')">
                    ✓ Fix Book Availability
                </button>
            </form>
        </div>
        
        <!-- Pending Requests Card -->
        <div class="card">
            <h2>📋 Pending Borrow Requests (<?php echo $pending_requests; ?>)</h2>
            
            <?php if (count($pending_list) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Book</th>
                            <th>Requested Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_list as $request): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($request['fullname'] ?? 'Unknown'); ?>
                                    <br><small><?php echo htmlspecialchars($request['email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['title'] ?? 'Unknown Book'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['borrow_date'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="action-btn" onclick="return confirm('Approve this request?')">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-btn reject" onclick="return confirm('Reject this request?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No pending requests</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="admindashboard.php" style="color: #2196F3; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
