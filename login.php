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
<body>
    <h2>Đăng nhập</h2>
    <?php if (!empty($error)) { ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php } ?>
    <form method="POST" action="">
        <label>Tên đăng nhập:</label><br>
        <input type="text" name="username" required><br>
        
        <label>Mật khẩu:</label><br>
        <input type="password" name="password" required><br>
        
        <input type="submit" value="Đăng nhập">
    </form>
    <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
</body>
</html>