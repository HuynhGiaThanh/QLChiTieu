<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config/db.php';
$user_id = $_SESSION['user_id'];

// Lọc theo tháng
$filterMonth = $_GET['month'] ?? date('Y-m');
$sql = "SELECT * FROM transactions 
        WHERE user_id = $user_id 
        AND DATE_FORMAT(created_at, '%Y-%m') = '$filterMonth' 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

// Tính tổng
$total_income = 0;
$total_expense = 0;
$transactions = [];
while ($row = $result->fetch_assoc()) {
    if ($row['type'] == 'income') $total_income += $row['amount'];
    else $total_expense += $row['amount'];
    $transactions[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quản lý Thu Chi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Quản lý Thu Chi</h2>

<p>Xin chào, bạn: <strong><?= $user_id ?></strong> | <a href="logout.php">Đăng xuất</a></p>

<form method="POST" action="add.php">
    <input type="number" step="0.01" name="amount" placeholder="Số tiền" required>
    <select name="type">
        <option value="income">Thu</option>
        <option value="expense">Chi</option>
    </select>
    <input type="text" name="category" placeholder="Danh mục" required>
    <input type="text" name="note" placeholder="Ghi chú">
    <input type="date" name="created_at" required>
    <button type="submit">Thêm</button>
</form>

<form method="GET" style="margin-top: 20px;">
    <label>Chọn tháng:</label>
    <input type="month" name="month" value="<?= $filterMonth ?>">
    <button type="submit">Lọc</button>
</form>

<h3>Danh sách giao dịch</h3>
<table border="1">
    <tr>
        <th>Ngày</th><th>Loại</th><th>Số tiền</th><th>Danh mục</th><th>Ghi chú</th><th>Xoá</th>
    </tr>
    <?php foreach ($transactions as $t): ?>
    <tr>
        <td><?= $t['created_at'] ?></td>
        <td><?= $t['type'] == 'income' ? 'Thu' : 'Chi' ?></td>
        <td><?= number_format($t['amount'], 2) ?></td>
        <td><?= htmlspecialchars($t['category']) ?></td>
        <td><?= htmlspecialchars($t['note']) ?></td>
        <td><a href="delete.php?id=<?= $t['id'] ?>" onclick="return confirm('Xoá?')">Xoá</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<h4>Tổng thu: <?= number_format($total_income, 2) ?> | Tổng chi: <?= number_format($total_expense, 2) ?></h4>
</body>
</html>