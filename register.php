<?php
include 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $check = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Tài khoản đã tồn tại.";
    } else {
        $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashedPassword')";
        if ($conn->query($sql)) {
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white shadow-lg rounded px-8 py-6 w-full max-w-md">
    <h2 class="text-2xl font-bold text-blue-600 mb-4 text-center">Đăng ký</h2>

    <?php if (isset($error)): ?>
        <p class="text-red-600 text-sm mb-3"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="text" name="username" required placeholder="Tên đăng nhập"
               class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="password" name="password" required placeholder="Mật khẩu"
               class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
            Đăng ký
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        Đã có tài khoản? <a href="login.php" class="text-blue-600 hover:underline">Đăng nhập</a>
    </p>
</div>
</body>
</html>
