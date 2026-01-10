<?php

$servername = "localhost";
$username = "root";
$password = "";
$db_name = "mups_cafe";

$conn = mysqli_connect($servername, $username, $password, $db_name);

if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    die('Database connection failed.');
}

/* ensure proper charset */
mysqli_set_charset($conn, 'utf8mb4');
