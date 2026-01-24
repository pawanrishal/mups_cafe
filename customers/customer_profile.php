<?php
session_start();
require_once '../config/dbconfig.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user information
$user_query = "SELECT * FROM users WHERE users_id = ?";
$user_stmt = $conn->prepare($user_query);
if($user_stmt) {
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
} else {
    $user = null;
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    
    // Validation
    if(empty($username)) {
        $error_msg = "Username cannot be empty";
    } elseif(empty($fullname)) {
        $error_msg = "Full name cannot be empty";
    } elseif(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address";
    } else {
        // Check if email already exists (for other users)
        $email_check = "SELECT users_id FROM users WHERE email = ? AND users_id != ?";
        $email_stmt = $conn->prepare($email_check);
        if($email_stmt) {
            $email_stmt->bind_param("si", $email, $user_id);
            $email_stmt->execute();
            if($email_stmt->get_result()->num_rows > 0) {
                $error_msg = "Email already in use by another account";
            } else {
                // Update profile
                $update_query = "UPDATE users SET username = ?, email = ?, fullname = ? WHERE users_id = ?";
                $update_stmt = $conn->prepare($update_query);
                if($update_stmt) {
                    $update_stmt->bind_param("sssi", $username, $email, $fullname, $user_id);
                    if($update_stmt->execute()) {
                        $_SESSION['username'] = $username;
                        $success_msg = "Profile updated successfully!";
                        $user['username'] = $username;
                        $user['email'] = $email;
                        $user['fullname'] = $fullname;
                    } else {
                        $error_msg = "Failed to update profile. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $error_msg = "Database error. Please try again.";
                }
            }
            $email_stmt->close();
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if(empty($current_password)) {
        $error_msg = "Current password is required";
    } elseif(empty($new_password) || empty($confirm_password)) {
        $error_msg = "New password and confirmation are required";
    } elseif(strlen($new_password) < 8) {
        $error_msg = "New password must be at least 6 characters";
    } elseif($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match";
    } else {
        // Verify current password
        if(password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_query = "UPDATE users SET password = ? WHERE users_id = ?";
            $pwd_stmt = $conn->prepare($pwd_query);
            if($pwd_stmt) {
                $pwd_stmt->bind_param("si", $hashed_password, $user_id);
                if($pwd_stmt->execute()) {
                    $success_msg = "Password changed successfully!";
                } else {
                    $error_msg = "Failed to change password. Please try again.";
                }
                $pwd_stmt->close();
            }
        } else {
            $error_msg = "Current password is incorrect";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/customer_profile.css">
    <title>My Profile - Mups Cafe</title>

</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>👤 My Profile</h1>
            <a href="customer_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <?php if($success_msg): ?>
            <div class="success-message">✓ <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="error-message">✗ <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if($user): ?>
            <!-- Profile Overview -->
            <div class="profile-section">
                <div class="user-avatar">☕</div>
                <h2 style="color: #8b4513; margin-bottom: 10px;"><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <p style="color: #666; margin-bottom: 20px;">@<?php echo htmlspecialchars($user['username']); ?></p>

                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Status:</span>
                        <span class="info-value" style="color: #28a745; font-weight: 600;">Active</span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Section -->
            <div class="profile-section">
                <h3 class="section-title">Edit Profile Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        <button type="reset" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="profile-section">
                <h3 class="section-title">Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-input-group">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                        <button type="reset" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="profile-section">
                <p>Unable to load profile information. Please try again later.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../Partials/footer.php'; ?>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = event.target.closest('.password-toggle');
            
            if (field.type === 'password') {
                field.type = 'text';
                button.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                field.type = 'password';
                button.innerHTML = '<i class="bi bi-eye"></i>';
            }
        }
    </script>
</body>
</html>
