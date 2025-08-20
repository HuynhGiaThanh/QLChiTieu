<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php'; // Đảm bảo đường dẫn đúng

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $note = $_POST['note'];
    $date = $_POST['created_at'];

    // Sử dụng prepared statement để tránh SQL injection
    $stmt = $conn->prepare("INSERT INTO transactions (amount, type, category, note, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("dssssi", $amount, $type, $category, $note, $date, $user_id);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "Lỗi: " . $conn->error;
    }
}