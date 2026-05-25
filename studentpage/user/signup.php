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
  <title>Sign Up | Digital Library</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/login.css" />
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  <style>
    .signup-image {
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
    }
    .password-requirements {
      text-align: left;
      font-size: 0.85rem;
      margin: 16px 0 16px 0;
      list-style: none;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .password-requirements li {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      transition: color 0.2s ease;
    }
    .password-requirements li.valid {
      color: var(--success);
    }
    .password-requirements li.invalid {
      color: var(--danger);
    }
    @media (max-width: 960px) {
      .signup-image {
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      }
    }
  </style>
</head>
<body>
  <div id="toastBox"></div>
  <div class="login-container">
    <!-- Left Side Image -->
    <div class="login-image signup-image">
      <img src="../Images/library-signup.png" alt="Sign Up Image" />
    </div>
    <!-- Right Side Form -->
    <div class="login-form">
      <h1>Create Account</h1>
      <p>Already have an account? <a href="../login.php">Sign In</a></p>
      <?php if (!empty($signup_error)): ?>
        <div class="form-alert"><?= htmlspecialchars($signup_error) ?></div>
      <?php endif; ?>
      <form method="POST" action="signup.php">
        <input class="input-field" type="text" name="fullname" placeholder="Full Name" required />
        <input class="input-field" type="email" name="email" placeholder="Email Address" required />
        <input class="input-field" type="password" id="password" name="password" placeholder="Password" required oninput="checkPasswordStrength()" />
        <ul class="password-requirements" id="password-requirements">
          <li id="length" class="invalid"><span class="icon">❌</span> At least 8 characters</li>
          <li id="uppercase" class="invalid"><span class="icon">❌</span> At least one uppercase letter</li>
          <li id="lowercase" class="invalid"><span class="icon">❌</span> At least one lowercase letter</li>
          <li id="number" class="invalid"><span class="icon">❌</span> At least one number</li>
          <li id="special" class="invalid"><span class="icon">❌</span> At least one special character (@$!%*?&)</li>
        </ul>
        <input class="input-field" type="password" name="confirm_password" placeholder="Confirm Password" required />
        <button type="submit" class="btn btn-primary">Create Account</button>
      </form>
      <div class="divider"><span>or</span></div>
      <div class="google-login">
        <button type="button" class="google-btn" title="Sign up with Google">
          <img src="../Images/google.png" alt="Google" />
        </button>
      </div>
    </div>
  </div>
  <script>
    let toastBox = document.getElementById('toastBox');
    let successMess = '<i class="fa-solid fa-circle-check"></i> Account Created Successfully!';
    function showToast(msg) {
        let toast = document.createElement('div'); 
        toast.classList.add('toast');
        toast.innerHTML = msg;
        toastBox.appendChild(toast); 
        if (msg.includes('error')) {
            toast.classList.add('error');
        }
        // Play notification sound if available
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
    
    function checkPasswordStrength() {
      const password = document.getElementById("password").value;
      const length = document.getElementById("length");
      const uppercase = document.getElementById("uppercase");
      const lowercase = document.getElementById("lowercase");
      const number = document.getElementById("number");
      const special = document.getElementById("special");
      
      // Check length
      if (password.length >= 8) {
        length.classList.remove('invalid');
        length.classList.add('valid');
        length.innerHTML = '<span class="icon">✅</span> At least 8 characters';
      } else {
        length.classList.remove('valid');
        length.classList.add('invalid');
        length.innerHTML = '<span class="icon">❌</span> At least 8 characters';
      }
      
      // Check uppercase
      if (/[A-Z]/.test(password)) {
        uppercase.classList.remove('invalid');
        uppercase.classList.add('valid');
        uppercase.innerHTML = '<span class="icon">✅</span> At least one uppercase letter';
      } else {
        uppercase.classList.remove('valid');
        uppercase.classList.add('invalid');
        uppercase.innerHTML = '<span class="icon">❌</span> At least one uppercase letter';
      }
      
      // Check lowercase
      if (/[a-z]/.test(password)) {
        lowercase.classList.remove('invalid');
        lowercase.classList.add('valid');
        lowercase.innerHTML = '<span class="icon">✅</span> At least one lowercase letter';
      } else {
        lowercase.classList.remove('valid');
        lowercase.classList.add('invalid');
        lowercase.innerHTML = '<span class="icon">❌</span> At least one lowercase letter';
      }
      
      // Check number
      if (/\d/.test(password)) {
        number.classList.remove('invalid');
        number.classList.add('valid');
        number.innerHTML = '<span class="icon">✅</span> At least one number';
      } else {
        number.classList.remove('valid');
        number.classList.add('invalid');
        number.innerHTML = '<span class="icon">❌</span> At least one number';
      }
      
      // Check special character
      if (/[@$!%*?&]/.test(password)) {
        special.classList.remove('invalid');
        special.classList.add('valid');
        special.innerHTML = '<span class="icon">✅</span> At least one special character (@$!%*?&)';
      } else {
        special.classList.remove('valid');
        special.classList.add('invalid');
        special.innerHTML = '<span class="icon">❌</span> At least one special character (@$!%*?&)';
      }
    }
  </script>
</body>
</html>
