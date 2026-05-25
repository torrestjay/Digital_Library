<?php
include "../dbcon.php";
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';
$birth_date = null;
$current_year = (int)date("Y");
// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle account information update
  if (isset($_POST['update_account'])) {
    $errors['account'] = [];
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($fullname === '') {
      $errors['account']['fullname'] = 'Full name is required';
    }
    if ($password !== '') {
      if (strlen($password) < 8) {
        $errors['account']['password'] = 'Password must be at least 8 characters';
      }
      if (!preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['account']['password'] = 'Password must contain at least one uppercase letter and one number.';
      }
    }
    if (empty($errors['account'])) {
      try {
        $query = 'UPDATE users SET fullname = ?';
        $params = [$fullname];
        $types = 's';
        if ($password !== '') {
          $query .= ', password = ?';
          $params[] = password_hash($password, PASSWORD_DEFAULT);
          $types .= 's';
        }
        $query .= ' WHERE id = ?';
        $params[] = $user_id;
        $types .= 'i';
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
          throw new Exception('Unable to prepare account update.');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $success = mysqli_stmt_affected_rows($stmt) > 0 ? 'Account information updated successfully!' : 'No changes made.';
        $_SESSION['fullname'] = $fullname;
      } catch (Exception $e) {
        $errors['account']['database'] = 'Error updating account: ' . $e->getMessage();
      }
    }
  }
  // Handle personal information update
  if (isset($_POST['update_info'])) {
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birth_month = $_POST['birth_month'] ?? '';
    $birth_day = $_POST['birth_day'] ?? '';
    $birth_year = $_POST['birth_year'] ?? '';
    // Validate and format birth_date
    if (!empty($birth_month) || !empty($birth_day) || !empty($birth_year)) {
      if (empty($birth_month) || empty($birth_day) || empty($birth_year)) {
        $errors['info']['birth_date'] = "Complete birth date required";
      } elseif (!ctype_digit($birth_year) || strlen($birth_year) != 4 || (int)$birth_year > ($current_year - 5)) {
        $errors['info']['birth_date'] = "Enter a valid birth year at least 5 years ago";
      } elseif (!checkdate($birth_month, $birth_day, $birth_year)) {
        $errors['info']['birth_date'] = "Invalid birth date";
      } else {
        $birth_date = sprintf("%04d-%02d-%02d", $birth_year, $birth_month, $birth_day);
      }
    }
    if (isset($_POST['update_info'])) {
      // ... validation code ...
      if (empty($errors['info'])) {
        try {
          // Step 1: Check if record exists
          $check_sql = "SELECT id FROM user_information WHERE user_id = ?";
          $check_stmt = mysqli_prepare($conn, $check_sql);
          mysqli_stmt_bind_param($check_stmt, "i", $user_id);
          mysqli_stmt_execute($check_stmt);
          mysqli_stmt_store_result($check_stmt);
          if (mysqli_stmt_num_rows($check_stmt) > 0) {
            // Step 2a: Update existing info
            $query = "UPDATE user_information 
            SET name = ?, lastname = ?, age = ?, contact_number = ?, address = ?, gender = ?, birth_date = ? 
            WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssissssi", $first_name, $last_name, $age, $contact, $address, $gender, $birth_date, $user_id);
          } else {
            // Step 2b: Insert new info
            $query = "INSERT INTO user_information (
              user_id, name, lastname, age, contact_number, address, gender, birth_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param(
              $stmt,
              "ississss", // 8 types
              $user_id,
              $first_name,
              $last_name,
              $age,
              $contact,
              $address,
              $gender,
              $birth_date
            );
          }
          // Execute query
          mysqli_stmt_execute($stmt);
          $success = "Personal information " . (mysqli_stmt_affected_rows($stmt) > 0 ? "saved" : "not changed") . " successfully!";
        } catch (Exception $e) {
          $errors['info']['database'] = "Error saving personal info: " . $e->getMessage();
        }
      }
    }
    
  }
}
// Fetch current data
$user_query = "SELECT `id`, `fullname`, `email`, `password`, `role`, `created_at` FROM `users` WHERE `id` = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
$admin_info_query = "SELECT `id`, `user_id`, `last_name`, `first_name`, `middle_initial`, `age`, `contact`, `address`, `gender`, `birth_date` FROM `admin_info` WHERE `user_id` = ?";
$stmt = mysqli_prepare($conn, $admin_info_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$admin_info_result = mysqli_stmt_get_result($stmt);
$admin_info_data = mysqli_fetch_assoc($admin_info_result);
// Parse birth date
$birth_month = '';
$birth_day = '';
$birth_year = '';
if (!empty($admin_info_data['birth_date'])) {
  $birth_parts = explode('-', $admin_info_data['birth_date']);
  $birth_year = $birth_parts[0];
  $birth_month = ltrim($birth_parts[1], '0');
  $birth_day = ltrim($birth_parts[2], '0');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Account Settings</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      min-height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fa 100%);
      color: #14324a;
      overflow-x: hidden;
    }
    
    .main-content {
      padding: 26px;
    }

    h2 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 6px;
      color: #0e3a5d;
    }

    p {
      font-size: 0.95rem;
      color: #5f7385;
      margin-bottom: 24px;
    }

    .tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 14px 0 24px;
    }

    .tabs .btn {
      min-height: 44px;
      padding: 0 20px;
      border-radius: 12px;
      font-weight: 700;
      transition: transform 0.2s ease, background 0.2s ease;
    }

    .tabs .btn:hover {
      transform: translateY(-1px);
    }

    .account-box {
      background: white;
      padding: 28px;
      border-radius: 24px;
      border: 1px solid #e5edf5;
      box-shadow: 0 10px 26px rgba(14, 58, 93, 0.08);
      margin-bottom: 24px;
    }

    .account-box h3 {
      margin: 0 0 18px;
      font-size: 1.1rem;
      font-weight: 700;
      color: #0e3a5d;
    }

    .account-box h3:not(:first-child) {
      margin-top: 24px;
    }

    .account-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
      font-size: 16px;
      flex-wrap: wrap;
    }

    .account-row label {
      font-weight: 600;
      min-width: 120px;
      color: #14324a;
    }

    .account-row span {
      color: #5f7385;
      flex: 1;
      min-width: 200px;
    }

    .birthdate-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: flex-start;
    }

    .birthdate-row label {
      margin-top: 0;
    }

    .birthdate-row select,
    .birthdate-row input[type="text"],
    .custom-input,
    .custom-input-address {
      padding: 12px 14px;
      font-size: 14px;
      border: 1px solid #d9e5f0;
      border-radius: 14px;
      background: #fff;
      color: #14324a;
      transition: all 0.2s ease;
      font-family: 'Poppins', sans-serif;
    }

    .birthdate-row select:focus,
    .birthdate-row input[type="text"]:focus,
    .custom-input:focus,
    .custom-input-address:focus {
      outline: none;
      border-color: #0e3a5d;
      box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.1);
      background: #fafbfc;
    }

    .custom-input {
      width: 300px;
    }

    .custom-input-address {
      width: 100%;
      max-width: 900px;
    }

    .birthdate-row select,
    .birthdate-row input[type="text"] {
      max-width: 140px;
    }

    .error {
      color: #d43d3d;
      font-size: 13px;
      margin-top: 6px;
      display: block;
      font-weight: 500;
    }

    .error-field {
      border-color: #d43d3d !important;
      background-color: #fff5f5 !important;
    }

    .error-field:focus {
      border-color: #d43d3d !important;
      box-shadow: 0 0 0 3px rgba(212, 61, 61, 0.1) !important;
    }

    .success-message {
      color: #15597c;
      background-color: #e8f2fb;
      padding: 14px 18px;
      border-radius: 16px;
      margin-bottom: 18px;
      border: 1px solid #b6d5ec;
      font-weight: 500;
    }

    .personal-info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 24px;
    }

    .personal-info-grid > div {
      display: flex;
      flex-direction: column;
    }

    .personal-info-grid .wide {
      grid-column: span 4;
    }

    .personal-info-grid label {
      font-weight: 600;
      margin-bottom: 8px;
      color: #14324a;
      font-size: 0.9rem;
    }

    .personal-info-grid input[type="text"],
    .personal-info-grid select {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
      border: 1px solid #d9e5f0;
      border-radius: 14px;
      background: #fff;
      color: #14324a;
      transition: all 0.2s ease;
      font-family: 'Poppins', sans-serif;
    }

    .personal-info-grid input[type="text"]:focus,
    .personal-info-grid select:focus {
      outline: none;
      border-color: #0e3a5d;
      box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.1);
      background: #fafbfc;
    }

    .personal-info-grid input.error-field,
    .personal-info-grid select.error-field {
      border-color: #d43d3d !important;
      background-color: #fff5f5 !important;
    }

    .account-box button[type="submit"],
    .account-box button.btn-primary,
    .account-box input[type="submit"] {
      background: linear-gradient(135deg, #0e3a5d, #1b678f);
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 700;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      margin-top: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      font-family: 'Poppins', sans-serif;
    }

    .account-box button[type="submit"]:hover,
    .account-box button.btn-primary:hover,
    .account-box input[type="submit"]:hover {
      background: linear-gradient(135deg, #0a2a47, #15527a);
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(14, 58, 93, 0.2);
    }

    .account-box button[type="submit"]:focus,
    .account-box button.btn-primary:focus,
    .account-box input[type="submit"]:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(14, 58, 93, 0.2);
    }

    .change-link {
      color: #0e3a5d;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .change-link:hover {
      text-decoration: underline;
      color: #1b678f;
    }

    @media (max-width: 1100px) {
      .personal-info-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .personal-info-grid .wide {
        grid-column: span 2;
      }
    }

    @media (max-width: 900px) {
      .custom-input {
        width: 100%;
        max-width: none;
      }

      .custom-input-address {
        width: 100%;
      }

      .birthdate-row {
        flex-direction: column;
        align-items: stretch;
      }

      .birthdate-row select,
      .birthdate-row input[type="text"] {
        width: 100%;
        max-width: none;
      }

      .account-row {
        flex-direction: column;
        align-items: flex-start;
      }

      .account-row label {
        margin-bottom: 6px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 18px;
      }

      h2 {
        font-size: 1.6rem;
      }

      .account-box {
        padding: 20px;
      }

      .personal-info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .personal-info-grid .wide {
        grid-column: span 1;
      }

      .account-row {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
      }

      .account-row label {
        min-width: auto;
      }

      .account-row span {
        min-width: auto;
      }

      .custom-input,
      .custom-input-address {
        width: 100% !important;
        max-width: none !important;
      }

      .birthdate-row select,
      .birthdate-row input[type="text"] {
        width: 100% !important;
        max-width: none !important;
      }

      .account-box button[type="submit"],
      .account-box button.btn-primary,
      .account-box input[type="submit"] {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
       <nav class="nav">
      <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
      <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library Icon" /><span>Library</span></a>
      <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books Icon" /><span>Borrowed Books</span></a>
      <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
      <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
      <a href="setting.php" class="active"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
    </nav>
      <div class="sign-out">
        <a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>
    <main class="main-content">
      <?php if (!empty($success)): ?>
        <div class="success-message">✓ <?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <h2>Account Settings</h2>
      <p>Manage your personal information and account preferences</p>
      
      <div class="account-box">
        <!-- Account Information Form -->
        <form action="" method="POST" onsubmit="return confirmFormSubmit(event, 'Do you want to update your account information?')">
          <h3>Account Information</h3>
          <div class="account-row">
            <label><strong>Email:</strong></label>
            <span><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></span>
          </div>
          <div class="account-row">
            <label><strong>Full Name:</strong></label>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="text" name="fullname"
              class="custom-input <?php echo isset($errors['account']['fullname']) ? 'error-field' : ''; ?>"
              value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>">
            <?php if (isset($errors['account']['fullname'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['account']['fullname']); ?></span>
            <?php endif; ?>
          </div>
          <div class="account-row">
            <label><strong>Password:</strong></label>
            <input class="custom-input <?php echo isset($errors['account']['password']) ? 'error-field' : ''; ?>" type="password" name="password" placeholder="Leave empty to keep current">
            <?php if (isset($errors['account']['password'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['account']['password']); ?></span>
            <?php endif; ?>
          </div>
          <button type="submit" name="update_account" class="btn btn-primary">Update Account</button>
        </form>
        <!-- Personal Information Form -->
        <form action="" method="POST" onsubmit="return confirmFormSubmit(event, 'Do you want to save your personal information?')">
          <h3>Personal Information</h3>
          <div class="account-row birthdate-row">
            <label><strong>Birth Date:</strong></label>
            <select name="birth_month" class="<?php echo isset($errors['info']['birth_date']) ? 'error-field' : ''; ?>">
              <option value="">MM</option>
              <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($birth_month == $i) ? 'selected' : ''; ?>>
                  <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                </option>
              <?php endfor; ?>
            </select>
            <select name="birth_day" class="<?php echo isset($errors['info']['birth_date']) ? 'error-field' : ''; ?>">
              <option value="">DD</option>
              <?php for ($i = 1; $i <= 31; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($birth_day == $i) ? 'selected' : ''; ?>>
                  <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                </option>
              <?php endfor; ?>
            </select>
            <input type="text" name="birth_year" placeholder="YYYY"
              class="<?php echo isset($errors['info']['birth_date']) ? 'error-field' : ''; ?>"
              value="<?php echo htmlspecialchars($birth_year); ?>"
              maxlength="4" pattern="\d{4}" inputmode="numeric"
              oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);">
            <?php if (isset($errors['info']['birth_date'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['info']['birth_date']); ?></span>
            <?php endif; ?>
          </div>
          <div class="personal-info-grid">
            <div>
              <label for="first_name">First Name:</label>
              <input type="text" id="first_name" name="first_name" placeholder="First Name"
                class="<?php echo isset($errors['info']['first_name']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['first_name'] ?? ''); ?>">
              <?php if (isset($errors['info']['first_name'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['first_name']); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <label for="last_name">Last Name:</label>
              <input type="text" id="last_name" name="last_name" placeholder="Last Name"
                class="<?php echo isset($errors['info']['last_name']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['last_name'] ?? ''); ?>">
              <?php if (isset($errors['info']['last_name'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['last_name']); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <label for="age">Age:</label>
              <input type="text" id="age" name="age" placeholder="Age"
                class="<?php echo isset($errors['info']['age']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['age'] ?? ''); ?>"
                inputmode="numeric" pattern="\d*"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);">
              <?php if (isset($errors['info']['age'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['age']); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <label for="contact">Contact Number:</label>
              <input type="text" id="contact" name="contact" placeholder="Phone Number"
                class="<?php echo isset($errors['info']['contact']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['contact'] ?? ''); ?>"
                maxlength="11" inputmode="numeric" pattern="\d{10,11}"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
              <?php if (isset($errors['info']['contact'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['contact']); ?></span>
              <?php endif; ?>
            </div>
            <div class="wide">
              <label for="address">Address:</label>
              <input type="text" id="address" name="address" placeholder="Address"
                class="custom-input-address <?php echo isset($errors['info']['address']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['address'] ?? ''); ?>">
              <?php if (isset($errors['info']['address'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['address']); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <label for="gender">Gender:</label>
              <?php if (!empty($admin_info_data['gender'])): ?>
                <input type="text" id="gender" name="gender" class="custom-input" readonly
                  value="<?php echo htmlspecialchars($admin_info_data['gender']); ?>">
              <?php else: ?>
                <select id="gender" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              <?php endif; ?>
            </div>
          </div>
          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
          <button type="submit" name="update_info" class="btn btn-primary">Update Personal Information</button>
        </form>
      </div>
    </main>
  </div>

  <!-- SweetAlert2 CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }

    function confirmFormSubmit(event, message) {
      event.preventDefault();
      const form = event.currentTarget;
      Swal.fire({
        title: 'Confirm',
        text: message,
        icon: 'question',
        iconColor: '#0e3a5d',
        showCancelButton: true,
        confirmButtonColor: '#0e3a5d',
        cancelButtonColor: '#e8eff7',
        confirmButtonText: 'Yes, continue',
        cancelButtonText: 'Cancel',
        customClass: {
          cancelButton: 'swal-secondary-btn'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
      return false;
    }
  </script>

  <?php if (!empty($success)): ?>
    <script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: <?php echo json_encode($success); ?>,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    </script>
  <?php endif; ?>

  <?php if (!empty($errors['account']['database'])): ?>
    <script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: <?php echo json_encode($errors['account']['database']); ?>,
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
      });
    </script>
  <?php endif; ?>

  <?php if (!empty($errors['info']['database'])): ?>
    <script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: <?php echo json_encode($errors['info']['database']); ?>,
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
      });
    </script>
  <?php endif; ?>
</body>
</html>
