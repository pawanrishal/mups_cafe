<?php
include "../config/dbconfig.php";
session_start();

if (!$conn) {
    die('Database connection error. Please contact the administrator.');
}

$fullname = "";
$username = "";
$email = "";
$error = "";
$success = "";
$showLogin = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmpassword'] ?? '';
 
    if (empty($fullname) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill all required fields.';
    } elseif (strlen($fullname) < 3) {
        $error = 'Full name must be at least 3 characters long.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/^[A-Z]/', $password)) {
        $error = 'Password must start with a capital letter.';
    } elseif (!preg_match('/\d/', $password)) {
        $error = 'Password must include at least one number.';
    } elseif (!preg_match('/[^\da-zA-Z]/', $password)) {
        $error = 'Password must include at least one special character.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check_username_sql = 'SELECT users_id FROM users WHERE username = ?';
        $check_stmt = $conn->prepare($check_username_sql);

        if (!$check_stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $check_stmt->bind_param('s', $username);
            $check_stmt->execute();
            $username_result = $check_stmt->get_result();

            if ($username_result && $username_result->num_rows > 0) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                $check_email_sql = 'SELECT users_id FROM users WHERE email = ?';
                $check_email_stmt = $conn->prepare($check_email_sql);

                if (!$check_email_stmt) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $check_email_stmt->bind_param('s', $email);
                    $check_email_stmt->execute();
                    $email_result = $check_email_stmt->get_result();

                    if ($email_result && $email_result->num_rows > 0) {
                        $error = 'Email already registered. Please use another email or login.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $insert_sql = 'INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)';
                        $insert_stmt = $conn->prepare($insert_sql);
                        if (!$insert_stmt) {
                            $error = 'Database error: ' . $conn->error;
                        } else {
                            $insert_stmt->bind_param('ssss', $fullname, $username, $email, $hashed_password);
                            if ($insert_stmt->execute()) {
                                // Keep user on this page briefly to show the animation,
                                // then the JS will reveal the login panel and optionally redirect.
                                $success = 'Registration successful! Please login.';
                                $showLogin = true;
                            } else {
                                $error = 'Registration failed. Please try again.';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUPS CAFE | Registration</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-panels">
            <div class="container register-panel">
                <form method="POST">
                    <fieldset>
                        <legend>Registration</legend>
                
                <!-- Error Message -->
                <?php if(!empty($error)): ?>
                    <div class="error-message show">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if(!empty($success)): ?>
                    <div class="success-message show">
                        <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?php echo htmlspecialchars($fullname); ?>" 
                        placeholder="Enter your full name"
                        required
                    >
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($username); ?>" 
                        placeholder="Choose a username"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>" 
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirmpassword">Confirm Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="confirmpassword" 
                            name="confirmpassword" 
                            placeholder="Re-enter your password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmpassword')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit">Register</button>
            </fieldset>
                </form>
                <div class="register-link">
                     Already have an account? <a href="../customers/login.php">Login</a>
               </div>
            </div>

            
        </div>
    </div>

    <!-- include animation script -->
    <script src="../assets/js/register.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleButton = event.target.closest('.toggle-password');
            const eyeIcon = toggleButton.querySelector('.eye-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.textContent = '👁️‍🗨️';
            } else {
                passwordField.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }

        // Initialize from server-side state
        const REGISTER_SHOW_LOGIN = <?php echo $showLogin ? 'true' : 'false'; ?>;
        const REGISTER_SUCCESS_MSG = <?php echo json_encode($success); ?>;
        if (REGISTER_SHOW_LOGIN) {
            // Trigger the animation after a tiny delay so the page paints
            window.addEventListener('load', () => {
                setTimeout(() => {
                    showLoginPanel();
                    // after the slide completes, redirect to the real login page
                    setTimeout(() => {
                        window.location.href = '/mups-cafe/customers/login.php';
                    }, 800); // matches the CSS transition (600ms) with small buffer
                }, 200);
            });
        }
    </script>
</body>
</html>