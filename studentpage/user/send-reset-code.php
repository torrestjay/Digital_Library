<?php
session_start();
include "../dbcon.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Don't reveal if email exists (security best practice)
        echo json_encode(['success' => false, 'message' => 'If an account exists with this email, you will receive a verification code.']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate a random 6-digit verification code
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $code_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store verification code in database (you'll need to add this column to users table)
    $stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $verification_code, $code_expiry, $email);
    
    if ($stmt->execute()) {
        // In production, you would send an actual email here
        // For now, we'll log it or store it in session for demo purposes
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $verification_code; // For demo/testing only
        
        // TODO: Send email with verification code
        // mail($email, 'Password Reset Code - Digital Library', 
        //      "Your verification code is: " . $verification_code . "\nThis code expires in 15 minutes.");
        
        echo json_encode(['success' => true, 'message' => 'Verification code sent to your email.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
