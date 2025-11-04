-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 08:24 AM
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
-- Database: `clothing_store101`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_settings`
--

CREATE TABLE `about_settings` (
  `id` int(11) NOT NULL,
  `our_company` text DEFAULT NULL,
  `our_history` text DEFAULT NULL,
  `our_mission` text DEFAULT NULL,
  `our_vision` text DEFAULT NULL,
  `values_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`values_json`)),
  `banner_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_settings`
--

INSERT INTO `about_settings` (`id`, `our_company`, `our_history`, `our_mission`, `our_vision`, `values_json`, `banner_image`) VALUES
(1, 'We are a customer-first apparel brand crafting modern essentials with quality, comfort, and sustainability in mind. WOW', 'Founded in 2020 by a small team of designers and engineers, we set out to remove friction between people and great clothing.', 'Empower customers to look and feel their best with thoughtfully designed essentials—delivered with exceptional service and honest pricing.', 'To be the most trusted everyday apparel brand in Africa and beyond, known for quality, sustainability, and a remarkable shopping experience.', '[{\"title\":\"Customer Obsession\",\"text\":\"We design, build, and improve with your feedback at the center.\"},{\"title\":\"Quality\",\"text\":\"Materials and craftsmanship that stand up to real life—wash after wash.\"},{\"title\":\"Transparency\",\"text\":\"Clear pricing, clear communication, clear policies.\"},{\"title\":\"Sustainability\",\"text\":\"Responsible sourcing and packaging with an eye on long-term impact.\"},{\"title\":\"Inclusivity\",\"text\":\"Styles and sizes for everyone, because great clothing is for all.\"}]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `line1` varchar(150) NOT NULL,
  `line2` varchar(150) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `status` enum('active','ordered','abandoned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 3, 'oqmdtgb1qe0j1o3nio2st8lqrt', 'ordered', '2025-10-09 20:29:39', '2025-10-10 09:20:29'),
(5, NULL, 'dm3nei36r5pe14lecdbl2kkgr5', 'active', '2025-10-09 21:43:26', '2025-10-09 21:43:30'),
(6, 2, 'l6a5e1naben6m2sb7iqdo1ag1j', 'active', '2025-10-09 21:53:18', '2025-10-10 08:12:51'),
(8, 3, 'gnkhfs88ckffsrnp49na2hkp4f', 'ordered', '2025-10-10 09:22:53', '2025-10-10 09:23:15'),
(13, 6, '7vnt91mobm2b9d6eivrnqgc4us', 'ordered', '2025-10-10 09:47:00', '2025-10-10 09:47:28'),
(17, NULL, '44eeg5qatpcf08cbs37jvauah2', 'active', '2025-10-10 13:07:28', '2025-10-10 13:08:34'),
(18, NULL, 'k6qcll7bggse5mgomf86hf6of0', 'active', '2025-10-10 13:11:03', '2025-10-10 13:11:08'),
(19, NULL, 'fnglll9cf13firmnrpajcrg984', 'active', '2025-10-11 04:26:33', '2025-10-11 04:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `variant_id`, `quantity`, `price`, `created_at`) VALUES
(9, 5, 1, 5, 20.00, '2025-10-09 21:43:26'),
(13, 6, 5, 2, 17.00, '2025-10-09 22:02:01'),
(15, 4, 5, 3, 17.00, '2025-10-10 09:19:11'),
(16, 8, 5, 2, 17.00, '2025-10-10 09:22:53'),
(17, 13, 5, 3, 17.00, '2025-10-10 09:47:00'),
(21, 17, 1, 1, 20.00, '2025-10-10 13:08:28'),
(25, 19, 7, 5, 500.00, '2025-10-11 04:28:03'),
(26, 19, 5, 1, 17.00, '2025-10-11 04:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `parent_id`, `created_at`) VALUES
(1, 'Tops', 'tops', NULL, '2025-10-10 11:04:29'),
(2, 'Bottoms', 'bottoms', NULL, '2025-10-10 11:04:49'),
(3, 'Dresses', 'dresses', NULL, '2025-10-10 11:05:02');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `address`, `contact_email`, `phone`) VALUES
(1, 'Ecom Clothing', '123 Main Street, Addis Ababa', 'info@ecom.com', '+1234569999');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `phone`, `role`, `salary`, `hire_date`, `status`) VALUES
(3, 'Natan q', NULL, '096766767', 'Manager', 123.00, '2025-10-06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT 0,
  `email` varchar(150) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `delivery_location` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `payment_method` enum('cod','bank','card','paypal') NOT NULL,
  `transaction_ref` varchar(150) DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `order_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`order_items`)),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `is_guest`, `email`, `first_name`, `last_name`, `city`, `delivery_location`, `phone_number`, `payment_method`, `transaction_ref`, `payment_screenshot`, `order_items`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 0, '', 'Abela', 'Kebede', 'Addis Ababa', 'Megenagna', '0927364738', 'bank', '12345678910', '/uploads/payments/proof_20251010_112029_647db700.jpg', '[{\"name\":\"Jeans\",\"variant_id\":5,\"size\":\"XS\",\"color\":\"Black\",\"quantity\":3,\"price\":17,\"line_total\":51}]', 65.27, 'pending', '2025-10-10 09:20:29', NULL),
(2, 3, 0, '', 'Abela', 'Kebede', 'Adama', 'Derartu Tulu Square', '0927364738', 'bank', '12345678910', '/uploads/payments/proof_20251010_112315_05f466a6.jpg', '[{\"name\":\"Jeans\",\"variant_id\":5,\"size\":\"XS\",\"color\":\"Black\",\"quantity\":2,\"price\":17,\"line_total\":34}]', 46.85, 'pending', '2025-10-10 09:23:15', NULL),
(3, 6, 0, '', 'John', 'Doe', 'Adama', 'Derartu Tulu Square', '096253634', 'bank', '98393484398', '/uploads/payments/proof_20251010_114728_e9125bed.jpg', '[{\"name\":\"Jeans\",\"variant_id\":5,\"size\":\"XS\",\"color\":\"Black\",\"quantity\":3,\"price\":17,\"line_total\":51}]', 65.27, 'pending', '2025-10-10 09:47:28', '2025-10-10 11:26:08');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `times_ordered` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `gender`, `base_price`, `description`, `image`, `created_at`, `times_ordered`) VALUES
(1, 'Cotton Shirt', 'Tops', 'male', 10.00, NULL, 'uploads/products/white_shirt_1759934346.jpg', '2025-10-08 14:39:06', 0),
(2, 'Hoodie', 'Tops', 'male', 20.00, NULL, 'uploads/products/hoodie_1759943919.jpg', '2025-10-08 17:18:39', 0),
(3, 'Jeans', 'Bottoms', 'male', 15.00, 'Classic denim jeans crafted for comfort and style. Made from durable, high-quality fabric with a perfect fit for everyday wear. Ideal for casual outings or dressed-up looks.', 'uploads/products/jeanz_1760036527.jpg', '2025-10-09 19:02:07', 0),
(4, 'Sundress', 'Dresses', 'female', 500.00, 'A light, breezy sundress perfect for sunny days. Made from soft, breathable fabric, it features a flattering neckline, adjustable straps, and a gently flared skirt for effortless summer style.', 'uploads/products/yellow_sundress_1760095994.jpg', '2025-10-10 11:33:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(150) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` enum('XS','S','M','L','XL','XXL') DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `color`, `size`, `stock`, `price`, `image`) VALUES
(1, 2, 'Black', 'XS', 20, NULL, 'uploads/variants/black_hoodie_1759943986.jpg'),
(2, 1, 'Red', 'M', 10, NULL, 'uploads/variants/red_shirt_1759944770.jpg'),
(5, 3, 'Black', 'XS', 22, 17.00, 'uploads/variants/black_jeans_1760036649.jpg'),
(6, 4, 'yellow', 'XS', 30, NULL, NULL),
(7, 4, 'Blue', 'M', 20, NULL, 'uploads/variants/blue_sundress_1760096078.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 3, 3, 'This product is very good.', '2025-10-09 19:31:44'),
(2, 4, 6, 2, 'It is not bad', '2025-10-10 11:51:03'),
(3, 2, 6, 4, 'Meh', '2025-10-10 11:52:10'),
(4, 1, 6, 1, 'the product is very bad', '2025-10-10 11:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `site_assets`
--

CREATE TABLE `site_assets` (
  `id` int(11) NOT NULL,
  `banner_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_assets`
--

INSERT INTO `site_assets` (`id`, `banner_path`) VALUES
(1, 'uploads/banners/banner_20251009_193229_46778713.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `change_amount` int(11) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `variant_id`, `change_amount`, `reason`, `created_at`) VALUES
(1, 5, -3, 'order', '2025-10-10 09:20:29'),
(2, 5, -2, 'order', '2025-10-10 09:23:15'),
(3, 5, -3, 'order', '2025-10-10 09:47:28');

-- --------------------------------------------------------

--
-- Table structure for table `support_settings`
--

CREATE TABLE `support_settings` (
  `id` int(11) NOT NULL,
  `faqs_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`faqs_json`)),
  `shipping_points_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_points_json`)),
  `returns_points_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`returns_points_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_settings`
--

INSERT INTO `support_settings` (`id`, `faqs_json`, `shipping_points_json`, `returns_points_json`) VALUES
(1, '[{\"q\":\"What sizes do you offer?\",\"a\":\"We offer sizes XS–XXL for most items.\\nSize guides are available on each product page.\"},{\"q\":\"How long will my order take?\",\"a\":\"Orders are processed in 1–2 business days. Addis Ababa deliveries typically arrive in 1–3 days, other cities 2–5 days.\"},{\"q\":\"Can I change or cancel my order?\",\"a\":\"If your order hasn’t shipped, contact us ASAP and we’ll do our best to update or cancel it.\"}]', '[\"Addis Ababa: 1–3 business days.\",\"Adama and other cities: 2–5 business days.\",\"Free shipping on orders over ETB 2000.\"]', '[\"30-day return window for unworn items with tags.\",\"Easy exchanges for size/color within 30 days.\",\"Contact support to initiate a return.\"]');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `upassword` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `fullname` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    status ENUM('Present','Absent','Late','On Leave') DEFAULT 'Present',
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_attendance (employee_id, date)
);
CREATE TABLE materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  unit VARCHAR(50) DEFAULT 'meter',  -- e.g. meters, yards, pieces
  quantity DECIMAL(10,2) DEFAULT 0,  -- total available
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE production (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  clothes_produced INT DEFAULT 0,
  material_used DECIMAL(10,2) DEFAULT 0,  -- e.g. how many meters used
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (material_id) REFERENCES materials(id)
);

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `upassword`, `role`, `fullname`, `address`, `phone`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'admin', 'admin@example.com', 'password', 'customer', 'Noah Samuel', 'Summit Street', '0966330344', '2025-10-08 13:00:34', NULL, NULL),
(2, 'samuel.teshome.worsa', 'ST@gmail.com', '$2y$10$Xgz.pex5NjMz4/u06eDASeFai6wQfYQtD2uU8RcmRRg5koC64oOSW', 'customer', 'Samuel Teshome Worsa', NULL, '+1234560000', '2025-10-08 18:06:47', NULL, NULL),
(3, 'ex', 'ex@gmail.com', '$2y$10$jWOxqj7YgMFIFP0w3fF93OsXczEjIaNeopm90765LJCvVo8DFas3q', 'customer', 'Abela Kebede', '', '0927364738', '2025-10-09 08:21:14', NULL, NULL),
(4, 'lorem', 'lorem@gmail.com', '$2y$10$jiZk4lUuPTlBkpP9X0TH3OtRJkNT//Tom0nuG4PYIbKtkRzeGqwOO', 'customer', 'Lorem Ipsum', 'Adama - Adama University', '092387328', '2025-10-09 09:57:14', NULL, NULL),
(5, 'stef.sam', 'stef@gmail.com', '$2y$10$O32ml1gDrFs3ORRFflnlV.7d/dBrhn/u7QkpkPqIisYVNXgpGlM7G', 'admin', 'Stef Sam', NULL, NULL, '2025-10-09 16:08:53', NULL, NULL),
(6, 'john', 'john@gmail.com', '$2y$10$JBVbu7ML.zIK/I2o8rhEeO8geUsBlq.tBkGHcqLPf4rp7fgayGXxO', 'customer', 'John Doe', 'Addis Ababa - Garment', '096253634', '2025-10-10 09:46:50', NULL, NULL),
(8, 'samuelnoah952', 'samuelnoah952@gmail.com', '$2y$10$TqsNUZS1OgvQLDU.hiyQDeSutpeGTMdh/30hWslp07Xot4T2ufcn2', 'customer', 'Noah Samuel', 'Addis Ababa - Megenagna', '0966330344', '2025-10-11 01:23:52', NULL, NULL),
(9, 'noah.samuel', 'samuelnoahi978@gmail.com', '$2y$10$.06Wpmzgg00YFSpZuA7QPuP/fE9I6HJ44K/6weFGGGofAgkKcvBaq', 'admin', 'Noah Samuel', NULL, NULL, '2025-10-11 01:26:20', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_settings`
--
ALTER TABLE `about_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session` (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cart_variant` (`cart_id`,`variant_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_name` (`name`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_variants_stock` (`stock`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reviews_product` (`product_id`);

--
-- Indexes for table `site_assets`
--
ALTER TABLE `site_assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `support_settings`
--
ALTER TABLE `support_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
