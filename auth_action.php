<?php
session_start();
include_once("connectdb.php");
$act = $_GET['act'] ?? '';

if ($act == 'register') {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$password')";
    if (mysqli_query($conn, $sql)) {
        header("Location: login.php?msg=success");
    }
}

if ($act == 'login') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $res = mysqli_query($conn, $sql);
    $user = mysqli_fetch_array($res);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        header("Location: index.php");
    } else {
        header("Location: login.php?error=invalid");
    }
}
?>