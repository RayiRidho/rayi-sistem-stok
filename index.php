<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $stored_password);
        $stmt->fetch();
        if ($password === $stored_password) {
            $_SESSION['admin_id'] = $id;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'  rel='stylesheet'>
</head>

<body>
    <div class="wrapper">
        <form action="" method="POST">
            <h1>Login</h1>

            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 10px;"><?= $error ?></div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="remember-forgot">
                <label><input type="checkbox"> Remember me</label>
                <a href="#">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
    </div>  
</body>
</html>