<?php
include 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $check = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        echo "Tài khoản đã tồn tại.";
    } else {
        $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashedPassword')";
        if ($conn->query($sql)) {
            header("Location: login.php");
        } else {
            echo "Lỗi tạo tài khoản.";
        }
    }
}
?>

<form method="POST">
    <input name="username" required placeholder="Tên đăng nhập">
    <input name="password" type="password" required placeholder="Mật khẩu">
    <button type="submit">Đăng ký</button>
</form>