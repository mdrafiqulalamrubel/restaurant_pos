-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 12:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_pos_bookings`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `duration` decimal(5,1) DEFAULT 1.0,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `sale_item_id`, `booking_date`, `booking_time`, `duration`, `notes`, `status`) VALUES
(1, 3, '2026-05-17', '13:28:00', 1.0, '', 'pending'),
(2, 6, '2026-05-17', '13:34:00', 1.0, '', 'pending'),
(3, 10, '2026-05-17', '13:36:00', 1.0, '', 'pending'),
(4, 11, '2026-05-17', '13:28:00', 1.0, '', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(200) DEFAULT 'Restaurant POS',
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `currency` varchar(10) DEFAULT '€',
  `currency_code` varchar(5) DEFAULT 'EUR',
  `tax_rate` decimal(5,2) DEFAULT 10.00,
  `receipt_footer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `name`, `address`, `phone`, `email`, `website`, `logo`, `currency`, `currency_code`, `tax_rate`, `receipt_footer`, `created_at`, `updated_at`) VALUES
(1, 'Daff Restaurant', '102, Shukrabad, Dhanmondi\r\nDhaka', '01782382140', 'rafiqulalam2@gmail.com', 'https://daffodilsoft.com', 'uploads/company/logo.png', '৳', 'BDT', 10.00, 'Thank you for visiting us!', '2026-05-17 07:23:53', '2026-05-17 08:32:34');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`, `due_amount`) VALUES
(1, 'MUHAMMAD RAFIQUL ALAM', '01782382140', 'rafiqulalam2@gmail.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 06:46:08', 0.00),
(2, 'MUHAMMAD RAFIQUL ALAM', '01782382140', 'rafiqulalam2@gmail.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 06:47:57', 0.00),
(3, 'MUHAMMAD RAFIQUL ALAM', '01782382140', 'rafiqulalam2@gmail.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 06:51:38', 0.00),
(4, 'Sparsha', '0175', 'software20@daffodil-bd.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 08:43:31', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `decorators`
--

CREATE TABLE `decorators` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dining_tables`
--

CREATE TABLE `dining_tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `location` varchar(50) DEFAULT NULL,
  `status` enum('available','occupied','reserved','maintenance') DEFAULT 'available',
  `current_order_id` int(11) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dining_tables`
--

INSERT INTO `dining_tables` (`id`, `table_number`, `capacity`, `location`, `status`, `current_order_id`, `qr_code`) VALUES
(1, 'T01', 2, 'Window Side', 'available', 5, NULL),
(2, 'T02', 2, 'Window Side', 'available', 4, NULL),
(3, 'T03', 4, 'Center', 'available', NULL, NULL),
(4, 'T04', 4, 'Center', 'available', NULL, NULL),
(5, 'T05', 6, 'Back Area', 'available', NULL, NULL),
(6, 'T06', 6, 'Back Area', 'available', NULL, NULL),
(7, 'T07', 8, 'VIP Corner', 'available', NULL, NULL),
(8, 'T08', 10, 'VIP Corner', 'available', NULL, NULL),
(9, 'T01', 2, 'Window Side', 'available', 6, NULL),
(10, 'T02', 2, 'Window Side', 'available', NULL, NULL),
(11, 'T03', 4, 'Center', 'available', NULL, NULL),
(12, 'T04', 4, 'Center', 'available', NULL, NULL),
(13, 'T05', 6, 'Back Area', 'available', NULL, NULL),
(14, 'T06', 6, 'Back Area', 'available', NULL, NULL),
(15, 'T07', 8, 'VIP Corner', 'available', NULL, NULL),
(16, 'T08', 10, 'VIP Corner', 'available', NULL, NULL),
(17, 'T01', 2, 'Window Side', 'available', 3, NULL),
(18, 'T02', 2, 'Window Side', 'available', 7, NULL),
(19, 'T03', 4, 'Center', 'available', NULL, NULL),
(20, 'T04', 4, 'Center', 'available', NULL, NULL),
(21, 'T05', 6, 'Back Area', 'available', NULL, NULL),
(22, 'T06', 6, 'Back Area', 'available', NULL, NULL),
(23, 'T07', 8, 'VIP Corner', 'available', NULL, NULL),
(24, 'T08', 10, 'VIP Corner', 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT 'cash',
  `receipt` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `booking_required` tinyint(1) DEFAULT 0,
  `booking_type` varchar(20) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `manufacturer_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `unit_price`, `cost_price`, `booking_required`, `booking_type`, `active`, `created_at`, `manufacturer_id`, `category`, `image`, `description`) VALUES
(1, 'Burger', 12.99, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Food', 'uploads/items/1778998218_6a095bca51492.webp', ''),
(2, 'Pizza', 18.99, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Food', 'uploads/items/1778998247_6a095be7b4e0a.webp', ''),
(3, 'Pasta', 14.99, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Food', 'uploads/items/1778998237_6a095bdd79ec5.png', ''),
(4, 'Coke', 3.50, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Beverages', 'uploads/items/1778998176_6a095ba0c3ea1.png', ''),
(5, 'Coffee', 4.50, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Beverages', 'uploads/items/1778998164_6a095b94dfac2.jpg', ''),
(6, 'Beer', 5.99, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Beverages', 'uploads/items/1778998150_6a095b86d619f.jpg', ''),
(7, 'Table Booking', 50.00, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Booking', 'uploads/items/1778998204_6a095bbc21aa6.jpg', ''),
(8, 'Private Hall', 200.00, 0.00, 0, '', 1, '2026-05-17 05:48:51', NULL, 'Booking', 'uploads/items/1778998189_6a095bada820b.png', ''),
(9, 'Hall Booking', 200.00, 0.00, 1, 'hall', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001502_6a09689e9366f.jpg', ''),
(10, 'Auditorium Booking', 300.00, 0.00, 1, '', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001471_6a09687fceaa1.jpg', ''),
(11, 'Rooftop Booking', 100.00, 0.00, 1, '', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001521_6a0968b1551d5.png', ''),
(12, 'Table Booking (2-Seater)', 10.00, 0.00, 1, 'table', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001534_6a0968bea4db9.jpg', ''),
(13, 'Table Booking (4-Seater)', 20.00, 0.00, 1, 'table', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001550_6a0968ce18021.png', ''),
(14, 'Table Booking (6-Seater)', 30.00, 0.00, 1, 'table', 1, '2026-05-17 06:33:15', NULL, 'Booking', 'uploads/items/1779001560_6a0968d8133c6.png', ''),
(15, 'Decorator Service', 150.00, 0.00, 1, '', 1, '2026-05-17 06:33:15', NULL, '', 'uploads/items/1779001587_6a0968f3e4142.png', ''),
(16, 'Catering Service', 500.00, 0.00, 1, 'catering', 1, '2026-05-17 06:33:15', NULL, '', 'uploads/items/1779001573_6a0968e5bc27f.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `manufacturers`
--

CREATE TABLE `manufacturers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manufacturers`
--

INSERT INTO `manufacturers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `created_at`, `opening_balance`) VALUES
(1, 'Daffodil International University', 'MUHAMMAD RAFIQUL ALAM', '01782382140', 'rafiqulalam2@gmail.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 07:06:38', 0.00),
(2, 'Daffodil International University', 'MUHAMMAD RAFIQUL ALAM', '01782382140', 'rafiqulalam2@gmail.com', '102, Shukrabad, Dhanmondi\r\nDhaka', '2026-05-17 07:13:50', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_tokens`
--

CREATE TABLE `order_tokens` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `token_number` int(11) NOT NULL,
  `token_date` date NOT NULL,
  `status` enum('pending','ready','served','completed') DEFAULT 'pending',
  `printed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tokens`
--

INSERT INTO `order_tokens` (`id`, `sale_id`, `token_number`, `token_date`, `status`, `printed_at`) VALUES
(1, 2, 1, '2026-05-17', 'pending', NULL),
(2, 3, 2, '2026-05-17', 'pending', NULL),
(3, 4, 3, '2026-05-17', 'pending', NULL),
(4, 5, 4, '2026-05-17', 'pending', NULL),
(5, 6, 5, '2026-05-17', 'pending', NULL),
(6, 7, 6, '2026-05-17', 'pending', NULL),
(7, 8, 7, '2026-05-17', 'pending', NULL),
(8, 9, 8, '2026-05-17', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `resource_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` decimal(5,1) DEFAULT 1.0,
  `booking_type` enum('hourly','daily','weekly','monthly') DEFAULT 'hourly',
  `event_type` varchar(50) DEFAULT NULL,
  `number_of_guests` int(11) DEFAULT 0,
  `special_requests` text DEFAULT NULL,
  `decorator_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `resource_type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `price_per_hour` decimal(10,2) DEFAULT 0.00,
  `price_per_day` decimal(10,2) DEFAULT 0.00,
  `images` text DEFAULT NULL,
  `status` enum('available','maintenance','booked') DEFAULT 'available',
  `features` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `resource_type_id`, `name`, `description`, `capacity`, `price_per_hour`, `price_per_day`, `images`, `status`, `features`) VALUES
(1, 1, 'Grand Ballroom', 'Luxury hall for weddings and parties', 500, 200.00, 1500.00, NULL, 'available', 'AC, Sound System, Stage, Lighting'),
(2, 1, 'Garden Hall', 'Outdoor garden setting', 300, 150.00, 1000.00, NULL, 'available', 'Garden View, Open Air, BBQ Area'),
(3, 2, 'Main Auditorium', 'Professional auditorium with stage', 800, 300.00, 2000.00, NULL, 'available', 'Projector, Stage, Dressing Rooms'),
(4, 3, 'Sky Rooftop', 'Rooftop with city view', 200, 100.00, 800.00, NULL, 'available', 'City View, Bar, Lounge Area'),
(5, 4, 'Board Room', 'Executive meeting room', 20, 50.00, 300.00, NULL, 'available', 'Whiteboard, Projector, WiFi'),
(6, 5, 'Kids Party Room', 'Colorful room for children parties', 50, 75.00, 500.00, NULL, 'available', 'Toys, Games, Decorations');

-- --------------------------------------------------------

--
-- Table structure for table `resource_types`
--

CREATE TABLE `resource_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT 'building',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resource_types`
--

INSERT INTO `resource_types` (`id`, `name`, `icon`, `sort_order`) VALUES
(1, 'Hall', 'building', 1),
(2, 'Auditorium', 'theater-masks', 2),
(3, 'Rooftop', 'umbrella-beach', 3),
(4, 'Conference Room', 'users', 4),
(5, 'Party Room', 'gift', 5);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `total`, `created_at`, `customer_id`, `payment_method`, `discount`, `tax`, `paid_amount`, `due_amount`) VALUES
(1, 8.80, '2026-05-17 06:26:20', NULL, 'cash', 0.00, 0.80, 8.80, 0.00),
(2, 330.00, '2026-05-17 07:28:36', NULL, 'card', 0.00, 30.00, 330.00, 0.00),
(3, 8.80, '2026-05-17 07:33:11', NULL, 'cash', 0.00, 0.80, 8.80, 0.00),
(4, 510.39, '2026-05-17 07:35:35', NULL, 'cash', 0.00, 46.40, 510.39, 0.00),
(5, 330.00, '2026-05-17 07:36:14', NULL, 'card', 0.00, 30.00, 330.00, 0.00),
(6, 67.06, '2026-05-17 07:38:55', NULL, 'card', 0.00, 6.10, 67.06, 0.00),
(7, 33.53, '2026-05-17 07:45:30', NULL, 'bkash', 0.00, 3.05, 33.53, 0.00),
(8, 11.54, '2026-05-17 07:54:11', NULL, 'cash', 0.00, 1.05, 11.54, 0.00),
(9, 83.55, '2026-05-17 08:28:54', NULL, 'cash', 0.00, 7.60, 83.55, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `item_id`, `qty`, `unit_price`) VALUES
(1, 1, 4, 1.000, 3.50),
(2, 1, 5, 1.000, 4.50),
(3, 2, 10, 1.000, 300.00),
(4, 3, 4, 1.000, 3.50),
(5, 3, 5, 1.000, 4.50),
(6, 4, 15, 1.000, 150.00),
(7, 4, 6, 1.000, 5.99),
(8, 4, 5, 1.000, 4.50),
(9, 4, 4, 1.000, 3.50),
(10, 4, 10, 1.000, 300.00),
(11, 5, 10, 1.000, 300.00),
(12, 6, 4, 1.000, 3.50),
(13, 6, 5, 1.000, 4.50),
(14, 6, 6, 1.000, 5.99),
(15, 6, 1, 1.000, 12.99),
(16, 6, 3, 1.000, 14.99),
(17, 6, 2, 1.000, 18.99),
(18, 7, 4, 2.000, 3.50),
(19, 7, 6, 1.000, 5.99),
(20, 7, 5, 1.000, 4.50),
(21, 7, 1, 1.000, 12.99),
(22, 8, 5, 1.000, 4.50),
(23, 8, 6, 1.000, 5.99),
(24, 9, 4, 1.000, 3.50),
(25, 9, 5, 1.000, 4.50),
(26, 9, 6, 1.000, 5.99),
(27, 9, 2, 1.000, 18.99),
(28, 9, 3, 2.000, 14.99),
(29, 9, 1, 1.000, 12.99);

-- --------------------------------------------------------

--
-- Table structure for table `table_orders`
--

CREATE TABLE `table_orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `order_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `table_orders`
--

INSERT INTO `table_orders` (`id`, `table_id`, `sale_id`, `order_time`, `status`) VALUES
(1, 1, 2, '2026-05-17 07:28:36', 'active'),
(2, 17, 3, '2026-05-17 07:33:11', 'active'),
(3, 2, 4, '2026-05-17 07:35:35', 'active'),
(4, 1, 5, '2026-05-17 07:36:14', 'active'),
(5, 9, 6, '2026-05-17 07:38:55', 'active'),
(6, 18, 7, '2026-05-17 07:45:30', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff','cashier') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `phone`, `password_hash`, `role`, `created_at`, `is_active`, `last_login`, `last_ip`) VALUES
(1, 'admin', NULL, NULL, NULL, '$2y$10$z9XPt8wL89OX/V7qFvijEOxH3MJ7bNj92Px2./QW1lypCQuycj5bK', 'admin', '2026-05-17 04:31:59', 1, '2026-05-17 10:01:55', '::1'),
(2, 'staff', 'Staff User', 'staff@restaurant.com', '1234567890', '$2y$10$D2spKZcUqb7c21dFP1Thd.V.f4v4PuUImSQupOXWueScU7d0X.t.m', 'staff', '2026-05-17 07:56:54', 1, '2026-05-17 09:22:06', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'user_updated', 'Updated user: staff', '::1', NULL, '2026-05-17 08:01:44'),
(2, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 08:19:04'),
(3, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:20:40'),
(4, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 08:20:43'),
(5, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:21:02'),
(6, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:21:08'),
(7, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 08:29:37'),
(8, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:30:26'),
(9, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 08:32:40'),
(10, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:32:41'),
(11, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 08:32:49'),
(12, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 08:42:06'),
(13, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 09:18:37'),
(16, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 09:18:54'),
(17, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 09:18:58'),
(20, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 09:21:49'),
(21, 1, 'logout', 'User logged out', '::1', NULL, '2026-05-17 09:21:58'),
(22, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 09:22:06'),
(23, 2, 'logout', 'User logged out', '::1', NULL, '2026-05-17 09:36:56'),
(24, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 09:37:02'),
(25, 1, 'logout', 'User logged out', '::1', NULL, '2026-05-17 10:01:52'),
(26, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-17 10:01:55'),
(27, 1, 'logout', 'User logged out', '::1', NULL, '2026-05-17 10:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `permission_value` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission_key`, `permission_value`, `created_at`) VALUES
(1, 1, 'all_access', 1, '2026-05-17 07:56:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_item_id` (`sale_item_id`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `decorators`
--
ALTER TABLE `decorators`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dining_tables`
--
ALTER TABLE `dining_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manufacturer_id` (`manufacturer_id`);

--
-- Indexes for table `manufacturers`
--
ALTER TABLE `manufacturers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_tokens`
--
ALTER TABLE `order_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token_date` (`token_number`,`token_date`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_type_id` (`resource_type_id`);

--
-- Indexes for table `resource_types`
--
ALTER TABLE `resource_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `table_orders`
--
ALTER TABLE `table_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_permission` (`user_id`,`permission_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `decorators`
--
ALTER TABLE `decorators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dining_tables`
--
ALTER TABLE `dining_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `manufacturers`
--
ALTER TABLE `manufacturers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_tokens`
--
ALTER TABLE `order_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `resource_types`
--
ALTER TABLE `resource_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `table_orders`
--
ALTER TABLE `table_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`);

--
-- Constraints for table `order_tokens`
--
ALTER TABLE `order_tokens`
  ADD CONSTRAINT `order_tokens_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `table_orders`
--
ALTER TABLE `table_orders`
  ADD CONSTRAINT `table_orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `dining_tables` (`id`),
  ADD CONSTRAINT `table_orders_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
