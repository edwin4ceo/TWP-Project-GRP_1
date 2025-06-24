-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 11:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bakeease`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProductReviewSummary` (IN `product_id_param` INT)   BEGIN
    INSERT INTO product_review_summary (
        product_id, total_reviews, average_rating, five_star_count, four_star_count, three_star_count, two_star_count, one_star_count
    )
    SELECT 
        product_id_param,
        COUNT(*),
        COALESCE(AVG(rating), 0),
        COUNT(CASE WHEN rating = 5 THEN 1 END),
        COUNT(CASE WHEN rating = 4 THEN 1 END),
        COUNT(CASE WHEN rating = 3 THEN 1 END),
        COUNT(CASE WHEN rating = 2 THEN 1 END),
        COUNT(CASE WHEN rating = 1 THEN 1 END)
    FROM product_reviews 
    WHERE product_id = product_id_param AND is_approved = TRUE
    ON DUPLICATE KEY UPDATE
        total_reviews = VALUES(total_reviews),
        average_rating = VALUES(average_rating),
        five_star_count = VALUES(five_star_count),
        four_star_count = VALUES(four_star_count),
        three_star_count = VALUES(three_star_count),
        two_star_count = VALUES(two_star_count),
        one_star_count = VALUES(one_star_count);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=eucjpms COLLATE=eucjpms_bin;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `role`, `status`, `password`, `phone`, `created_at`, `updated_at`) VALUES
('AD001', 'Admin User', 'admin@bakeease.com', 'Admin', 'Active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01236118248', '2025-06-10 17:27:08', '2025-06-18 22:06:21'),
('AD002', 'Manager User', 'manager@bakeease.com', 'Manager', 'Active', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01234567885', '2025-06-10 17:27:08', '2025-06-18 22:06:25'),
('ST246', 'edwin', 'edwin3190@gmail.com', 'Head Baker', 'Active', '$2y$10$oxiKuAy8ddLPDjw458ehzevcw1bydd7y2PUTVGjaNhLg04CDQsu46', '01828136823', '2025-06-18 22:07:26', '2025-06-18 22:07:26'),
('ST697', 'soonkit', 'soonkit0726@gmail.com', 'Cashier', 'Active', '$2y$10$3Jt43KsLbTBHsoDZmYR0guxiAH2eKVoK9y3bAVPGD3LHb3Fj4ESmK', '01114024118', '2025-06-14 23:32:35', '2025-06-18 22:05:53'),
('ST900', 'zunyi', 'zunyi1459@gmail.com', 'Delivery', 'Active', '$2y$10$3eEvQSyHFUDn8cFyL84f3eUNb32itIcqe9sqPPQ55Q6iy6SyFJkyG', '0123486384', '2025-06-18 22:05:30', '2025-06-18 22:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Cakes', 'Tasty Cakes'),
(2, 'Breads', 'Scented Breads'),
(3, 'Pastries', 'Delicious pastries');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `customer_id`, `created_at`) VALUES
(1, 'Edwin Teo Yuan Jing', 'mone01009@gmail.com', 'good', 10, '2025-06-22 14:30:15'),
(2, 'Edwin Teo Yuan Jing', 'mone01009@gmail.com', 'good', 10, '2025-06-22 14:44:14'),
(3, 'Edwin Teo Yuan Jing', 'mone01009@gmail.com', 'good', 10, '2025-06-22 14:45:16');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `phone`, `address`, `created_at`, `status`) VALUES
(1, 'wong', 'hihi26@gmail.com', '', '+601114025118', '39, Jalan Jaya Putra 5/52, Taman Jaya Putra Perdana, 81100, Johor Bahru', '2025-06-14 23:40:39', 'active'),
(2, 'chuah', 'woonlong@yahoo.com', '', '+601151676353', '84, Jalan Putra 47, Taman Daya, 81100, Johor Bahru, Johor,', '2025-06-18 05:29:17', 'active'),
(3, 'edwin', 'teooo@gmail.com', '', '+60186725368', '58, Jalan Merah, Taman Rosmerah, 73500, Ayer Keroh, Melaka', '2025-06-18 17:21:08', 'active'),
(4, 'zunyi', 'chan1459@hotmail.com', '', '+601732815422', '67, Jalan Pinang 24, Taman Daya, 81100, Johor Bahru, Johor', '2025-06-18 17:23:41', 'active'),
(5, 'ngng', 'junjun29@gmail.com', '', '+60169592825', '285, Jalan M2, Taman Merdeka, 84200, Muar, Johor', '2025-06-18 17:27:48', 'active'),
(6, 'guanwei', 'weiwei31@hotmail.com', '', '+60107891020', '69, Jalan Besar, Taman Tanjung Laboh, 83000, Batu Pahat, Johor', '2025-06-18 17:28:41', 'active'),
(7, 'tkyu', 'khaiyu02@gmail.com', '', '+601127687558', '11, Jalan Putra 2/16, Taman Setia Indah, 86000, Kluang, Johor', '2025-06-18 17:32:35', 'active'),
(8, 'keekee', 'doyouluvme@gmail.com', '', '+60113266323', '29, Jalan Perdana 3/8, Taman Mohammad, 82000, Pontian, Johor', '2025-06-18 17:33:16', 'active'),
(9, 'chunchun', 'kit719@gmail.com', '', '+601121611161', '30. Jalan Setia 3/17, Taman Indah , Alor Setar, 05100, Kedah', '2025-06-18 17:36:55', 'active'),
(10, 'ivan teo', 'mone01009@gmail.com', '$2y$10$I5rnF2efa/VeiEqV6OW4h.vCOBTscCfot7y.t466CNZD38GexbJh2', '0163558148', NULL, '2025-06-22 02:16:06', 'active'),
(11, 'EDWIN TEO YUAN JING', 'mone@gmail.com', '$2y$10$jCxADqJuzc0iQJTKHdmG2uy1v1DFboIxf8fl4s0evYKvPrgVtTjfi', '0163558148', NULL, '2025-06-23 02:20:46', 'active'),
(12, 'chan jiejie', 'ying@gmail.com', '$2y$10$N93O72d//uH4k3we3TwqZOyLrZ4LQkHRR/AyzLxXVnvZeAxExaQTa', '0183238182', NULL, '2025-06-24 01:23:30', 'active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_feedback_summary`
-- (See below for the actual view)
--
CREATE TABLE `customer_feedback_summary` (
`total_feedback` bigint(21)
,`avg_overall_rating` decimal(14,4)
,`avg_delivery_rating` decimal(14,4)
,`avg_product_quality_rating` decimal(14,4)
,`positive_recommendations` bigint(21)
,`negative_recommendations` bigint(21)
,`recommendation_percentage` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL DEFAULT 'Guest',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `customer_name`, `total_amount`, `status`, `delivery_address`, `delivery_date`, `order_date`, `customer_email`, `customer_phone`) VALUES
(8, 2, 'chuah', 12.00, 'completed', '123', NULL, '2025-06-18 14:56:37', 'woonlong@yahoo.com', '01151676353'),
(9, 1, 'wong', 123.00, 'cancelled', '123abc', NULL, '2025-06-18 14:57:05', 'hihi26@gmail.com', '01114025118'),
(23, 2, 'chuah', 123.00, 'completed', 'casa', NULL, '2025-06-18 21:05:14', 'woonlong@yahoo.com', '01151676353'),
(24, 2, 'chuah', 233.00, 'completed', 'wqdawd', NULL, '2025-06-18 21:05:24', 'woonlong@yahoo.com', '01151676353'),
(31, 3, 'edwin', 123.00, 'cancelled', 'dfdasfc', NULL, '2025-06-18 22:29:06', NULL, NULL),
(32, 3, 'edwin', 444.00, 'processing', '14fthdf', NULL, '2025-06-18 22:30:24', NULL, NULL),
(33, 7, 'tkyu', 342.00, 'processing', 'afadf', NULL, '2025-06-18 22:35:59', NULL, NULL),
(34, 7, 'tkyu', 333.00, 'pending', 'das', NULL, '2025-06-18 22:49:46', NULL, NULL),
(35, 10, 'ivan teo', 11.24, 'pending', '0', NULL, '2025-06-22 10:23:25', 'mone01009@gmail.com', '0163558148'),
(36, 10, 'ivan teo', 90.95, 'pending', '0', NULL, '2025-06-23 07:32:52', 'mone01009@gmail.com', '0163558148'),
(37, 10, 'ivan teo', 256.80, 'pending', '21', NULL, '2025-06-23 11:33:53', 'mone01009@gmail.com', '0163558148'),
(38, 10, 'ivan teo', 272.85, 'pending', '21', NULL, '2025-06-23 11:46:00', 'mone01009@gmail.com', '0163558148'),
(39, 12, 'chan jiejie', 6.96, 'pending', '21', NULL, '2025-06-24 07:28:25', 'ying@gmail.com', '0183238182'),
(40, 12, 'chan jiejie', 85.60, 'pending', '21', NULL, '2025-06-24 09:18:36', 'ying@gmail.com', '0183238182'),
(41, 12, 'chan jiejie', 6.96, 'pending', '21', NULL, '2025-06-24 09:20:05', 'ying@gmail.com', '0183238182');

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_details_view`
-- (See below for the actual view)
--
CREATE TABLE `order_details_view` (
`id` int(11)
,`customer_id` int(11)
,`customer_name` varchar(100)
,`total_amount` decimal(10,2)
,`status` varchar(20)
,`order_date` timestamp
,`delivery_address` text
,`customer_email` varchar(100)
,`customer_phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `order_feedback`
--

CREATE TABLE `order_feedback` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `overall_rating` int(11) NOT NULL CHECK (`overall_rating` >= 1 and `overall_rating` <= 5),
  `delivery_rating` int(11) NOT NULL CHECK (`delivery_rating` >= 1 and `delivery_rating` <= 5),
  `product_quality_rating` int(11) NOT NULL CHECK (`product_quality_rating` >= 1 and `product_quality_rating` <= 5),
  `comments` text NOT NULL,
  `would_recommend` enum('definitely','probably','not_sure','probably_not','definitely_not') NOT NULL,
  `improvement_suggestions` text DEFAULT NULL,
  `feedback_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_feedback`
--

INSERT INTO `order_feedback` (`id`, `order_id`, `customer_id`, `overall_rating`, `delivery_rating`, `product_quality_rating`, `comments`, `would_recommend`, `improvement_suggestions`, `feedback_date`, `is_featured`) VALUES
(1, 23, 2, 5, 5, 5, 'Outstanding experience from start to finish! The ordering process was very smooth, delivery was right on time, and the cake quality exceeded my expectations. Perfect for our family celebration!', 'definitely', 'Maybe consider offering more sugar-free options for health-conscious customers.', '2025-06-19 13:30:00', 0),
(2, 24, 2, 4, 4, 5, 'Very satisfied with my order! The products were fresh and delicious. Delivery was punctual and everything was well-packaged. Great bakery service overall.', 'definitely', 'Would love to see weekend delivery slots for busy working customers like myself.', '2025-06-19 14:00:00', 0),
(3, 32, 3, 4, 3, 4, 'Good quality baked goods and reasonable pricing. The items were fresh and tasty. Delivery took slightly longer than expected but still within promised timeframe. Overall satisfied.', 'probably', 'Faster delivery during peak hours would be great. Maybe provide real-time order tracking feature.', '2025-06-19 07:30:00', 0),
(4, 40, 12, 3, 4, 4, 'good', 'definitely', 'good', '2025-06-24 09:19:04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 35, 11, 1, 10.50),
(2, 36, 8, 1, 85.00),
(3, 37, 1, 3, 80.00),
(4, 38, 3, 3, 85.00),
(5, 39, 13, 1, 6.50),
(6, 40, 1, 1, 80.00),
(7, 41, 13, 1, 6.50);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `features` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `image`, `stock`, `created_at`, `features`) VALUES
(1, 'Chocolate Indulgence', 'Rich and moist chocolate cake topped with ganache.', 80.00, 'Cake', 'images/chocolate_cake.jpg', 50, '2025-06-15 07:56:14', NULL),
(2, 'Classic Cheesecake', 'Creamy cheesecake with a full buttery biscuit base.', 90.00, 'Cake', 'images/cheesecake.jpg', 50, '2025-06-15 07:56:14', NULL),
(3, 'Pandan Gula Melaka', 'Fragrant pandan cake layered with gula melaka syrup.', 85.00, 'Cake', 'images/pandan_gula_melaka.jpg', 50, '2025-06-15 07:56:14', NULL),
(4, 'Red Velvet Delight', 'Moist red velvet cake layered with smooth cream cheese frosting.', 95.00, 'Cake', 'images/red_velvet.webp', 50, '2025-06-15 07:56:14', NULL),
(5, 'Tiramisu Classic', 'Layered dessert with espresso-soaked sponge and mascarpone cream.', 98.00, 'Cake', 'images/tiramisu.jpg', 50, '2025-06-15 07:56:14', NULL),
(6, 'Mango Mousse Cake', 'Light and airy mango mousse layered over sponge cake base.', 88.00, 'Cake', 'images/mango_mousse.jpg', 50, '2025-06-15 07:56:14', NULL),
(7, 'Blueberry Crumble', 'Sweet and tart blueberry filling with buttery crumble topping.', 75.00, 'Cake', 'images/blueberry_crumble.jpg', 40, '2025-06-15 07:56:14', NULL),
(8, 'Carrot Walnut Cake', 'Spiced carrot cake with crunchy walnuts and cream cheese frosting.', 85.00, 'Cake', 'images/carrot_walnut.jpg', 50, '2025-06-15 07:56:14', NULL),
(9, 'Mocha Buttercream Cake', 'Decadent chocolate cake filled with rich mocha buttercream.', 92.00, 'Cake', 'images/mocha_buttercream.jpg', 50, '2025-06-15 07:56:14', NULL),
(10, 'Sourdough Bread', 'Crusty artisan sourdough loaf', 12.00, 'Breads', 'images/sourdough.jpg', 10, '2025-06-15 08:19:23', NULL),
(11, 'Whole Wheat Bread', 'Healthy whole wheat sandwich bread', 10.50, 'Breads', 'images/wheat_bread.jpg', 15, '2025-06-15 08:19:23', NULL),
(12, 'Croissant', 'Flaky and buttery French croissant', 5.00, 'Pastries', 'images/croissant.jpg', 20, '2025-06-15 08:19:23', NULL),
(13, 'Apple Danish', 'Pastry filled with apple compote', 6.50, 'Pastries', 'images/apple_danish.jpg', 18, '2025-06-15 08:19:23', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_rating_summary`
-- (See below for the actual view)
--
CREATE TABLE `product_rating_summary` (
`id` int(11)
,`name` varchar(100)
,`category` varchar(50)
,`price` decimal(10,2)
,`image` varchar(255)
,`average_rating` decimal(3,2)
,`total_reviews` int(11)
,`five_star_count` int(11)
,`four_star_count` int(11)
,`three_star_count` int(11)
,`two_star_count` int(11)
,`one_star_count` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 1,
  `helpful_count` int(11) DEFAULT 0,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `customer_id`, `customer_name`, `customer_email`, `rating`, `comment`, `review_date`, `is_approved`, `helpful_count`, `is_verified_purchase`, `ip_address`) VALUES
(1, 1, 2, 'chuah', 'woonlong@yahoo.com', 5, 'Absolutely amazing chocolate cake! Perfect for our anniversary celebration. Rich, moist, and the ganache was divine. Worth every penny!', '2025-06-20 06:30:00', 1, 0, 0, NULL),
(2, 1, 3, 'edwin', 'teooo@gmail.com', 4, 'Very good chocolate cake, great taste and texture. Only wish it was slightly less sweet for my preference. Overall highly recommended!', '2025-06-21 02:15:00', 1, 0, 0, NULL),
(3, 2, 4, 'zunyi', 'chan1459@hotmail.com', 5, 'Best cheesecake I have ever tasted! The texture was perfect - creamy and smooth. The biscuit base added the perfect crunch. Will definitely order again!', '2025-06-21 08:45:00', 1, 0, 0, NULL),
(4, 2, 5, 'ngng', 'junjun29@gmail.com', 4, 'Really good cheesecake! Fresh ingredients and you can taste the quality. Delivery was prompt and packaging kept it fresh. Nice presentation for our family gathering.', '2025-06-22 03:20:00', 1, 0, 0, NULL),
(5, 10, 6, 'guanwei', 'weiwei31@hotmail.com', 5, 'Excellent sourdough! Crusty exterior and perfect soft interior. Tastes like authentic artisan bread. Great for sandwiches and toast. Amazing value for the quality!', '2025-06-22 00:30:00', 1, 0, 0, NULL),
(6, 12, 7, 'tkyu', 'khaiyu02@gmail.com', 5, 'These croissants are amazing! Flaky, buttery, and fresh. Perfect with my morning coffee. Arrived still warm and well-packaged. Authentic French quality!', '2025-06-22 01:15:00', 1, 0, 0, NULL),
(7, 12, 8, 'keekee', 'doyouluvme@gmail.com', 4, 'Good quality croissants with authentic French taste. Delivery was on time and the pastries were fresh. Nice presentation and reasonable pricing. Will order again!', '2025-06-22 23:45:00', 1, 0, 0, NULL),
(8, 13, 10, 'ivan teo', 'mone01009@gmail.com', 4, 'Very good Apple Danish, great taste and texture. Only wish it was slightly less sweet for my preference. Overall highly recommended!', '2025-06-23 11:59:46', 1, 0, 0, '::1');

--
-- Triggers `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_summary_after_review_delete` AFTER DELETE ON `product_reviews` FOR EACH ROW BEGIN
    CALL UpdateProductReviewSummary(OLD.product_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_summary_after_review_insert` AFTER INSERT ON `product_reviews` FOR EACH ROW BEGIN
    CALL UpdateProductReviewSummary(NEW.product_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_summary_after_review_update` AFTER UPDATE ON `product_reviews` FOR EACH ROW BEGIN
    CALL UpdateProductReviewSummary(NEW.product_id);
    IF OLD.product_id != NEW.product_id THEN
        CALL UpdateProductReviewSummary(OLD.product_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_review_summary`
--

CREATE TABLE `product_review_summary` (
  `product_id` int(11) NOT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `five_star_count` int(11) DEFAULT 0,
  `four_star_count` int(11) DEFAULT 0,
  `three_star_count` int(11) DEFAULT 0,
  `two_star_count` int(11) DEFAULT 0,
  `one_star_count` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_review_summary`
--

INSERT INTO `product_review_summary` (`product_id`, `total_reviews`, `average_rating`, `five_star_count`, `four_star_count`, `three_star_count`, `two_star_count`, `one_star_count`, `last_updated`) VALUES
(1, 2, 4.50, 1, 1, 0, 0, 0, '2025-06-23 11:28:27'),
(2, 2, 4.50, 1, 1, 0, 0, 0, '2025-06-23 11:28:27'),
(10, 1, 5.00, 1, 0, 0, 0, 0, '2025-06-23 11:28:27'),
(12, 2, 4.50, 1, 1, 0, 0, 0, '2025-06-23 11:28:27'),
(13, 1, 4.00, 0, 1, 0, 0, 0, '2025-06-23 11:59:46');

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_feedback`
-- (See below for the actual view)
--
CREATE TABLE `recent_feedback` (
`id` int(11)
,`order_id` int(11)
,`overall_rating` int(11)
,`delivery_rating` int(11)
,`product_quality_rating` int(11)
,`comments` text
,`would_recommend` enum('definitely','probably','not_sure','probably_not','definitely_not')
,`feedback_date` timestamp
,`customer_name` varchar(100)
,`total_amount` decimal(10,2)
,`order_date` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `customer_feedback_summary`
--
DROP TABLE IF EXISTS `customer_feedback_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `customer_feedback_summary`  AS SELECT count(0) AS `total_feedback`, avg(`order_feedback`.`overall_rating`) AS `avg_overall_rating`, avg(`order_feedback`.`delivery_rating`) AS `avg_delivery_rating`, avg(`order_feedback`.`product_quality_rating`) AS `avg_product_quality_rating`, count(case when `order_feedback`.`would_recommend` in ('definitely','probably') then 1 end) AS `positive_recommendations`, count(case when `order_feedback`.`would_recommend` in ('definitely_not','probably_not') then 1 end) AS `negative_recommendations`, round(count(case when `order_feedback`.`would_recommend` in ('definitely','probably') then 1 end) * 100.0 / count(0),2) AS `recommendation_percentage` FROM `order_feedback` ;

-- --------------------------------------------------------

--
-- Structure for view `order_details_view`
--
DROP TABLE IF EXISTS `order_details_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_details_view`  AS SELECT `o`.`id` AS `id`, `o`.`customer_id` AS `customer_id`, coalesce(`c`.`name`,`o`.`customer_name`,'Guest') AS `customer_name`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`order_date` AS `order_date`, `o`.`delivery_address` AS `delivery_address`, coalesce(`o`.`customer_email`,`c`.`email`,'Not provided') AS `customer_email`, coalesce(`o`.`customer_phone`,`c`.`phone`,'Not provided') AS `customer_phone` FROM (`orders` `o` left join `customers` `c` on(`o`.`customer_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `product_rating_summary`
--
DROP TABLE IF EXISTS `product_rating_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_rating_summary`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category` AS `category`, `p`.`price` AS `price`, `p`.`image` AS `image`, coalesce(`prs`.`average_rating`,0) AS `average_rating`, coalesce(`prs`.`total_reviews`,0) AS `total_reviews`, coalesce(`prs`.`five_star_count`,0) AS `five_star_count`, coalesce(`prs`.`four_star_count`,0) AS `four_star_count`, coalesce(`prs`.`three_star_count`,0) AS `three_star_count`, coalesce(`prs`.`two_star_count`,0) AS `two_star_count`, coalesce(`prs`.`one_star_count`,0) AS `one_star_count` FROM (`products` `p` left join `product_review_summary` `prs` on(`p`.`id` = `prs`.`product_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `recent_feedback`
--
DROP TABLE IF EXISTS `recent_feedback`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_feedback`  AS SELECT `of`.`id` AS `id`, `of`.`order_id` AS `order_id`, `of`.`overall_rating` AS `overall_rating`, `of`.`delivery_rating` AS `delivery_rating`, `of`.`product_quality_rating` AS `product_quality_rating`, `of`.`comments` AS `comments`, `of`.`would_recommend` AS `would_recommend`, `of`.`feedback_date` AS `feedback_date`, `o`.`customer_name` AS `customer_name`, `o`.`total_amount` AS `total_amount`, `o`.`order_date` AS `order_date` FROM (`order_feedback` `of` join `orders` `o` on(`of`.`order_id` = `o`.`id`)) ORDER BY `of`.`feedback_date` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_feedback`
--
ALTER TABLE `order_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_overall_rating` (`overall_rating`),
  ADD KEY `idx_feedback_date` (`feedback_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_review_date` (`review_date`);

--
-- Indexes for table `product_review_summary`
--
ALTER TABLE `product_review_summary`
  ADD PRIMARY KEY (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `order_feedback`
--
ALTER TABLE `order_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
