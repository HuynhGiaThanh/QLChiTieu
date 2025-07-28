<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';
$user_id = $_SESSION['user_id'];
$id = $_GET['id'];
$conn->query("DELETE FROM transactions WHERE id=$id AND user_id=$user_id");
header("Location: index.php");