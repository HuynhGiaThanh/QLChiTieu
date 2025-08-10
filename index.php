<?php
session_start();
include 'config/db.php';

// nếu chưa đăng nhập -> chuyển về login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// lấy tên user
$user = $conn->query("SELECT username FROM users WHERE id = $user_id")->fetch_assoc();
$username = $user ? htmlspecialchars($user['username']) : 'Người dùng';

// lấy danh sách giao dịch
$transactions = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Quản lý Thu/Chi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
<nav class="bg-white shadow">
    <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
        <div class="text-lg font-bold text-blue-600">Quản lý Thu/Chi</div>
        <div class="space-x-4">
            <span class="text-sm">Xin chào, <strong><?= $username ?></strong></span>
            <a href="logout.php" class="text-sm text-red-600 hover:underline">Đăng xuất</a>
        </div>
    </div>
</nav>

<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded shadow p-4 mb-6">
        <h2 class="text-xl font-semibold mb-2">Thêm giao dịch mới</h2>
        <form action="add.php" method="POST" class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <input name="amount" type="number" step="0.01" required placeholder="Số tiền"
                   class="p-2 border rounded md:col-span-1">
            <select name="type" required class="p-2 border rounded md:col-span-1">
                <option value="income">Thu</option>
                <option value="expense">Chi</option>
            </select>
            <input name="category" placeholder="Danh mục" class="p-2 border rounded md:col-span-1">
            <input name="created_at" type="date" required class="p-2 border rounded md:col-span-1">
            <input name="note" placeholder="Ghi chú" class="p-2 border rounded md:col-span-4">
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded md:col-span-4">Lưu</button>
        </form>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="text-xl font-semibold mb-2">Danh sách giao dịch</h2>
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left">
                        <th class="px-3 py-2">Ngày</th>
                        <th class="px-3 py-2">Số tiền</th>
                        <th class="px-3 py-2">Loại</th>
                        <th class="px-3 py-2">Danh mục</th>
                        <th class="px-3 py-2">Ghi chú</th>
                        <th class="px-3 py-2">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $transactions->fetch_assoc()): ?>
                    <tr class="border-t">
                        <td class="px-3 py-2"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['amount']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['type']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['category']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($row['note']) ?></td>
                        <td class="px-3 py-2">
                            <a href="delete.php?id=<?= $row['id'] ?>" class="text-red-600 hover:underline"
                               onclick="return confirm('Xác nhận xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <p>Chưa có giao dịch nào.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
