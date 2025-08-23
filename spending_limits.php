<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$user_id = $_SESSION['user_id'];

// Xử lý thêm/sửa/xoá giới hạn
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_limit'])) {
        $category = $conn->real_escape_string($_POST['category']);
        $amount = floatval($_POST['amount']);
        $period = $conn->real_escape_string($_POST['period']);
        
        // Kiểm tra xem đã có giới hạn cho danh mục này chưa
        $check = $conn->prepare("SELECT * FROM spending_limits WHERE user_id = ? AND category = ?");
        $check->bind_param("is", $user_id, $category);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Đã có giới hạn cho danh mục này!";
        } else {
            $stmt = $conn->prepare("INSERT INTO spending_limits (user_id, category, amount, period) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $user_id, $category, $amount, $period);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Thêm giới hạn thành công!";
            } else {
                $_SESSION['error'] = "Lỗi khi thêm giới hạn!";
            }
        }
    }
    elseif (isset($_POST['update_limit'])) {
        $limit_id = intval($_POST['limit_id']);
        $amount = floatval($_POST['amount']);
        $period = $conn->real_escape_string($_POST['period']);
        
        $stmt = $conn->prepare("UPDATE spending_limits SET amount = ?, period = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("dsii", $amount, $period, $limit_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Cập nhật giới hạn thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật giới hạn!";
        }
    }
    elseif (isset($_POST['delete_limit'])) {
        $limit_id = intval($_POST['limit_id']);
        
        $stmt = $conn->prepare("DELETE FROM spending_limits WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $limit_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Xoá giới hạn thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi xoá giới hạn!";
        }
    }
    
    header("Location: index.php");
    exit;
}

// Lấy thông tin giới hạn để chỉnh sửa
if (isset($_GET['edit_id'])) {
    $limit_id = intval($_GET['edit_id']);
    $limit_result = $conn->query("SELECT * FROM spending_limits WHERE id = $limit_id AND user_id = $user_id");
    $edit_limit = $limit_result->fetch_assoc();
}
?>

<!-- Modal thêm/sửa giới hạn -->
<div id="limitModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLimitModal()">&times;</span>
        <h3 id="limitModalTitle"><i class="fas fa-chart-line"></i> 
            <?php echo isset($edit_limit) ? 'Sửa giới hạn chi tiêu' : 'Thiết lập giới hạn chi tiêu'; ?>
        </h3>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="modal-form">
            <input type="hidden" id="limit_id" name="limit_id" value="<?php echo isset($edit_limit) ? $edit_limit['id'] : ''; ?>">
            
            <div class="form-group">
                <label for="limit_category"><i class="fas fa-tag"></i> Danh mục</label>
                <select id="limit_category" name="category" required <?php echo isset($edit_limit) ? 'disabled' : ''; ?>>
                    <option value="">Chọn danh mục</option>
                    <option value="Sống" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Sống') ? 'selected' : ''; ?>>Sống</option>
                    <option value="Tiết kiệm" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Tiết kiệm') ? 'selected' : ''; ?>>Tiết kiệm</option>
                    <option value="Chơi" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Chơi') ? 'selected' : ''; ?>>Chơi</option>
                    <option value="Ăn uống" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Ăn uống') ? 'selected' : ''; ?>>Ăn uống</option>
                    <option value="Đầu tư" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Đầu tư') ? 'selected' : ''; ?>>Đầu tư</option>
                    <option value="Khác" <?php echo (isset($edit_limit) && $edit_limit['category'] == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                </select>
                <?php if (isset($edit_limit)): ?>
                    <input type="hidden" name="category" value="<?php echo $edit_limit['category']; ?>">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="limit_amount"><i class="fas fa-money-bill-wave"></i> Giới hạn (VND)</label>
                <input type="number" id="limit_amount" name="amount" required step="1000" 
                       placeholder="0" value="<?php echo isset($edit_limit) ? $edit_limit['amount'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="limit_period"><i class="fas fa-calendar"></i> Chu kỳ</label>
                <select id="limit_period" name="period" required>
                    <option value="daily" <?php echo (isset($edit_limit) && $edit_limit['period'] == 'daily') ? 'selected' : ''; ?>>Hàng ngày</option>
                    <option value="weekly" <?php echo (isset($edit_limit) && $edit_limit['period'] == 'weekly') ? 'selected' : ''; ?>>Hàng tuần</option>
                    <option value="monthly" <?php echo (isset($edit_limit) && $edit_limit['period'] == 'monthly') ? 'selected' : ''; ?>>Hàng tháng</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeLimitModal()">Hủy</button>
                <button type="submit" class="submit-btn" name="<?php echo isset($edit_limit) ? 'update_limit' : 'add_limit'; ?>">
                    <i class="fas fa-save"></i> <?php echo isset($edit_limit) ? 'Cập nhật' : 'Lưu giới hạn'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openLimitModal(limitId = null) {
    if (limitId) {
        window.location.href = 'spending_limits.php?edit_id=' + limitId;
    } else {
        window.location.href = 'spending_limits.php';
    }
}

function closeLimitModal() {
    window.location.href = 'index.php';
}

function deleteLimit(limitId) {
    if (confirm('Bạn có chắc chắn muốn xoá giới hạn này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'spending_limits.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'limit_id';
        input.value = limitId;
        form.appendChild(input);
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_limit';
        deleteInput.value = '1';
        form.appendChild(deleteInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Tự động mở modal nếu có tham số edit
<?php if (isset($_GET['edit_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('limitModal').style.display = 'block';
});
<?php endif; ?>
</script>