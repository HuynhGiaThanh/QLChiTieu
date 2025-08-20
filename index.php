<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Quản lý Thu/Chi</title>
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'style.css' : './style.css'; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Quản lý Thu/Chi</h2>
    <p>Xin chào, <?php echo $_SESSION['username']; ?> | <a href="logout.php">Đăng xuất</a></p>

    <div class="container">
        <!-- Ô 1: Nội dung chính -->
        <div class="main-content">
            <!-- Form thêm giao dịch -->
            <h3>Thêm giao dịch mới</h3>
            <form method="POST" action="add.php" class="transaction-form">
                <input type="number" name="amount" placeholder="Số tiền" required step="0.01" class="form-input">
                <select name="type" required class="form-select">
                    <option value="Thu">Thu</option>
                    <option value="Chi">Chi</option>
                </select>
                <input type="text" name="category" placeholder="Danh mục" required class="form-input">
                <input type="text" name="note" placeholder="Ghi chú" class="form-input">
                <input type="date" name="created_at" value="<?php echo date('Y-m-d'); ?>" required class="form-input">
                <button type="submit" class="submit-btn">Thêm giao dịch</button>
            </form>
            
            <hr>
            
            <!-- Danh sách giao dịch -->
            <h3>Danh sách giao dịch</h3>
            <?php
            $user_id = $_SESSION['user_id'];
            $result = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC");
            
            if ($result->num_rows > 0) {
                echo "<table border='1' class='transactions-table'>";
                echo "<tr><th>Số tiền</th><th>Loại</th><th>Danh mục</th><th>Ghi chú</th><th>Ngày</th><th>Thao tác</th></tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . number_format($row['amount']) . " VNĐ</td>";
                    echo "<td>" . $row['type'] . "</td>";
                    echo "<td>" . $row['category'] . "</td>";
                    echo "<td>" . $row['note'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td><a href='delete.php?id=" . $row['id'] . "' onclick='return confirm(\"Bạn có chắc chắn muốn xóa?\")' class='delete-link'>Xóa</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='no-data'>Chưa có giao dịch nào.</p>";
            }
            ?>
        </div>

        <!-- Các phần biểu đồ và lịch giữ nguyên -->
        <div class="chart">
            <h3>Biểu đồ thống kê</h3>
            <canvas id="myChart"></canvas>
        </div>

        <div class="calendar">
            <h3>Lịch thống kê</h3>
            <table class="calendar-table">
                <tr>
                    <th>CN</th><th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th>
                </tr>
                <tr>
                    <td></td><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td>
                </tr>
                <tr>
                    <td>7</td><td>8</td><td>9</td><td>10</td><td>11</td><td>12</td><td>13</td>
                </tr>
                <tr>
                    <td>14</td><td>15</td><td>16</td><td>17</td><td>18</td><td>19</td><td>20</td>
                </tr>
                <tr>
                    <td>21</td><td>22</td><td>23</td><td>24</td><td>25</td><td>26</td><td>27</td>
                </tr>
                <tr>
                    <td>28</td><td>29</td><td>30</td><td>31</td><td></td><td></td><td></td>
                </tr>
            </table>
        </div>
    </div>

    <script>
    // Script biểu đồ
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3'],
            datasets: [{
                label: 'Số tiền',
                data: [500000, 700000, 300000],
                borderWidth: 1,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(79, 70, 229, 0.8)'
                ],
                borderColor: [
                    'rgba(102, 126, 234, 1)',
                    'rgba(118, 75, 162, 1)',
                    'rgba(79, 70, 229, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Thống kê thu chi theo tháng'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' VNĐ';
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>