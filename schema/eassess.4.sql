-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2013 at 04:14 AM
-- Server version: 5.5.27
-- PHP Version: 5.4.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `eassess`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE IF NOT EXISTS `assessment` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkFCAR` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `numSuccess` int(11) NOT NULL,
  `numEvaluated` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `coordinator`
--

CREATE TABLE IF NOT EXISTS `coordinator` (
  `fkCourse` int(10) unsigned NOT NULL,
  `fkUser` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fkCourse`,`fkUser`),
  KEY `fk_Coordinator_Course1_idx` (`fkCourse`),
  KEY `fk_Coordinator_User1_idx` (`fkUser`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `coordinator`
--

INSERT INTO `coordinator` (`fkCourse`, `fkUser`) VALUES
(74, 6),
(75, 6),
(76, 5),
(77, 5),
(78, 4),
(79, 5);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned NOT NULL,
  `fkUserModified` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text,
  `prefix` varchar(4) NOT NULL,
  `number` varchar(4) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueCourseConstraint` (`prefix`,`number`),
  KEY `fk_Course_Department1_idx` (`fkDepartment`),
  KEY `fk_Course_User1_idx` (`fkUserModified`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=80 ;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`ID`, `fkDepartment`, `fkUserModified`, `name`, `description`, `prefix`, `number`, `dateCreated`, `dateModified`, `status`) VALUES
(73, 2, 2, 'Calculus I', 'Some students test out of this class with AP Calculus. This class provides an introduction to basic calculus concepts such as limits, derivatives, and basic integrals.', 'MATH', '160', '2013-07-09 15:50:01', '2013-07-09 15:50:01', 1),
(74, 2, 2, 'Calculus II', 'This class introduces common exact integration solution techniques such as integration by parts, partial fraction decomposition, and trigonometric substitution. Additionally, the class explores infinite series.', 'MATH', '161', '2013-07-09 15:52:30', '2013-07-09 15:52:30', 1),
(75, 2, 2, 'Calculus III', 'This class covers multivariable calculus.', 'MATH', '260', '2013-07-09 15:53:09', '2013-07-09 15:53:09', 1),
(76, 1, 3, 'Introduction to Algorithmic Design I', 'Easy class. Used to weed out the students who can''t learn how to program.', 'CSCI', '130', '2013-07-09 16:17:35', '2013-07-10 12:36:44', 1),
(77, 1, 3, 'Introduction to Algorithmic Design II', 'Still easy class. Still used to weed out students who can''t learn how to program.', 'CSCI', '140', '2013-07-09 16:18:12', '2013-07-10 12:36:49', 1),
(78, 1, 3, 'Data Structures', 'Hard class. Math heavy.', 'CSCI', '220', '2013-07-09 16:18:33', '2013-07-10 12:36:59', 1),
(79, 1, 3, 'Introduction to Algorithmic Design III', 'Slightly harder.', 'CSCI', '150', '2013-07-09 16:19:36', '2013-07-10 12:36:54', 1);

-- --------------------------------------------------------

--
-- Table structure for table `degree`
--

CREATE TABLE IF NOT EXISTS `degree` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(10) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueCodeConstraint` (`code`,`fkDepartment`),
  KEY `fk_Track_Department1_idx` (`fkDepartment`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `degree`
--

INSERT INTO `degree` (`ID`, `fkDepartment`, `name`, `code`, `dateCreated`, `status`) VALUES
(1, 1, 'Computer Science', 'CS', '2013-07-09 16:14:37', 1),
(3, 1, 'Information Systems', 'IS', '2013-07-09 16:14:47', 1),
(4, 1, 'Information Technology', 'IT', '2013-07-10 15:50:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE IF NOT EXISTS `department` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`ID`, `name`, `dateCreated`, `status`) VALUES
(1, 'Computer Science', '2013-07-09 15:39:36', 1),
(2, 'Mathematics', '2013-07-09 15:39:43', 1),
(3, 'Business', '2013-07-09 15:40:10', 1);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE IF NOT EXISTS `evaluation` (
  `fkFCAR` int(10) unsigned NOT NULL,
  `fkObjective` int(10) unsigned NOT NULL,
  `evaluation` text NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`fkFCAR`,`fkObjective`),
  KEY `fk_Evaluation_Objective1_idx` (`fkObjective`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fcar`
--

CREATE TABLE IF NOT EXISTS `fcar` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkCourse` int(10) unsigned NOT NULL,
  `fkUser` int(10) unsigned NOT NULL,
  `fkDegree` int(10) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `term` varchar(2) NOT NULL,
  `section` varchar(32) DEFAULT NULL,
  `modification` text,
  `feedback` text,
  `reflection` text,
  `improvement` text,
  `A` tinyint(4) DEFAULT NULL,
  `BPlus` tinyint(4) DEFAULT NULL,
  `B` tinyint(4) DEFAULT NULL,
  `CPlus` tinyint(4) DEFAULT NULL,
  `C` tinyint(4) DEFAULT NULL,
  `DPlus` tinyint(4) DEFAULT NULL,
  `D` tinyint(4) DEFAULT NULL,
  `F` tinyint(4) DEFAULT NULL,
  `dateCreated` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueSectionConstraint` (`year`,`term`,`section`,`fkCourse`,`fkDegree`),
  KEY `fk_FCAR_Course1_idx` (`fkCourse`),
  KEY `fk_Section_User1_idx` (`fkUser`),
  KEY `fk_FCAR_Degree1_idx` (`fkDegree`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `fcar`
--

INSERT INTO `fcar` (`ID`, `fkCourse`, `fkUser`, `fkDegree`, `year`, `term`, `section`, `modification`, `feedback`, `reflection`, `improvement`, `A`, `BPlus`, `B`, `CPlus`, `C`, `DPlus`, `D`, `F`, `dateCreated`, `dateModified`, `status`) VALUES
(8, 78, 10, 3, 2013, 'F1', '01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2013-07-18 22:13:11', '2013-07-18 22:13:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `objective`
--

CREATE TABLE IF NOT EXISTS `objective` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkCourse` int(10) unsigned NOT NULL,
  `fkUserModified` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  `number` tinyint(4) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `fk_objectives_courses1_idx` (`fkCourse`),
  KEY `fk_Objective_User1_idx` (`fkUserModified`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `objective`
--

INSERT INTO `objective` (`ID`, `fkCourse`, `fkUserModified`, `description`, `number`, `dateCreated`, `dateModified`, `status`) VALUES
(1, 79, 10, 'This is the first objective. It measures math and s***.', 1, '2013-07-16 16:41:51', '2013-07-16 16:42:12', 1),
(2, 77, 10, 'This is the fifth objective. It measures EVERYTHING.', 5, '2013-07-16 16:43:44', '2013-07-16 16:43:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE IF NOT EXISTS `result` (
  `fkAssessment` int(10) unsigned NOT NULL,
  `fkObjective` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fkAssessment`,`fkObjective`),
  KEY `fk_Result_Objective1_idx` (`fkObjective`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `slo`
--

CREATE TABLE IF NOT EXISTS `slo` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) NOT NULL,
  `description` text NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `slo`
--

INSERT INTO `slo` (`ID`, `code`, `description`, `dateCreated`, `status`) VALUES
(7, 'A', 'An ability to apply fundamental principles of computing and mathematics.', '2013-07-09 21:23:35', 1),
(9, 'B', 'An ability to analyze a problem, and identify and define the requirements appropriate to its solution.', '2013-07-10 00:56:42', 1),
(10, 'C', 'This is the best SLO ever. It practically makes the other SLOs quake in their boots.', '2013-07-16 16:44:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sloxdegree`
--

CREATE TABLE IF NOT EXISTS `sloxdegree` (
  `fkSLO` int(10) unsigned NOT NULL,
  `fkDegree` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fkSLO`,`fkDegree`),
  KEY `fk_xrefSLODegree_Degree1_idx` (`fkDegree`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sloxdegree`
--

INSERT INTO `sloxdegree` (`fkSLO`, `fkDegree`) VALUES
(7, 1),
(9, 1),
(7, 3),
(9, 3),
(10, 3),
(10, 4);

-- --------------------------------------------------------

--
-- Table structure for table `sloxobjective`
--

CREATE TABLE IF NOT EXISTS `sloxobjective` (
  `fkSLO` int(10) unsigned NOT NULL,
  `fkObjective` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fkSLO`,`fkObjective`),
  KEY `fk_xrefSLOObjective_Objective1_idx` (`fkObjective`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sloxobjective`
--

INSERT INTO `sloxobjective` (`fkSLO`, `fkObjective`) VALUES
(7, 1),
(7, 2),
(9, 2);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned NOT NULL,
  `firstName` varchar(35) NOT NULL,
  `lastName` varchar(35) NOT NULL,
  `username` varchar(10) NOT NULL,
  `passwordHash` char(82) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `email_UNIQUE` (`username`),
  KEY `fk_User_Department1_idx` (`fkDepartment`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `fkDepartment`, `firstName`, `lastName`, `username`, `passwordHash`, `level`, `dateCreated`, `status`) VALUES
(2, 2, 'Jim', 'Solazzo', 'jsolazzo', '$2a$08$rByePQf1BEOp6pxAmzsF8ucvGEv4R05XTHY6bKpgpkWGr/2cTH0iGrByePQf1BEOp6pxAmzsF85', 2, '2013-07-09 15:40:57', 1),
(3, 1, 'John', 'Stamey', 'jstamey', '$2a$08$d5POxmwqr23xVMVY/tdxwO7/eTK1s8rIknWoUNcWicpx5gEF3kshqd5POxmwqr23xVMVY/tdxwa', 2, '2013-07-09 15:41:25', 1),
(4, 1, 'Heather', 'Rickard', 'hrickard', '$2a$08$uML0WgeLvKvT35AzAQ6as.11kZXgggb.tk9xxZbgsCiO08SqucYHeuML0WgeLvKvT35AzAQ6asM', 1, '2013-07-09 15:43:09', 1),
(5, 1, 'Mike', 'Murphy', 'mmurphy', '$2a$08$5nJN.gB7.TvuJA6aWJ.PR.UpWu2AXTeHeTToFAGFoNLJnKetIEDpK5nJN.gB7.TvuJA6aWJ.PRL', 1, '2013-07-09 15:44:03', 1),
(6, 2, 'Dave', 'Duncan', 'dduncan', '$2a$08$LuLC/yZAO4WdnpuHlXDUAupx5z8YXU7CvPC7qcpZ4QTLsCeup85VmLuLC/yZAO4WdnpuHlXDUAy', 1, '2013-07-09 15:46:35', 1),
(7, 2, 'Tom', 'Hoffman', 'thoffman', '$2a$08$vo3dX.CHlmxSrSwgX0LRkONkt2aecPY02ck9bR4h1X.2YKRNYGl9Wvo3dX.CHlmxSrSwgX0LRkR', 1, '2013-07-09 15:46:52', 1),
(8, 2, 'Andrew', 'Incognito', 'aincognito', '$2a$08$1bxztiirBP1aIZ9tInUfN.Ul1jtX0YH5InvMrbTvSKp4GdQbZXClC1bxztiirBP1aIZ9tInUfNC', 1, '2013-07-09 15:47:58', 1),
(10, 1, 'Jean', 'French', 'jfrench', '$2a$08$ZbrPhyuRXAhvbfsRRHDwFOpduRdsW6CqzMaRD/tFbTyH0VKf7WrgeZbrPhyuRXAhvbfsRRHDwFX', 3, '2013-07-10 12:51:21', 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `fk_Result_Section1` FOREIGN KEY (`fkFCAR`) REFERENCES `fcar` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `coordinator`
--
ALTER TABLE `coordinator`
  ADD CONSTRAINT `fk_Coordinator_Course1` FOREIGN KEY (`fkCourse`) REFERENCES `course` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Coordinator_User1` FOREIGN KEY (`fkUser`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `fk_Course_User1` FOREIGN KEY (`fkUserModified`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Course_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `degree`
--
ALTER TABLE `degree`
  ADD CONSTRAINT `fk_Track_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `fk_Evaluation_FCAR1` FOREIGN KEY (`fkFCAR`) REFERENCES `fcar` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Evaluation_Objective1` FOREIGN KEY (`fkObjective`) REFERENCES `objective` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `fcar`
--
ALTER TABLE `fcar`
  ADD CONSTRAINT `fk_FCAR_Course1` FOREIGN KEY (`fkCourse`) REFERENCES `course` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Section_User1` FOREIGN KEY (`fkUser`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_FCAR_Degree1` FOREIGN KEY (`fkDegree`) REFERENCES `degree` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `objective`
--
ALTER TABLE `objective`
  ADD CONSTRAINT `fk_Objective_User1` FOREIGN KEY (`fkUserModified`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_objectives_courses1` FOREIGN KEY (`fkCourse`) REFERENCES `course` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `fk_Result_Assessment1` FOREIGN KEY (`fkAssessment`) REFERENCES `assessment` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Result_Objective1` FOREIGN KEY (`fkObjective`) REFERENCES `objective` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `sloxdegree`
--
ALTER TABLE `sloxdegree`
  ADD CONSTRAINT `fk_xrefSLODegree_Degree1` FOREIGN KEY (`fkDegree`) REFERENCES `degree` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_xrefSLODegree_SLO1` FOREIGN KEY (`fkSLO`) REFERENCES `slo` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `sloxobjective`
--
ALTER TABLE `sloxobjective`
  ADD CONSTRAINT `fk_xrefSLOObjective_Objective1` FOREIGN KEY (`fkObjective`) REFERENCES `objective` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_xrefSLOObjective_SLO1` FOREIGN KEY (`fkSLO`) REFERENCES `slo` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_User_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
