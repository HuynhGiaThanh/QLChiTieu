<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = floatval($_POST['amount']);
    $type = $conn->real_escape_string($_POST['type']);
    $category = $conn->real_escape_string($_POST['category']);
    $note = $conn->real_escape_string($_POST['note']);
    $date = $conn->real_escape_string($_POST['created_at']);

    try {
        $stmt = $conn->prepare("INSERT INTO transactions (amount, type, category, note, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("dssssi", $amount, $type, $category, $note, $date, $user_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            header("Location: index.php?error=add_failed");
            exit();
        }
    } catch (Exception $e) {
        header("Location: index.php?error=add_error");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>