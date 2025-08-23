<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

// Kiểm tra xem bảng spending_limits có tồn tại không
$table_check = $conn->query("SHOW TABLES LIKE 'spending_limits'");
if ($table_check->num_rows == 0) {
    // Nếu bảng không tồn tại, chuyển hướng về trang chính
    header("Location: index.php?error=table_not_exist");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $id = intval($_GET['id']);

    try {
        $stmt = $conn->prepare("DELETE FROM spending_limits WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=limit_deleted");
            exit();
        } else {
            header("Location: index.php?error=delete_limit_failed");
            exit();
        }
    } catch (Exception $e) {
        header("Location: index.php?error=delete_limit_error");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>