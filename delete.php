<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

if (isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $id = intval($_GET['id']);

    try {
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=delete");
            exit();
        } else {
            header("Location: index.php?error=delete_failed");
            exit();
        }
    } catch (Exception $e) {
        header("Location: index.php?error=delete_error");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>