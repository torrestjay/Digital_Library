<?php
include 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit;
    }
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Check if email already exists
    $check_email_stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check_email_stmt->bind_param('s', $email);
    $check_email_stmt->execute();
    $check_email = $check_email_stmt->get_result();
    if ($check_email && $check_email->num_rows > 0) {
        $check_email_stmt->close();
        echo "Email already registered.";
        exit;
    }
    $check_email_stmt->close();
    // Insert into database
    $insert_stmt = $conn->prepare('INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)');
    $insert_stmt->bind_param('sss', $fullname, $email, $hashed_password);
    if ($insert_stmt->execute()) {
        echo "Signup successful! <a href='../login.php'>Login here</a>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    $insert_stmt->close();
}
?>
