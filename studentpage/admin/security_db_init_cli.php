<?php
// CLI version for database initialization (no session required)
include('../dbcon.php');

echo "=== SECURITY DATABASE INITIALIZATION ===\n\n";

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
    $check_secret = $conn->query("SHOW COLUMNS FROM users LIKE 'mfa_secret'");
    if ($check_secret->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN mfa_secret VARCHAR(255) AFTER mfa_enabled");
        $results[] = "✓ Added mfa_secret column to users table";
    } else {
        $results[] = "✓ mfa_secret column already exists";
    }
    
    // Check and add last_login column
    $check_login = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($check_login->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN last_login DATETIME AFTER mfa_secret");
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
    $check_pwd = $conn->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    if ($check_pwd->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN password_changed_at DATETIME AFTER failed_login_attempts");
        $results[] = "✓ Added password_changed_at column to users table";
    } else {
        $results[] = "✓ password_changed_at column already exists";
    }
    
    // ============================================
    // 2. ADD COLUMNS TO BOOKS TABLE
    // ============================================
    
    // Check and add archived_at column
    $check_arch = $conn->query("SHOW COLUMNS FROM books LIKE 'archived_at'");
    if ($check_arch->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archived_at DATETIME AFTER availability");
        $results[] = "✓ Added archived_at column to books table";
    } else {
        $results[] = "✓ archived_at column already exists";
    }
    
    // Check and add archived_by column
    $check_by = $conn->query("SHOW COLUMNS FROM books LIKE 'archived_by'");
    if ($check_by->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archived_by INT AFTER archived_at");
        $results[] = "✓ Added archived_by column to books table";
    } else {
        $results[] = "✓ archived_by column already exists";
    }
    
    // Check and add archive_reason column
    $check_reason = $conn->query("SHOW COLUMNS FROM books LIKE 'archive_reason'");
    if ($check_reason->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN archive_reason TEXT AFTER archived_by");
        $results[] = "✓ Added archive_reason column to books table";
    } else {
        $results[] = "✓ archive_reason column already exists";
    }
    
    // ============================================
    // 3. CREATE ADMIN_ROLES TABLE
    // ============================================
    
    $check_roles = $conn->query("SHOW TABLES LIKE 'admin_roles'");
    if ($check_roles->num_rows == 0) {
        $create_roles = "CREATE TABLE admin_roles (
            role_id INT PRIMARY KEY AUTO_INCREMENT,
            role_name VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_roles);
        
        // Insert default roles
        $conn->query("INSERT INTO admin_roles (role_name, description) VALUES ('super_admin', 'Full system access')");
        $conn->query("INSERT INTO admin_roles (role_name, description) VALUES ('admin', 'Standard admin')");
        $conn->query("INSERT INTO admin_roles (role_name, description) VALUES ('moderator', 'Content moderation only')");
        
        $results[] = "✓ Created admin_roles table";
    } else {
        $results[] = "✓ admin_roles table already exists";
    }
    
    // ============================================
    // 4. CREATE AUDIT_TRAIL TABLE
    // ============================================
    
    $check_audit = $conn->query("SHOW TABLES LIKE 'audit_trail'");
    if ($check_audit->num_rows == 0) {
        $create_audit = "CREATE TABLE audit_trail (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action VARCHAR(100),
            resource_type VARCHAR(50),
            resource_id INT,
            old_data JSON,
            new_data JSON,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (admin_id),
            INDEX (action),
            INDEX (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_audit);
        $results[] = "✓ Created audit_trail table";
    } else {
        $results[] = "✓ audit_trail table already exists";
    }
    
    // ============================================
    // 5. CREATE INTRUSION_LOG TABLE
    // ============================================
    
    $check_intrusion = $conn->query("SHOW TABLES LIKE 'intrusion_log'");
    if ($check_intrusion->num_rows == 0) {
        $create_intrusion = "CREATE TABLE intrusion_log (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            event_type VARCHAR(50),
            ip_address VARCHAR(45),
            user_agent VARCHAR(500),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (user_id),
            INDEX (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_intrusion);
        $results[] = "✓ Created intrusion_log table";
    } else {
        $results[] = "✓ intrusion_log table already exists";
    }
    
    // ============================================
    // 6. CREATE MFA_SECRETS TABLE
    // ============================================
    
    $check_mfa_table = $conn->query("SHOW TABLES LIKE 'mfa_secrets'");
    if ($check_mfa_table->num_rows == 0) {
        $create_mfa = "CREATE TABLE mfa_secrets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            secret VARCHAR(255),
            backup_codes JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_mfa);
        $results[] = "✓ Created mfa_secrets table";
    } else {
        $results[] = "✓ mfa_secrets table already exists";
    }
    
    // ============================================
    // 7. CREATE VULNERABILITY_REPORT TABLE
    // ============================================
    
    $check_vuln = $conn->query("SHOW TABLES LIKE 'vulnerability_report'");
    if ($check_vuln->num_rows == 0) {
        $create_vuln = "CREATE TABLE vulnerability_report (
            id INT PRIMARY KEY AUTO_INCREMENT,
            scan_type VARCHAR(100),
            severity VARCHAR(20),
            description TEXT,
            affected_users JSON,
            recommendation TEXT,
            scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            INDEX (severity),
            INDEX (scanned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_vuln);
        $results[] = "✓ Created vulnerability_report table";
    } else {
        $results[] = "✓ vulnerability_report table already exists";
    }
    
    // ============================================
    // 8. CREATE ARCHIVE_LOG TABLE
    // ============================================
    
    $check_archive = $conn->query("SHOW TABLES LIKE 'archive_log'");
    if ($check_archive->num_rows == 0) {
        $create_archive_log = "CREATE TABLE archive_log (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            book_id INT,
            book_title VARCHAR(255),
            book_author VARCHAR(255),
            archived_by INT,
            action VARCHAR(20),
            reason TEXT,
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX (book_id),
            INDEX (archived_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($create_archive_log);
        $results[] = "✓ Created archive_log table";
    } else {
        $results[] = "✓ archive_log table already exists";
    }
    
    echo implode("\n", $results);
    echo "\n\n✅ DATABASE INITIALIZATION COMPLETE\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
