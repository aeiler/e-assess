-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2013 at 06:33 PM
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
(1, 6),
(2, 6),
(3, 7),
(4, 7);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text,
  `prefix` varchar(4) NOT NULL,
  `number` varchar(4) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueCourseConstraint` (`prefix`,`number`),
  KEY `fk_Course_Department1_idx` (`fkDepartment`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`ID`, `fkDepartment`, `name`, `description`, `prefix`, `number`, `dateCreated`, `status`) VALUES
(1, 4, 'Data Structures', 'Hard class with a lot of math.', 'CSCI', '220', '2013-07-05 15:15:56', 1),
(2, 4, 'Introduction to Algorithmic Design I', 'Boring, easy class. Used to weed out the people who can''t learn how to program.', 'CSCI', '130', '2013-07-06 11:15:31', 1),
(3, 4, 'Introduction to Algorithmic Design II', 'Also a boring, easy class. Used to weed out the people who can''t learn how to program.', 'CSCI', '140', '2013-07-06 11:16:33', 1),
(4, 4, 'Introduction to Algorithmic Design III', 'No longer easy. Still used to weed out the people who can''t learn how to program.', 'CSCI', '150', '2013-07-06 11:17:19', 1);

-- --------------------------------------------------------

--
-- Table structure for table `degree`
--

CREATE TABLE IF NOT EXISTS `degree` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(10) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueCodeConstraint` (`code`,`fkDepartment`),
  KEY `fk_Track_Department1_idx` (`fkDepartment`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `degree`
--

INSERT INTO `degree` (`ID`, `fkDepartment`, `name`, `code`, `dateCreated`, `status`) VALUES
(1, 4, 'Information Systems', 'IS', '2013-07-06 12:03:24', 1),
(2, 4, 'Computer Science', 'CS', '2013-07-06 12:11:50', 1);

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
  UNIQUE KEY `uniqueNameConstraint` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`ID`, `name`, `dateCreated`, `status`) VALUES
(4, 'Computer Science', '2013-07-05 13:51:02', 1),
(5, 'Mathematics', '2013-07-05 13:51:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fcar`
--

CREATE TABLE IF NOT EXISTS `fcar` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkCourse` int(10) unsigned NOT NULL,
  `fkUser` int(10) unsigned NOT NULL,
  `fkDegree` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `term` varchar(2) NOT NULL,
  `section` varchar(32) DEFAULT NULL,
  `reflection` text,
  `dateCreated` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueSectionConstraint` (`year`,`term`,`section`,`fkCourse`),
  KEY `fk_FCAR_Course1_idx` (`fkCourse`),
  KEY `fk_Section_User1_idx` (`fkUser`),
  KEY `fk_FCAR_Degree1_idx` (`fkDegree`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `objective`
--

CREATE TABLE IF NOT EXISTS `objective` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkCourse` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  `number` tinyint(4) NOT NULL,
  `evaluation` text NOT NULL,
  `dateCreated` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `fk_objectives_courses1_idx` (`fkCourse`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE IF NOT EXISTS `result` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkFCAR` int(10) unsigned NOT NULL,
  `fkObjective` int(10) unsigned NOT NULL,
  `numSuccess` int(11) NOT NULL,
  `numEvaluated` int(11) NOT NULL,
  `successExplanation` text,
  `reflection` text,
  `dateCreated` datetime NOT NULL,
  `dateModified` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `fk_Result_Objective1_idx` (`fkObjective`),
  KEY `fk_Result_Section1` (`fkFCAR`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UniqueCodeConstraint` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fkDepartment` int(10) unsigned DEFAULT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `fkDepartment`, `firstName`, `lastName`, `username`, `passwordHash`, `level`, `dateCreated`, `status`) VALUES
(4, NULL, 'Aaron', 'Smith', 'ajsmith7', '$2a$08$dtWaJQV0YwoHxEnEV596v.mQlutL1Bng30AbGCOxjkXvGvxeJ.uF.dtWaJQV0YwoHxEnEV596vE', 3, '2013-07-05 13:46:50', 1),
(5, 4, 'John', 'Stamey', 'jstamey', '$2a$08$o53Nr48KVX1c4b/PDl7O3.jncxEmiXgSK0/X0mF33HMD0tvX2FjC2o53Nr48KVX1c4b/PDl7O3L', 2, '2013-07-05 14:03:07', 1),
(6, 4, 'Mike', 'Murphy', 'mmurphy', '$2a$08$XtLO6aR81JJ5pae0YYySteq5nnVZPxu2K53Z2aGZEvy33WWTMNcPeXtLO6aR81JJ5pae0YYyStf', 1, '2013-07-05 14:07:36', 1),
(7, 4, 'Clint', 'Fuchs', 'cfuchs', '$2a$08$FVa27.k4HogrC/7NLIrZNebwD1i/U2jyqdHGjwad3LSIp.fmEyBUeFVa27.k4HogrC/7NLIrZNi', 1, '2013-07-06 11:39:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `xrefslodegree`
--

CREATE TABLE IF NOT EXISTS `xrefslodegree` (
  `fkSLO` int(10) unsigned NOT NULL,
  `fkDegree` int(11) NOT NULL,
  PRIMARY KEY (`fkSLO`,`fkDegree`),
  KEY `fk_xrefSLODegree_Degree1_idx` (`fkDegree`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `xrefsloobjective`
--

CREATE TABLE IF NOT EXISTS `xrefsloobjective` (
  `fkSLO` int(10) unsigned NOT NULL,
  `fkObjective` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fkSLO`,`fkObjective`),
  KEY `fk_xrefSLOObjective_Objective1_idx` (`fkObjective`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

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
  ADD CONSTRAINT `fk_Course_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `degree`
--
ALTER TABLE `degree`
  ADD CONSTRAINT `fk_Track_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `fcar`
--
ALTER TABLE `fcar`
  ADD CONSTRAINT `fk_FCAR_Course1` FOREIGN KEY (`fkCourse`) REFERENCES `course` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_FCAR_Degree1` FOREIGN KEY (`fkDegree`) REFERENCES `degree` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Section_User1` FOREIGN KEY (`fkUser`) REFERENCES `user` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `objective`
--
ALTER TABLE `objective`
  ADD CONSTRAINT `fk_objectives_courses1` FOREIGN KEY (`fkCourse`) REFERENCES `course` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `fk_Result_Objective1` FOREIGN KEY (`fkObjective`) REFERENCES `objective` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Result_Section1` FOREIGN KEY (`fkFCAR`) REFERENCES `fcar` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_User_Department1` FOREIGN KEY (`fkDepartment`) REFERENCES `department` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `xrefslodegree`
--
ALTER TABLE `xrefslodegree`
  ADD CONSTRAINT `fk_xrefSLODegree_Degree1` FOREIGN KEY (`fkDegree`) REFERENCES `degree` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_xrefSLODegree_SLO1` FOREIGN KEY (`fkSLO`) REFERENCES `slo` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `xrefsloobjective`
--
ALTER TABLE `xrefsloobjective`
  ADD CONSTRAINT `fk_xrefSLOObjective_Objective1` FOREIGN KEY (`fkObjective`) REFERENCES `objective` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_xrefSLOObjective_SLO1` FOREIGN KEY (`fkSLO`) REFERENCES `slo` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
