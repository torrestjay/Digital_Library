<?php
/**
 * Security & Audit Trail Utilities
 * Provides functions for logging admin actions, RBAC checks, and intrusion detection
 */

// ============================================
// AUDIT LOGGING FUNCTIONS
// ============================================

function logAdminAction($conn, $action, $category, $resource_type = null, $resource_id = null, 
                        $resource_name = null, $old_values = null, $new_values = null, $notes = null) {
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Convert values to JSON if provided
    $old_json = $old_values ? (is_string($old_values) ? $old_values : json_encode($old_values)) : null;
    $new_json = $new_values ? (is_string($new_values) ? $new_values : json_encode($new_values)) : null;
    
    // Insert into audit_trail table (actual schema)
    $stmt = $conn->prepare("
        INSERT INTO audit_trail 
        (admin_id, action, resource_type, resource_id, old_data, new_data, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        error_log("Audit logging prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param(
        "ississs",
        $user_id,
        $action,
        $resource_type,
        $resource_id,
        $old_json,
        $new_json,
        $ip_address
    );
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Audit logging execute error: " . $stmt->error);
    }
    $stmt->close();
    
    return $result;
}

// ============================================
// INTRUSION DETECTION FUNCTIONS
// ============================================

function logFailedLogin($conn, $user_id, $reason = 'Invalid credentials') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Increment failed attempts
    $update_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Get current failed attempts
    $check_stmt = $conn->prepare("SELECT failed_login_attempts FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    $check_stmt->close();
    
    $failed_attempts = $user['failed_login_attempts'] ?? 0;
    
    // Lock account if too many failed attempts
    if ($failed_attempts >= 5) {
        $lock_stmt = $conn->prepare("UPDATE users SET account_locked = 1 WHERE id = ?");
        $lock_stmt->bind_param("i", $user_id);
        $lock_stmt->execute();
        $lock_stmt->close();
    }
    
    // Log intrusion attempt
    $details = json_encode([
        'reason' => $reason,
        'failed_attempts' => $failed_attempts,
        'account_locked' => ($failed_attempts >= 5)
    ]);
    
    $severity = $failed_attempts >= 5 ? 'high' : 'medium';
    
    $log_stmt = $conn->prepare("
        INSERT INTO intrusion_log (event_type, user_id, ip_address, user_agent, details, severity, status)
        VALUES ('failed_login', ?, ?, ?, ?, ?, 'open')
    ");
    
    if ($log_stmt) {
        $log_stmt->bind_param("isss", $user_id, $ip_address, $user_agent, $details, $severity);
        $log_stmt->execute();
        $log_stmt->close();
    }
}

function logSuccessfulLogin($conn, $user_id) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Reset failed attempts
    $reset_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE id = ?");
    $reset_stmt->bind_param("i", $user_id);
    $reset_stmt->execute();
    $reset_stmt->close();
    
    // Log successful login
    logAdminAction($conn, 'Login', 'authentication', 'user', $user_id);
}

function isAccountLocked($conn, $user_id) {
    $stmt = $conn->prepare("SELECT account_locked FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return isset($user['account_locked']) && $user['account_locked'] == 1;
}

function getIntrustionSummary($conn) {
    $summary = [];
    
    // Failed login attempts in last 24 hours
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM intrusion_log 
        WHERE event_type = 'failed_login' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND status = 'open'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['failed_logins_24h'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Locked accounts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE account_locked = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['locked_accounts'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Open suspicious activities
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM intrusion_log WHERE status = 'open'");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['open_events'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $summary;
}

// ============================================
// RBAC (ROLE-BASED ACCESS CONTROL) FUNCTIONS
// ============================================

function getUserRole($conn, $user_id) {
    $stmt = $conn->prepare("SELECT admin_role FROM users WHERE id = ?");
    if (!$stmt) {
        error_log("getUserRole prepare error: " . $conn->error);
        return 'user'; // Default fallback
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log("getUserRole execute error: " . $stmt->error);
        $stmt->close();
        return 'user'; // Default fallback
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("getUserRole get_result error: " . $stmt->error);
        $stmt->close();
        return 'user'; // Default fallback
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user['admin_role'] ?? 'admin'; // Default to 'admin' if not set
}

function getRolePermissions($conn, $role_name) {
    // Check if admin_roles table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_roles'");
    if (!$table_check || $table_check->num_rows == 0) {
        error_log("admin_roles table does not exist");
        return []; // Return empty permissions if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT permissions FROM admin_roles WHERE role_name = ?");
    if (!$stmt) {
        error_log("getRolePermissions prepare error: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("s", $role_name);
    if (!$stmt->execute()) {
        error_log("getRolePermissions execute error: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("getRolePermissions get_result error: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $role = $result->fetch_assoc();
    $stmt->close();
    
    if (!$role) {
        error_log("Role not found: " . $role_name);
        return [];
    }
    
    $permissions = json_decode($role['permissions'], true);
    return is_array($permissions) ? $permissions : [];
}

function hasPermission($conn, $user_id, $permission) {
    $role = getUserRole($conn, $user_id);
    if (!$role) {
        return false;
    }
    
    $permissions = getRolePermissions($conn, $role);
    if (!is_array($permissions)) {
        return false;
    }
    
    return isset($permissions[$permission]) && $permissions[$permission] == 1;
}

function requirePermission($conn, $user_id, $permission, $redirect = true) {
    if (!hasPermission($conn, $user_id, $permission)) {
        if ($redirect) {
            http_response_code(403);
            die("Access Denied: You do not have permission to access this resource.");
        }
        return false;
    }
    return true;
}

function checkAdminAccess($user_id) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
        http_response_code(403);
        die("Unauthorized access");
    }
}

// ============================================
// MFA (MULTI-FACTOR AUTHENTICATION) FUNCTIONS
// ============================================

function isMFAEnabled($conn, $user_id) {
    $stmt = $conn->prepare("SELECT mfa_enabled FROM users WHERE id = ?");
    if (!$stmt) {
        error_log("isMFAEnabled prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log("isMFAEnabled execute error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return isset($user['mfa_enabled']) && $user['mfa_enabled'] == 1;
}

function toggleMFA($conn, $user_id, $enable) {
    $status = $enable ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE users SET mfa_enabled = ? WHERE id = ?");
    if (!$stmt) {
        error_log("toggleMFA prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $status, $user_id);
    if (!$stmt->execute()) {
        error_log("toggleMFA execute error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    if ($result && $enable) {
        // Generate and store secret
        $secret = generateMFASecret();
        
        $secret_stmt = $conn->prepare("
            INSERT INTO mfa_secrets (user_id, secret) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE secret = VALUES(secret)
        ");
        
        if ($secret_stmt) {
            $secret_stmt->bind_param("is", $user_id, $secret);
            if (!$secret_stmt->execute()) {
                error_log("toggleMFA mfa_secrets insert error: " . $secret_stmt->error);
            }
            $secret_stmt->close();
        } else {
            error_log("toggleMFA mfa_secrets prepare error: " . $conn->error);
        }
        
        return $secret;
    }
    
    return true;
}

function generateMFASecret() {
    $bytes = random_bytes(32);
    return base64_encode($bytes);
}

function generateBackupCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        $codes[] = $code;
    }
    return $codes;
}

// ============================================
// VULNERABILITY SCANNER FUNCTIONS
// ============================================

function scanAdminVulnerabilities($conn) {
    $vulnerabilities = [];
    
    // Check if admin_info table exists (legacy table)
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_info'");
    $has_admin_info = $table_check && $table_check->num_rows > 0;
    
    // Check for weak passwords (last changed > 90 days)
    $weak_pwd = $conn->query("
        SELECT id, name, email FROM users 
        WHERE (password_changed_at IS NULL OR password_changed_at < DATE_SUB(NOW(), INTERVAL 90 DAY))
        AND role IN ('admin', 'superadmin')
    ");
    
    if ($weak_pwd) {
        while ($user = $weak_pwd->fetch_assoc()) {
            $vulnerabilities[] = [
                'type' => 'weak_password',
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'severity' => 'high',
                'description' => 'Admin password not changed in 90+ days',
                'action' => 'Update password immediately'
            ];
        }
    }
    
    // Check for missing profile information
    $missing_info = $conn->query("
        SELECT id, name, email FROM users 
        WHERE (phone IS NULL OR phone = '' OR address IS NULL OR address = '')
        AND role IN ('admin', 'superadmin')
    ");
    
    if ($missing_info) {
        while ($user = $missing_info->fetch_assoc()) {
            $vulnerabilities[] = [
                'type' => 'incomplete_profile',
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'severity' => 'medium',
                'description' => 'Admin profile information incomplete',
                'action' => 'Update profile information in Account Settings'
            ];
        }
    }
    
    // Check for inactive accounts (not logged in > 30 days)
    $inactive = $conn->query("
        SELECT id, name, email, last_login FROM users 
        WHERE (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))
        AND role IN ('admin', 'superadmin')
    ");
    
    if ($inactive) {
        while ($user = $inactive->fetch_assoc()) {
            $vulnerabilities[] = [
                'type' => 'inactive_account',
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'severity' => 'medium',
                'description' => 'Admin account inactive for 30+ days',
                'action' => 'Review access or disable account'
            ];
        }
    }
    
    // Check for accounts without MFA
    $no_mfa = $conn->query("
        SELECT id, name, email FROM users 
        WHERE mfa_enabled = 0
        AND role IN ('admin', 'superadmin')
    ");
    
    if ($no_mfa) {
        while ($user = $no_mfa->fetch_assoc()) {
            $vulnerabilities[] = [
                'type' => 'no_mfa',
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'severity' => 'high',
                'description' => 'Admin account does not have MFA enabled',
                'action' => 'Enable MFA in Account Settings'
            ];
        }
    }
    
    return $vulnerabilities;
}

function storeVulnerabilityReports($conn, $vulnerabilities) {
    foreach ($vulnerabilities as $vuln) {
        $stmt = $conn->prepare("
            INSERT INTO vulnerability_report 
            (user_id, vulnerability_type, severity, description, recommended_action, status)
            VALUES (?, ?, ?, ?, ?, 'open')
            ON DUPLICATE KEY UPDATE 
                severity = VALUES(severity),
                description = VALUES(description)
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                "issss",
                $vuln['user_id'],
                $vuln['type'],
                $vuln['severity'],
                $vuln['description'],
                $vuln['action']
            );
            $stmt->execute();
            $stmt->close();
        }
    }
}

function getVulnerabilityReport($conn) {
    $report = [];
    
    $stmt = $conn->query("SELECT * FROM vulnerability_report WHERE status = 'open' ORDER BY severity DESC");
    
    while ($vuln = $stmt->fetch_assoc()) {
        $report[] = $vuln;
    }
    
    return $report;
}

?>
