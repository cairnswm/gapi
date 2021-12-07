-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.10-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             10.3.0.5771
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table justdance.friendrating
CREATE TABLE IF NOT EXISTS `friendrating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `icon` varchar(20) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- Dumping data for table justdance.friendrating: ~0 rows (approximately)
/*!40000 ALTER TABLE `friendrating` DISABLE KEYS */;
INSERT INTO `friendrating` (`id`, `name`, `icon`) VALUES
	(1, 'Love', 'heart');
/*!40000 ALTER TABLE `friendrating` ENABLE KEYS */;

-- Dumping structure for table justdance.friendrequest
CREATE TABLE IF NOT EXISTS `friendrequest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profileid` int(11) DEFAULT NULL,
  `friendid` int(11) DEFAULT NULL,
  `accepted` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table justdance.friendrequest: ~0 rows (approximately)
/*!40000 ALTER TABLE `friendrequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `friendrequest` ENABLE KEYS */;

-- Dumping structure for table justdance.friends
CREATE TABLE IF NOT EXISTS `friends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profileid` int(11) DEFAULT NULL,
  `friendid` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  KEY `id` (`id`),
  KEY `Index 2` (`profileid`),
  KEY `Index 3` (`friendid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- Dumping data for table justdance.friends: ~3 rows (approximately)
/*!40000 ALTER TABLE `friends` DISABLE KEYS */;
INSERT INTO `friends` (`id`, `profileid`, `friendid`, `rating`, `created`) VALUES
	(1, 1, 2, 1, '2019-12-07 21:34:10'),
	(2, 1, 3, 1, '2019-12-07 21:34:21'),
	(3, 1, 5, 1, '2019-12-08 08:27:37');
/*!40000 ALTER TABLE `friends` ENABLE KEYS */;

-- Dumping structure for table justdance.messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `message` varchar(500) NOT NULL,
  `sent` timestamp NOT NULL DEFAULT current_timestamp(),
  `read` datetime DEFAULT NULL,
  `replyto` int(11) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `Index 2` (`from`),
  KEY `Index 3` (`to`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

-- Dumping data for table justdance.messages: ~3 rows (approximately)
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` (`id`, `from`, `to`, `message`, `sent`, `read`, `replyto`) VALUES
	(1, 2, 1, 'Hello Patricuia', '2019-12-08 17:18:24', '0000-00-00 00:00:00', NULL),
	(2, 5, 1, 'Would you like to join me at the Nothern Lights festival? Im looking for a dance partner for the night.', '2019-12-08 17:35:31', '0000-00-00 00:00:00', NULL),
	(3, 1, 2, 'Hi Michael', '2019-12-29 09:14:44', NULL, NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;

-- Dumping structure for table justdance.profile
CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '0',
  `gender` varchar(1) DEFAULT 'F',
  `signupdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `avatar` varchar(50) DEFAULT NULL,
  `message` varchar(150) DEFAULT NULL,
  `tagline` varchar(200) DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

-- Dumping data for table justdance.profile: ~5 rows (approximately)
/*!40000 ALTER TABLE `profile` DISABLE KEYS */;
INSERT INTO `profile` (`id`, `name`, `gender`, `signupdate`, `status`, `avatar`, `message`, `tagline`, `country`, `state`, `city`) VALUES
	(1, 'Michael Jones', 'M', '2019-12-07 21:24:42', 'I cant dance :(', NULL, NULL, NULL, NULL, NULL, NULL),
	(2, 'Joey McLean', 'F', '2019-12-07 21:26:31', 'Would like to learn to Dance', NULL, NULL, NULL, NULL, NULL, NULL),
	(3, 'Sarah Gibbons', 'F', '2019-12-07 21:33:54', 'Pasionate Dancer', 'dana.png', 'Im going to Kizomba night to meet my favorite teachers, and dance  the night away! ', NULL, NULL, NULL, NULL),
	(4, 'Nadia Caroso', 'M', '2019-12-08 07:39:01', 'Will be a Kizomba Teacher!', 'claudio.png', NULL, NULL, NULL, NULL, NULL),
	(5, 'Patricia Smith', 'F', '2019-12-08 07:41:14', 'Master Teacher', 'patricia_cardoso.jpg', 'Will be at Kizomba night', NULL, NULL, NULL, NULL);
/*!40000 ALTER TABLE `profile` ENABLE KEYS */;

-- Dumping structure for view justdance.wall
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `wall` (
	`id` INT(11) NULL,
	`friendid` INT(11) NULL,
	`name` VARCHAR(50) NOT NULL COLLATE 'latin1_swedish_ci',
	`gender` VARCHAR(1) NULL COLLATE 'latin1_swedish_ci',
	`status` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`avatar` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`message` VARCHAR(150) NULL COLLATE 'latin1_swedish_ci'
) ENGINE=MyISAM;

-- Dumping structure for view justdance.wall
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `wall`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `wall` AS select `f`.`profileid` AS `id`,`f`.`friendid` AS `friendid`,`p`.`name` AS `name`,`p`.`gender` AS `gender`,`p`.`status` AS `status`,`p`.`avatar` AS `avatar`,`p`.`message` AS `message` from (`profile` `p` join `friends` `f`) where `p`.`id` = `f`.`friendid`;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
