<?php
session_start();

// Nếu đã đăng nhập thì chuyển thẳng vào trang chính
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php'; // Sử dụng file kết nối chung

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Sử dụng password_verify để kiểm tra mật khẩu hash
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id']; // THÊM DÒNG NÀY
            $_SESSION['username'] = $row['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Tên đăng nhập không tồn tại!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <h2>Đăng nhập</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label>Tên đăng nhập:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="auth-btn">Đăng nhập</button>
        </form>
        
        <div class="auth-footer">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
    </div>
</body>
</html>