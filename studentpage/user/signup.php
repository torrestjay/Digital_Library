<?php
session_start();
include "../dbcon.php";

$signup_error = "";
$signup_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $fullname = trim($_POST['fullname']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Field validation
  if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
    $signup_error = "All fields are required.";
  } elseif ($password !== $confirm_password) {
    $signup_error = "Passwords do not match.";
  } elseif (strlen($password) < 8) {
    $signup_error = "Password must be at least 8 characters long.";
  } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])/', $password)) {
    $signup_error = "Password must contain uppercase, lowercase, number, and special character.";
  } else {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $signup_error = "Email already registered.";
    } else {
      // Insert new user (role defaults to 'student')
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'student')");
      $stmt->bind_param("sss", $fullname, $email, $hashed_password);

      if ($stmt->execute()) {
        $signup_success = "Account created successfully!";
        header("Location: ../login.php?status=success");
        exit();
      } else {
        $signup_error = "Something went wrong. Please try again.";
      }
    }

    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up Digital Library</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Libre Baskerville', sans-serif;
      background-color: #f8f4f0;
      display: flex;
      height: 100vh;
    }

    .signup-container {
      display: flex;
      width: 100%;
    }

    .signup-form {
      flex: 1;
      background-color: #f8f4f0;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 0 80px;
      text-align: center;
      height: 100vh;
    }

    .form-wrapper {
      width: 100%;
      max-width: 400px;
    }

    .form-wrapper h1 {
      font-family: 'Libre Baskerville', serif;
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .form-wrapper p {
      margin-bottom: 30px;
      color: #666;
    }

    .form-wrapper input {
      width: 100%;
      padding: 15px;
      margin-bottom: 20px;
      border: 1px solid #1a1919;
      border-radius: 8px;
      font-size: 1rem;
    }

    .form-wrapper a {
      text-decoration: none;
      font-size: 0.9rem;
      color: #0077cc;
    }

    .form-wrapper button {
      background-color: #1f3556;
      color: white;
      border: 1px solid #1a1919;
      padding: 15px;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 10px;
    }

    .google-login {
      text-align: center;
      margin-top: 20px;
    }

    .google-login img {
      width: 30px;
      cursor: pointer;
    }

    .signup-image {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f8f4f0;
      padding: 40px;
    }

    .signup-image img {
      max-width: 110%;
      height: auto;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .divider {
      text-align: center;
      margin: 30px 0 20px;
      position: relative;
    }

    .divider::before, .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 45%;
      height: 1px;
      background-color: #ccc;
    }

    .divider::before { left: 0; }
    .divider::after { right: 0; }

    .divider span {
      padding: 0 10px;
      background-color: #fff;
      color: #888;
    }

    .message.error {
      color: red;
      margin-bottom: 15px;
    }

    .message.success {
      color: green;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="signup-container">
    <div class="signup-form">
      <div class="form-wrapper">
        <h1>Hello!</h1>
        <p>Fill in all informations.</p>

        <?php if ($signup_error): ?>
          <div class="message error"><?= htmlspecialchars($signup_error) ?></div>
        <?php elseif ($signup_success): ?>
          <div class="message success"><?= htmlspecialchars($signup_success) ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
          <input type="text" name="fullname" placeholder="Full Name" required />
          <input type="email" name="email" placeholder="Email Address" required />
          <input type="password" id="password" name="password" placeholder="Password" required oninput="checkPasswordStrength()" />
          <ul id="password-requirements" style="text-align: left; font-size: 0.9rem; margin-top: -15px; margin-bottom: 15px; color: #888;">
            <li id="length" style="color: red;">❌ At least 8 characters</li>
            <li id="uppercase" style="color: red;">❌ At least one uppercase letter</li>
            <li id="lowercase" style="color: red;">❌ At least one lowercase letter</li>
            <li id="number" style="color: red;">❌ At least one number</li>
            <li id="special" style="color: red;">❌ At least one special character (@$!%*?&)</li>
          </ul>
          <input type="password" name="confirm_password" placeholder="Confirm Password" required />
          <p>Already have an account? <a href="../login.php">Sign In</a></p>
          <button type="submit">Sign Up</button>
        </form>

        <div class="divider"><span>or sign-up with</span></div>

        <div class="google-login">
          <img src="../Images/google.png" alt="Google Sign Up" />
        </div>
      </div>
    </div>

    <div class="signup-image">
      <img src="../Images/library-signup.png" alt="Library Sign Up" />
    </div>
  </div>
  <script>
  function checkPasswordStrength() {
    const password = document.getElementById("password").value;

    const length = document.getElementById("length");
    const uppercase = document.getElementById("uppercase");
    const lowercase = document.getElementById("lowercase");
    const number = document.getElementById("number");
    const special = document.getElementById("special");

    // Check length
    if (password.length >= 8) {
      length.textContent = "✅ At least 8 characters";
      length.style.color = "green";
    } else {
      length.textContent = "❌ At least 8 characters";
      length.style.color = "red";
    }

    // Check uppercase
    if (/[A-Z]/.test(password)) {
      uppercase.textContent = "✅ At least one uppercase letter";
      uppercase.style.color = "green";
    } else {
      uppercase.textContent = "❌ At least one uppercase letter";
      uppercase.style.color = "red";
    }

    // Check lowercase
    if (/[a-z]/.test(password)) {
      lowercase.textContent = "✅ At least one lowercase letter";
      lowercase.style.color = "green";
    } else {
      lowercase.textContent = "❌ At least one lowercase letter";
      lowercase.style.color = "red";
    }

    // Check number
    if (/\d/.test(password)) {
      number.textContent = "✅ At least one number";
      number.style.color = "green";
    } else {
      number.textContent = "❌ At least one number";
      number.style.color = "red";
    }

    // Check special character
    if (/[@$!%*?&]/.test(password)) {
      special.textContent = "✅ At least one special character (@$!%*?&)";
      special.style.color = "green";
    } else {
      special.textContent = "❌ At least one special character (@$!%*?&)";
      special.style.color = "red";
    }
  }
</script>

</body>
</html>
