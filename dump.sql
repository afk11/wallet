-- phpMyAdmin SQL Dump
-- version 4.4.13.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 02, 2016 at 08:36 PM
-- Server version: 5.6.30-0ubuntu0.15.10.1
-- PHP Version: 5.6.11-1ubuntu3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `utxo`
--

-- --------------------------------------------------------

--
-- Table structure for table `chain`
--

CREATE TABLE IF NOT EXISTS `chain` (
  `id` int(11) NOT NULL,
  `hashKey` varbinary(32) NOT NULL,
  `version` int(9) NOT NULL,
  `prev` varbinary(32) NOT NULL,
  `merkle` varbinary(32) NOT NULL,
  `ntime` int(19) NOT NULL,
  `nbits` int(19) NOT NULL,
  `nonce` int(19) NOT NULL,
  `height` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `script`
--

CREATE TABLE IF NOT EXISTS `script` (
  `id` int(9) NOT NULL,
  `scriptPubKey` blob NOT NULL,
  `start_at` int(19) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `script`
--

INSERT INTO `script` (`id`, `scriptPubKey`, `start_at`) VALUES
(1, 0x4104ae1a62fe09c5f51b13905f07f06b99a2f7159b2225f374cd378d71302fa28414e7aab37397f554a7df5f142c21c1b7303b8a0626f1baded5c72a704f7e6cd84cac, 0);

-- --------------------------------------------------------

--
-- Table structure for table `utxo`
--

CREATE TABLE IF NOT EXISTS `utxo` (
  `id` int(9) NOT NULL,
  `outpoint` varbinary(36) NOT NULL,
  `scriptPubKey` blob NOT NULL,
  `value` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chain`
--
ALTER TABLE `chain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hashKey` (`hashKey`);

--
-- Indexes for table `script`
--
ALTER TABLE `script`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utxo`
--
ALTER TABLE `utxo`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chain`
--
ALTER TABLE `chain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `script`
--
ALTER TABLE `script`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `utxo`
--
ALTER TABLE `utxo`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;