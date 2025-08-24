-- Tạo database
CREATE DATABASE IF NOT EXISTS `qlchitieu` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `qlchitieu`;

-- Bảng người dùng
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng giao dịch
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('Thu','Chi') NOT NULL,
  `category` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng giới hạn chi tiêu
CREATE TABLE IF NOT EXISTS `spending_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `period` enum('daily','weekly','monthly') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_category` (`user_id`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chèn dữ liệu mẫu
INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2023-01-01 00:00:00');

-- Mật khẩu là "password"

-- Giao dịch mẫu
INSERT INTO `transactions` (`id`, `amount`, `type`, `category`, `note`, `created_at`, `user_id`, `created`) VALUES
(1, 1000000.00, 'Thu', 'Lương', 'Lương tháng 1', '2023-01-15', 1, '2023-01-15 08:30:00'),
(2, 500000.00, 'Thu', 'Thưởng', 'Thưởng cuối năm', '2023-01-20', 1, '2023-01-20 14:45:00'),
(3, 300000.00, 'Chi', 'Ăn uống', 'Ăn tối với gia đình', '2023-01-22', 1, '2023-01-22 19:20:00'),
(4, 200000.00, 'Chi', 'Sống', 'Tiền điện', '2023-01-25', 1, '2023-01-25 10:15:00'),
(5, 1000000.00, 'Chi', 'Tiết kiệm', 'Gửi tiết kiệm', '2023-01-28', 1, '2023-01-28 16:30:00');

-- Giới hạn chi tiêu mẫu
INSERT INTO `spending_limits` (`id`, `user_id`, `category`, `amount`, `period`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ăn uống', 1000000.00, 'monthly', '2023-01-01 00:00:00', '2023-01-01 00:00:00'),
(2, 1, 'Sống', 2000000.00, 'monthly', '2023-01-01 00:00:00', '2023-01-01 00:00:00'),
(3, 1, 'Chơi', 500000.00, 'monthly', '2023-01-01 00:00:00', '2023-01-01 00:00:00');

-- Khóa ngoại
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `spending_limits`
  ADD CONSTRAINT `spending_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;