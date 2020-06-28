-- MariaDB dump 10.17  Distrib 10.4.13-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: jse_dma
-- ------------------------------------------------------
-- Server version	10.4.13-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `access_permissions`
--

LOCK TABLES `access_permissions` WRITE;
/*!40000 ALTER TABLE `access_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `activitylog`
--

LOCK TABLES `activitylog` WRITE;
/*!40000 ALTER TABLE `activitylog` DISABLE KEYS */;
/*!40000 ALTER TABLE `activitylog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_client_order_execution_reports`
--

LOCK TABLES `broker_client_order_execution_reports` WRITE;
/*!40000 ALTER TABLE `broker_client_order_execution_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_client_order_execution_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_client_orders`
--

LOCK TABLES `broker_client_orders` WRITE;
/*!40000 ALTER TABLE `broker_client_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_client_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_client_permissions`
--

LOCK TABLES `broker_client_permissions` WRITE;
/*!40000 ALTER TABLE `broker_client_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_client_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_clients`
--

LOCK TABLES `broker_clients` WRITE;
/*!40000 ALTER TABLE `broker_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_settlement_accounts`
--

LOCK TABLES `broker_settlement_accounts` WRITE;
/*!40000 ALTER TABLE `broker_settlement_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_settlement_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_trading_accounts`
--

LOCK TABLES `broker_trading_accounts` WRITE;
/*!40000 ALTER TABLE `broker_trading_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_trading_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_user_permissions`
--

LOCK TABLES `broker_user_permissions` WRITE;
/*!40000 ALTER TABLE `broker_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `broker_users`
--

LOCK TABLES `broker_users` WRITE;
/*!40000 ALTER TABLE `broker_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `broker_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `foreign_brokers`
--

LOCK TABLES `foreign_brokers` WRITE;
/*!40000 ALTER TABLE `foreign_brokers` DISABLE KEYS */;
/*!40000 ALTER TABLE `foreign_brokers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `local_brokers`
--

LOCK TABLES `local_brokers` WRITE;
/*!40000 ALTER TABLE `local_brokers` DISABLE KEYS */;
/*!40000 ALTER TABLE `local_brokers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `log_activities`
--

LOCK TABLES `log_activities` WRITE;
/*!40000 ALTER TABLE `log_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2020_03_03_024154_create_local_broker_table',1),(4,'2020_03_03_024159_create_foreign_broker',1),(5,'2020_03_03_024504_create_broker_client_table',1),(6,'2020_03_03_024903_create_broker_user',1),(7,'2020_03_03_025920_create_broker_settlement_account',1),(8,'2020_03_03_030459_create_broker_trading_account',1),(9,'2020_03_06_142702_create_activity_log_table',1),(10,'2020_03_06_145954_create_log_activity_table',1),(11,'2020_03_10_181701_create_roles_table',1),(12,'2020_03_10_181808_create_role_user_table',1),(13,'2020_03_13_160440_create_broker_client_orders',1),(14,'2020_04_15_052829_broker_users_permissions_table',1),(15,'2020_04_15_212601_broker_clients_permissions',1),(16,'2020_04_16_192535_create_access_permissions_table',1),(17,'2020_04_16_201739_create_permission_tables',1),(18,'2020_04_17_003846_add_local_broker_id_to_users_table',1),(19,'2020_04_21_125304_add_currency_field_to_broker_settlement_account_table',1),(20,'2020_04_28_134802_add_account_balance_to_broker_clients_table',1),(21,'2020_05_03_185942_add_broker_client_id_to_broker_client_orders_table',1),(22,'2020_05_04_215856_add_filled_orders_to_broker_client_orders_table',1),(23,'2020_05_04_220059_add_filled_orders_to_broker_settlement_accounts',1),(24,'2020_05_05_030258_add_filler_orders_to_broker_clients_table',1),(25,'2020_05_05_171833_add_clordid_to_broker_client_orders_table',1),(26,'2020_05_05_172894_create_broker_client_order_execution_reports',1),(27,'2020_05_28_243830_add_status_to_broker_trading_accounts_table',1),(28,'2020_06_18_182628_add_trading_account_number_to_broker_client_orders',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
INSERT INTO `model_has_permissions` VALUES (1,'App\\User',2),(2,'App\\User',2),(3,'App\\User',2),(4,'App\\User',2),(5,'App\\User',2);
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'create-broker-user','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(2,'read-broker-user','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(3,'update-broker-user','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(4,'delete-broker-user','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(5,'approve-broker-user','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(6,'create-broker-client','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(7,'read-broker-client','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(8,'update-broker-client','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(9,'delete-broker-client','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(10,'approve-broker-client','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(11,'create-broker-order','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(12,'read-broker-order','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(13,'update-broker-order','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(14,'delete-broker-order','web','2020-06-26 21:58:47','2020-06-26 21:58:47'),(15,'approve-broker-order','web','2020-06-26 21:58:47','2020-06-26 21:58:47');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (1,1,1),(2,5,2),(3,6,3),(4,7,4);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'ADMD','web','DMA Admin	','2020-06-26 21:58:47','2020-06-26 21:58:47'),(2,'	OPRD','web','DMA Operator','2020-06-26 21:58:47','2020-06-26 21:58:47'),(3,'BRKF','web','Outbound Foreign Broker	','2020-06-26 21:58:47','2020-06-26 21:58:47'),(4,'AGTS','web','Settlement Agent	','2020-06-26 21:58:47','2020-06-26 21:58:47'),(5,'ADMB','web','Local Broker Admin','2020-06-26 21:58:47','2020-06-26 21:58:47'),(6,'OPRB','web','Local Broker Operator	','2020-06-26 21:58:47','2020-06-26 21:58:47'),(7,'TRDB','web','Local Broker Trader','2020-06-26 21:58:47','2020-06-26 21:58:47'),(8,'FIXR','web','FIX Router','2020-06-26 21:58:47','2020-06-26 21:58:47');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'JSE DMA Admin','admin@innovate10x.com',NULL,'Approved','$2y$10$02g4vHOYoZAGFNsmCNv7OuHBDT5QT8/zcDJCdguDVJHZnkWyesfR.',NULL,'FVmkm0JfRT6xtnBJ1HbrILLkAja4up5XpI1FxDtIlnSgnO9xWBdRZCG6y31e','2020-06-26 21:58:48','2020-06-26 21:58:48',NULL),(2,'Local Broker Admin','local_broker@JMMB.com',NULL,'Approved','$2y$10$pjXdpIi0Zk70SWxf3JmECuON1wiz1tpCEDHThvg/TJlJVvSNu85rW',NULL,NULL,'2020-06-26 21:58:48','2020-06-26 21:58:48',NULL),(3,'Local Broker Operator','operator@innovate10x.com',NULL,'Approved','$2y$10$qYG4rR/QQKbbTd/Q2uoeVOgNvJF5jIHjM2YVoJG0h14QdEZLDcXHe',NULL,NULL,'2020-06-26 21:58:48','2020-06-26 21:58:48',NULL),(4,'Local Broker trader','trader@innovate10x. com',NULL,'Approved','$2y$10$.hJHg2TPUpQ2wyMfGJxxM.L6DaAPE81E64rHueFK4EiM8ljklpPwO',NULL,NULL,'2020-06-26 21:58:48','2020-06-26 21:58:48',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-06-27 20:48:21
