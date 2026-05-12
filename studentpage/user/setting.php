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
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($fullname)) {
        $errors['account']['fullname'] = "Full name is required";
    }

    // Check if password is provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['account']['password'] = "Password must be at least 8 characters";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['account']['password'] = "Password must contain at least one uppercase letter and one number.";
        }
    }

    if (empty($errors['account'])) {
        try {
            // Build SQL
            $query = "UPDATE users SET fullname = ?";
            $params = [$fullname];
            $types = "s";

            // If password is valid and provided, hash and include it
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }

            $query .= " WHERE id = ?";
            $params[] = $user_id;
            $types .= "i";

            $stmt = mysqli_prepare($conn, $query);
            if ($stmt === false) {
                throw new Exception("MySQL prepare error: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $success = "Account information updated successfully!";
                $_SESSION['fullname'] = $fullname;
            } else {
                $success = "No changes made.";
            }

        } catch (Exception $e) {
            $errors['account']['database'] = "Error updating account: " . $e->getMessage();
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
  <title>Admin Setting</title>
  <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body,
    html {
      height: 100%;
    font-family: 'Poppins', sans-serif;
      background-color: #f9f7f4;
    }

    .container {
      display: flex;
      height: 100vh;
    }

    .sidebar {
      background-color: #0e3a5d;
      width: 250px;
      color: white;
      display: flex;
      flex-direction: column;
      transition: width 0.3s ease;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .sidebar.collapsed span {
      display: none;
    }

    .logo {
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      cursor: pointer;
      padding: 10px;
    }

    .logo img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
    }

    .nav {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      padding-top: 20px;
    }

    .nav a,
    .sign-out a {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      color: white;
      text-decoration: none;
      transition: background 0.2s;
    }

    .nav a:hover,
    .sign-out a:hover {
      background-color: #12476f;
    }

    .icon {
      width: 25px;
      height: 25px;
    }

    .nav span,
    .sign-out span {
      margin-left: 10px;
      white-space: nowrap;
    }

    .sign-out {
      margin-top: auto;
    }

    .header {
      position: fixed;
      top: 0;
      left: 250px;
      width: calc(100% - 250px);
      z-index: 1000;
      padding: 12px 20px;
      background-color: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #e0e0e0;
      height: 60px;
      box-sizing: border-box;
      transition: left 0.3s ease, width 0.3s ease;
    }

    .header-icons .icon {
      margin-left: 10px;
      cursor: pointer;
      width: 30px;
      height: 30px;
    }

    .main-content {
      flex: 1;
      padding: 60px 20px 20px;
      background-color: #f5f5f5;
      overflow-y: auto;
      transition: padding-left 0.3s ease;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 5px;
    }

    p {
      font-size: 14px;
      color: #333;
      margin-bottom: 20px;
    }

    .tabs {
      margin: 10px 0 20px;
    }

    .tabs button {
      background: #003d5c;
      color: white;
      padding: 10px 20px;
      border: none;
      margin-right: 10px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    .account-box {
      background: white;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ddd;
      margin-top: 10px;
    }

    .account-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      font-size: 16px;
    }

    .birthdate-row select,
    .birthdate-row input[type="text"] {
      padding: 5px;
      font-size: 14px;
      margin-left: 5px;
    }

    .change-link {
      color: #005b7f;
      text-decoration: none;
      font-weight: 500;
    }

    .change-link:hover {
      text-decoration: underline;
    }

    .account-box h3 {
      margin: 20px 0 10px;
      font-size: 18px;
    }

    .personal-info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-bottom: 10px;
    }

    .personal-info-grid .wide {
      grid-column: span 4;
    }

    .personal-info-grid input[type="text"] {
      padding: 8px;
      font-size: 14px;
      border: 1px solid #aaa;
      border-radius: 4px;
    }

    .error {
      color: #d9534f;
      font-size: 14px;
      margin-top: 5px;
    }

    .error-field {
      border: 1px solid #d9534f !important;
    }

    .success-message {
      color: #5cb85c;
      background-color: #f8f9fa;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      border: 1px solid #5cb85c;
    }

    .update-btn {
      background: #005b7f;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      margin-top: 10px;
    }

    .update-btn:hover {
      background: #00415a;
    }

    .custom-input {
      width: 300px;
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .custom-input:focus {
      border-color: #007bff;
      outline: none;
    }

    .custom-input-address {
      width: 900px;
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .custom-input-address:focus {
      border-color: #007bff;
      outline: none;
    }

    .error-field {
      border-color: red !important;
      background-color: #ffe6e6;
    }


    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        height: 100%;
        z-index: 1001;
        left: 0;
        top: 0;
      }

      .header {
        left: 70px;
        width: calc(100% - 70px);
      }

      .sidebar:not(.collapsed)~.main-content .header {
        left: 250px;
        width: calc(100% - 250px);
      }

      .main-content {
        padding-left: 70px;
      }

      .sidebar:not(.collapsed)~.main-content {
        padding-left: 250px;
      }

      .personal-info-grid {
        grid-template-columns: repeat(2, 1fr);
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
      <a href="Book-Details.php"><img class="icon" src="../Images/Details.png" alt="Details Icon" /><span>Book Details</span></a>
      <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track Icon" /><span>Track and Record</span></a>
      <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support Icon" /><span>Support Page</span></a>
      <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
    </nav>
      <div class="sign-out">
        <a href="../logout.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="setting.php"><img class="icon" src="../Images/profile.png"></a> 
        </div>
      </header>

      <h2>Account Settings</h2>
      <p>Manage your personal information and account preferences</p>

      <div class="account-box">
        <!-- Account Information Form -->
        <form action="" method="POST">
          <h3>Account Information</h3>
          <div class="account-row">
            <label><strong>Email:</strong></label>
            <span><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></span>
          </div>

          <div class="account-row">
            <label><strong>Full Name:</strong></label>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="text" name="fullname"
              class="custom-input"
              value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>"
              <?php echo isset($errors['account']['fullname']) ? 'class="error-field"' : ''; ?>>
            <?php if (isset($errors['account']['fullname'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['account']['fullname']); ?></span>
            <?php endif; ?>
          </div>

          <div class="account-row">
            <label><strong>Password:</strong></label>
            <input class="custom-input" type="password" name="password" placeholder="Leave empty to keep current"
              <?php echo isset($errors['account']['password']) ? 'class="error-field"' : ''; ?>>
            <?php if (isset($errors['account']['password'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['account']['password']); ?></span>
            <?php endif; ?>
          </div>



          <button type="submit" name="update_account" class="update-btn">Update Account</button>
        </form>

        <!-- Personal Information Form -->
        <form action="" method="POST">
          <h3>Personal Information</h3>

          <div class="account-row birthdate-row">
            <label><strong>Birth Date:</strong></label>

            <select name="birth_month" <?php echo isset($errors['info']['birth_date']) ? 'class="error-field"' : ''; ?>>
              <option value="">MM</option>
              <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($birth_month == $i) ? 'selected' : ''; ?>>
                  <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                </option>
              <?php endfor; ?>
            </select>

            <select name="birth_day" <?php echo isset($errors['info']['birth_date']) ? 'class="error-field"' : ''; ?>>
              <option value="">DD</option>
              <?php for ($i = 1; $i <= 31; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($birth_day == $i) ? 'selected' : ''; ?>>
                  <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                </option>
              <?php endfor; ?>
            </select>

            <input type="text" name="birth_year" placeholder="YYYY"
              value="<?php echo htmlspecialchars($birth_year); ?>"
              maxlength="4" pattern="\d{4}" inputmode="numeric"
              oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);"
              <?php echo isset($errors['info']['birth_date']) ? 'class="error-field"' : ''; ?>>


            <?php
            // PHP validation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              if (!ctype_digit($birth_year) || strlen($birth_year) !== 4) {
                $errors['info']['birth_date'] = "";
              } elseif ((int)$birth_year > ($current_year - 5)) {
                $errors['info']['birth_date'] = "Birth year must be at least 5 years before the current year.";
              }
            }

            if (isset($errors['info']['birth_date'])): ?>
              <span class="error"><?php echo htmlspecialchars($errors['info']['birth_date']); ?></span>
            <?php endif; ?>
          </div>

          <div class="personal-info-grid">

            <div>
              <label for="last_name">Last Name:</label><br>
              <input type="text" id="last_name" name="last_name" placeholder="Lastname"
                class="custom-input <?php echo isset($errors['info']['last_name']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['last_name'] ?? ''); ?>">
              <?php if (isset($errors['info']['last_name'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['last_name']); ?></span>
              <?php endif; ?>
            </div>

            <div>
              <label for="first_name">First Name:</label><br>
              <input type="text" id="first_name" name="first_name" placeholder="Firstname"
                class="custom-input <?php echo isset($errors['info']['first_name']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['first_name'] ?? ''); ?>">
              <?php if (isset($errors['info']['first_name'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['first_name']); ?></span>
              <?php endif; ?>
            </div>

            <div>
              <label for="age">Age:</label><br>
              <input type="text" id="age" name="age" placeholder="Age"
                class="custom-input <?php echo isset($errors['info']['age']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['age'] ?? ''); ?>">
              <?php if (isset($errors['info']['age'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['age']); ?></span>
              <?php endif; ?>
            </div>

            <div>
              <label for="contact">Contact Number:</label><br>
              <input type="text" id="contact" name="contact" placeholder="Contact"
                class="custom-input <?php echo isset($errors['info']['contact']) ? 'error-field' : ''; ?>"
                value="<?php echo htmlspecialchars($admin_info_data['contact'] ?? ''); ?>"
                maxlength="11" inputmode="numeric" pattern="\d{10,11}"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">

              <?php if (isset($errors['info']['contact'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['info']['contact']); ?></span>
              <?php endif; ?>
            </div>

            <div class="wide">
              <label for="address">Address:</label><br>
              <input type="text" id="address" name="address" placeholder="Address"
                class="custom-input-address"
                value="<?php echo htmlspecialchars($admin_info_data['address'] ?? ''); ?>">
            </div>

             <div>
              <label for="gender">Gender:</label><br>
              <?php if (!empty($admin_info_data['gender'])): ?>
                <input type="text" id="gender" name="gender" class="custom-input" readonly
                  value="<?php echo htmlspecialchars($admin_info_data['gender']); ?>">
              <?php else: ?>
                <select id="gender" name="gender" class="custom-input" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              <?php endif; ?>
            </div>

          </div>


          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
          <button type="submit" name="update_info" class="update-btn">Update Personal Information</button>
        </form>
      </div>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const header = document.querySelector('.header');
      sidebar.classList.toggle('collapsed');

      if (sidebar.classList.contains('collapsed')) {
        header.style.left = '70px';
        header.style.width = 'calc(100% - 70px)';
      } else {
        header.style.left = '250px';
        header.style.width = 'calc(100% - 250px)';
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.add('collapsed');
        const header = document.querySelector('.header');
        header.style.left = '70px';
        header.style.width = 'calc(100% - 70px)';
      }
    });
  </script>
  <!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

</body>

</html>