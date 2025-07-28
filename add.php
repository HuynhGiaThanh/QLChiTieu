<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$amount = $_POST['amount'];
$type = $_POST['type'];
$category = $_POST['category'];
$note = $_POST['note'];
$date = $_POST['created_at'];

$sql = "INSERT INTO transactions (amount, type, category, note, created_at, user_id)
        VALUES ('$amount', '$type', '$category', '$note', '$date', $user_id)";

$conn->query($sql);
header("Location: index.php");