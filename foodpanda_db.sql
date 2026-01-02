-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2025 at 11:38 PM
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
-- Database: `foodpanda_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_to_cart` (IN `p_user_id` INT, IN `p_item_id` INT, IN `p_quantity` INT)   BEGIN
    DECLARE v_existing_qty INT;

SELECT Quantity INTO v_existing_qty
    FROM Cart
    WHERE User_ID = p_user_id AND Item_ID = p_item_id;
    
    IF v_existing_qty IS NOT NULL THEN
        
        UPDATE Cart 
        SET Quantity = Quantity + p_quantity
        WHERE User_ID = p_user_id AND Item_ID = p_item_id;
    ELSE
        
        INSERT INTO Cart (User_ID, Item_ID, Quantity)
        VALUES (p_user_id, p_item_id, p_quantity);
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calculate_cart_total` (IN `p_user_id` INT, OUT `p_total` DECIMAL(10,2))   BEGIN
    SELECT COALESCE(SUM(i.Price * c.Quantity), 0) INTO p_total
    FROM Cart c
    JOIN Item i ON c.Item_ID = i.Item_ID
    WHERE c.User_ID = p_user_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `Address_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Label` varchar(50) NOT NULL,
  `Street` varchar(255) NOT NULL,
  `City` varchar(100) NOT NULL,
  `Postcode` varchar(20) NOT NULL,
  `State` varchar(100) DEFAULT 'Kuala Lumpur',
  `Country` varchar(100) NOT NULL DEFAULT 'Malaysia',
  `Is_Default` tinyint(1) DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`Address_ID`, `User_ID`, `Label`, `Street`, `City`, `Postcode`, `State`, `Country`, `Is_Default`, `Created_At`, `Updated_At`) VALUES
(1, 1, 'Home', '3-6-Eonju-ro 136-gil', 'Gangnam-gu', '', 'Seoul', 'Republic of Korea', 0, '2025-12-26 19:47:59', '2025-12-26 19:55:27'),
(2, 2, 'Home', '42 Hangang-daero', 'Yonsan District', '', 'Seoul', 'Republic of Korea', 1, '2025-12-26 19:47:59', '2025-12-26 19:52:37'),
(8, 3, 'Home', '789 Pine Rd', 'Shah Alam', '40000', 'Selangor', 'Malaysia', 1, '2025-12-27 18:41:14', '2025-12-27 18:41:14'),
(9, 4, 'Home', '202 Elm St', 'Subang Jaya', '47500', 'Selangor', 'Malaysia', 1, '2025-12-27 18:41:14', '2025-12-27 18:41:14'),
(10, 5, 'Home', '303 Maple Dr', 'Ampang', '68000', 'Selangor', 'Malaysia', 1, '2025-12-27 18:41:14', '2025-12-27 18:41:14'),
(11, 6, 'Home', '404 Cedar Ln', 'Cheras', '56000', 'Kuala Lumpur', 'Malaysia', 1, '2025-12-27 18:41:14', '2025-12-27 18:41:14');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`, `Name`, `Email`, `Password`, `User_ID`, `Created_At`) VALUES
(1, 'lina liyana', 'linaadmin@gmail.com', 'linaaa', NULL, '2025-12-26 17:59:05'),
(2, 'Admin User', 'admin@foodpanda.com', 'Admin123@', NULL, '2025-12-27 18:41:14'),
(3, 'Test Admin', 'testadmin@foodpanda.com', 'Admin1@0', NULL, '2025-12-27 18:42:22');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `Cart_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Item_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `Added_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`Cart_ID`, `User_ID`, `Item_ID`, `Quantity`, `Added_At`) VALUES
(2, 1, 8, 1, '2025-12-19 22:27:00'),
(3, 3, 12, 3, '2025-12-23 02:10:00'),
(4, 3, 7, 1, '2025-12-23 02:12:00'),
(6, 1, 15, 1, '2025-12-23 19:25:00'),
(7, 1, 9, 2, '2025-12-23 19:27:00'),
(8, 4, 11, 1, '2025-12-19 03:40:00'),
(10, 6, 2, 1, '2025-12-23 21:05:00'),
(11, 3, 6, 2, '2025-12-23 20:40:00'),
(12, 3, 10, 1, '2025-12-23 20:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `Category_ID` int(11) NOT NULL,
  `Category_Name` varchar(100) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`Category_ID`, `Category_Name`, `Created_At`) VALUES
(1, 'Beverages', '2025-12-27 17:05:52'),
(2, 'Bubble Tea', '2025-12-27 17:05:52'),
(3, 'Malaysian Food', '2025-12-27 17:05:52'),
(4, 'Pizza', '2025-12-27 17:05:52'),
(5, 'Fast Food', '2025-12-27 17:05:52'),
(6, 'Sushi', '2025-12-27 17:05:52'),
(7, 'Japanese Food', '2025-12-27 17:05:52'),
(8, 'Burgers', '2025-12-27 17:05:52');

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `Item_ID` int(11) NOT NULL,
  `Item_Name` varchar(100) NOT NULL,
  `Category_ID` int(11) NOT NULL,
  `Restaurant_ID` int(11) DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Image` varchar(225) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Stock` int(11) DEFAULT 0,
  `Is_Available` tinyint(1) DEFAULT 1,
  `Is_Featured` tinyint(1) DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`Item_ID`, `Item_Name`, `Category_ID`, `Restaurant_ID`, `Price`, `Image`, `Description`, `Stock`, `Is_Available`, `Is_Featured`, `Created_At`, `Updated_At`) VALUES
(1, 'White Peach Oolong Milk Tea', 2, 5, 13.50, 'whitepeachoolongmilktea.webp', 'A smooth oolong with white peach aroma, blending creamy richness and refreshing sweetness.', 100, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(2, 'Grapefruit Jasmine Tea', 2, 5, 15.50, 'grapefruitjasminetea.webp', 'Fresh grapefruit with jasmine tea, blending citrus brightness and delicate floral notes.', 100, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(3, 'Da Hong Pao Tea', 2, 5, 13.50, 'dahongpaotea.webp', 'Traditional Da Hong Pao with orange-red hue, rich fragrance, and a smooth, refreshing taste.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(4, 'Roti Canai', 3, 12, 2.30, 'rotiCanai.jpg', 'Freshly made, crispy on the outside and soft inside. Served hot and perfect to enjoy with dhal or curry.', 150, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(5, 'Nasi Lemak', 3, 12, 4.50, 'item-nasi-lemak.jpg', 'Traditional Malaysian breakfast.', 120, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(6, 'Fried Rice', 3, 12, 6.00, 'item-fried-rice.jpg', 'Special fried rice with chicken.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(7, 'Margherita Pizza', 4, 6, 25.90, 'item-margherita-pizza.jpg', 'Regular size pizza with classic tomato and mozzarella.', 50, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(8, 'Pepperoni Pizza', 4, 6, 23.90, 'item-pepperoni-pizza.jpg', 'Regular size pizza loaded with pepperoni slices.', 50, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(9, 'Hawaiian Pizza', 4, 6, 29.90, 'hawaiianPizza.jpg', 'Regular size pizza with ham and pineapple.', 50, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(10, 'Signature Bang Bang Milk Tea', 1, 3, 12.50, 'bangmilktea.webp', 'Our best-selling brown sugar warm pearls with fresh milk.', 100, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(11, 'Strawberry Pudding Smoothie', 1, 3, 10.70, 'strawberrypudding.webp', 'A blended sensation of fruity strawberry topped with custard pudding.', 100, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(12, 'Caramel Macchiato', 1, 3, 13.40, 'caramelmacchiato.webp', 'Fragrant espresso with a creamy splash of steamed milk.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(13, 'Ayam Gepuk Set', 3, 4, 24.10, 'ayamgepukset.webp', 'A delightful assortment of grilled chicken, shrimp, and seasonal vegetables served with fragrant rice.', 80, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(14, 'Talapia Top Global', 3, 4, 21.10, 'talapiatopglobal.webp', 'Pan-seared tilapia topped with zesty mango salsa, served on a bed of cilantro rice.', 80, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(15, 'Bakso Top Global', 3, 4, 16.20, 'baksotopglobal.webp', 'Savory meatballs in rich broth, served with fresh herbs and noodles for a delightful experience.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(16, 'XXL Fried Chicken', 5, 2, 16.90, 'xxlfriedchicken.webp', 'Triple the size, triple the joy.', 70, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(17, 'Bobbie Bun Double Up Combo', 5, 2, 23.90, 'bobbiebundoubleupcombo.webp', 'Served with a beverage of your choice & Bob potato fries.', 60, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(18, 'Bob Potato Fries', 5, 2, 7.90, 'bobpotatofries.webp', 'Shoestring fries coated with salt & spices.', 150, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(19, 'Chicken Burger', 8, 2, 6.90, 'item-chicken-burger.jpg', 'Crispy chicken patty with fresh lettuce and special sauce.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(20, 'Classic Beef Burger', 8, 2, 7.90, 'item-beef-burger.jpg', 'Juicy beef patty with lettuce, tomato, and cheese.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(21, 'Salmon Nishouku Gunkan', 6, 1, 6.90, 'salmonnishoukugunkan.webp', 'Raw salmon with mentai mayo.', 80, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(22, 'Beef Sukiyaki Chawanmushi', 7, 1, 7.90, 'beefsukiyakichawanmushi.webp', 'Japanese egg custard topped with pan-fried beef, omelette & sukiyaki sauce.', 80, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(23, 'Grand Mixed Bento', 7, 1, 24.50, 'grandmixedbento.webp', 'Deep-fried salmon, chicken with spicy sauce, mayo, fried chicken dumpling served with edamame & piri piri renkon.', 50, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(24, 'California Maki', 6, 1, 9.50, 'californiamaki.jpg', 'Classic California roll with crab, avocado, and cucumber.', 100, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(25, 'Salmon Nigiri (2pcs)', 6, 1, 7.90, 'salmonnigiri.jpg', 'Fresh salmon nigiri sushi.', 100, 1, 0, '2025-12-27 17:23:41', '2025-12-27 17:23:41'),
(26, 'Sushi Platter A', 6, 1, 38.90, 'sushikingplatterA.jpg', '12 pcs assorted sushi selection.', 40, 1, 1, '2025-12-27 17:23:41', '2025-12-27 17:23:41');

--
-- Triggers `item`
--
DELIMITER $$
CREATE TRIGGER `tr_check_stock_availability` BEFORE UPDATE ON `item` FOR EACH ROW BEGIN
    IF NEW.Stock <= 0 THEN
        SET NEW.Is_Available = FALSE;
    ELSE
        SET NEW.Is_Available = TRUE;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `Order_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Cart_ID` int(11) DEFAULT NULL,
  `Total_Price` decimal(10,2) NOT NULL,
  `Payment_ID` int(11) DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Pending',
  `Order_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`Order_ID`, `User_ID`, `Cart_ID`, `Total_Price`, `Payment_ID`, `Status`, `Order_Date`, `Updated_At`) VALUES
(1, 1, NULL, 40.60, 1, 'Delivered', '2025-12-20 06:30:00', '2025-12-28 09:57:20'),
(2, 3, 3, 48.30, 2, 'Out for Delivery', '2025-12-23 10:15:00', '2025-12-28 09:57:20'),
(3, 4, NULL, 35.70, 3, 'Preparing', '2025-12-24 04:00:00', '2025-12-28 09:57:20'),
(4, 1, 6, 73.10, 4, 'Confirmed', '2025-12-24 03:30:00', '2025-12-28 09:57:20'),
(5, 4, 8, 35.20, 5, 'Delivered', '2025-12-19 11:45:00', '2025-12-28 09:57:20'),
(6, 6, NULL, 54.30, 6, 'Cancelled', '2025-12-22 08:20:00', '2025-12-28 09:57:20'),
(7, 6, NULL, 26.70, 7, 'Pending', '2025-12-24 05:10:00', '2025-12-27 18:41:14'),
(8, 3, NULL, 79.30, 8, 'Preparing', '2025-12-24 04:45:00', '2025-12-27 18:41:14');

--
-- Triggers `order`
--
DELIMITER $$
CREATE TRIGGER `tr_update_stock_on_order` AFTER INSERT ON `order` FOR EACH ROW BEGIN
    
    
    UPDATE Item 
    SET Stock = Stock - 1
    WHERE Item_ID IN (SELECT Item_ID FROM Cart WHERE User_ID = NEW.User_ID);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(110) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expiry`, `created_at`) VALUES
(1, 'txt@gmail.com', '5906cb6547f750485c738dea57ceeb0ddc084a065f0df34ddbbb61f0d37131b9', '2025-12-26 06:52:11', '2025-12-26 04:52:11'),
(2, 'Nora@gmail.com', '4d3ee152fc45728861845aa33098950b8f76d62faf3168a83c445dce9e6c7064', '2025-12-26 08:02:22', '2025-12-26 06:02:22');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Payment_Method` varchar(50) NOT NULL,
  `Receipt` varchar(100) DEFAULT NULL,
  `Payment_Date` date NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Status` varchar(20) DEFAULT 'Completed',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Order_ID`, `Payment_Method`, `Receipt`, `Payment_Date`, `Amount`, `Status`, `Created_At`) VALUES
(1, 1, 'Credit Card', NULL, '2025-12-20', 40.60, 'Completed', '2025-12-27 18:41:13'),
(2, 2, 'E-Wallet', NULL, '2025-12-23', 48.30, 'Completed', '2025-12-27 18:41:13'),
(3, 3, 'Cash on Delivery', NULL, '2025-12-24', 35.70, 'Pending', '2025-12-27 18:41:13'),
(4, 4, 'Debit Card', NULL, '2025-12-24', 73.10, 'Completed', '2025-12-27 18:41:13'),
(5, 5, 'Credit Card', NULL, '2025-12-19', 35.20, 'Completed', '2025-12-27 18:41:13'),
(6, 6, 'Credit Card', NULL, '2025-12-22', 54.30, 'Failed', '2025-12-27 18:41:13'),
(7, 7, 'Cash on Delivery', NULL, '2025-12-24', 26.70, 'Pending', '2025-12-27 18:41:13'),
(8, 8, 'E-Wallet', NULL, '2025-12-24', 79.30, 'Completed', '2025-12-27 18:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `Payment_Method_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Card_Holder_Name` varchar(100) NOT NULL,
  `Card_Type` varchar(50) NOT NULL,
  `Card_Number` varchar(19) NOT NULL,
  `Expiry_Date` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `CVV` varchar(4) NOT NULL,
  `Is_Default` tinyint(1) DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`Payment_Method_ID`, `User_ID`, `Card_Holder_Name`, `Card_Type`, `Card_Number`, `Expiry_Date`, `CVV`, `Is_Default`, `Created_At`, `Updated_At`) VALUES
(1, 1, 'Park Han', 'Visa', '2424 2424 2424 1242', '2035-12', '123', 0, '2025-12-26 20:23:57', '2025-12-26 20:26:09');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `logo` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `category_id`, `link`, `logo`) VALUES
(1, 'Sushi King', 6, 'Sushiking.html', 'logo.sushiking.jpg'),
(2, 'Uncle Bob', 5, 'Unclebob.html', 'logo.unclebob.jpg'),
(3, 'Tealive', 1, 'Tealive.html', 'logo.tealive.jpg'),
(4, 'Top Global', 3, 'Topglobal.html', 'logo.topglobal.webp'),
(5, 'Chagee', 2, 'Chagee.html', 'logo.chagee.jpg'),
(6, 'Pizza Hut', 4, 'PizzaHut.html', 'pizzahut.jpg'),
(7, 'Wing Stop', 5, 'userComingSoon.html', 'wingstop.jpg'),
(8, 'Cookiecrumbs', 1, 'userComingSoon.html', 'cookiecrumbs.webp'),
(9, 'Nobu', 7, 'userComingSoon.html', 'nobu.jpg'),
(10, 'Nasi Kandar Leman', 3, 'userComingSoon.html', 'nasikandarleman.jpg'),
(11, 'Gordon Ramsay Grill', 3, 'userComingSoon.html', 'gordonramsay.webp'),
(12, 'Restaurant Maju M', 3, 'MajuM.html', 'ayamgoreng.jpg'),
(13, 'McDonalds', 5, 'comingsoon.html', 'logo.mcd.jpg'),
(14, 'KFC', 5, 'comingsoon.html', 'kfc.jpg'),
(15, 'ZUS Coffee', 1, 'comingsoon.html', 'logo.zus.jpg'),
(16, 'Subway', 5, 'comingsoon.html', 'logo.subway.jpg'),
(17, 'Boost Juice', 1, 'comingsoon.html', 'logo.boost.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `Review_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` >= 1 and `Rating` <= 5),
  `Comment` text DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Name` varchar(60) NOT NULL,
  `Email` varchar(110) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `PhoneNo` varchar(15) NOT NULL,
  `Address` text DEFAULT NULL,
  `Profile_Picture` varchar(255) DEFAULT NULL,
  `Date_Of_Birth` date DEFAULT NULL,
  `Gender` enum('male','female','other','') DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Username`, `Name`, `Email`, `Password`, `PhoneNo`, `Address`, `Profile_Picture`, `Date_Of_Birth`, `Gender`, `Created_At`, `Updated_At`) VALUES
(1, 'hani', 'Park Han', 'ahof@gmail.com', 'Lina@0', '0172958243', '3-6-Eonju-ro 136-gil, Gangnam-gu, Seoul', 'uploads/profile_pictures/profile_1_1766777739.jpeg', '2003-09-25', 'male', '2025-12-26 03:21:03', '2025-12-27 18:41:13'),
(2, 'soobin', 'Choi soobin', 'txt@gmail.com', 'Moa@2u', '0132958243', '42 Hangang-daero, Yonsan District, Seoul', NULL, NULL, NULL, '2025-12-26 04:50:47', '2025-12-27 18:41:13'),
(3, 'michelle_tan', 'Michelle Tan', 'michelle.t@email.com', 'Customer@123', '+60187654321', 'Shah Alam', NULL, '1995-03-10', 'female', '2025-12-27 18:41:13', '2025-12-27 18:41:13'),
(4, 'david_wong', 'David Wong', 'david.w@email.com', 'Customer@456', '+60165432109', 'Subang Jaya', NULL, '1992-04-12', 'male', '2025-12-27 18:41:13', '2025-12-27 18:41:13'),
(5, 'fatimah_ali', 'Fatimah Ali', 'fatimah.a@email.com', 'Rider@789', '+60154321098', 'Ampang', NULL, '1998-02-28', 'female', '2025-12-27 18:41:13', '2025-12-27 18:41:13'),
(6, 'kevin_lim', 'Kevin Lim', 'kevin.l@email.com', 'Customer@321', '+60143210987', 'Cheras', NULL, '1997-05-18', 'male', '2025-12-27 18:41:13', '2025-12-27 18:41:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_cart_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_cart_summary` (
`Cart_ID` int(11)
,`User_ID` int(11)
,`Customer_Name` varchar(60)
,`Item_Name` varchar(100)
,`Price` decimal(10,2)
,`Quantity` int(11)
,`Subtotal` decimal(20,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_inventory_status`
-- (See below for the actual view)
--
CREATE TABLE `vw_inventory_status` (
`Item_ID` int(11)
,`Item_Name` varchar(100)
,`Category_Name` varchar(100)
,`Price` decimal(10,2)
,`Stock` int(11)
,`Is_Available` tinyint(1)
,`Stock_Status` varchar(12)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_order_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_order_details` (
`Order_ID` int(11)
,`Order_Date` timestamp
,`Customer_Name` varchar(60)
,`Email` varchar(110)
,`PhoneNo` varchar(15)
,`Total_Price` decimal(10,2)
,`Status` varchar(20)
,`Payment_Method` varchar(50)
,`Payment_Date` date
);

-- --------------------------------------------------------

--
-- Structure for view `vw_cart_summary`
--
DROP TABLE IF EXISTS `vw_cart_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cart_summary`  AS SELECT `c`.`Cart_ID` AS `Cart_ID`, `u`.`User_ID` AS `User_ID`, `u`.`Name` AS `Customer_Name`, `i`.`Item_Name` AS `Item_Name`, `i`.`Price` AS `Price`, `c`.`Quantity` AS `Quantity`, `i`.`Price`* `c`.`Quantity` AS `Subtotal` FROM ((`cart` `c` join `user` `u` on(`c`.`User_ID` = `u`.`User_ID`)) join `item` `i` on(`c`.`Item_ID` = `i`.`Item_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_inventory_status`
--
DROP TABLE IF EXISTS `vw_inventory_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_inventory_status`  AS SELECT `i`.`Item_ID` AS `Item_ID`, `i`.`Item_Name` AS `Item_Name`, `c`.`Category_Name` AS `Category_Name`, `i`.`Price` AS `Price`, `i`.`Stock` AS `Stock`, `i`.`Is_Available` AS `Is_Available`, CASE WHEN `i`.`Stock` = 0 THEN 'Out of Stock' WHEN `i`.`Stock` <= 10 THEN 'Low Stock' ELSE 'In Stock' END AS `Stock_Status` FROM (`item` `i` join `category` `c` on(`i`.`Category_ID` = `c`.`Category_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_order_details`
--
DROP TABLE IF EXISTS `vw_order_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_order_details`  AS SELECT `o`.`Order_ID` AS `Order_ID`, `o`.`Order_Date` AS `Order_Date`, `u`.`Name` AS `Customer_Name`, `u`.`Email` AS `Email`, `u`.`PhoneNo` AS `PhoneNo`, `o`.`Total_Price` AS `Total_Price`, `o`.`Status` AS `Status`, `p`.`Payment_Method` AS `Payment_Method`, `p`.`Payment_Date` AS `Payment_Date` FROM ((`order` `o` join `user` `u` on(`o`.`User_ID` = `u`.`User_ID`)) left join `payment` `p` on(`o`.`Payment_ID` = `p`.`Payment_ID`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`Address_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_user_default` (`User_ID`,`Is_Default`),
  ADD KEY `idx_address_default` (`Is_Default`),
  ADD KEY `idx_address_user_label` (`User_ID`,`Label`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_admin_email` (`Email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`Cart_ID`),
  ADD KEY `Item_ID` (`Item_ID`),
  ADD KEY `idx_cart_user` (`User_ID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`Category_ID`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`Item_ID`),
  ADD KEY `idx_category` (`Category_ID`),
  ADD KEY `idx_restaurant` (`Restaurant_ID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`Order_ID`),
  ADD KEY `Payment_ID` (`Payment_ID`),
  ADD KEY `idx_order_user` (`User_ID`),
  ADD KEY `idx_order_status` (`Status`),
  ADD KEY `fk_order_cart` (`Cart_ID`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_email` (`email`),
  ADD KEY `idx_password_resets_token` (`token`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`Payment_Method_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_payment_user_default` (`User_ID`,`Is_Default`),
  ADD KEY `idx_payment_default` (`Is_Default`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_restaurant_category` (`category_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_review_order` (`Order_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_user_email` (`Email`),
  ADD KEY `idx_user_username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `Address_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `Cart_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `Payment_Method_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`Item_ID`) REFERENCES `item` (`Item_ID`) ON DELETE CASCADE;

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `fk_item_category` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_restaurant` FOREIGN KEY (`Restaurant_ID`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_order_cart` FOREIGN KEY (`Cart_ID`) REFERENCES `cart` (`Cart_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`Payment_ID`) REFERENCES `payment` (`Payment_ID`) ON DELETE SET NULL;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `order` (`Order_ID`) ON DELETE CASCADE;

--
-- Constraints for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD CONSTRAINT `payment_method_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `fk_restaurant_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`Category_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`Order_ID`) REFERENCES `order` (`Order_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
