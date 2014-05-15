-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 16, 2014 at 01:30 AM
-- Server version: 5.5.31
-- PHP Version: 5.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `api338`
--
CREATE DATABASE IF NOT EXISTS `api338` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `api338`;

-- --------------------------------------------------------

--
-- Table structure for table `api_users`
--

CREATE TABLE IF NOT EXISTS `api_users` (
  `api_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `api_username` varchar(65) NOT NULL,
  `api_password` varchar(65) NOT NULL,
  PRIMARY KEY (`api_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `api_users`
--

INSERT INTO `api_users` (`api_user_id`, `api_username`, `api_password`) VALUES
(1, 'marshall', 'lol123'),
(2, 'karl', 'lol123');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_title` varchar(150) NOT NULL,
  `article_sub_title` varchar(200) NOT NULL,
  `article_content` text NOT NULL,
  `article_views` int(11) NOT NULL,
  `article_unique_views` int(11) NOT NULL,
  `article_author_id` int(11) NOT NULL,
  `article_post_date` date NOT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`article_id`, `article_title`, `article_sub_title`, `article_content`, `article_views`, `article_unique_views`, `article_author_id`, `article_post_date`) VALUES
(1, 'The Large Families That Rule The World', 'The Government Don''t Rule The World, Goldman Sachs Does', 'Some people have started realizing that there are large financial groups that dominate the world. Forget the political intrigues, conflicts, revolutions and wars. It is not pure chance. Everything has been planned for a long time.\r\n\r\nSome call it "conspiracy theories" or New World Order. Anyway, the key to understanding the current political and economic events is a restricted core of families who have accumulated more wealth and power.\r\n\r\nWe are speaking of 6, 8 or maybe 12 families who truly dominate the world. Know that it is a mystery difficult to unravel.', 1000, 350, 1, '2014-02-17'),
(2, 'North Korea has committed crimes against humanity: UN report', 'Investigation cites cases of extermination, torture, and abductions, as commission calls for international justice', 'The United Nations today announced that North Korea has committed crimes against humanity, calling for the reclusive state to be prosecuted in the International Criminal Court (ICC). A UN commission presented its findings Monday in a report that experts describe as the most authoritative and damning account to date of the abuses carried out under the Kim family for the past 60 years. Pyongyang says it "categorically and totally rejects" the UN''s findings, while China has signaled that it will move to block initiatives to bring the country before the ICC.', 1600, 928, 1, '2014-02-17'),
(3, 'Causal Determinism', 'Causal determinism is, roughly speaking, the idea that every event is necessitated by antecedent events and conditions together with the laws of nature.', 'Causal determinism is, roughly speaking, the idea that every event is necessitated by antecedent events and conditions together with the laws of nature. The idea is ancient, but first became subject to clarification and mathematical analysis in the eighteenth century. Determinism is deeply connected with our understanding of the physical sciences and their explanatory ambitions, on the one hand, and with our views about human free action on the other. In both of these general areas there is no agreement over whether determinism is true (or even whether it can be known true or false), and what the import for human agency would be in either case.', 2000, 1490, 2, '2014-02-17');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_article_id` int(11) NOT NULL,
  `comment_full_name` varchar(50) NOT NULL,
  `comment_full` text NOT NULL,
  `comment_location` varchar(65) NOT NULL,
  `comment_likes` int(1) NOT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `comment_article_id`, `comment_full_name`, `comment_full`, `comment_location`, `comment_likes`) VALUES
(1, 1, 'Eric Thomas', 'Success does not require you to look out the window. It only requires you to look in the mirror.', 'Los Angeles, California', 5),
(2, 2, 'Jason Silva', 'We have a responsibility to awe.', 'Kyoto, Japan', 25);

-- --------------------------------------------------------

--
-- Table structure for table `quota`
--

CREATE TABLE IF NOT EXISTS `quota` (
  `quota_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `quota` int(11) NOT NULL,
  PRIMARY KEY (`quota_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `quota`
--

INSERT INTO `quota` (`quota_id`, `user_id`, `date`, `quota`) VALUES
(1, 1, '2014-03-18', 50),
(6, 2, '2014-03-26', 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email_address` varchar(170) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email_address`) VALUES
(1, 'karl', 'louis', 'hadwen', 'karl@crownedtraders.com'),
(2, 'joerogan', 'louis', 'rogan', 'joe@jre.com'),
(3, 'terencemckenna', 'terence', 'mckenna', 'terencemckenna@phy.com'),
(4, 'louistheroux', 'louis', 'theroux', 'louistheroux@when.com');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
