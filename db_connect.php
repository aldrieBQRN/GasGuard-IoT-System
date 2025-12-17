<?php

// Database Connection Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gas_monitoring";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Set PHP Timezone (Fixes Email & Script times)
date_default_timezone_set('Asia/Manila'); 

// 2. Set MySQL Timezone (Fixes Database Insert times)
$conn->query("SET time_zone = '+08:00'");

// Set character set
$conn->set_charset("utf8");

?>

