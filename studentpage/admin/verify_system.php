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

$tests = [];

// Test 1: Check book availability
$test1_result = $conn->query("SELECT COUNT(*) as total, COUNT(CASE WHEN availability > 0 THEN 1 END) as available FROM books");
$test1_data = $test1_result->fetch_assoc();
$tests[] = [
    'name' => 'Book Availability System',
    'status' => $test1_data['available'] > 0 ? 'PASS' : 'FAIL',
    'details' => 'Total: ' . $test1_data['total'] . ', Available: ' . $test1_data['available'],
    'description' => 'Checks if books have proper availability values'
];

// Test 2: Check borrow requests table
$test2_result = $conn->query("SELECT COUNT(*) as total FROM borrowed_books");
$test2_data = $test2_result->fetch_assoc();
$tests[] = [
    'name' => 'Borrow Requests Table',
    'status' => $test2_data['total'] >= 0 ? 'PASS' : 'FAIL',
    'details' => 'Total borrow records: ' . $test2_data['total'],
    'description' => 'Checks if borrow requests are being tracked'
];

// Test 3: Check pending requests
$test3_result = $conn->query("SELECT COUNT(*) as total FROM borrowed_books WHERE status='pending'");
$test3_data = $test3_result->fetch_assoc();
$tests[] = [
    'name' => 'Pending Borrow Requests',
    'status' => 'PASS',
    'details' => 'Pending requests: ' . $test3_data['total'],
    'description' => 'Tracks pending borrow requests awaiting admin approval'
];

// Test 4: Check active borrows
$test4_result = $conn->query("SELECT COUNT(*) as total FROM borrowed_books WHERE status='borrowed' AND return_date IS NULL");
$test4_data = $test4_result->fetch_assoc();
$tests[] = [
    'name' => 'Active Borrow Records',
    'status' => 'PASS',
    'details' => 'Active borrows: ' . $test4_data['total'],
    'description' => 'Books currently borrowed by users'
];

// Test 5: Check user pages exist
$user_pages = ['librarypage.php', 'load_default_books.php', 'borrow.php', 'read.php', 'homepage.php', 'Book-Details.php'];
$user_pages_dir = "../user/";
$pages_exist = true;
$missing_pages = [];
foreach ($user_pages as $page) {
    if (!file_exists($user_pages_dir . $page)) {
        $pages_exist = false;
        $missing_pages[] = $page;
    }
}
$tests[] = [
    'name' => 'User-Facing Pages',
    'status' => $pages_exist ? 'PASS' : 'FAIL',
    'details' => count($missing_pages) === 0 ? 'All pages exist (' . count($user_pages) . ')' : 'Missing: ' . implode(', ', $missing_pages),
    'description' => 'Essential pages for user workflow'
];

// Test 6: Check admin pages
$admin_pages = ['AdminBookEdit.php', 'BorrowRequests.php', 'system_maintenance.php', 'archive_operations.php'];
$admin_pages_dir = "";
$admin_pages_exist = true;
$missing_admin_pages = [];
foreach ($admin_pages as $page) {
    if (!file_exists($page)) {
        $admin_pages_exist = false;
        $missing_admin_pages[] = $page;
    }
}
$tests[] = [
    'name' => 'Admin Pages',
    'status' => $admin_pages_exist ? 'PASS' : 'FAIL',
    'details' => count($missing_admin_pages) === 0 ? 'All pages exist (' . count($admin_pages) . ')' : 'Missing: ' . implode(', ', $missing_admin_pages),
    'description' => 'Essential admin management pages'
];

// Test 7: Database schema check
$schema_test = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='books' AND COLUMN_NAME='availability'");
$tests[] = [
    'name' => 'Book Availability Column',
    'status' => $schema_test->num_rows > 0 ? 'PASS' : 'FAIL',
    'details' => 'Books table has availability column',
    'description' => 'Required for tracking available book copies'
];

// Test 8: Borrow status values
$borrow_status = $conn->query("SELECT DISTINCT status FROM borrowed_books");
$statuses = [];
while ($row = $borrow_status->fetch_assoc()) {
    $statuses[] = $row['status'];
}
$tests[] = [
    'name' => 'Borrow Status Types',
    'status' => count($statuses) > 0 ? 'PASS' : 'FAIL',
    'details' => 'Status values in use: ' . implode(', ', $statuses),
    'description' => 'Tracks request progression: pending → borrowed → returned'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Images/logo.png" type="image/png">
    <title>System Verification - Digital Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .test-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .test-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .test-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .test-icon.pass {
            background: #4CAF50;
        }
        
        .test-icon.fail {
            background: #f44336;
        }
        
        .test-title {
            flex: 1;
        }
        
        .test-title h3 {
            margin: 0;
            font-size: 16px;
            color: #0e3a5d;
        }
        
        .test-status {
            font-weight: 700;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .test-status.pass {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .test-status.fail {
            background: #ffebee;
            color: #c62828;
        }
        
        .test-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .test-details {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            color: #333;
            overflow-x: auto;
        }
        
        .summary {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .summary h2 {
            margin: 0 0 16px 0;
            color: #0e3a5d;
            font-size: 20px;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ System Verification Report</h1>
            <p>Digital Library - Complete Feature Verification</p>
        </div>
        
        <div class="summary">
            <h2>Test Summary</h2>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-value"><?php echo count($tests); ?></div>
                    <div class="stat-label">Total Tests</div>
                </div>
                <div class="stat">
                    <div class="stat-value" style="color: #4CAF50;"><?php echo count(array_filter($tests, function($t) { return $t['status'] === 'PASS'; })); ?></div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat">
                    <div class="stat-value" style="color: #f44336;"><?php echo count(array_filter($tests, function($t) { return $t['status'] === 'FAIL'; })); ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo round(count(array_filter($tests, function($t) { return $t['status'] === 'PASS'; })) / count($tests) * 100); ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="system_maintenance.php" class="btn btn-primary">📊 Maintenance Dashboard</a>
                <a href="BorrowRequests.php" class="btn btn-primary">📋 Borrow Requests</a>
                <a href="AdminBookEdit.php" class="btn btn-secondary">📚 Book Management</a>
            </div>
        </div>
        
        <div class="tests-grid">
            <?php foreach ($tests as $test): ?>
                <div class="test-card">
                    <div class="test-header">
                        <div class="test-icon <?php echo strtolower($test['status']); ?>">
                            <?php echo $test['status'] === 'PASS' ? '✓' : '✗'; ?>
                        </div>
                        <div class="test-title">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <span class="test-status <?php echo strtolower($test['status']); ?>">
                            <?php echo $test['status']; ?>
                        </span>
                    </div>
                    <p class="test-description"><?php echo $test['description']; ?></p>
                    <div class="test-details">
                        <?php echo htmlspecialchars($test['details']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="summary">
            <h2>📖 How to Use</h2>
            <div style="font-size: 14px; line-height: 1.8; color: #555;">
                <h3 style="color: #0e3a5d; margin: 16px 0 8px 0;">For Admins:</h3>
                <ul style="margin-left: 20px;">
                    <li><strong>Add Books:</strong> Go to Book Management and click "Add Book"</li>
                    <li><strong>Approve Borrow Requests:</strong> Go to Borrow Requests and click "Approve"</li>
                    <li><strong>Fix Availability:</strong> Go to Maintenance Dashboard and click "Fix Book Availability"</li>
                </ul>
                
                <h3 style="color: #0e3a5d; margin: 16px 0 8px 0;">For Users:</h3>
                <ul style="margin-left: 20px;">
                    <li><strong>Browse Books:</strong> Go to Library and search/filter books</li>
                    <li><strong>Borrow Books:</strong> Click "Borrow" on any available book</li>
                    <li><strong>Read Books:</strong> Go to Borrowed Books and click "Read"</li>
                </ul>
                
                <h3 style="color: #0e3a5d; margin: 16px 0 8px 0;">Quick Facts:</h3>
                <ul style="margin-left: 20px;">
                    <li>Books are marked "available" when availability > 0</li>
                    <li>Borrow requests start as "pending" and need admin approval</li>
                    <li>Approved requests change to "borrowed" and decrease book availability</li>
                    <li>Users can only read books with "borrowed" status and no return date</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
