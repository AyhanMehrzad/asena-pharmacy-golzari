-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: asena_pharmacy_golzari
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pet_type` varchar(50) DEFAULT NULL,
  `pet_race` varchar(50) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `pet_name` varchar(255) DEFAULT NULL,
  `pet_gender` varchar(50) DEFAULT NULL,
  `pet_age` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,2,4,'2026-07-26','17:30','completed','2026-07-24 15:53:19','گربه','persian',NULL,NULL,NULL,NULL),(2,5,4,'2026-07-26','09:00','cancelled','2026-07-25 01:38:32','سگ','Husky',NULL,NULL,NULL,NULL),(3,6,4,'2026-07-26','16:45','cancelled','2026-07-25 02:04:34','سگ','Husky',NULL,NULL,NULL,NULL),(4,7,4,'2026-08-01','08:00','pending','2026-07-25 12:57:01','سگ','bulldog',NULL,NULL,NULL,NULL),(5,2,4,'2026-08-02','09:45','cancelled','2026-07-31 19:10:41','سگ','germenshepert',NULL,NULL,NULL,NULL),(6,2,4,'2026-08-08','08:45','pending','2026-08-01 17:03:51','گربه','persian',NULL,NULL,NULL,NULL),(7,11,4,'2026-08-08','17:30','pending','2026-08-03 11:54:19','سگ','',NULL,NULL,NULL,NULL),(8,11,5,'2026-08-12','18:15','pending','2026-08-03 12:39:51','سگ','',NULL,NULL,NULL,NULL),(9,12,4,'2026-08-22','08:45','pending','2026-08-18 13:00:21','سگ','',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autoship_plans`
--

DROP TABLE IF EXISTS `autoship_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoship_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `interval_months` int(11) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autoship_plans`
--

LOCK TABLES `autoship_plans` WRITE;
/*!40000 ALTER TABLE `autoship_plans` DISABLE KEYS */;
INSERT INTO `autoship_plans` VALUES (1,'اشتراک ۳ ماهه',3,5,'2026-07-25 01:51:50'),(2,'اشتراک ۶ ماهه',6,10,'2026-07-25 01:51:50'),(3,'اشتراک ۱۲ ماهه',12,20,'2026-07-25 01:51:50');
/*!40000 ALTER TABLE `autoship_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autoship_subscriptions`
--

DROP TABLE IF EXISTS `autoship_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoship_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `next_delivery_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `autoship_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `autoship_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `autoship_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `autoship_subscriptions_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autoship_subscriptions`
--

LOCK TABLES `autoship_subscriptions` WRITE;
/*!40000 ALTER TABLE `autoship_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `autoship_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `goal_amount` int(11) NOT NULL,
  `current_amount` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','completed','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES (1,'Save Homeless Animals','Please help us raise funds for our shelter to support homeless pets. Every small donation counts.',10000000,150000,'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=500','active','2026-08-01 13:57:26');
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_type` enum('user','admin','ai') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,6,'user','[AI] Hello, how can I feed my cat?',0,'2026-07-25 02:00:20'),(2,6,'ai','این یک پیام خودکار از دستیار هوشمند پت‌کر است. شما پرسیدید: \'Hello, how can I feed my cat?\'. در حال حاضر من در فاز آزمایشی هستم.',1,'2026-07-25 02:00:20');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_events`
--

DROP TABLE IF EXISTS `dashboard_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dashboard_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `event_time` varchar(5) NOT NULL,
  `color` varchar(20) DEFAULT 'primary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_events`
--

LOCK TABLES `dashboard_events` WRITE;
/*!40000 ALTER TABLE `dashboard_events` DISABLE KEYS */;
INSERT INTO `dashboard_events` VALUES (1,'شروع شیفت کلینیک','08:00','primary','2026-07-25 02:30:42'),(2,'بررسی سفارشات','12:00','secondary','2026-07-25 02:30:42');
/*!40000 ALTER TABLE `dashboard_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `specialty` varchar(255) NOT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `baseline_rating` decimal(3,1) DEFAULT 4.9,
  `review_count` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `price` int(11) DEFAULT 450000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `schedule_info` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (4,'akbar nami','پزشک عمومی',5.0,4.9,0,'uploads/doctors/6a63b8fcdf04e_329748003990695799.jpeg',150000,'2026-07-23 20:12:40',4,'09990999','{\"sat\":{\"m_start\":\"08:00\",\"m_end\":\"14:00\",\"a_start\":\"16:00\",\"a_end\":\"21:00\"},\"sun\":{\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_start\":\"16:00\",\"a_end\":\"20:00\"}}'),(5,'ali','vet',5.0,4.9,0,'uploads/doctors/6a63b90c61a3d_doctor kitty.jpeg',600000,'2026-07-23 20:35:20',3,NULL,'{\"sat\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"sun\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"mon\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"tue\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"wed\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"thu\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"fri\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"}}'),(8,'doctor','عمومی',5.0,4.9,0,NULL,450000,'2026-08-03 08:24:56',10,'user.doctor',NULL);
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `donor_name` varchar(255) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('pending','successful','failed') DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,2,'ناشناس',1,50000,'successful','360976201','2026-08-01 14:30:46'),(2,2,'Sina',1,100000,'successful','360977501','2026-08-01 14:31:47');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` int(11) NOT NULL,
  `product_name_snapshot` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,3,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult'),(2,3,8,1,280000,'شامپو ضد ریزش موی سگ تریکسی'),(3,3,31,1,100000,'Cat Toy Mouse Updated'),(4,4,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult'),(5,4,31,1,100000,'Cat Toy Mouse Updated'),(6,5,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult'),(7,5,31,1,100000,'Cat Toy Mouse Updated'),(8,6,32,1,740000,'خمیر ضد انگل آیورمکتین مخصوص اسب اکولان'),(9,6,38,1,690000,'بلوس آهسته‌رهش کلسیم و ویتامین D3 گاو تازه زا');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_logs`
--

DROP TABLE IF EXISTS `order_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) NOT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_logs`
--

LOCK TABLES `order_logs` WRITE;
/*!40000 ALTER TABLE `order_logs` DISABLE KEYS */;
INSERT INTO `order_logs` VALUES (1,2,'delivered','processing','2026-07-25 02:49:02'),(2,2,'processing','delivered','2026-07-25 02:49:07'),(3,1,'cancelled','pending_payment','2026-07-25 02:49:18'),(4,1,'pending_payment','processing','2026-07-25 02:49:24'),(5,1,'processing','shipped','2026-07-25 02:49:27'),(6,1,'shipped','delivered','2026-07-25 02:49:32'),(7,3,'processing','shipped','2026-07-25 13:01:51'),(8,4,'processing','shipped','2026-07-31 19:11:16'),(9,3,'shipped','delivered','2026-07-31 19:11:21');
/*!40000 ALTER TABLE `order_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `discount_amount` int(11) DEFAULT 0,
  `status` enum('pending_payment','processing','shipped','delivered','cancelled') DEFAULT 'pending_payment',
  `gateway_ref_id` varchar(100) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,6,5490000,0,'delivered',NULL,NULL,'2026-07-25 02:13:27'),(2,6,5490000,0,'delivered',NULL,NULL,'2026-07-25 02:18:18'),(3,7,2360000,0,'delivered',NULL,NULL,'2026-07-25 12:58:00'),(4,2,2080000,0,'shipped',NULL,NULL,'2026-07-31 19:10:57'),(5,11,2080000,0,'processing',NULL,NULL,'2026-08-03 11:54:02'),(6,13,1430000,0,'processing',NULL,NULL,'2026-08-24 20:56:07');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_documents`
--

DROP TABLE IF EXISTS `pet_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_documents`
--

LOCK TABLES `pet_documents` WRITE;
/*!40000 ALTER TABLE `pet_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `pet_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `discount_price` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `brand` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `target_animal` varchar(50) DEFAULT 'all',
  `pharmacy_tag` varchar(100) DEFAULT NULL,
  `is_autoship` tinyint(1) NOT NULL DEFAULT 0,
  `baseline_rating` decimal(3,1) DEFAULT 4.8,
  `autoship_discount` int(11) DEFAULT 10,
  `rating_cache` decimal(2,1) DEFAULT 4.5,
  `review_count_cache` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult','غذای سگ',2450000,1980000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','شایر',7,'dog',NULL,1,4.8,10,4.8,0),(2,'کنسرو گربه گورمت گلد با طعم مرغ','غذای گربه',150000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','جوسرا',10,'cat',NULL,1,4.8,10,4.9,22),(3,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','جوسرا',10,'all',NULL,0,4.8,10,4.5,0),(4,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','جوسرا',10,'all',NULL,0,4.8,10,4.5,0),(5,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,'all',NULL,0,4.8,10,4.5,0),(6,'قطره مولتی ویتامین سگ و گربه','مکمل دارویی',450000,390000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','شایر',10,'all','therapy',1,4.8,15,4.5,0),(7,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','نوتری پت',10,'all',NULL,0,4.8,10,4.5,0),(8,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رفلکس',9,'all',NULL,0,4.8,10,4.5,0),(9,'غذای خشک گربه بالغ عقیم شده رویال کنین','غذای گربه',2850000,2600000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','شایر',10,'cat',NULL,1,4.8,10,4.9,22),(10,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,'all',NULL,0,4.8,10,4.5,0),(11,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult','غذای سگ',2450000,1980000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','جوسرا',10,'dog',NULL,1,4.8,10,4.8,14),(12,'کنسرو گربه گورمت گلد با طعم مرغ','غذای گربه',150000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رفلکس',10,'cat',NULL,1,4.8,10,4.9,22),(13,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','نوتری پت',10,'all',NULL,0,4.8,10,4.5,0),(14,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(15,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','شایر',10,'all',NULL,0,4.8,10,4.5,0),(16,'قطره مولتی ویتامین سگ و گربه','مکمل دارویی',450000,390000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all','therapy',1,4.8,15,4.5,0),(17,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(18,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','پت‌کر',10,'all',NULL,0,4.8,10,4.5,0),(19,'غذای خشک گربه بالغ عقیم شده رویال کنین','غذای گربه',2850000,2600000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','شایر',10,'cat',NULL,1,4.8,10,4.9,22),(20,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(21,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult','غذای سگ',2450000,1980000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','نوتری پت',10,'dog',NULL,1,4.8,10,4.8,14),(22,'کنسرو گربه گورمت گلد با طعم مرغ','غذای گربه',150000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رویال کنین',10,'cat',NULL,1,4.8,10,4.9,22),(23,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(24,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رفلکس',10,'all',NULL,0,4.8,10,4.5,0),(25,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(26,'قطره مولتی ویتامین سگ و گربه','مکمل دارویی',450000,390000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all','therapy',1,4.8,15,4.5,0),(27,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,'all',NULL,0,4.8,10,4.5,0),(28,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رفلکس',10,'all',NULL,0,4.8,10,4.5,0),(29,'غذای خشک گربه بالغ عقیم شده رویال کنین','غذای گربه',2850000,2600000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رویال کنین',10,'cat',NULL,1,4.8,10,4.9,22),(30,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,'all',NULL,0,4.8,10,4.5,0),(31,'Cat Toy Mouse Updated','Toys',120000,100000,'assets/images/toy-mouse.jpg','Great toy for cats','2026-07-25 01:20:40','Test Brand',12,'all',NULL,0,4.8,10,4.5,0),(32,'خمیر ضد انگل آیورمکتین مخصوص اسب اکولان','داروخانه تخصصی',850000,740000,'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=600&auto=format&fit=crop&q=80','ژل خوراکی ضد انگل و کرم‌کش قوی برای کنترل انواع انگل‌های داخلی و روده‌ای اسب‌ها با اثرگذاری طولانی‌مدت.','2026-08-24 18:53:00','اکولان',14,'horse','dewormer',1,4.8,12,4.9,18),(33,'روغن و مرهم تقویتی سم اسب مدل Hoof Care Pro','داروخانه تخصصی',620000,540000,'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80','فرمولاسیون ویژه حاوی تار طبیعی و بیوتین جهت تقویت بافت شاخی سم اسب و جلوگیری از ترک خوردگی و خشکی.','2026-08-24 18:53:00','کاوامیرا',20,'horse','hoof_care',1,4.8,10,4.7,9),(34,'محلول مسکن و ضدالتهاب اسب فینیل بوتازون خوراکی','داروخانه تخصصی',980000,890000,'https://images.unsplash.com/photo-1598974357801-cbca100e6571?w=600&auto=format&fit=crop&q=80','داروی ضد درد و تسکین التهابات تاندونی و مفاصل اسب‌های کورس و پرش، موثر در بهبودی سریع صدمات عضلانی.','2026-08-24 18:53:00','وت‌فارما',12,'horse','pain_management',0,4.8,5,5.0,24),(35,'پودر مکمل الکترولیت و ویتامین E اسب اکواین','داروخانه تخصصی',1250000,1100000,'https://images.unsplash.com/photo-1566251037378-5e04e3bec343?w=600&auto=format&fit=crop&q=80','مکمل تامین املاح ضروری و ویتامین‌های آنتی‌اکسیدان پس از تمرینات سنگین، جلوگیری از دهیدراتاسیون و گرفتگی عضلات.','2026-08-24 18:53:00','نوترینت پرو',25,'horse','vitamins',1,4.8,15,4.8,16),(36,'پماد پستانی ضد ورم پستان حاد و تحت حاد گاو شیری','داروخانه تخصصی',450000,390000,'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=600&auto=format&fit=crop&q=80','سوسپانسیون آنتی بیوتیکی فوق العاده قوی جهت درمان و کنترل ورم پستان با دوره پرهیز کوتاه مدت.','2026-08-24 18:53:00','وت‌مکس',30,'cow','inflammation',1,4.8,10,4.9,31),(37,'اسپری اکسید روی و تار ضد گندیدگی سم دام (Foot Rot)','داروخانه تخصصی',320000,280000,'https://images.unsplash.com/photo-1546445317-29f4545e9d53?w=600&auto=format&fit=crop&q=80','اسپری درمانی و ضدعفونی کننده لایه‌های شاخی سم گاو و گوسفند جهت پیشگیری از لنگش و عفونت سم.','2026-08-24 18:53:00','کاوامیرا',40,'cow','hoof_care',1,4.8,10,4.6,14),(38,'بلوس آهسته‌رهش کلسیم و ویتامین D3 گاو تازه زا','داروخانه تخصصی',780000,690000,'https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?w=600&auto=format&fit=crop&q=80','پیشگیری قطعی از تب شیر (Hypocalcemia) و فلجی زایمان با فراهمی زیستی بالا در شکمبه دام سنگین.','2026-08-24 18:53:00','فارماپرو',19,'cow','vitamins',1,4.8,15,5.0,27),(39,'واکسن کشته دامی آنتروتوکسمی و شاربن علامتی','داروخانه تخصصی',550000,NULL,'https://images.unsplash.com/photo-1588693951525-6b7a5ee2e3d3?w=600&auto=format&fit=crop&q=80','ایمن‌سازی فعال گله در برابر پرخوری و کلستریدیوزهای شایع با بالاترین تیتر آنتی‌بادی ایمنی‌بخش.','2026-08-24 18:53:00','رازی وت',50,'cow','vaccines',0,4.8,0,4.8,19),(40,'محلول خوراکی مولتی ویتامین + اسیدهای آمینه پرورشی طیور','داروخانه تخصصی',290000,245000,'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&auto=format&fit=crop&q=80','تقویت ضریب تبدیل غذایی، بهبود رشد جوجه یک‌روزه و ارتقای مقاومت سیستم ایمنی در شرایط استرس گرمایی.','2026-08-24 18:53:00','اویسان',60,'chick','vitamins',1,4.8,10,4.9,42),(41,'پودر محلول در آب ضد کوکسیدیوز و عفونت‌های گوارشی جوجه','داروخانه تخصصی',380000,330000,'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=600&auto=format&fit=crop&q=80','داروی درمانی و کنترل‌کننده کوکسیدیوز روده‌ای و اسهال‌های خونی در مزارع پرورش جوجه و نیمچه گوشتی.','2026-08-24 18:53:00','کمی فارما',35,'chick','drugs',1,4.8,12,4.7,15),(42,'واکسن قطره چشمی نیوکاسل سویه لاسوتا + برونشیت طیور','داروخانه تخصصی',420000,NULL,'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&auto=format&fit=crop&q=80','واکسیناسیون زنده جهت ایجاد ایمنی مخاطی و همورال فوق‌العاده قوی در سیستم تنفسی جوجه و طیور تخمگذار.','2026-08-24 18:53:00','رازی وت',80,'chick','vaccines',0,4.8,0,5.0,38),(43,'محلول ضدعفونی کننده و کمک‌های اولیه هوای سالن و آب طیور','داروخانه تخصصی',310000,260000,'https://images.unsplash.com/photo-1596704017254-9b121068fb31?w=600&auto=format&fit=crop&q=80','ضدعفونی کننده غیرسمی با پایه نانو نقره برای التیام زخم‌ها، استریل کردن خطوط آبرسانی و هوای سالن.','2026-08-24 18:53:00','نانووت',45,'chick','first_aid',1,4.8,10,4.6,11),(44,'قرص ضد انگل و کرم‌کش سگ درنتال پلاس بایر آلمان','داروخانه تخصصی',490000,420000,'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600&auto=format&fit=crop&q=80','معتبرترین قرص ضدانگل طیف وسیع برای سگ‌ها جهت نابودی تضمینی کرم‌های نواری، گرد و ژیاردیا.','2026-08-24 18:53:00','بایر (Bayer)',50,'dog','dewormer',1,4.8,15,5.0,64),(45,'شربت ضد التهاب و مسکن ملئوکسیکام خوراکی سگ','داروخانه تخصصی',580000,495000,'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80','داروی ضد التهاب غیر استروئیدی (NSAID) برای کاهش سریع دردهای ناشی از استئوآرتریت و جراحی‌های ارتوپدی.','2026-08-24 18:53:00','وت‌فارما',25,'dog','pain_management',1,4.8,10,4.8,29),(46,'اسپری استنشاقی و ضد اسپاسم تنفسی سگ‌های نژاد پوزه‌کوتاه','داروخانه تخصصی',640000,560000,'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=600&auto=format&fit=crop&q=80','اسپری تخصصی جهت بهبود تنفس، کاهش التهاب مجاری تنفسی و آسم در سگ‌های بولداگ، پاگ و شیتزو.','2026-08-24 18:53:00','پت‌مدیکال',18,'dog','inflammation',1,4.8,10,4.9,21),(47,'کیت جامع کمک‌های اولیه اورژانسی سگ و حیوانات خانگی','داروخانه تخصصی',890000,780000,'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600&auto=format&fit=crop&q=80','شامل بتادین حیوانی، بانداژ خودچسب، پنس کنه کش، دماسنج دیجیتال، پد گاز استریل و اسپری التیام زخم.','2026-08-24 18:53:00','تریکسی',30,'dog','first_aid',0,4.8,5,4.9,35),(48,'بالم ارگانیک نرم‌کننده و محافظ پد پنجه سگ و گربه','داروخانه تخصصی',280000,230000,'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&auto=format&fit=crop&q=80','بالم کاملاً طبیعی حاوی شی باتر و موم عسل برای بازسازی ترک خوردگی و خشکی پنجه ناشی از پیاده‌روی روی آسفالت گرم یا سرد.','2026-08-24 18:53:00','پت‌کر',35,'dog','hoof_care',1,4.8,10,4.7,18),(49,'خمیر مالت و مکمل ویتامینه تقویت ایمنی گربه جیم کت','داروخانه تخصصی',460000,390000,'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80','دفع آسان گلوله‌های مویی (Hairball) و تقویت پوشش مو و ناخن گربه با ویتامین‌های گروه B و زینک.','2026-08-24 18:53:00','جیم کت (GimCat)',45,'cat','vitamins',1,4.8,15,5.0,78),(50,'قطره ضد استرس و فرومون آرامبخش درمانی گربه فلی‌وی','داروخانه تخصصی',720000,640000,'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600&auto=format&fit=crop&q=80','کاهش اضطراب محیطی، ترس از سفر، پرخاشگری و رفتارهای نشانه‌گذاری با تقلید فرومون چهره‌ای مادر.','2026-08-24 18:53:00','فلی‌وی (Feliway)',20,'cat','therapy',1,4.8,10,4.9,43),(51,'قطره موضعی ضد کک، کنه و انگل‌های پوستی گربه ادوکیت','داروخانه تخصصی',520000,450000,'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=600&auto=format&fit=crop&q=80','محافظت ماهیانه پشت گردنی علیه طیف گسترده‌ای از انگل‌های خارجی و جرب گوش در گربه‌ها.','2026-08-24 18:53:00','بایر (Bayer)',35,'cat','dewormer',1,4.8,12,4.8,37),(52,'قطره اشک شستشو و رفع عفونت و التهاب چشم گربه','داروخانه تخصصی',290000,240000,'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600&auto=format&fit=crop&q=80','محلول استریل پاک‌کننده لکه‌های اشک زیر چشم و تسکین سوزش و التهابات ملتحمه در گربه‌های پرشین و DSH.','2026-08-24 18:53:00','پت‌مدیکال',40,'cat','inflammation',1,4.8,10,4.6,19);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percentage` int(11) NOT NULL,
  `points_cost` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `promo_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('product','doctor') NOT NULL,
  `target_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_verified_buyer` tinyint(1) DEFAULT 1,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,6,'product',31,5,'great product!',1,'approved','2026-07-25 01:59:27');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_deliveries`
--

DROP TABLE IF EXISTS `subscription_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `delivery_month` int(11) NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `status` enum('pending','shipped','delivered','not_received') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `subscription_deliveries_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_deliveries`
--

LOCK TABLES `subscription_deliveries` WRITE;
/*!40000 ALTER TABLE `subscription_deliveries` DISABLE KEYS */;
INSERT INTO `subscription_deliveries` VALUES (1,2,1,'2026-08-03','delivered'),(2,2,2,'2026-09-02','pending'),(3,2,3,'2026-10-02','pending'),(4,2,4,'2026-11-01','pending'),(5,2,5,'2026-12-01','pending'),(6,2,6,'2026-12-31','pending'),(7,3,1,'2026-08-24','pending'),(8,3,2,'2026-09-05','pending'),(9,3,3,'2026-10-05','pending'),(10,4,1,'2026-08-25','pending'),(11,4,2,'2026-09-24','pending'),(12,4,3,'2026-10-24','pending'),(13,5,1,'2026-08-15','shipped'),(14,5,2,'2026-08-28','pending'),(15,6,1,'2026-08-20','pending'),(16,6,2,'2026-09-20','pending'),(17,3,4,'2026-08-24','shipped'),(18,6,3,'2026-08-20','shipped');
/*!40000 ALTER TABLE `subscription_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_logs`
--

DROP TABLE IF EXISTS `subscription_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `old_status` enum('pending_payment','processing','shipped','delivered','cancelled') DEFAULT NULL,
  `new_status` enum('pending_payment','processing','shipped','delivered','cancelled') NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `subscription_logs_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_logs`
--

LOCK TABLES `subscription_logs` WRITE;
/*!40000 ALTER TABLE `subscription_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `frequency_days` int(11) NOT NULL,
  `next_delivery_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('user','ai','admin') NOT NULL,
  `message` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
INSERT INTO `ticket_messages` VALUES (1,1,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-07-31 12:31:35'),(2,2,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-07-31 12:31:51'),(3,1,'user','hi',NULL,'2026-07-31 12:51:15'),(4,1,'ai','خطا در برقراری ارتباط با مغز لئو.',NULL,'2026-07-31 12:51:17'),(5,2,'user','hello',NULL,'2026-07-31 12:51:31'),(6,2,'admin','hi',NULL,'2026-07-31 12:51:45'),(7,2,'admin','what is the problem with your pet',NULL,'2026-07-31 12:52:01'),(8,2,'user','hi',NULL,'2026-07-31 13:04:41'),(9,3,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-01 13:16:39'),(10,4,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-08-01 17:04:41'),(11,5,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-01 18:17:18'),(12,6,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:16:02'),(13,7,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:23:13'),(14,8,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:24:18'),(15,9,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-08-03 09:05:41'),(16,10,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 11:52:33'),(17,11,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 11:55:12'),(18,14,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-18 12:04:24'),(19,15,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-18 12:58:32'),(20,16,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-24 19:20:40');
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mode` enum('ai','admin') DEFAULT 'admin',
  `status` enum('open','closed','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_tickets_status` (`status`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,2,'ai','closed','2026-07-31 12:31:35','2026-08-01 13:16:03'),(2,2,'admin','closed','2026-07-31 12:31:51','2026-08-01 13:16:03'),(3,2,'ai','closed','2026-08-01 13:16:39','2026-08-03 08:12:56'),(4,2,'admin','closed','2026-08-01 17:04:41','2026-08-03 08:12:56'),(5,9,'ai','closed','2026-08-01 18:17:18','2026-08-03 08:12:56'),(6,7,'ai','closed','2026-08-03 08:16:02','2026-08-24 19:31:37'),(7,2,'ai','closed','2026-08-03 08:23:13','2026-08-24 19:31:37'),(8,10,'ai','closed','2026-08-03 08:24:18','2026-08-24 19:31:37'),(9,2,'admin','closed','2026-08-03 09:05:41','2026-08-24 19:31:37'),(10,11,'ai','closed','2026-08-03 11:52:33','2026-08-24 19:31:37'),(11,3,'ai','closed','2026-08-03 11:55:12','2026-08-24 19:31:37'),(14,1,'ai','closed','2026-08-18 12:04:24','2026-08-24 19:31:37'),(15,12,'ai','closed','2026-08-18 12:58:32','2026-08-24 19:31:37'),(16,13,'ai','open','2026-08-24 19:20:40','2026-08-24 19:20:40');
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_pets`
--

DROP TABLE IF EXISTS `user_pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_pets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `race` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_pets`
--

LOCK TABLES `user_pets` WRITE;
/*!40000 ALTER TABLE `user_pets` DISABLE KEYS */;
INSERT INTO `user_pets` VALUES (1,2,'joei','سگ','germenshepert','2026-07-23 11:51:15','نر','8'),(3,2,'pisi','گربه','persian','2026-07-23 13:41:51','ماده','2'),(4,5,'Bobby2','سگ','Husky','2026-07-25 01:35:21',NULL,NULL),(5,7,'dogie','سگ','bulldog','2026-07-25 12:32:50',NULL,NULL),(6,11,'akbar','سگ','','2026-08-03 11:52:49',NULL,NULL);
/*!40000 ALTER TABLE `user_pets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_subscriptions`
--

DROP TABLE IF EXISTS `user_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('active','ended','cancelled') DEFAULT 'active',
  `next_delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_months` int(11) DEFAULT 3,
  `payment_model` enum('monthly','upfront') DEFAULT 'monthly',
  `delivery_frequency` varchar(50) DEFAULT '1_month',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_subs_status` (`status`),
  CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscriptions`
--

LOCK TABLES `user_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_subscriptions` DISABLE KEYS */;
INSERT INTO `user_subscriptions` VALUES (2,2,'اشتراک ۶ ماهه',2100000,'active','2026-09-02','2026-07-31 19:08:06',6,'monthly','1_month'),(3,11,'اشتراک ۳ ماهه',2500000,'active','2026-09-23','2026-08-03 11:53:44',3,'monthly','1_month'),(4,4,'اشتراک ۳ ماهه ویژه گربه',1850000,'active','2026-08-25','2026-08-20 08:00:00',3,'monthly','2_weeks'),(5,6,'اشتراک ماهانه داروهای قلبی سگ',950000,'active','2026-08-28','2026-08-10 10:00:00',1,'monthly','2_weeks'),(6,5,'اشتراک ۶ ماهه مکمل و سم اسب',3200000,'active','2026-09-19','2026-08-01 07:00:00',6,'monthly','1_month');
/*!40000 ALTER TABLE `user_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sms_code` varchar(10) DEFAULT NULL,
  `role` enum('user','admin','doctor') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL,
  `pet_type` varchar(50) DEFAULT NULL,
  `pet_race` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `last_monthly_points_date` date DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `apple_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `phone_2` (`phone`),
  UNIQUE KEY `google_id` (`google_id`),
  UNIQUE KEY `apple_id` (`apple_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'09123456789','مدیر سیستم',NULL,'admin','2026-07-21 13:23:55',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,40,'2026-08-18',NULL,NULL),(2,'096154654','ayhan',NULL,'admin','2026-07-21 17:16:18','$2y$10$wQ/UJ0eAxhmzvST6hlmYaOuNu/jWLHjPytzydJbiEit.l3.ZOx1D.',NULL,NULL,'mehrzad.ayhan@gmail.com','تبریز','','نارمک, Golgasht, مرز محله, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51639-17697, Iran',38.06273998,46.32526875,220,'2026-08-01',NULL,NULL),(3,'doctor@gmail.com','ali',NULL,'doctor','2026-07-23 19:19:04','$2y$10$cseRCBybswwGyndV1Z4s6OqWMRgp5YK2l54SE2MzJgPYnczSIsLKO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,90,'2026-08-03',NULL,NULL),(4,'09990999','akbar nami',NULL,'doctor','2026-07-23 19:21:54','$2y$10$GqI3ekTbgb9F8IiomfpCmec32eUnAJaSTwIsfTuJacVzTL7UJGcWu',NULL,NULL,'nami.akbar@gmail.com',NULL,NULL,'',NULL,NULL,0,'2026-07-23',NULL,NULL),(5,'09000000001','test user',NULL,'user','2026-07-25 01:33:28','$2y$10$qIF//jFhW3RAys4qL31UoORnmBRDO8ghsGa.7LgCmdgw8f4t5MOne',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,90,'2026-07-25',NULL,NULL),(6,'09194360331','کاربر تستی OAuth',NULL,'admin','2026-07-25 01:57:34',NULL,NULL,NULL,'',NULL,NULL,'',NULL,NULL,90,'2026-07-25','mock_123456',NULL),(7,'user.user@gmail.com','user.user',NULL,'user','2026-07-25 12:07:59','$2y$10$NRVKFcOVP96F.KVePQwFlec1FgVGLNge/J9FjoSTVYnSzVJmGZM6m',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,140,'2026-08-03',NULL,NULL),(8,'user.admin@gmail.com','user.admin',NULL,'admin','2026-07-25 12:59:15','$2y$10$eOfr0iUrovwG7ALhRvEijeLquk3DZ9RxP64GhHFPDWD8I1zZlqryy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-07-25',NULL,NULL),(9,'cataloguser','Catalog User',NULL,'user','2026-08-01 18:17:17','$2y$10$A1x/tZQKwG29pTij62yKhermzbVR0XpvsuZ.Zjm5bWHbd3PcfEFKC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-08-01',NULL,NULL),(10,'user.doctor','doctor',NULL,'doctor','2026-08-03 08:24:18','$2y$10$jCdzvKTkiHuJXJV83GTmHeJ8liRGqV4GeMWEqGBBEuvigoKHCkQGq',NULL,NULL,'',NULL,NULL,'',NULL,NULL,70,'2026-08-03',NULL,NULL),(11,'user.more','user1',NULL,'user','2026-08-03 11:52:32','$2y$10$k5PC7Jh7bgb/C99hRg5eHOD1kRAfqFctGHiJaSoLzF/UOrM2u/.ae',NULL,NULL,NULL,'تبریز','1234567890','پردیس ۲, Baghmisheh, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51584-46719, Iran',38.06741958,46.38874054,170,'2026-08-03',NULL,NULL),(12,'09144046728','ayxan mehrzad',NULL,'user','2026-08-18 12:58:29','$2y$10$Ges1WajBHR5DqyKcJUhoH.LAvyrBk/ZPof7Mv18vK3qrdMbd4caIe',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-08-18',NULL,NULL),(13,'09146676978','ayhan',NULL,'admin','2026-08-24 19:20:39','$2y$10$vX.FbOnuI2eP8BVVR./a1.oxGb.d4O.oywOLigJKuKLL/EkYvn25e',NULL,NULL,NULL,'تبریز','1234567890','امیرالمومنین, World trade, Vali asr, ولیعصر, Valiasr, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51578-48778, Iran',38.06606810,46.36505127,120,'2026-08-24',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,6,31,'2026-07-25 01:58:06'),(2,6,2,'2026-07-25 02:31:06'),(3,7,31,'2026-07-25 12:08:41');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 22:57:22
