<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Xử lý xoá giao dịch nếu có yêu cầu
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    if ($stmt->execute()) {
        header("Location: index.php?success=deleted");
        exit;
    } else {
        header("Location: index.php?error=delete_failed");
        exit;
    }
}

// LẤY DỮ LIỆU GIỚI HẠN CHI TIÊU - THÊM XỬ LÝ LỖI
$limits = [];
$alerts = [];
$limits_result = false;

try {
    // Kiểm tra xem bảng spending_limits có tồn tại không
    $table_check = $conn->query("SHOW TABLES LIKE 'spending_limits'");
    if ($table_check->num_rows > 0) {
        $limits_result = $conn->query("SELECT * FROM spending_limits WHERE user_id = $user_id");
        if ($limits_result) {
            while ($limit = $limits_result->fetch_assoc()) {
                $limits[$limit['category']] = $limit;
            }
        }
    }
} catch (Exception $e) {
    // Ghi log lỗi nhưng không hiển thị cho người dùng
    error_log("Lỗi khi truy vấn spending_limits: " . $e->getMessage());
}

// TÍNH TOÁN CHI TIÊU THEO DANH MỤC TRONG KỲ
$current_date = date('Y-m-d');
$period_conditions = [
    'daily' => "created_at = '$current_date'",
    'weekly' => "YEARWEEK(created_at, 1) = YEARWEEK('$current_date', 1)",
    'monthly' => "YEAR(created_at) = YEAR('$current_date') AND MONTH(created_at) = MONTH('$current_date')"
];

$category_spending = [];
$expense_categories = $conn->query("SELECT category, SUM(amount) as spent 
                                   FROM transactions 
                                   WHERE user_id = $user_id AND type = 'Chi' 
                                   GROUP BY category");

while ($row = $expense_categories->fetch_assoc()) {
    $category_spending[$row['category']] = $row['spent'];
}

// KIỂM TRA GIỚI HẠN VÀ TẠO CẢNH BÁO (chỉ nếu có dữ liệu giới hạn)
if (!empty($limits)) {
    foreach ($limits as $limit) {
        $category = $limit['category'];
        $limit_amount = $limit['amount'];
        $period = $limit['period'];
        $spent = $category_spending[$category] ?? 0;
        
        // Tính chi tiêu theo kỳ
        $period_condition = $period_conditions[$period];
        $period_spent_result = $conn->query("SELECT SUM(amount) as period_spent 
                                            FROM transactions 
                                            WHERE user_id = $user_id 
                                            AND type = 'Chi' 
                                            AND category = '$category'
                                            AND $period_condition");
        
        if ($period_spent_result) {
            $period_spent = $period_spent_result->fetch_assoc()['period_spent'] ?? 0;
            
            if ($period_spent > 0) {
                $percentage = ($period_spent / $limit_amount) * 100;
                
                if ($percentage >= 100) {
                    $alerts[] = [
                        'type' => 'danger',
                        'message' => "Bạn đã vượt quá giới hạn chi tiêu cho $category ($period). Đã chi: " . number_format($period_spent) . " VND / Giới hạn: " . number_format($limit_amount) . " VND"
                    ];
                } elseif ($percentage >= 80) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => "Bạn sắp đạt giới hạn chi tiêu cho $category ($period). Đã chi: " . number_format($period_spent) . " VND / Giới hạn: " . number_format($limit_amount) . " VND (" . round($percentage) . "%)"
                    ];
                }
            }
        }
    }
}

// Lấy dữ liệu thống kê
try {
    // Tổng quan các ví - CẬP NHẬT: xoá Love, thêm Ăn uống
    $wallet_stats = $conn->query("SELECT 
        SUM(CASE WHEN category = 'Sống' THEN amount ELSE 0 END) as total_song,
        SUM(CASE WHEN category = 'Tiết kiệm' THEN amount ELSE 0 END) as total_tietkiem,
        SUM(CASE WHEN category = 'Chơi' THEN amount ELSE 0 END) as total_choi,
        SUM(CASE WHEN category = 'Ăn uống' THEN amount ELSE 0 END) as total_anuong,
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
    
    // DỮ LIỆU CHO BIỂU ĐỒ MỚI
    // Tổng thu
    $total_income = $conn->query("SELECT SUM(amount) as total FROM transactions 
        WHERE user_id = $user_id AND type = 'Thu'")->fetch_assoc()['total'] ?? 0;
    
    // Tổng chi theo danh mục
    $expense_by_category = $conn->query("SELECT 
        category,
        SUM(amount) as total_amount
        FROM transactions 
        WHERE user_id = $user_id AND type = 'Chi'
        GROUP BY category 
        ORDER BY total_amount DESC");
    
    // Tạo mảng dữ liệu cho biểu đồ
    $chart_labels = [];
    $chart_data = [];
    $chart_colors = [];
    $chart_categories = [];
    
    // Thêm các danh mục chi tiêu
    $color_palette = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'];
    $color_index = 0;
    
    $total_expense = 0;
    while ($row = $expense_by_category->fetch_assoc()) {
        $chart_labels[] = $row['category'];
        $chart_data[] = $row['total_amount'];
        $chart_colors[] = $color_palette[$color_index % count($color_palette)];
        $chart_categories[] = $row['category'];
        $total_expense += $row['total_amount'];
        $color_index++;
    }
    
    // Thêm phần dư (nếu có)
    $balance = $total_income - $total_expense;
    if ($balance > 0) {
        $chart_labels[] = 'Dư';
        $chart_data[] = $balance;
        $chart_colors[] = '#90EE90';
        $chart_categories[] = 'Dư';
    }
        
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

        <!-- HIỂN THỊ CẢNH BÁO GIỚI HẠN CHI TIÊU -->
        <?php if (!empty($alerts)): ?>
        <div class="alerts-container">
            <?php foreach ($alerts as $alert): ?>
            <div class="alert-notification <?php echo $alert['type']; ?>">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Cảnh báo chi tiêu</div>
                    <div class="alert-message"><?php echo $alert['message']; ?></div>
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

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

        <!-- PHẦN GIỚI HẠN CHI TIÊU -->
        <div class="limits-section">
            <div class="limits-header">
                <h3><i class="fas fa-chart-line"></i> Giới hạn chi tiêu</h3>
                <button class="add-limit-btn" onclick="showLimitModal()">
                    <i class="fas fa-plus"></i> Thêm giới hạn
                </button>
            </div>
            
            <div class="limits-list">
                <?php 
                if ($limits_result && $limits_result->num_rows > 0): 
                    $limits_result->data_seek(0); // Reset con trỏ
                    while ($limit = $limits_result->fetch_assoc()): 
                        $category = $limit['category'];
                        $limit_amount = $limit['amount'];
                        $period = $limit['period'];
                        
                        // Tính chi tiêu theo kỳ
                        $period_condition = $period_conditions[$period];
                        $period_spent_result = $conn->query("SELECT SUM(amount) as period_spent 
                                                            FROM transactions 
                                                            WHERE user_id = $user_id 
                                                            AND type = 'Chi' 
                                                            AND category = '$category'
                                                            AND $period_condition");
                        
                        $period_spent = $period_spent_result->fetch_assoc()['period_spent'] ?? 0;
                        $percentage = $limit_amount > 0 ? min(100, ($period_spent / $limit_amount) * 100) : 0;
                        
                        $progress_class = '';
                        if ($percentage >= 100) {
                            $progress_class = 'danger';
                        } elseif ($percentage >= 80) {
                            $progress_class = 'warning';
                        }
                ?>
                <div class="limit-item <?php echo $percentage >= 100 ? 'limit-warning' : ''; ?>">
                    <div class="limit-info">
                        <div class="limit-category"><?php echo $category; ?></div>
                        <div class="limit-details">
                            <span class="limit-amount"><?php echo number_format($period_spent); ?> / <?php echo number_format($limit_amount); ?> VND</span>
                            <span class="limit-period"><?php 
                                echo $period == 'daily' ? 'Hàng ngày' : 
                                     ($period == 'weekly' ? 'Hàng tuần' : 'Hàng tháng');
                            ?></span>
                            <span class="limit-percentage">(<?php echo round($percentage); ?>%)</span>
                        </div>
                        <div class="limit-progress">
                            <div class="limit-progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <div class="limit-actions">
                        <button class="edit-limit-btn" onclick="editLimit(<?php echo $limit['id']; ?>, '<?php echo $category; ?>', <?php echo $limit_amount; ?>, '<?php echo $period; ?>')">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button class="delete-limit-btn" onclick="deleteLimit(<?php echo $limit['id']; ?>)">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="no-limits">
                    <i class="fas fa-chart-pie"></i>
                    <p>Chưa có giới hạn chi tiêu nào được thiết lập</p>
                </div>
                <?php endif; ?>
            </div>
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
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="wallet-info">
                    <h3><?php echo number_format($stats['total_anuong'] ?? 0); ?> VND</h3>
                    <p>Tổng tiền ví Ăn uống</p>
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
                        <!-- ĐÃ XOÁ NÚT "Xem thống kê" -->
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

                <!-- Thêm phần thống kê nhanh thay cho bảng điều khiển -->
                <div class="account-section">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-bar"></i> Thống kê nhanh</h3>
                    </div>
                    <div class="account-summary">
                        <div class="summary-item">
                            <span class="label">Số giao dịch:</span>
                            <span class="value"><?php echo $recent_transactions->num_rows; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Danh mục chi nhiều nhất:</span>
                            <span class="value">
                                <?php 
                                $max_expense = 0;
                                $max_category = 'Chưa có';
                                // Reset con trỏ để lặp lại
                                $expense_by_category->data_seek(0);
                                while ($row = $expense_by_category->fetch_assoc()) {
                                    if ($row['total_amount'] > $max_expense) {
                                        $max_expense = $row['total_amount'];
                                        $max_category = $row['category'];
                                    }
                                }
                                echo $max_category . ' (' . number_format($max_expense) . ' VND)';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Chart -->
            <div class="right-column">
                <div class="chart-section">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-pie"></i> Biểu đồ thu chi tổng quan</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="chart-summary">
                        <p><strong>Tổng thu:</strong> <?php echo number_format($total_income); ?> VND</p>
                        <div class="chart-legend" id="chartLegend">
                            <!-- Nội dung động sẽ được thêm bằng JavaScript -->
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
                    <?php 
                    // Reset con trỏ kết quả để lặp lại
                    $recent_transactions->data_seek(0);
                    while ($row = $recent_transactions->fetch_assoc()): ?>
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
                            <div class="transaction-actions">
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                        <option value="Ăn uống">Ăn uống</option> <!-- ĐÃ THAY THẾ LOVE BẰNG ĂN UỐNG -->
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

    <!-- MODAL THÊM/ SỬA GIỚI HẠN CHI TIÊU -->
    <div id="limitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLimitModal()">&times;</span>
            <h3 id="limitModalTitle"><i class="fas fa-chart-line"></i> Thêm giới hạn chi tiêu</h3>
            <form method="POST" action="add_limit.php" class="modal-form">
                <input type="hidden" id="limitId" name="id" value="">
                
                <div class="form-group">
                    <label for="limitCategory"><i class="fas fa-tag"></i> Danh mục</label>
                    <select id="limitCategory" name="category" required>
                        <option value="">Chọn danh mục</option>
                        <option value="Sống">Sống</option>
                        <option value="Tiết kiệm">Tiết kiệm</option>
                        <option value="Chơi">Chơi</option>
                        <option value="Ăn uống">Ăn uống</option>
                        <option value="Đầu tư">Đầu tư</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="limitAmount"><i class="fas fa-money-bill-wave"></i> Giới hạn (VND)</label>
                    <input type="number" id="limitAmount" name="amount" required step="1000" placeholder="0">
                </div>

                <div class="form-group">
                    <label for="limitPeriod"><i class="fas fa-calendar"></i> Kỳ hạn</label>
                    <select id="limitPeriod" name="period" required>
                        <option value="daily">Hàng ngày</option>
                        <option value="weekly">Hàng tuần</option>
                        <option value="monthly" selected>Hàng tháng</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeLimitModal()">Hủy</button>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Lưu giới hạn
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    
    // Sử dụng dữ liệu từ PHP
    const categoryData = {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: <?php echo json_encode($chart_colors); ?>,
            borderWidth: 2,
            hoverOffset: 15
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
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = <?php echo $total_income; ?>;
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value.toLocaleString()} VND (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Tạo phần chú thích động
    const legendContainer = document.getElementById('chartLegend');
    legendContainer.innerHTML = ''; // Xóa nội dung cũ

    <?php 
    $chart_data_js = json_encode($chart_data);
    $chart_categories_js = json_encode($chart_categories);
    $chart_colors_js = json_encode($chart_colors);
    ?>
    
    const chartData = <?php echo $chart_data_js; ?>;
    const chartCategories = <?php echo $chart_categories_js; ?>;
    const chartColors = <?php echo $chart_colors_js; ?>;
    
    for (let i = 0; i < chartCategories.length; i++) {
        if (chartData[i] > 0) {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            legendItem.innerHTML = `
                <span class="color-dot" style="background: ${chartColors[i]}"></span>
                <span>${chartCategories[i]}: ${chartData[i].toLocaleString()} VND</span>
            `;
            legendContainer.appendChild(legendItem);
        }
    }

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

    // Hàm xử lý giới hạn chi tiêu
    function showLimitModal() {
        document.getElementById('limitModalTitle').innerHTML = '<i class="fas fa-chart-line"></i> Thêm giới hạn chi tiêu';
        document.getElementById('limitId').value = '';
        document.getElementById('limitCategory').value = '';
        document.getElementById('limitAmount').value = '';
        document.getElementById('limitPeriod').value = 'monthly';
        document.getElementById('limitModal').style.display = 'block';
    }

    function editLimit(id, category, amount, period) {
        document.getElementById('limitModalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa giới hạn chi tiêu';
        document.getElementById('limitId').value = id;
        document.getElementById('limitCategory').value = category;
        document.getElementById('limitAmount').value = amount;
        document.getElementById('limitPeriod').value = period;
        document.getElementById('limitModal').style.display = 'block';
    }

    function closeLimitModal() {
        document.getElementById('limitModal').style.display = 'none';
    }

    function deleteLimit(id) {
        if (confirm('Bạn có chắc chắn muốn xoá giới hạn chi tiêu này?')) {
            window.location.href = 'delete_limit.php?id=' + id;
        }
    }

    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('addModal');
        const limitModal = document.getElementById('limitModal');
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === limitModal) {
            closeLimitModal();
        }
    }

    // Close modal with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
            closeLimitModal();
        }
    });

    // Xác nhận xoá giao dịch
    function confirmDelete(transactionId) {
        if (confirm('Bạn có chắc chắn muốn xoá giao dịch này?')) {
            window.location.href = 'index.php?delete_id=' + transactionId;
        }
    }
    </script>
</body>
</html>