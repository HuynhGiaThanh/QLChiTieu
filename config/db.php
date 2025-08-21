<?php
$host = "localhost";
$db = "qlchitieu";
$user = "root";
$pass = "";
$port = 3306;

// Kiểm tra kết nối MySQL trước
$test_conn = @new mysqli($host, $user, $pass);
if ($test_conn->connect_error) {
    die("❌ Lỗi kết nối MySQL: " . $test_conn->connect_error . 
        "<br>👉 Hãy khởi động MySQL trong XAMPP");
}

// Kiểm tra database tồn tại
if (!$test_conn->select_db($db)) {
    // Tạo database nếu chưa tồn tại
    $test_conn->query("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $test_conn->select_db($db);
    
    // Tạo bảng users
    $test_conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Tạo bảng transactions
    $test_conn->query("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('Thu', 'Chi') NOT NULL,
        category VARCHAR(50) NOT NULL,
        note TEXT,
        created_at DATE NOT NULL,
        user_id INT NOT NULL,
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Kết nối chính thức
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>