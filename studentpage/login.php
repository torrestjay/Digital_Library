<?php
session_start();
include "dbcon.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"]);
  $password = $_POST["password"];
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      // Secure password verification
      if (password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['email'] = $user['email'];
          $_SESSION['username'] = $user['fullname'];
          $_SESSION['role'] = $user['role'] ?? 'student';
          if ($_SESSION['role'] === 'admin') {
              header("Location: admin/admindashboard.php?status=success");
          } else {
              header("Location: user/homepage.php?status=success");
          }
          exit();
      } else {
          $error = "Incorrect password.";
      }
  } else {
      $error = "Email not found.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | Digital Library</title>
  <link rel="stylesheet" href="css/design-system.css" />
  <link rel="stylesheet" href="css/login.css" />
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
</head>
<body>
  <div id="toastBox"></div>
  <div class="login-container">
    <!-- Left Side Image -->
    <div class="login-image">
      <img src="Images/login.png" alt="Login Image" />
    </div>
    <!-- Right Side Form -->
    <div class="login-form">
      <h1>Welcome Back!</h1>
      <p>Don't have an account? <a href="user/signup.php">Sign Up</a></p>
      <?php if (!empty($error)): ?>
        <div class="form-alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <input class="input-field" type="email" name="email" placeholder="Email Address" required />
        <input class="input-field" type="password" name="password" placeholder="Password" required />
        <a href="user/forgotpassword.html">Forgot your password?</a>
        <button type="submit" class="btn btn-primary">Sign In</button>
      </form>
      <div class="divider"><span>or</span></div>
      <div class="google-login">
        <button type="button" class="google-btn" title="Sign in with Google">
          <img src="Images/google.png" alt="Google" />
        </button>
      </div>
    </div>
  </div>
  <script>
    let toastBox = document.getElementById('toastBox');
    let successMess = '<i class="fa-solid fa-circle-check"></i>Account Created Successful!';
    function showToast(msg) {
        let toast = document.createElement('div'); 
        toast.classList.add('toast');
        toast.innerHTML = msg;
        toastBox.appendChild(toast); 
        if (msg.includes('error')) {
            toast.classList.add('error');
        }
        // Play notification sound
        const sound = document.getElementById('notifySound');
        if (sound) sound.play();
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        showToast(successMess);
        window.history.replaceState(null, null, window.location.pathname);
    }
  </script>
</body>
</html>
