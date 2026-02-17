<?php
$host = "localhost";
$user = "Admin";
$pw = "Password"; 
$db = "superworlds"; // เปลี่ยนเป็นชื่อ DB ของคุณ

$conn = mysqli_connect($host, $user, $pw, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

?>
