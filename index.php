<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu thống kê
try {
    // Tổng quan các ví
    $wallet_stats = $conn->query("SELECT 
        SUM(CASE WHEN category = 'Sống' THEN amount ELSE 0 END) as total_song,
        SUM(CASE WHEN category = 'Tiết kiệm' THEN amount ELSE 0 END) as total_tietkiem,
        SUM(CASE WHEN category = 'Chơi' THEN amount ELSE 0 END) as total_choi,
        SUM(CASE WHEN category = 'Love' THEN amount ELSE 0 END) as total_love,
        SUM(CASE WHEN category = 'Đầu tư' THEN amount ELSE 0 END) as total_dautu,
        SUM(CASE WHEN type = 'Thu' THEN amount ELSE 0 END) as total_thu,
        SUM(CASE WHEN type = 'Chi' THEN amount ELSE 0 END) as total_chi,
        (SUM(CASE WHEN type = 'Thu' THEN amount ELSE 0 END) - SUM(CASE WHEN type = 'Chi' THEN amount ELSE 0 END)) as balance
        FROM transactions WHERE user_id = $user_id");
    
    $stats = $wallet_stats->fetch_assoc();
    
    // Thống kê theo danh mục chi tiêu
    $category_stats = $conn->query("SELECT 
        category,
        SUM(amount) as total_amount,
        COUNT(*) as transaction_count
        FROM transactions 
        WHERE user_id = $user_id AND type = 'Chi'
        GROUP BY category 
        ORDER BY total_amount DESC");
        
    // Giao dịch gần đây
    $recent_transactions = $conn->query("SELECT * FROM transactions 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC LIMIT 5");
        
} catch (Exception $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TÀI CHÍNH CÁ NHÂN - ADMINDER</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="finance-dashboard">
    <div class="finance-container">
        <!-- Header -->
        <header class="finance-header">
            <div class="header-left">
                <h1><i class="fas fa-wallet"></i> TÀI CHÍNH CÁ NHÂN</h1>
            </div>
            <div class="header-right">
                <span class="user-welcome">Xin chào, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </header>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="action-btn income-btn" onclick="showAddForm('Thu')">
                <i class="fas fa-plus-circle"></i>
                <span>Thêm khoản thu</span>
            </button>
            <button class="action-btn expense-btn" onclick="showAddForm('Chi')">
                <i class="fas fa-minus-circle"></i>
                <span>Thêm khoản chi</span>
            </button>
        </div>

        <!-- Wallet Summary Grid -->
        <div class="wallet-grid">
            <div class="wallet-card">
                <div class="wallet-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_song'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Sống</p>
                </div>
            </div>

            <div class="wallet-card">
                <div class="wallet-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_tietkiem'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Tiết kiệm</p>
                </div>
            </div>

            <div class="wallet-card">
                <div class="wallet-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_choi'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Chơi</p>
                </div>
            </div>

            <div class="wallet-card">
                <div class="wallet-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_love'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Love</p>
                </div>
            </div>

            <div class="wallet-card">
                <div class="wallet-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_dautu'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Đầu tư</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content-grid">
            <!-- Left Column - Account Summary -->
            <div class="left-column">
                <div class="account-section">
                    <div class="section-header">
                        <h3><i class="fas fa-credit-card"></i> Tài khoản - Ví</h3>
                        <a href="#" class="view-all">Xem thống kê →</a>
                    </div>
                    <div class="account-summary">
                        <div class="summary-item">
                            <span class="label">Tổng thu:</span>
                            <span class="value income">+<?php echo number_format($stats['total_thu'] ?? 0); ?> VND</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Tổng chi:</span>
                            <span class="value expense">-<?php echo number_format($stats['total_chi'] ?? 0); ?> VND</span>
                        </div>
                        <div class="summary-item total">
                            <span class="label">Số dư:</span>
                            <span class="value balance"><?php echo number_format($stats['balance'] ?? 0); ?> VND</span>
                        </div>
                    </div>
                </div>

                <!-- Control Panel -->
                <div class="control-section">
                    <div class="section-header">
                        <h3><i class="fas fa-sliders-h"></i> Bảng điều khiển</h3>
                    </div>
                    <div class="control-panel">
                        <div class="control-item">
                            <label>Năm:</label>
                            <select>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022" selected>2022</option>
                            </select>
                        </div>
                        <div class="control-item">
                            <label>Tháng:</label>
                            <select>
                                <option value="10" selected>10</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="control-item">
                            <label>Ví:</label>
                            <select>
                                <option value="11" selected>11 - Ví chính</option>
                                <option value="12">12 - Ví phụ</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Chart -->
            <div class="right-column">
                <div class="chart-section">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-pie"></i> Biểu đồ chi tiêu theo danh mục</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="chart-summary">
                        <p><strong>Tổng số tiền:</strong> <?php echo number_format($stats['total_chi'] ?? 0); ?> VND</p>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="color-dot" style="background: #FF6384;"></span>
                                <span>Ăn uống: 700,000</span>
                            </div>
                            <div class="legend-item">
                                <span class="color-dot" style="background: #36A2EB;"></span>
                                <span>Mua sắm: 550,000</span>
                            </div>
                            <div class="legend-item">
                                <span class="color-dot" style="background: #FFCE56;"></span>
                                <span>Giải trí: 225,000</span>
                            </div>
                            <div class="legend-item">
                                <span class="color-dot" style="background: #4BC0C0;"></span>
                                <span>Y tế: 45,000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="transactions-section">
            <div class="section-header">
                <h3><i class="fas fa-history"></i> Giao dịch gần đây</h3>
            </div>
            <div class="transactions-list">
                <?php if ($recent_transactions->num_rows > 0): ?>
                    <?php while ($row = $recent_transactions->fetch_assoc()): ?>
                        <?php
                        $amount_class = $row['type'] == 'Thu' ? 'income' : 'expense';
                        $icon = $row['type'] == 'Thu' ? 'fa-arrow-down' : 'fa-arrow-up';
                        $amount_sign = $row['type'] == 'Thu' ? '+' : '-';
                        ?>
                        <div class="transaction-item">
                            <div class="transaction-icon <?php echo $amount_class; ?>">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="transaction-details">
                                <h4><?php echo $row['category']; ?></h4>
                                <p><?php echo $row['note'] ?: 'Không có ghi chú'; ?></p>
                                <span class="transaction-date"><?php echo $row['created_at']; ?></span>
                            </div>
                            <div class="transaction-amount <?php echo $amount_class; ?>">
                                <?php echo $amount_sign . number_format($row['amount']); ?> VND
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-transactions">
                        <i class="fas fa-receipt"></i>
                        <p>Chưa có giao dịch nào</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="finance-footer">
            <p>Copyright 2024 © by TÀI CHÍNH CÁ NHÂN. All rights reserved.</p>
        </footer>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Thêm giao dịch mới</h3>
            <form method="POST" action="add.php" class="modal-form">
                <input type="hidden" id="transactionType" name="type" value="">
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-money-bill-wave"></i> Số tiền (VND)</label>
                    <input type="number" id="amount" name="amount" required step="1000" placeholder="0">
                </div>

                <div class="form-group">
                    <label for="category"><i class="fas fa-tag"></i> Danh mục</label>
                    <select id="category" name="category" required>
                        <option value="">Chọn danh mục</option>
                        <option value="Sống">Sống</option>
                        <option value="Tiết kiệm">Tiết kiệm</option>
                        <option value="Chơi">Chơi</option>
                        <option value="Love">Love</option>
                        <option value="Đầu tư">Đầu tư</option>
                        <option value="Lương">Lương</option>
                        <option value="Thưởng">Thưởng</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note"><i class="fas fa-sticky-note"></i> Ghi chú</label>
                    <textarea id="note" name="note" placeholder="Ghi chú về giao dịch..."></textarea>
                </div>

                <div class="form-group">
                    <label for="created_at"><i class="fas fa-calendar"></i> Ngày giao dịch</label>
                    <input type="date" id="created_at" name="created_at" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Lưu giao dịch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    
    const categoryData = {
        labels: ['Ăn uống', 'Mua sắm', 'Giải trí', 'Y tế', 'Giáo dục', 'Khác'],
        datasets: [{
            data: [700000, 550000, 225000, 45000, 900000, 370000],
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ],
            borderWidth: 2
        }]
    };

    new Chart(categoryCtx, {
        type: 'doughnut',
        data: categoryData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '60%'
        }
    });

    // Modal functions
    function showAddForm(type) {
        const modal = document.getElementById('addModal');
        const modalTitle = document.getElementById('modalTitle');
        const typeInput = document.getElementById('transactionType');
        
        typeInput.value = type;
        modalTitle.innerHTML = type === 'Thu' ? 
            '<i class="fas fa-plus-circle"></i> Thêm khoản thu' : 
            '<i class="fas fa-minus-circle"></i> Thêm khoản chi';
        
        modal.style.display = 'block';
    }

    function closeModal() {
        document.getElementById('addModal').style.display = 'none';
    }

    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('addModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Close modal with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    </script>
</body>
</html>