<?php
include 'config/db.php';

if($conn){
    echo "Database connected successfully!";
}else{
    echo "Database connection failed!";
}
?>

<?php
header("Location: auth/register.php"); // change this if your register filename is different
exit();
