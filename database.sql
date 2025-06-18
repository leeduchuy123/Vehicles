create database vehicle_violation_system;

-- drop database vehicle_violation_system;

USE vehicle_violation_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO `users` VALUES 
(1,'admin1','$2y$10$8MJvKSRXYSJ1Yd7MiOAUYeZA.4XzfBMt9xhutA4QqYsYLQzNXDKMi','2025-05-24 14:42:15'),
(2,'admin2','admin123','2025-05-25 03:40:24'),
(3,'admin','admin123','2025-05-25 03:42:35');

-- Create owners table
CREATE TABLE IF NOT EXISTS owners (
    owner_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO `owners` VALUES 
(1,'Nguyễn Văn A','0901234567','Số 123 Đường Lê Lợi, Quận 1, TP.HCM','2025-05-24 14:43:11'),
(2,'Trần Thị B','0912345678','Số 456 Đường Nguyễn Huệ, Quận 1, TP.HCM','2025-05-24 14:43:11'),
(3,'Lê Văn C','0923456789','Số 789 Đường Cách Mạng Tháng 8, Quận 3, TP.HCM','2025-05-24 14:43:11'),
(4,'Phạm Thị D','0934567890','Số 101 Đường Võ Văn Tần, Quận 3, TP.HCM','2025-05-24 14:43:11'),
(5,'Hoàng Văn E','0945678901','Số 202 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM','2025-05-24 14:43:11');


-- Create vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    license_plate VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('Car', 'Motorcycle') NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(owner_id) ON DELETE CASCADE
);
INSERT INTO `vehicles` VALUES 
(2,1,'51A-67890','Motorcycle','Honda','Wave','Xanh','2025-05-24 14:43:48'),
(3,2,'59A-23456','Car','Honda','Civic','Trắng','2025-05-24 14:43:48'),
(4,3,'59P-34567','Motorcycle','Yamaha','Exciter','Đỏ','2025-05-24 14:43:48'),
(5,4,'51D-45678','Car','Ford','Ranger','Xám','2025-05-24 14:43:48'),
(6,5,'51F-56789','Car','Mazda','CX-5','Đỏ','2025-05-24 14:43:48'),
(7,1,'51A-12345','Car','Toyota','Camry','Đen','2025-05-24 14:49:24');


-- Create violations table
CREATE TABLE IF NOT EXISTS violations (
    violation_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    description TEXT NOT NULL,
    fine DECIMAL(10,2) NOT NULL,
    violation_date DATE NOT NULL,
    location VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);
INSERT INTO `violations` VALUES 
(3,2,'Không đội mũ bảo hiểm',500000.00,'2025-05-10','Đường Nguyễn Huệ, Quận 1','2025-05-24 14:44:35'),(4,3,'Vượt quá tốc độ',1200000.00,'2025-05-25','Đường Võ Văn Kiệt','2025-05-24 14:44:35'),
(5,4,'Không có giấy phép lái xe',1000000.00,'2025-04-10','Đường Điện Biên Phủ','2025-05-24 14:44:35'),(6,5,'Đậu xe sai quy định',700000.00,'2025-05-26','Đường Lê Duẩn, Quận 1','2025-05-24 14:44:35');


-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    violation_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Online', 'Offline') NOT NULL,
    status ENUM('Pending', 'Completed', 'Failed') NOT NULL,
    payment_date TIMESTAMP NOT NULL,
    payer_name VARCHAR(100),
    payer_phone VARCHAR(15),
    payer_email VARCHAR(100),
    note TEXT,
    FOREIGN KEY (violation_id) REFERENCES violations(violation_id) ON DELETE CASCADE
);

-- Create operation_history table
CREATE TABLE IF NOT EXISTS operation_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    target_table VARCHAR(50) NOT NULL,
    target_id INT NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO `categories` VALUES 
(1,'Vượt đèn đỏ','Vi phạm tín hiệu giao thông đèn đỏ','2025-05-24 14:45:43'),
(2,'Vượt tốc độ','Vi phạm giới hạn tốc độ quy định','2025-05-24 14:45:43'),
(3,'Đậu xe sai quy định','Đậu xe không đúng nơi quy định','2025-05-24 14:45:43'),
(4,'Không đội mũ bảo hiểm','Không đội mũ bảo hiểm khi tham gia giao thông','2025-05-24 14:45:43'),
(5,'Không có giấy phép','Không có giấy phép lái xe hoặc giấy tờ xe','2025-05-24 14:45:43');


-- Create news table
CREATE TABLE IF NOT EXISTS news (
	id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    image VARCHAR(255),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO `news` VALUES 
(1,'Cảnh sát giao thông bắt cướp','Cảnh sát giao thông bắt cướp','uploads/news/news_6831e701348d3.jpg','Một đối tượng nguy hiểm lạng lách đánh võng, bị tổ công an giao thông vây bắt','2025-05-24 15:34:25','2025-05-24 15:34:25'),
(2,'Tai nạn giao thông','Tai nạn giao thông','uploads/news/news_6832308b3a61a.jpeg','Tai nạn giao thông giữa 2 xe máy','2025-05-24 20:48:11','2025-05-24 20:48:11'),
(3,'CSGT ra quân','CSGT ra quân1','uploads/news/news_683230abc8b02.jpg','CSGT ra quân','2025-05-24 20:48:43','2025-05-24 20:48:43');


-- Create indexes for better performance
CREATE INDEX idx_vehicles_license_plate ON vehicles(license_plate);
CREATE INDEX idx_violations_date ON violations(violation_date);
CREATE INDEX idx_violations_location ON violations(location);
CREATE INDEX idx_payments_method ON payments(payment_method);
CREATE INDEX idx_payments_status ON payments(status);

-- insert into users(username, password) values ('admin', 'admin123');
-- USE vehicle_violation_system;
-- select *  from users;

-- update users
-- set username = 'admin1'
-- where user_id = 1;