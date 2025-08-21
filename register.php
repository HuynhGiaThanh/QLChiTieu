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
<body class="auth-body">
    <div class="auth-container">
        <h2>Đăng ký</h2>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Tên đăng nhập:</label>
                <input type="text" name="username" required placeholder="Nhập tên đăng nhập">
            </div>
            
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu">
            </div>
            
            <button type="submit" class="auth-btn">Đăng ký</button>
        </form>

        <div class="auth-footer">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>
</body>
</html>