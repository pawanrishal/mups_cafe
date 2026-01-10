<?php
session_start();

// Protect admin pages
if(!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header('Location: ../customers/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> — you are logged in as admin.</p>
</body>
</html>