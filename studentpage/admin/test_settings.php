<?php
include '../dbcon.php';

echo "=== ACCOUNT SETTINGS MODULE TEST ===\n\n";

// Test 1: Check database columns
echo "TEST 1: Database Columns\n";
echo "------------------------\n";

$required_columns = [
    'fullname', 'email', 'password', 'phone', 'address', 'gender',
    'admin_role', 'mfa_enabled', 'mfa_secret', 'last_login', 
    'account_locked', 'failed_login_attempts', 'password_changed_at'
];

$result = $conn->query("DESCRIBE users");
$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

$missing = array_diff($required_columns, $existing_columns);
if (empty($missing)) {
    echo "✓ All required columns exist\n";
} else {
    echo "✗ Missing columns: " . implode(', ', $missing) . "\n";
}

// Test 2: Check audit_trail table for logging
echo "\nTEST 2: Audit Trail Table\n";
echo "-------------------------\n";

$check = $conn->query("SHOW TABLES LIKE 'audit_trail'");
if ($check->num_rows > 0) {
    echo "✓ audit_trail table exists\n";
    
    // Check audit_trail columns
    $audit_cols = $conn->query("DESCRIBE audit_trail");
    echo "  Columns: ";
    while ($row = $audit_cols->fetch_assoc()) {
        echo $row['Field'] . ", ";
    }
    echo "\n";
} else {
    echo "✗ audit_trail table missing\n";
}

// Test 3: Check security_utils.php for logAdminAction function
echo "\nTEST 3: Security Functions\n";
echo "--------------------------\n";

if (file_exists('security_utils.php')) {
    echo "✓ security_utils.php exists\n";
    
    // Check if logAdminAction is defined
    include 'security_utils.php';
    if (function_exists('logAdminAction')) {
        echo "✓ logAdminAction function available\n";
    } else {
        echo "✗ logAdminAction function not found\n";
    }
} else {
    echo "✗ security_utils.php missing\n";
}

// Test 4: Check SweetAlert2 integration
echo "\nTEST 4: SweetAlert2 Integration\n";
echo "--------------------------------\n";

$settings_file = file_get_contents('SettingAdmin.php');
if (strpos($settings_file, 'sweetalert2@11') !== false) {
    echo "✓ SweetAlert2 CDN included\n";
}
if (strpos($settings_file, 'Swal.fire') !== false) {
    echo "✓ SweetAlert2 calls present\n";
}

// Test 5: Check form validation
echo "\nTEST 5: Form Validation\n";
echo "----------------------\n";

$validations_found = [
    'fullname' => strpos($settings_file, 'fullname.length < 2') !== false,
    'password' => strpos($settings_file, 'password && password.length < 6') !== false,
    'phone' => strpos($settings_file, 'phoneDigits.length < 10') !== false,
    'address' => strpos($settings_file, 'address.length > 500') !== false
];

foreach ($validations_found as $field => $found) {
    echo ($found ? "✓" : "✗") . " $field validation: " . ($found ? "present" : "missing") . "\n";
}

// Test 6: Check database operations
echo "\nTEST 6: Database Prepared Statements\n";
echo "------------------------------------\n";

$stmt_checks = [
    'SELECT prepared' => strpos($settings_file, 'prepare("SELECT * FROM users WHERE id = ?)') !== false,
    'UPDATE prepared' => strpos($settings_file, 'prepare("UPDATE users SET') !== false,
    'Error checking' => strpos($settings_file, 'if (!$') !== false
];

foreach ($stmt_checks as $check => $found) {
    echo ($found ? "✓" : "✗") . " $check\n";
}

echo "\n✅ TEST SUMMARY COMPLETE\n";
?>
