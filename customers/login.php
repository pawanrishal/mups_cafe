<?php
include "../config/dbconfig.php";

session_start();

$username = "";
$error = "";
$success = "";

if(isset($_GET['success'])){
    $success = htmlspecialchars($_GET['success']);
}

if(isset($_SESSION["customer_id"]) && !isset($_GET['force'])){
    header("Location: customer_dashboard.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";

    if(empty($username) || empty($password)){
        $error = "Please enter both username and password.";
    }
    else{
        $check_sql = "SELECT users_id, username, password, role FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if(!$check_stmt){
            $error = "Database error. Please try again later.";
        }
        else{
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if($result->num_rows > 0){
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION["customer_id"] = $user['users_id'];
                    $_SESSION["customer_name"] = $user['username'];
                    $_SESSION["role"] = $user['role'];

                    if (isset($_POST['remember'])) {
                        setcookie("username", $user['username'], time() + (30 * 24 * 60 * 60), "/");
                    }

                    if (isset($user['role']) && $user['role'] === 'admin') {
                        header("Location: ../admin/admin_dashboard.php");
                    } else {
                        header("Location: ../customers/customer_dashboard.php");
                    }
                    exit();
                } else {
                    // If password_verify failed, check whether the stored value is plaintext
                    if (isset($user['password']) && $user['password'] === $password) {
                        // Stored password appears to be plaintext. Upgrade to a secure hash and log the event.
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare('UPDATE users SET password = ? WHERE users_id = ?');
                        if ($update_stmt) {
                            $update_stmt->bind_param('si', $newHash, $user['users_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }

                        // Log the user in after upgrading the hash
                        $_SESSION["customer_id"] = $user['users_id'];
                        $_SESSION["customer_name"] = $user['username'];
                        $_SESSION["role"] = $user['role'];

                        if (isset($_POST['remember'])) {
                            setcookie("username", $user['username'], time() + (30 * 24 * 60 * 60), "/");
                        }

                        if (isset($user['role']) && $user['role'] === 'admin') {
                            header("Location: ../admin/admin_dashboard.php");
                        } else {
                            header("Location: ../customers/customer_dashboard.php");
                        }
                        exit();
                    }

                    // Otherwise log safe diagnostics for investigation
                    $stored = isset($user['password']) ? $user['password'] : '[missing]';
                    $prefix = substr($stored, 0, 6);
                    $submitted_len = strlen($password);
                    $stored_len = strlen($stored);
                    error_log("Login failed for user '{$username}': password_verify failed. stored_prefix={$prefix}, stored_len={$stored_len}, submitted_len={$submitted_len}");
                    $error = "Invalid password. Please try again.";
                }
            }
            else{
                $error = "Username not found. Please register or check your username.";
            }
        }
    }
}

if(isset($_COOKIE['username'])){
    $username = htmlspecialchars($_COOKIE['username']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUPS CAFE | Customer Login</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="container">
        <form method="POST">
            <fieldset>
                <legend>Customer Login</legend>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message show">
                        <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message show">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($username); ?>" 
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>
                

                <div class = "forget-box">
                    <div class="remember-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                     <a href="../auth/forget_password.php" class="forget">Forget Password?</a> 
                </div>  
               
                

                <button type="submit">Login</button>
            </fieldset>
        </form>

        <div class="register-link">
            Don't have an account? <a href="../auth/register.php">Register here</a>
        </div>
        
    </div>

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
    </script>
</body>
</html>
