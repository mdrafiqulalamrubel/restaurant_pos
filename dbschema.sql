-- Run this to create the database and tables
CREATE DATABASE IF NOT EXISTS restaurant_pos_bookings;
USE restaurant_pos_bookings;

-- Items (products: food/drink + booking items)
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  cost_price DECIMAL(10,2) DEFAULT 0,
  booking_required TINYINT(1) DEFAULT 0,
  booking_type VARCHAR(20) DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales (invoices)
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales items (line items)
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  item_id INT NOT NULL,
  qty DECIMAL(10,3) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Bookings (for booking items only)
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_item_id INT NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  duration DECIMAL(5,1) DEFAULT 1,
  notes TEXT,
  status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE CASCADE
);