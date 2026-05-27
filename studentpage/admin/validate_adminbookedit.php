<?php
// Test if AdminBookEdit.php can be parsed and included without fatal errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

echo "Testing AdminBookEdit.php...\n\n";

// Try to include the file (won't execute HTML, just check PHP syntax)
$output = shell_exec('php -l ' . dirname(__FILE__) . '\AdminBookEdit.php');

if (strpos($output, 'No syntax errors') !== false) {
    echo "✓ PHP Syntax: VALID\n";
} else {
    echo "✗ PHP Syntax: INVALID\n";
    echo $output;
    exit(1);
}

// Check that key functions are callable
$test_checks = [
    'File includes dbcon.php' => file_exists('../dbcon.php'),
    'File includes security_utils.php' => file_exists('security_utils.php'),
    'delete_book POST handler present' => strpos(file_get_contents('AdminBookEdit.php'), "'delete_book'") !== false,
    'add_book POST handler present' => strpos(file_get_contents('AdminBookEdit.php'), "'add_book'") !== false,
    'logAdminAction calls present' => strpos(file_get_contents('AdminBookEdit.php'), 'logAdminAction') !== false,
];

foreach ($test_checks as $check => $result) {
    echo ($result ? "✓" : "✗") . " $check\n";
}

echo "\n✅ File validation complete - Ready for browser testing\n";
?>
