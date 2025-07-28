<?php
include 'config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = $_POST['username'];
    $p = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username='$u'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
    } else {
        echo "Sai tài khoản hoặc mật khẩu.";
    }
}
?>

<form method="POST">
    <input name="username" required>
    <input name="password" type="password" required>
    <button type="submit">Đăng nhập</button>
    <p><a href="register.php">Chưa có tài khoản?</a></p>
</form>