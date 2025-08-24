## Cách 1: Tự động tạo database (Khuyến nghị)

1. Đảm bảo file `database/qlchitieu.sql` tồn tại trong thư mục dự án
2. Truy cập http://localhost/[tên-thư-mục]
3. Hệ thống sẽ tự động tạo database và import dữ liệu mẫu

## Cách 2: Import thủ công bằng phpMyAdmin

1. Truy cập http://localhost/phpmyadmin
2. Tạo database mới với tên `qlchitieu`
3. Chọn database vừa tạo
4. Nhấn tab "Import"
5. Chọn file `database/qlchitieu.sql` từ dự án của bạn
6. Nhấn "Go" để import

![Import SQL](https://i.imgur.com/8aFcB1x.png)

## Thông tin đăng nhập mặc định

Sau khi import database, bạn có thể đăng nhập với:
- Username: `demo`
- Password: `password`

## Dữ liệu mẫu

Database đã bao gồm:
- 1 user demo
- 5 giao dịch mẫu (3 thu, 2 chi)
- 3 giới hạn chi tiêu mẫu