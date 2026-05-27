<?php
// Test JSON output from getDashboardData.php

echo "=== Testing getDashboardData.php JSON Output ===\n\n";

// Capture the output
ob_start();
include "getDashboardData.php";
$output = ob_get_clean();

// Check if it's valid JSON
echo "Output length: " . strlen($output) . " bytes\n";
echo "First 100 chars: " . substr($output, 0, 100) . "\n\n";

// Try to decode
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Valid JSON!\n";
    echo "Response keys: " . implode(", ", array_keys($decoded)) . "\n";
    echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    if ($decoded['success']) {
        echo "Data keys: " . implode(", ", array_keys($decoded['data'])) . "\n";
    }
} else {
    echo "❌ Invalid JSON!\n";
    echo "Error: " . json_last_error_msg() . "\n";
    echo "Raw output:\n" . $output . "\n";
}
?>
