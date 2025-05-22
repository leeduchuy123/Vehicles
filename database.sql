-- Create database
CREATE DATABASE IF NOT EXISTS vehicle_violation_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_violation_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create owners table
CREATE TABLE IF NOT EXISTS owners (
    owner_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    violation_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Online', 'Offline') NOT NULL,
    status ENUM('Pending', 'Completed', 'Failed') NOT NULL,
    payment_date TIMESTAMP NOT NULL,
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
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_vehicles_license_plate ON vehicles(license_plate);
CREATE INDEX idx_violations_date ON violations(violation_date);
CREATE INDEX idx_violations_location ON violations(location);
CREATE INDEX idx_payments_method ON payments(payment_method);
CREATE INDEX idx_payments_status ON payments(status);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$8MJvKSRXYSJ1Yd7MiOAUYeZA.4XzfBMt9xhutA4QqYsYLQzNXDKMi');

-- Insert sample data for owners
INSERT INTO owners (name, phone, address) VALUES
('Nguyễn Văn A', '0901234567', 'Số 123 Đường Lê Lợi, Quận 1, TP.HCM'),
('Trần Thị B', '0912345678', 'Số 456 Đường Nguyễn Huệ, Quận 1, TP.HCM'),
('Lê Văn C', '0923456789', 'Số 789 Đường Cách Mạng Tháng 8, Quận 3, TP.HCM'),
('Phạm Thị D', '0934567890', 'Số 101 Đường Võ Văn Tần, Quận 3, TP.HCM'),
('Hoàng Văn E', '0945678901', 'Số 202 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM');

-- Insert sample data for vehicles
INSERT INTO vehicles (owner_id, license_plate, type, brand, model, color) VALUES
(1, '51A-12345', 'Car', 'Toyota', 'Camry', 'Đen'),
(1, '51A-67890', 'Motorcycle', 'Honda', 'Wave', 'Xanh'),
(2, '59A-23456', 'Car', 'Honda', 'Civic', 'Trắng'),
(3, '59P-34567', 'Motorcycle', 'Yamaha', 'Exciter', 'Đỏ'),
(4, '51D-45678', 'Car', 'Ford', 'Ranger', 'Xám'),
(5, '51F-56789', 'Car', 'Mazda', 'CX-5', 'Đỏ');

-- Insert sample data for violations
INSERT INTO violations (vehicle_id, description, fine, violation_date, location) VALUES
(1, 'Vượt đèn đỏ', 1500000, '2023-05-15', 'Ngã tư Phú Nhuận'),
(1, 'Đậu xe sai quy định', 700000, '2023-06-20', 'Đường Lê Lợi, Quận 1'),
(2, 'Không đội mũ bảo hiểm', 500000, '2023-06-10', 'Đường Nguyễn Huệ, Quận 1'),
(3, 'Vượt quá tốc độ', 1200000, '2023-05-25', 'Đường Võ Văn Kiệt'),
(4, 'Không có giấy phép lái xe', 1000000, '2023-06-05', 'Đường Điện Biên Phủ'),
(5, 'Đậu xe sai quy định', 700000, '2023-06-15', 'Đường Lê Duẩn, Quận 1');

-- Insert sample data for payments
INSERT INTO payments (violation_id, amount, payment_method, status, payment_date) VALUES
(1, 1500000, 'Online', 'Completed', '2023-05-20 10:30:00'),
(3, 500000, 'Offline', 'Completed', '2023-06-15 14:45:00'),
(4, 1200000, 'Online', 'Pending', '2023-06-01 09:15:00');

-- Insert sample data for categories
INSERT INTO categories (category_name, description) VALUES
('Vượt đèn đỏ', 'Vi phạm tín hiệu giao thông đèn đỏ'),
('Vượt tốc độ', 'Vi phạm giới hạn tốc độ quy định'),
('Đậu xe sai quy định', 'Đậu xe không đúng nơi quy định'),
('Không đội mũ bảo hiểm', 'Không đội mũ bảo hiểm khi tham gia giao thông'),
('Không có giấy phép', 'Không có giấy phép lái xe hoặc giấy tờ xe');
