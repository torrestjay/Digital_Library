<?php
session_start();
include("../dbcon.php");
include('security_utils.php');

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

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = (int)($_POST['request_id'] ?? 0);
    
    if ($action === 'approve' && $request_id > 0) {
        // Get the request details
        $req_query = "SELECT * FROM borrowed_books WHERE id = ?";
        $stmt = $conn->prepare($req_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $req_result = $stmt->get_result();
        $request = $req_result->fetch_assoc();
        $stmt->close();
        
        if ($request) {
            // Approve the request - set status to 'borrowed'
            $update_query = "UPDATE borrowed_books SET status = 'borrowed', borrow_date = CURDATE(), due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY) WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                // Update book availability
                $book_update = "UPDATE books SET availability = GREATEST(0, availability - 1) WHERE id = ?";
                $stmt2 = $conn->prepare($book_update);
                $stmt2->bind_param("i", $request['book_id']);
                $stmt2->execute();
                $stmt2->close();
                
                // Log the action
                $new_data = json_encode(['status' => 'borrowed', 'user_id' => $request['user_id'], 'book_id' => $request['book_id']]);
                logAdminAction($conn, 'Approve Borrow', 'borrow-approve', 'borrow', $request_id, "Approved borrow request #{$request_id}", null, $new_data);
            }
            $stmt->close();
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } elseif ($action === 'reject' && $request_id > 0) {
        // Reject the request - delete it
        $req_query = "SELECT * FROM borrowed_books WHERE id = ?";
        $stmt = $conn->prepare($req_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $req_result = $stmt->get_result();
        $request = $req_result->fetch_assoc();
        $stmt->close();
        
        if ($request) {
            $delete_query = "DELETE FROM borrowed_books WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                // Log the action
                $old_data = json_encode(['status' => 'pending', 'user_id' => $request['user_id'], 'book_id' => $request['book_id']]);
                logAdminAction($conn, 'Reject Borrow', 'borrow-reject', 'borrow', $request_id, "Rejected borrow request #{$request_id}", $old_data, null);
            }
            $stmt->close();
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    }
}

// Get pending requests with detailed information
$pending_query = "SELECT bb.id, bb.user_id, bb.book_id, bb.status, bb.borrow_date, 
                         b.title as book_title, b.author, b.cover_image,
                         u.fullname, u.email
                  FROM borrowed_books bb
                  LEFT JOIN books b ON bb.book_id = b.id
                  LEFT JOIN users u ON bb.user_id = u.id
                  WHERE bb.status = 'pending'
                  ORDER BY bb.borrow_date DESC";

$pending_result = $conn->query($pending_query);
$pending_requests = [];
if ($pending_result) {
    while ($row = $pending_result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

// Get approved/borrowed requests
$borrowed_query = "SELECT bb.id, bb.user_id, bb.book_id, bb.status, bb.borrow_date, bb.due_date,
                          b.title as book_title, b.author,
                          u.fullname, u.email
                   FROM borrowed_books bb
                   LEFT JOIN books b ON bb.book_id = b.id
                   LEFT JOIN users u ON bb.user_id = u.id
                   WHERE bb.status = 'borrowed'
                   ORDER BY bb.due_date ASC
                   LIMIT 20";

$borrowed_result = $conn->query($borrowed_query);
$borrowed_requests = [];
if ($borrowed_result) {
    while ($row = $borrowed_result->fetch_assoc()) {
        $borrowed_requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="../Images/logo.png" type="image/png">
    <title>Borrow Requests - Digital Library</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-design-system.css" />
    <link rel="stylesheet" href="../css/admin-utilities.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
    
    <style>
        .content-section {
            padding: var(--space-24);
            overflow-y: auto;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-32);
            gap: var(--space-16);
            flex-wrap: wrap;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: var(--font-size-2xl);
            color: var(--color-text-primary);
        }
        
        .tabs {
            display: flex;
            gap: var(--space-12);
            margin-bottom: var(--space-24);
            border-bottom: 2px solid var(--color-border);
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: var(--space-12) var(--space-16);
            cursor: pointer;
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-600);
            color: var(--color-text-secondary);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all var(--transition-base) ease;
        }
        
        .tab-btn.active {
            color: var(--color-primary-light);
            border-bottom-color: var(--color-primary-light);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .request-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--space-20);
            margin-bottom: var(--space-16);
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: var(--space-20);
            align-items: center;
            transition: all var(--transition-base) ease;
        }
        
        .request-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius-md);
            flex-shrink: 0;
        }
        
        .request-info {
            flex: 1;
        }
        
        .request-info h3 {
            margin: 0 0 var(--space-8) 0;
            color: var(--color-text-primary);
        }
        
        .request-info p {
            margin: var(--space-4) 0;
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }
        
        .request-actions {
            display: flex;
            gap: var(--space-12);
            flex-shrink: 0;
        }
        
        .btn-small {
            padding: var(--space-8) var(--space-16);
            font-size: var(--font-size-xs);
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            font-weight: var(--font-weight-600);
            transition: all var(--transition-base) ease;
        }
        
        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #45a049;
        }
        
        .btn-reject {
            background-color: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #da190b;
        }
        
        .empty-state {
            text-align: center;
            padding: var(--space-40);
            color: var(--color-text-secondary);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: var(--space-16);
            opacity: 0.5;
        }
        
        .badge {
            display: inline-block;
            padding: var(--space-4) var(--space-12);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-600);
        }
        
        .badge.pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .badge.borrowed {
            background-color: #D1ECF1;
            color: #0C5460;
        }
    </style>
</head>
<body>
    <script src="includes/sidebar-behavior.js"></script>
    
    <div class="container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <section class="content-section">
                <div class="page-header">
                    <h1><i class="fas fa-request"></i> Borrow Request Management</h1>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Updated',
                            text: 'The borrow request has been processed.',
                            confirmButtonColor: 'var(--color-primary-light)',
                            timer: 2000
                        });
                    </script>
                <?php endif; ?>
                
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('pending')">
                        <i class="fas fa-clock"></i> Pending (<?php echo count($pending_requests); ?>)
                    </button>
                    <button class="tab-btn" onclick="switchTab('borrowed')">
                        <i class="fas fa-book"></i> Borrowed (<?php echo count($borrowed_requests); ?>)
                    </button>
                </div>
                
                <!-- Pending Requests Tab -->
                <div id="pending" class="tab-content active">
                    <?php if (count($pending_requests) > 0): ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card">
                                <img src="../Images/<?php echo htmlspecialchars($request['cover_image'] ?? 'default.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($request['book_title']); ?>"
                                     class="book-cover">
                                
                                <div class="request-info">
                                    <h3><?php echo htmlspecialchars($request['book_title']); ?></h3>
                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($request['author'] ?? 'Unknown'); ?></p>
                                    <p><strong>Requested by:</strong> <?php echo htmlspecialchars($request['fullname']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                                    <p><strong>Request Date:</strong> <?php echo date('M d, Y h:i A', strtotime($request['borrow_date'])); ?></p>
                                    <span class="badge pending">Pending</span>
                                </div>
                                
                                <div class="request-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn-small btn-approve" title="Approve request">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn-small btn-reject" onclick="return confirm('Reject this request?')" title="Reject request">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                            <p>No pending borrow requests</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Borrowed Books Tab -->
                <div id="borrowed" class="tab-content">
                    <?php if (count($borrowed_requests) > 0): ?>
                        <?php foreach ($borrowed_requests as $borrow): ?>
                            <div class="request-card">
                                <img src="../Images/<?php echo htmlspecialchars($borrow['cover_image'] ?? 'default.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($borrow['book_title']); ?>"
                                     class="book-cover">
                                
                                <div class="request-info">
                                    <h3><?php echo htmlspecialchars($borrow['book_title']); ?></h3>
                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($borrow['author'] ?? 'Unknown'); ?></p>
                                    <p><strong>Borrowed by:</strong> <?php echo htmlspecialchars($borrow['fullname']); ?></p>
                                    <p><strong>Borrow Date:</strong> <?php echo date('M d, Y', strtotime($borrow['borrow_date'])); ?></p>
                                    <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($borrow['due_date'])); ?></p>
                                    <span class="badge borrowed">Borrowed</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-book"></i></div>
                            <p>No active borrows</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
