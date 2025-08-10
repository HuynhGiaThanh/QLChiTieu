<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = $conn->real_escape_string($_POST['username']);
    $p = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$u'";
    $result = $conn->query($sql);
    $user = $result ? $result->fetch_assoc() : null;

    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-lg rounded px-8 py-6 w-full max-w-md">
    <h2 class="text-2xl font-bold text-blue-600 mb-4 text-center">Đăng nhập</h2>

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
            Đăng nhập
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        Chưa có tài khoản? <a href="register.php" class="text-blue-600 hover:underline">Đăng ký ngay</a>
    </p>
</div>

</body>
</html>
