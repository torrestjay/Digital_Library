<?php
session_start();
include('../dbcon.php');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$results = [];

try {
    // ============================================
    // 1. ADD COLUMNS TO USERS TABLE
    // ============================================
    
    // Check and add admin_role column
    $check_role = $conn->query("SHOW COLUMNS FROM users LIKE 'admin_role'");
    if ($check_role->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN admin_role VARCHAR(50) DEFAULT 'admin' AFTER role");
        $results[] = "✓ Added admin_role column to users table";
    } else {
        $results[] = "✓ admin_role column already exists";
    }
    
    // Check and add mfa_enabled column
    $check_mfa = $conn->query("SHOW COLUMNS FROM users LIKE 'mfa_enabled'");
    if ($check_mfa->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN mfa_enabled TINYINT(1) DEFAULT 0 AFTER admin_role");
        $results[] = "✓ Added mfa_enabled column to users table";
    } else {
        $results[] = "✓ mfa_enabled column already exists";
    }
    
    // Check and add mfa_secret column
    $check_mfa_secret = $conn->query("SHOW COLUMNS FROM users LIKE 'mfa_secret'");
    if ($check_mfa_secret->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN mfa_secret VARCHAR(255) NULL AFTER mfa_enabled");
        $results[] = "✓ Added mfa_secret column to users table";
    } else {
        $results[] = "✓ mfa_secret column already exists";
    }
    
    // Check and add last_login column
    $check_last_login = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($check_last_login->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER mfa_secret");
        $results[] = "✓ Added last_login column to users table";
    } else {
        $results[] = "✓ last_login column already exists";
    }
    
    // Check and add account_locked column
    $check_locked = $conn->query("SHOW COLUMNS FROM users LIKE 'account_locked'");
    if ($check_locked->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN account_locked TINYINT(1) DEFAULT 0 AFTER last_login");
        $results[] = "✓ Added account_locked column to users table";
    } else {
        $results[] = "✓ account_locked column already exists";
    }
    
    // Check and add failed_login_attempts column
    $check_failed = $conn->query("SHOW COLUMNS FROM users LIKE 'failed_login_attempts'");
    if ($check_failed->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER account_locked");
        $results[] = "✓ Added failed_login_attempts column to users table";
    } else {
        $results[] = "✓ failed_login_attempts column already exists";
    }
    
    // Check and add password_changed_at column
    $check_pwd_changed = $conn->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    if ($check_pwd_changed->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL AFTER failed_login_attempts");
        $results[] = "✓ Added password_changed_at column to users table";
    } else {
        $results[] = "✓ password_changed_at column already exists";
    }
    
    // ============================================
    // 2. CREATE ADMIN_ROLES TABLE
    // ============================================
    
    $check_roles = $conn->query("SHOW TABLES LIKE 'admin_roles'");
    if ($check_roles->num_rows == 0) {
        $create_roles = "CREATE TABLE admin_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) UNIQUE NOT NULL,
            description VARCHAR(255),
            permissions JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_role_name (role_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_roles);
        
        // Insert default roles
        $conn->query("INSERT INTO admin_roles (role_name, description, permissions) VALUES 
            ('super_admin', 'Full system access', JSON_OBJECT(
                'manage_books', 1,
                'manage_users', 1,
                'manage_admins', 1,
                'view_audit_logs', 1,
                'manage_roles', 1,
                'system_settings', 1
            )),
            ('admin', 'Full book and user management', JSON_OBJECT(
                'manage_books', 1,
                'manage_users', 1,
                'view_audit_logs', 1
            )),
            ('moderator', 'Content moderation only', JSON_OBJECT(
                'manage_books', 1,
                'view_audit_logs', 0
            ))
        ");
        
        $results[] = "✓ Created admin_roles table with default roles";
    } else {
        $results[] = "✓ admin_roles table already exists";
    }
    
    // ============================================
    // 3. CREATE AUDIT_TRAIL TABLE
    // ============================================
    
    $check_audit = $conn->query("SHOW TABLES LIKE 'audit_trail'");
    if ($check_audit->num_rows == 0) {
        $create_audit = "CREATE TABLE audit_trail (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            admin_name VARCHAR(100),
            admin_email VARCHAR(100),
            action VARCHAR(100) NOT NULL,
            action_category VARCHAR(50),
            resource_type VARCHAR(50),
            resource_id INT,
            resource_name VARCHAR(255),
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            status VARCHAR(20),
            notes VARCHAR(500),
            action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_admin_id (admin_id),
            INDEX idx_action (action),
            INDEX idx_action_date (action_date),
            INDEX idx_resource (resource_type, resource_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_audit);
        $results[] = "✓ Created audit_trail table";
    } else {
        $results[] = "✓ audit_trail table already exists";
    }
    
    // ============================================
    // 4. CREATE INTRUSION_LOG TABLE
    // ============================================
    
    $check_intrusion = $conn->query("SHOW TABLES LIKE 'intrusion_log'");
    if ($check_intrusion->num_rows == 0) {
        $create_intrusion = "CREATE TABLE intrusion_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            details JSON,
            severity VARCHAR(20),
            status VARCHAR(20),
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_intrusion);
        $results[] = "✓ Created intrusion_log table";
    } else {
        $results[] = "✓ intrusion_log table already exists";
    }
    
    // ============================================
    // 5. CREATE MFA_SECRETS TABLE
    // ============================================
    
    $check_mfa_table = $conn->query("SHOW TABLES LIKE 'mfa_secrets'");
    if ($check_mfa_table->num_rows == 0) {
        $create_mfa = "CREATE TABLE mfa_secrets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            secret VARCHAR(255) NOT NULL,
            backup_codes JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_mfa);
        $results[] = "✓ Created mfa_secrets table";
    } else {
        $results[] = "✓ mfa_secrets table already exists";
    }
    
    // ============================================
    // 6. CREATE VULNERABILITY_REPORT TABLE
    // ============================================
    
    $check_vuln = $conn->query("SHOW TABLES LIKE 'vulnerability_report'");
    if ($check_vuln->num_rows == 0) {
        $create_vuln = "CREATE TABLE vulnerability_report (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            vulnerability_type VARCHAR(100) NOT NULL,
            severity VARCHAR(20),
            description VARCHAR(500),
            recommended_action VARCHAR(500),
            status VARCHAR(20),
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_severity (severity),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_vuln);
        $results[] = "✓ Created vulnerability_report table";
    } else {
        $results[] = "✓ vulnerability_report table already exists";
    }
    
    // ============================================
    // 7. VERIFY BOOKS TABLE ARCHIVE COLUMNS
    // ============================================
    
    $check_archived = $conn->query("SHOW COLUMNS FROM books LIKE 'archived_at'");
    if ($check_archived->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archived_at TIMESTAMP NULL");
        $results[] = "✓ Added archived_at column to books table";
    } else {
        $results[] = "✓ archived_at column already exists";
    }
    
    $check_archived_by = $conn->query("SHOW COLUMNS FROM books LIKE 'archived_by'");
    if ($check_archived_by->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archived_by INT NULL");
        $results[] = "✓ Added archived_by column to books table";
    } else {
        $results[] = "✓ archived_by column already exists";
    }
    
    $check_archive_reason = $conn->query("SHOW COLUMNS FROM books LIKE 'archive_reason'");
    if ($check_archive_reason->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archive_reason VARCHAR(500) NULL");
        $results[] = "✓ Added archive_reason column to books table";
    } else {
        $results[] = "✓ archive_reason column already exists";
    }
    
    // ============================================
    // 8. CREATE ARCHIVE_LOG TABLE
    // ============================================
    
    $check_archive_log = $conn->query("SHOW TABLES LIKE 'archive_log'");
    if ($check_archive_log->num_rows == 0) {
        $create_archive_log = "CREATE TABLE archive_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            book_title VARCHAR(255) NOT NULL,
            admin_id INT NOT NULL,
            admin_email VARCHAR(100),
            action VARCHAR(50) NOT NULL,
            reason VARCHAR(500),
            action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_book_id (book_id),
            INDEX idx_admin_id (admin_id),
            INDEX idx_action_date (action_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_archive_log);
        $results[] = "✓ Created archive_log table";
    } else {
        $results[] = "✓ archive_log table already exists";
    }
    
    // Set default admin role if not set
    $conn->query("UPDATE users SET admin_role = 'super_admin' WHERE admin_role IS NULL OR admin_role = ''");
    
} catch (Exception $e) {
    $results[] = "✗ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Database Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0e3a5d 0%, #1b678f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #0e3a5d;
            text-align: center;
            margin-bottom: 30px;
        }
        .results {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .result-item {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #4CAF50;
            background: white;
            border-radius: 3px;
        }
        .result-item.error {
            border-left-color: #F44336;
        }
        .success-message {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #0e3a5d;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Security System Initialization</h1>
        
        <div class="results">
            <?php foreach ($results as $result): ?>
                <div class="result-item <?php echo strpos($result, '✗') !== false ? 'error' : ''; ?>">
                    <?php echo htmlspecialchars($result); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="success-message">
            ✓ Security infrastructure initialized successfully!<br>
            All tables and columns are ready.
        </div>
        
        <div class="back-link">
            <a href="admindashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
