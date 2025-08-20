<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php'; // Đảm bảo đường dẫn đúng

$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

// Sử dụng prepared statement
$stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header("Location: index.php");
exit();
?>