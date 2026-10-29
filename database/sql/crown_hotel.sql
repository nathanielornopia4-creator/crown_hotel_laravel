CREATE DATABASE IF NOT EXISTS crown_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crown_hotel;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','Hotel Administrator/Manager','Front Desk Staff','Hotel Staff') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
  room_id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(30) NOT NULL UNIQUE,
  room_type ENUM('Standard Room','Deluxe Room','Family Size','Suite') NOT NULL,
  image VARCHAR(255) DEFAULT '',
  capacity INT NOT NULL DEFAULT 2,
  rate DECIMAL(12,2) NOT NULL,
  status ENUM('Available','Occupied','Reserved','Maintenance','Cleaning','Inactive') NOT NULL DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservations (
  reservation_id INT AUTO_INCREMENT PRIMARY KEY,
  reference_no VARCHAR(30) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  guest_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  nights INT NOT NULL,
  rate DECIMAL(12,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  downpayment DECIMAL(12,2) NOT NULL,
  payment_method ENUM('Bank Transfer','Walk-in') NOT NULL DEFAULT 'Bank Transfer',
  payment_reference VARCHAR(100) DEFAULT '',
  status ENUM('Pending','Reserved','Confirmed','Checked In','Checked Out','Cancelled') NOT NULL DEFAULT 'Pending',
  policy_accepted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(user_id),
  CONSTRAINT fk_res_room FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);

CREATE TABLE transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method ENUM('Bank Transfer','Walk-in') NOT NULL DEFAULT 'Bank Transfer',
  reference_details VARCHAR(150) DEFAULT '',
  status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_res FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

INSERT INTO rooms (room_number, room_type, image, capacity, rate, status) VALUES
('101','Standard Room','',2,23000,'Available'),
('201','Deluxe Room','',2,35000,'Available'),
('301','Family Size','',5,52000,'Available'),
('401','Suite','',4,86000,'Available');
