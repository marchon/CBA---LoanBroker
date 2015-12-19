-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost:8889
-- Generation Time: Dec 13, 2015 at 06:14 PM
-- Server version: 5.5.34
-- PHP Version: 5.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rulebase`
--
CREATE DATABASE IF NOT EXISTS `rulebase` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `rulebase`;

-- --------------------------------------------------------

--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
CREATE TABLE `banks` (
  `id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `lowest_credit_score_allowed` int(10) NOT NULL,
  `cvr` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `banks`
--

INSERT INTO `banks` (`id`, `name`, `lowest_credit_score_allowed`, `cvr`) VALUES
(1, 'Not Danske Bank', 700, 11111111),
(2, 'Not Nordea Bank', 650, 22222222);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banks`
--
ALTER TABLE `banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
