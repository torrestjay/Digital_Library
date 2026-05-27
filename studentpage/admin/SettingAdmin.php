<?php
session_start();
include('../dbcon.php');
include('security_utils.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$user_query) {
    $error = "Database error: " . $conn->error;
}
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_query->close();
}

if (!$user_data) {
    die("User data not found");
}

// Handle account information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validation
    if (empty($fullname)) {
        $error = 'Full name is required';
    } elseif (strlen($fullname) < 2) {
        $error = 'Full name must be at least 2 characters';
    } elseif (strlen($fullname) > 100) {
        $error = 'Full name cannot exceed 100 characters';
    } else {
        // Check password if provided
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif (strlen($password) > 255) {
                $error = 'Password is too long';
            }
        }
        
        if (empty($error)) {
            // Update account info
            if (!empty($password)) {
                // Password update requested
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, password = ?, password_changed_at = NOW() WHERE id = ?");
                if (!$update_stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $update_stmt->bind_param("ssi", $fullname, $hashed_password, $user_id);
                    if ($update_stmt->execute()) {
                        // Log the action
                        $old_data = json_encode(['fullname' => $user_data['fullname']]);
                        $new_data = json_encode(['fullname' => $fullname, 'password_changed' => true]);
                        logAdminAction($conn, $user_id, 'Update Account Info', 'account', $user_id, $old_data, $new_data);
                        
                        $message = 'Account information and password updated successfully!';
                        // Refresh user data
                        $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        if ($user_query) {
                            $user_query->bind_param("i", $user_id);
                            $user_query->execute();
                            $user_result = $user_query->get_result();
                            $user_data = $user_result->fetch_assoc();
                            $user_query->close();
                        }
                    } else {
                        $error = 'Failed to update account information: ' . $conn->error;
                    }
                    $update_stmt->close();
                }
            } else {
                // Just update name
                $update_stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
                if (!$update_stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $update_stmt->bind_param("si", $fullname, $user_id);
                    if ($update_stmt->execute()) {
                        // Log the action
                        $old_data = json_encode(['fullname' => $user_data['fullname']]);
                        $new_data = json_encode(['fullname' => $fullname]);
                        logAdminAction($conn, $user_id, 'Update Full Name', 'account', $user_id, $old_data, $new_data);
                        
                        $message = 'Full name updated successfully!';
                        // Refresh user data
                        $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        if ($user_query) {
                            $user_query->bind_param("i", $user_id);
                            $user_query->execute();
                            $user_result = $user_query->get_result();
                            $user_data = $user_result->fetch_assoc();
                            $user_query->close();
                        }
                    } else {
                        $error = 'Failed to update account information: ' . $conn->error;
                    }
                    $update_stmt->close();
                }
            }
        }
    }
}

// Handle personal information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_personal'])) {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    // Validation
    $validation_errors = [];
    
    if (!empty($phone)) {
        // Remove non-digit characters for validation
        $phone_digits = preg_replace('/[^\d]/', '', $phone);
        if (strlen($phone_digits) < 10 || strlen($phone_digits) > 15) {
            $validation_errors[] = 'Phone number must be 10-15 digits';
        }
    }
    
    if (!empty($address) && strlen($address) > 500) {
        $validation_errors[] = 'Address cannot exceed 500 characters';
    }
    
    if (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other', ''])) {
        $validation_errors[] = 'Invalid gender selection';
    }
    
    if (!empty($validation_errors)) {
        $error = implode('; ', $validation_errors);
    } else {
        // Prepare old data for logging
        $old_data = json_encode([
            'phone' => $user_data['phone'] ?? '',
            'address' => $user_data['address'] ?? '',
            'gender' => $user_data['gender'] ?? ''
        ]);
        
        $new_data = json_encode([
            'phone' => $phone,
            'address' => $address,
            'gender' => $gender
        ]);
        
        $update_stmt = $conn->prepare("UPDATE users SET phone = ?, address = ?, gender = ? WHERE id = ?");
        if (!$update_stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $update_stmt->bind_param("sssi", $phone, $address, $gender, $user_id);
            if ($update_stmt->execute()) {
                // Log the action
                logAdminAction($conn, $user_id, 'Update Personal Info', 'account', $user_id, $old_data, $new_data);
                
                $message = 'Personal information updated successfully!';
                // Refresh user data
                $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
                if ($user_query) {
                    $user_query->bind_param("i", $user_id);
                    $user_query->execute();
                    $user_result = $user_query->get_result();
                    $user_data = $user_result->fetch_assoc();
                    $user_query->close();
                }
            } else {
                $error = 'Failed to update personal information: ' . $conn->error;
            }
            $update_stmt->close();
        }
    }
}

// Handle MFA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_mfa'])) {
    $action = $_POST['toggle_mfa'];
    if (!in_array($action, ['enable', 'disable'])) {
        $error = 'Invalid MFA action';
    } else {
        $enable = ($action === 'enable') ? 1 : 0;
        $old_mfa = $user_data['mfa_enabled'];
        
        // Update MFA setting
        $update = $conn->prepare("UPDATE users SET mfa_enabled = ? WHERE id = ?");
        if (!$update) {
            $error = "Database error: " . $conn->error;
        } else {
            $update->bind_param("ii", $enable, $user_id);
            if ($update->execute()) {
                // Log the action
                $action_str = $enable ? 'Enable' : 'Disable';
                $old_data = json_encode(['mfa_enabled' => (bool)$old_mfa]);
                $new_data = json_encode(['mfa_enabled' => (bool)$enable]);
                logAdminAction($conn, $user_id, "$action_str MFA", 'security', $user_id, $old_data, $new_data);
                
                $message = "Multi-Factor Authentication has been " . ($enable ? 'enabled' : 'disabled');
                
                // Refresh user data
                $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
                if ($user_query) {
                    $user_query->bind_param("i", $user_id);
                    $user_query->execute();
                    $user_result = $user_query->get_result();
                    $user_data = $user_result->fetch_assoc();
                    $user_query->close();
                }
            } else {
                $error = 'Failed to update MFA setting: ' . $conn->error;
            }
            $update->close();
        }
    }
}

// Get user role safely
$user_role = $user_data['admin_role'] ?? 'admin';

// Check for vulnerability issues specific to account settings
$security_issues = [];

// Check if password hasn't been changed in 90 days
if ($user_data['password_changed_at']) {
    $last_change = strtotime($user_data['password_changed_at']);
    $days_ago = floor((time() - $last_change) / (60 * 60 * 24));
    if ($days_ago > 90) {
        $security_issues[] = "Your password hasn't been changed in $days_ago days. Consider updating it.";
    }
} else {
    $security_issues[] = "Your password has never been changed. Consider setting a strong password.";
}

// Check if MFA is disabled
if (!$user_data['mfa_enabled']) {
    $security_issues[] = "Multi-Factor Authentication is disabled. Enable it for enhanced security.";
}

// Check account lockout status
if ($user_data['account_locked']) {
    $security_issues[] = "Your account is currently locked. Contact an administrator.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Account Settings - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    .settings-section {
      background: white;
      padding: 30px;
      margin-bottom: 24px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .settings-section-title {
      font-size: 18px;
      font-weight: 600;
      color: #0e3a5d;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #2c3e50;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #bdc3c7;
      border-radius: 6px;
      font-family: Poppins, sans-serif;
      font-size: 14px;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #2196F3;
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .form-group input:disabled,
    .form-group select:disabled {
      background-color: #f5f5f5;
      color: #999;
      cursor: not-allowed;
    }
    
    .form-group small {
      color: #7f8c8d;
      margin-top: 4px;
      display: block;
    }
    
    .info-display {
      padding: 12px;
      background: #f8fbff;
      border-left: 4px solid #2196F3;
      border-radius: 4px;
      margin-bottom: 16px;
    }
    
    .info-display .label {
      font-weight: 600;
      color: #0e3a5d;
      font-size: 12px;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    
    .info-display .value {
      color: #2c3e50;
      font-size: 14px;
    }
    
    .buttons {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }
    
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-family: Poppins, sans-serif;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    
    .btn-primary {
      background-color: #2196F3;
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      background-color: #1976D2;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }
    
    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .security-warning {
      background: #fff3e0;
      border-left: 4px solid #FF9800;
      padding: 16px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .security-warning .title {
      color: #FF9800;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .security-issue-item {
      font-size: 13px;
      margin: 8px 0;
      color: #F57C00;
    }
    
    .toggle-switch {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 26px;
    }
    
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 26px;
    }
    
    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }
    
    input:checked + .slider {
      background-color: #4CAF50;
    }
    
    input:checked + .slider:before {
      transform: translateX(24px);
    }
    
    .role-badge {
      display: inline-block;
      padding: 6px 12px;
      background: #2196F3;
      color: white;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .role-badge.super_admin {
      background: #F44336;
    }
    
    @media (max-width: 768px) {
      .settings-section {
        padding: 20px;
      }
      
      .buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar Behavior Script -->
  <script src="includes/sidebar-behavior.js"></script>
  
  <div class="container">
    <!-- Standardized Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <section class="content-section">
        <h2 class="section-title"><i class="fas fa-cog" style="margin-right: 12px;"></i>Account Settings</h2>
        
        <?php if (!empty($security_issues)): ?>
          <div class="security-warning">
            <div class="title"><i class="fas fa-shield-alt"></i> Security Recommendations</div>
            <?php foreach ($security_issues as $issue): ?>
              <div class="security-issue-item">• <?php echo htmlspecialchars($issue); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <!-- Account Information Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-user-circle"></i> Account Information
          </h3>
          
          <div class="info-display">
            <div class="label">Email Address</div>
            <div class="value"><?php echo htmlspecialchars($user_data['email']); ?></div>
          </div>
          
          <div class="info-display">
            <div class="label">Admin Role</div>
            <div class="value">
              <span class="role-badge <?php echo htmlspecialchars(str_replace('_', '-', $user_role)); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $user_role)); ?>
              </span>
            </div>
          </div>
          
          <form method="POST" id="accountForm">
            <div class="form-group">
              <label for="fullname">Full Name *</label>
              <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>" required minlength="2" maxlength="100">
              <small>Your display name (2-100 characters)</small>
            </div>
            
            <div class="form-group">
              <label for="password">New Password</label>
              <input type="password" id="password" name="password" placeholder="Leave blank to keep current password" minlength="6" maxlength="255">
              <small>Leave blank if you don't want to change your password. Must be 6+ characters if changing.</small>
            </div>
            
            <div class="buttons">
              <button type="submit" name="update_account" value="1" class="btn btn-primary">
                <i class="fas fa-save"></i>Save Account Changes
              </button>
            </div>
          </form>
        </div>
        
        <!-- Personal Information Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-address-card"></i> Personal Information
          </h3>
          
          <form method="POST" id="personalForm">
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="1234567890" maxlength="20">
              <small>Optional. 10-15 digits (spaces and dashes allowed)</small>
            </div>
            
            <div class="form-group">
              <label for="gender">Gender</label>
              <select id="gender" name="gender">
                <option value="">Select Gender</option>
                <option value="Male" <?php echo ($user_data['gender'] === 'Male' ? 'selected' : ''); ?>>Male</option>
                <option value="Female" <?php echo ($user_data['gender'] === 'Female' ? 'selected' : ''); ?>>Female</option>
                <option value="Other" <?php echo ($user_data['gender'] === 'Other' ? 'selected' : ''); ?>>Other</option>
              </select>
              <small>Optional. Used for profile personalization</small>
            </div>
            
            <div class="form-group">
              <label for="address">Address</label>
              <textarea id="address" name="address" rows="3" placeholder="Enter your address" maxlength="500"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
              <small>Optional. Maximum 500 characters</small>
            </div>
            
            <div class="buttons">
              <button type="submit" name="update_personal" value="1" class="btn btn-primary">
                <i class="fas fa-save"></i>Save Personal Info
              </button>
            </div>
          </form>
        </div>
        
        <!-- Security Settings Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-shield-alt"></i> Security Settings
          </h3>
          
          <div class="form-group">
            <label>Multi-Factor Authentication (MFA)</label>
            <div class="toggle-switch">
              <label class="switch">
                <input type="checkbox" id="mfaToggle" <?php echo ($user_data['mfa_enabled'] ? 'checked' : ''); ?> onchange="handleMFAToggle()">
                <span class="slider"></span>
              </label>
              <span id="mfaStatus" style="color: #2c3e50; font-weight: 500;">
                MFA is currently <strong><?php echo ($user_data['mfa_enabled'] ? 'ENABLED' : 'DISABLED'); ?></strong>
              </span>
            </div>
            <small>Multi-Factor Authentication adds an extra layer of security to your account by requiring a second verification method.</small>
          </div>
          
          <div class="info-display">
            <div class="label">Last Password Change</div>
            <div class="value">
              <?php 
              if ($user_data['password_changed_at']) {
                echo date('M d, Y \a\t h:i A', strtotime($user_data['password_changed_at']));
              } else {
                echo "Never changed since account creation";
              }
              ?>
            </div>
          </div>
          
          <div class="info-display">
            <div class="label">Last Login</div>
            <div class="value">
              <?php 
              if ($user_data['last_login']) {
                echo date('M d, Y \a\t h:i A', strtotime($user_data['last_login']));
              } else {
                echo "No login recorded";
              }
              ?>
            </div>
          </div>
          
          <div class="info-display">
            <div class="label">Account Status</div>
            <div class="value">
              <?php echo $user_data['account_locked'] ? '<span style="color: #F44336;">LOCKED</span>' : '<span style="color: #4CAF50;">ACTIVE</span>'; ?>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <form id="mfaForm" method="POST" style="display: none;">
    <input type="hidden" name="toggle_mfa" id="mfaAction" value="">
  </form>

  <script>
    // Handle MFA toggle with confirmation
    function handleMFAToggle() {
      const checkbox = document.getElementById('mfaToggle');
      const action = checkbox.checked ? 'enable' : 'disable';
      const actionCapitalized = action.charAt(0).toUpperCase() + action.slice(1);
      
      Swal.fire({
        title: actionCapitalized + ' MFA?',
        text: 'Multi-Factor Authentication will be ' + action + 'd. This change will take effect immediately.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, ' + actionCapitalized + ' MFA',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update your MFA settings.',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          document.getElementById('mfaAction').value = action;
          document.getElementById('mfaForm').submit();
        } else {
          // Reset checkbox if cancelled
          checkbox.checked = !checkbox.checked;
        }
      });
    }
    
    // Account Form Validation and Submission
    document.getElementById('accountForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const fullname = document.getElementById('fullname').value.trim();
      const password = document.getElementById('password').value.trim();
      
      // Client-side validation
      let validation_errors = [];
      
      if (!fullname) {
        validation_errors.push('Full name is required');
      } else if (fullname.length < 2) {
        validation_errors.push('Full name must be at least 2 characters');
      } else if (fullname.length > 100) {
        validation_errors.push('Full name cannot exceed 100 characters');
      }
      
      if (password && password.length < 6) {
        validation_errors.push('Password must be at least 6 characters');
      }
      
      if (validation_errors.length > 0) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: '<ul style="text-align: left; margin: 10px 0;">' + 
                validation_errors.map(e => '<li>' + e + '</li>').join('') + 
                '</ul>',
          confirmButtonColor: '#2196F3'
        });
        return;
      }
      
      // Show confirmation
      Swal.fire({
        title: 'Save Changes?',
        text: password ? 'Your full name and password will be updated.' : 'Your full name will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your changes.',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Submit the form
          this.submit();
        }
      });
    });
    
    // Personal Form Validation and Submission
    document.getElementById('personalForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const phone = document.getElementById('phone').value.trim();
      const address = document.getElementById('address').value.trim();
      const gender = document.getElementById('gender').value.trim();
      
      // Client-side validation
      let validation_errors = [];
      
      if (phone) {
        const phoneDigits = phone.replace(/[^\d]/g, '');
        if (phoneDigits.length < 10 || phoneDigits.length > 15) {
          validation_errors.push('Phone number must be 10-15 digits');
        }
      }
      
      if (address && address.length > 500) {
        validation_errors.push('Address cannot exceed 500 characters');
      }
      
      if (gender && !['Male', 'Female', 'Other'].includes(gender)) {
        validation_errors.push('Invalid gender selection');
      }
      
      if (validation_errors.length > 0) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: '<ul style="text-align: left; margin: 10px 0;">' + 
                validation_errors.map(e => '<li>' + e + '</li>').join('') + 
                '</ul>',
          confirmButtonColor: '#2196F3'
        });
        return;
      }
      
      // Show confirmation
      Swal.fire({
        title: 'Save Changes?',
        text: 'Your personal information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your changes.',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Submit the form
          this.submit();
        }
      });
    });
    
    // Show success/error messages from PHP
    <?php if (!empty($message)): ?>
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: '<?php echo addslashes($message); ?>',
      confirmButtonColor: '#2196F3',
      allowOutsideClick: false
    });
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?php echo addslashes($error); ?>',
      confirmButtonColor: '#2196F3',
      allowOutsideClick: false
    });
    <?php endif; ?>
  </script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn->close();
}
?>

    $errors = [];
    if (!empty($phone) && !preg_match('/^\d{10,15}$/', preg_replace('/[^\d]/', '', $phone))) {
        $errors[] = 'Phone number must be 10-15 digits';
    }
    
    if (empty($errors)) {
        $old_data = [
            'phone' => $user_data['phone'] ?? '',
            'address' => $user_data['address'] ?? '',
            'gender' => $user_data['gender'] ?? ''
        ];
        
        $new_data = [
            'phone' => $phone,
            'address' => $address,
            'gender' => $gender
        ];
        
        $update_stmt = $conn->prepare("UPDATE users SET phone = ?, address = ?, gender = ? WHERE id = ?");
        if (!$update_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $update_stmt->bind_param("sssi", $phone, $address, $gender, $user_id);
        if ($update_stmt->execute()) {
            logAdminAction($conn, 'Update Personal Info', 'account_settings', 'user', $user_id, null, $old_data, $new_data);
            $message = 'Personal information updated successfully!';
            // Refresh user data
            $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $user_query->bind_param("i", $user_id);
            $user_query->execute();
            $user_result = $user_query->get_result();
            $user_data = $user_result->fetch_assoc();
            $user_query->close();
        } else {
            $error = 'Failed to update personal information';
        }
        $update_stmt->close();
    } else {
        $error = implode(', ', $errors);
    }
}

// Handle MFA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_mfa'])) {
    $action = $_POST['toggle_mfa'] === 'enable' ? 'Enable' : 'Disable';
    $enable = $_POST['toggle_mfa'] === 'enable';
    
    $old_mfa = $user_data['mfa_enabled'];
    
    // Simple toggle if toggleMFA not available, or use function if available
    if (function_exists('toggleMFA')) {
        $result = toggleMFA($conn, $user_id, $enable);
    } else {
        // Fallback if security_utils not properly loaded
        $status = $enable ? 1 : 0;
        $update = $conn->prepare("UPDATE users SET mfa_enabled = ? WHERE id = ?");
        if (!$update) {
            die("Prepare failed: " . $conn->error);
        }
        if ($update) {
            $update->bind_param("ii", $status, $user_id);
            $result = $update->execute();
            $update->close();
        } else {
            $result = false;
        }
    }
    
    if ($result) {
        if (function_exists('logAdminAction')) {
            logAdminAction($conn, "MFA $action", 'security', 'user', $user_id, null, 
                         ['mfa_enabled' => $old_mfa], ['mfa_enabled' => $enable ? 1 : 0]);
        }
        
        $message = "Multi-Factor Authentication has been " . ($enable ? 'enabled' : 'disabled');
        // Refresh user data
        $user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
        if (!$user_query) {
            die("Prepare failed: " . $conn->error);
        }
        if ($user_query) {
            $user_query->bind_param("i", $user_id);
            $user_query->execute();
            $user_result = $user_query->get_result();
            $user_data = $user_result->fetch_assoc();
            $user_query->close();
        }
    } else {
        $error = 'Failed to update MFA setting';
    }
}

// Get user role and permissions
$user_role = 'admin'; // Default role
$permissions = [];

// Try to get role safely
if (class_exists('mysqli')) {
    $role_result = $conn->query("SHOW COLUMNS FROM users LIKE 'admin_role'");
    if ($role_result && $role_result->num_rows > 0) {
        $role_result2 = $conn->query("SELECT admin_role FROM users WHERE id = $user_id");
        if ($role_result2) {
            $role_data = $role_result2->fetch_assoc();
            if ($role_data && !empty($role_data['admin_role'])) {
                $user_role = $role_data['admin_role'];
            }
        }
    }
}

// Get vulnerability info for this user
$vulnerabilities = [];
if ($conn->query("SHOW TABLES LIKE 'vulnerability_report'")) {
    $vuln_result = $conn->query("SELECT * FROM vulnerability_report WHERE user_id = $user_id AND status = 'open' LIMIT 5");
    if ($vuln_result) {
        while ($vuln = $vuln_result->fetch_assoc()) {
            $vulnerabilities[] = $vuln;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Account Settings - Digital Library</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Design System & Utilities -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/3b07bc6295.js" crossorigin="anonymous"></script>
  
  <style>
    .settings-section {
      background: white;
      padding: 30px;
      margin-bottom: 24px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .settings-section-title {
      font-size: 18px;
      font-weight: 600;
      color: #0e3a5d;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #2c3e50;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #bdc3c7;
      border-radius: 6px;
      font-family: Poppins, sans-serif;
      font-size: 14px;
      transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #2196F3;
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .info-display {
      padding: 12px;
      background: #f8fbff;
      border-left: 4px solid #2196F3;
      border-radius: 4px;
      margin-bottom: 16px;
    }
    
    .info-display .label {
      font-weight: 600;
      color: #0e3a5d;
      font-size: 12px;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    
    .info-display .value {
      color: #2c3e50;
      font-size: 14px;
    }
    
    .buttons {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }
    
    .vulnerability-warning {
      background: #fff3e0;
      border-left: 4px solid #FF9800;
      padding: 16px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .vulnerability-warning .title {
      color: #FF9800;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .vulnerability-item {
      font-size: 13px;
      margin: 8px 0;
      color: #F57C00;
    }
    
    .toggle-switch {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 26px;
    }
    
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 26px;
    }
    
    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }
    
    input:checked + .slider {
      background-color: #4CAF50;
    }
    
    input:checked + .slider:before {
      transform: translateX(24px);
    }
    
    .role-badge {
      display: inline-block;
      padding: 6px 12px;
      background: #2196F3;
      color: white;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .role-badge.super_admin {
      background: #F44336;
    }
    
    @media (max-width: 768px) {
      .settings-section {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar Behavior Script -->
  <script src="includes/sidebar-behavior.js"></script>
  
  <div class="container">
    <!-- Standardized Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <section class="content-section">
        <h2 class="section-title"><i class="fas fa-cog" style="margin-right: 12px;"></i>Account Settings</h2>
        
        <?php if (!empty($vulnerabilities)): ?>
          <div class="vulnerability-warning">
            <div class="title"><i class="fas fa-exclamation-triangle"></i> Security Vulnerabilities Detected</div>
            <?php foreach ($vulnerabilities as $vuln): ?>
              <div class="vulnerability-item">
                • <?php echo htmlspecialchars($vuln['description']); ?> - <strong><?php echo htmlspecialchars($vuln['recommended_action']); ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <!-- Account Information Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-user-circle"></i> Account Information
          </h3>
          
          <div class="info-display">
            <div class="label">Email Address</div>
            <div class="value"><?php echo htmlspecialchars($user_data['email']); ?></div>
          </div>
          
          <div class="info-display">
            <div class="label">Admin Role</div>
            <div class="value">
              <span class="role-badge <?php echo htmlspecialchars($user_role); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $user_role)); ?>
              </span>
            </div>
          </div>
          
          <form method="POST" id="accountForm">
            <div class="form-group">
              <label for="fullname">Full Name *</label>
              <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="password">New Password (Leave blank to keep current)</label>
              <input type="password" id="password" name="password" placeholder="Enter new password (6+ characters)">
              <small style="color: #7f8c8d; margin-top: 4px; display: block;">If you enter a password, it will be updated.</small>
            </div>
            
            <div class="buttons">
              <button type="submit" name="update_account" class="btn btn-primary">
                <i class="fas fa-save" style="margin-right: 8px;"></i>Save Changes
              </button>
            </div>
          </form>
        </div>
        
        <!-- Personal Information Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-address-card"></i> Personal Information
          </h3>
          
          <form method="POST" id="personalForm">
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="1234567890">
            </div>
            
            <div class="form-group">
              <label for="gender">Gender</label>
              <select id="gender" name="gender">
                <option value="">Select Gender</option>
                <option value="Male" <?php echo ($user_data['gender'] === 'Male' ? 'selected' : ''); ?>>Male</option>
                <option value="Female" <?php echo ($user_data['gender'] === 'Female' ? 'selected' : ''); ?>>Female</option>
                <option value="Other" <?php echo ($user_data['gender'] === 'Other' ? 'selected' : ''); ?>>Other</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="address">Address</label>
              <textarea id="address" name="address" rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="buttons">
              <button type="submit" name="update_personal" class="btn btn-primary">
                <i class="fas fa-save" style="margin-right: 8px;"></i>Save Changes
              </button>
            </div>
          </form>
        </div>
        
        <!-- Security Settings Section -->
        <div class="settings-section">
          <h3 class="settings-section-title">
            <i class="fas fa-shield-alt"></i> Security Settings
          </h3>
          
          <div class="form-group">
            <label>Multi-Factor Authentication (MFA)</label>
            <div class="toggle-switch">
              <label class="switch">
                <input type="checkbox" id="mfaToggle" <?php echo ($user_data['mfa_enabled'] ? 'checked' : ''); ?> onchange="toggleMFA()">
                <span class="slider"></span>
              </label>
              <span id="mfaStatus" style="color: #2c3e50; font-weight: 500;">
                MFA is currently <strong><?php echo ($user_data['mfa_enabled'] ? 'ENABLED' : 'DISABLED'); ?></strong>
              </span>
            </div>
            <small style="color: #7f8c8d; margin-top: 12px; display: block;">
              Multi-Factor Authentication adds an extra layer of security to your account.
            </small>
          </div>
          
          <div class="info-display">
            <div class="label">Last Password Change</div>
            <div class="value">
              <?php 
              if ($user_data['password_changed_at']) {
                echo date('M d, Y \a\t h:i A', strtotime($user_data['password_changed_at']));
              } else {
                echo "Never changed since account creation";
              }
              ?>
            </div>
          </div>
          
          <div class="info-display">
            <div class="label">Last Login</div>
            <div class="value">
              <?php 
              if ($user_data['last_login']) {
                echo date('M d, Y \a\t h:i A', strtotime($user_data['last_login']));
              } else {
                echo "No login recorded";
              }
              ?>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <form id="mfaForm" method="POST" style="display: none;">
    <input type="hidden" name="toggle_mfa" id="mfaAction" value="">
  </form>

  <script>
    function toggleMFA() {
      const checkbox = document.getElementById('mfaToggle');
      const action = checkbox.checked ? 'enable' : 'disable';
      
      Swal.fire({
        title: 'Update MFA Setting?',
        text: `MFA will be ${action}d. This change will take effect immediately.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, ' + action + ' MFA',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('mfaAction').value = action;
          document.getElementById('mfaForm').submit();
        } else {
          checkbox.checked = !checkbox.checked;
        }
      });
    }
    
    document.getElementById('accountForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const fullname = document.getElementById('fullname').value.trim();
      
      if (!fullname) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please enter your full name'
        });
        return;
      }
      
      Swal.fire({
        title: 'Save Changes?',
        text: 'Your account information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });
    
    document.getElementById('personalForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      Swal.fire({
        title: 'Save Changes?',
        text: 'Your personal information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196F3',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });
    
    <?php if (!empty($message)): ?>
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: '<?php echo addslashes($message); ?>',
      confirmButtonColor: '#2196F3'
    });
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?php echo addslashes($error); ?>',
      confirmButtonColor: '#2196F3'
    });
    <?php endif; ?>
  </script>
</body>
</html>
<?php $conn->close(); ?>

