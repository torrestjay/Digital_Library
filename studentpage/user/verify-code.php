<?php
session_start();
include "../dbcon.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && isset($_POST['verification_code'])) {
    $email = trim($_POST['email']);
    $verification_code = trim($_POST['verification_code']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }
    
    // Check if verification code matches and is not expired
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expiry > NOW()");
    $stmt->bind_param("ss", $email, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate a temporary token for password reset
    $reset_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    
    // Store token in database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $reset_token, $token_expiry, $email);
    
    if ($stmt->execute()) {
        $_SESSION['reset_verified_email'] = $email;
        $_SESSION['reset_token'] = $reset_token;
        
        echo json_encode(['success' => true, 'message' => 'Code verified successfully.', 'token' => $reset_token]);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
