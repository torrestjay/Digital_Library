#!/usr/bin/env php
<?php
/**
 * Admin Panel QA Verification Script
 * Tests each admin page for common issues and consistency
 * 
 * Usage: php admin_qa_check.php
 */

define('ADMIN_PATH', __DIR__);

$test_results = [
    'critical' => [],
    'warnings' => [],
    'passed' => []
];

$admin_pages = [
    'admindashboard.php',
    'AdminBookEdit.php',
    'AdminUserPage.php',
    'SettingAdmin.php',
    'AdminNotif.php',
    'AdminRules.php',
    'ArchiveHistory.php',
    'ArchivedBooks.php',
    'AuditLogs.php',
    'SecurityDashboard.php'
];

echo "=================================================================\n";
echo "ADMIN PANEL QA VERIFICATION\n";
echo "=================================================================\n\n";

// Test 1: Check for critical includes
echo "[1/5] Checking critical includes...\n";
foreach ($admin_pages as $page) {
    $file = ADMIN_PATH . '/' . $page;
    if (!file_exists($file)) {
        $test_results['critical'][] = "Page not found: $page";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check for dbcon.php include
    if (strpos($content, "include('../dbcon.php')") === false && 
        strpos($content, "include(\"../dbcon.php\")") === false &&
        strpos($content, 'include "../dbcon.php"') === false) {
        $test_results['critical'][] = "$page missing database connection";
    } else {
        $test_results['passed'][] = "$page has database connection";
    }
}
echo "✓ Completed\n\n";

// Test 2: Check for sidebar include
echo "[2/5] Checking for standardized sidebar...\n";
foreach ($admin_pages as $page) {
    $file = ADMIN_PATH . '/' . $page;
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Check for sidebar include
    if (strpos($content, "includes/admin_sidebar.php") !== false) {
        $test_results['passed'][] = "$page uses standardized sidebar";
    } else if (strpos($content, "<aside class=\"sidebar\"") === false) {
        $test_results['critical'][] = "$page missing sidebar";
    } else {
        $test_results['warnings'][] = "$page has inline sidebar (not using component)";
    }
}
echo "✓ Completed\n\n";

// Test 3: Check for session handling
echo "[3/5] Checking session initialization...\n";
foreach ($admin_pages as $page) {
    $file = ADMIN_PATH . '/' . $page;
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Check for session_start
    if (strpos($content, "session_start()") !== false) {
        $test_results['passed'][] = "$page initializes session";
    } else {
        // Some pages might not need it (API endpoints)
        if (strpos($content, "<?php") !== false) {
            $test_results['warnings'][] = "$page may be missing session_start()";
        }
    }
}
echo "✓ Completed\n\n";

// Test 4: Check for toggleSidebar function
echo "[4/5] Checking toggleSidebar function...\n";
foreach ($admin_pages as $page) {
    $file = ADMIN_PATH . '/' . $page;
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Check for toggleSidebar
    if (strpos($content, "function toggleSidebar()") !== false) {
        $test_results['passed'][] = "$page has toggleSidebar function";
    } else if (strpos($content, "<aside") !== false) {
        // If page has sidebar, it needs toggleSidebar
        $test_results['critical'][] = "$page missing toggleSidebar function";
    }
}
echo "✓ Completed\n\n";

// Test 5: Check for CSS includes
echo "[5/5] Checking CSS includes...\n";
foreach ($admin_pages as $page) {
    $file = ADMIN_PATH . '/' . $page;
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Check for design system CSS
    if (strpos($content, "admin-design-system.css") !== false) {
        $test_results['passed'][] = "$page includes design-system CSS";
    } else if (strpos($content, "<head") !== false) {
        $test_results['warnings'][] = "$page may be missing design-system CSS";
    }
}
echo "✓ Completed\n\n";

// Print results
echo "=================================================================\n";
echo "TEST RESULTS\n";
echo "=================================================================\n\n";

if (!empty($test_results['critical'])) {
    echo "❌ CRITICAL ISSUES (" . count($test_results['critical']) . "):\n";
    foreach ($test_results['critical'] as $issue) {
        echo "   • $issue\n";
    }
    echo "\n";
}

if (!empty($test_results['warnings'])) {
    echo "⚠️  WARNINGS (" . count($test_results['warnings']) . "):\n";
    foreach ($test_results['warnings'] as $warning) {
        echo "   • $warning\n";
    }
    echo "\n";
}

if (!empty($test_results['passed'])) {
    $passed_count = count($test_results['passed']);
    echo "✓ PASSED (" . $passed_count . " checks):\n";
    // Show first 5 passed
    for ($i = 0; $i < min(5, count($test_results['passed'])); $i++) {
        echo "   • " . $test_results['passed'][$i] . "\n";
    }
    if ($passed_count > 5) {
        echo "   ... and " . ($passed_count - 5) . " more\n";
    }
    echo "\n";
}

// Summary
$critical_count = count($test_results['critical']);
$warning_count = count($test_results['warnings']);

echo "=================================================================\n";
if ($critical_count === 0 && $warning_count === 0) {
    echo "✓ ALL CHECKS PASSED\n";
    echo "Admin panel appears to be correctly configured.\n";
} else if ($critical_count === 0) {
    echo "⚠️  CONFIGURATION OK WITH WARNINGS\n";
    echo "Please review " . $warning_count . " warning(s) above.\n";
} else {
    echo "❌ CRITICAL ISSUES DETECTED\n";
    echo "Please fix " . $critical_count . " critical issue(s) before deployment.\n";
}
echo "=================================================================\n";

exit($critical_count > 0 ? 1 : 0);
?>
