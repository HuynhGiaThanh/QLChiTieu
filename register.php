<?php
include 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Kiểm tra username đã tồn tại chưa
    $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Tài khoản đã tồn tại.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Sử dụng prepared statement
        $sql = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $sql->bind_param("ss", $username, $hashedPassword);
        
        if ($sql->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Lỗi tạo tài khoản.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Đăng ký</h2>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" required placeholder="Tên đăng nhập">
        <input type="password" name="password" required placeholder="Mật khẩu">
        <button type="submit">Đăng ký</button>
    </form>

    <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</body>
</html>