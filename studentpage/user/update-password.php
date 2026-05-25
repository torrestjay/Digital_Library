<?php
session_start();
include "../dbcon.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && isset($_POST['token']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $reset_token = trim($_POST['token']);
    $password = $_POST['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }
    
    // Validate password
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        exit;
    }
    
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain uppercase, lowercase, number, and special character.']);
        exit;
    }
    
    // Verify token
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("ss", $email, $reset_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token.']);
        exit;
    }
    
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password and clear reset tokens
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expiry = NULL, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Clear session reset data
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_verified_email']);
        unset($_SESSION['reset_token']);
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating password. Please try again.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
