<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $category = $conn->real_escape_string($_POST['category']);
    $amount = floatval($_POST['amount']);
    $period = $conn->real_escape_string($_POST['period']);

    try {
        // Kiểm tra xem đã có giới hạn cho danh mục này chưa
        $check_stmt = $conn->prepare("SELECT id FROM spending_limits WHERE user_id = ? AND category = ?");
        $check_stmt->bind_param("is", $user_id, $category);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Nếu đã tồn tại, cập nhật giới hạn
            $stmt = $conn->prepare("UPDATE spending_limits SET amount = ?, period = ?, updated_at = NOW() WHERE user_id = ? AND category = ?");
            $stmt->bind_param("dsis", $amount, $period, $user_id, $category);
        } else {
            // Nếu chưa tồn tại, thêm mới
            $stmt = $conn->prepare("INSERT INTO spending_limits (user_id, category, amount, period) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $user_id, $category, $amount, $period);
        }
        
        if ($stmt->execute()) {
            header("Location: index.php?success=limit_updated");
            exit();
        } else {
            header("Location: index.php?error=limit_failed");
            exit();
        }
    } catch (Exception $e) {
        header("Location: index.php?error=limit_error");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
[file content end]