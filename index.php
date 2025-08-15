<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Quản lý Thu/Chi</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Quản lý Thu/Chi</h2>
    <p>Xin chào, <?php echo $_SESSION['username']; ?> | <a href="logout.php">Đăng xuất</a></p>

    <div class="container">
        <!-- Ô 1: Nội dung chính -->
        <div class="main-content">
            <?php include 'add.php'; ?>
            <hr>
            <?php if (file_exists('list.php')) include 'list.php'; ?>
        </div>

        <!-- Ô 2: Biểu đồ -->
        <div class="chart">
            <h3>Biểu đồ thống kê</h3>
            <canvas id="myChart"></canvas>
        </div>

        <!-- Ô 3: Lịch -->
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
    // Ví dụ dữ liệu biểu đồ
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3'],
            datasets: [{
                label: 'Số tiền',
                data: [500000, 700000, 300000],
                borderWidth: 1,
                backgroundColor: 'rgba(54, 162, 235, 0.5)'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html>
