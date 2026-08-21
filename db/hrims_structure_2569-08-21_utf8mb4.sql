/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: hrims
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `aopod_death_list`
--

DROP TABLE IF EXISTS `aopod_death_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aopod_death_list` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `patient_death` varchar(5) DEFAULT NULL,
  `patient_deathday` date DEFAULT NULL,
  `person_death` varchar(5) DEFAULT NULL,
  `person_deathday` date DEFAULT NULL,
  `active_clinics` int(11) NOT NULL DEFAULT 0,
  `active_clinics_list` text DEFAULT NULL,
  `has_clinics` tinyint(4) NOT NULL DEFAULT 0,
  `death_table_date` date DEFAULT NULL,
  `death_table_diag` varchar(20) DEFAULT NULL,
  `death_table_cause` varchar(255) DEFAULT NULL,
  `aopod_death_date` date DEFAULT NULL,
  `aopod_death_diag` varchar(20) DEFAULT NULL,
  `aopod_death_cause` varchar(255) DEFAULT NULL,
  `aopod_death_place` varchar(255) DEFAULT NULL,
  `is_complete` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aopod_death_list_cid_index` (`cid`),
  KEY `aopod_death_list_hn_index` (`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2228 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budget_year`
--

DROP TABLE IF EXISTS `budget_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_year` (
  `LEAVE_YEAR_ID` varchar(10) NOT NULL DEFAULT '',
  `LEAVE_YEAR_NAME` varchar(255) DEFAULT '',
  `DATE_BEGIN` date DEFAULT NULL,
  `DATE_END` date DEFAULT NULL,
  `ACTIVE` enum('True','False') DEFAULT 'False',
  `DAY_PER_YEAR` int(11) DEFAULT 10,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`LEAVE_YEAR_ID`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_103`
--

DROP TABLE IF EXISTS `debtor_1102050101_103`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_103` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_109`
--

DROP TABLE IF EXISTS `debtor_1102050101_109`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_109` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_201`
--

DROP TABLE IF EXISTS `debtor_1102050101_201`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_201` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_202`
--

DROP TABLE IF EXISTS `debtor_1102050101_202`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_202` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` decimal(10,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_203`
--

DROP TABLE IF EXISTS `debtor_1102050101_203`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_203` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_209`
--

DROP TABLE IF EXISTS `debtor_1102050101_209`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_209` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `pp` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double DEFAULT NULL,
  `repno` varchar(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_216`
--

DROP TABLE IF EXISTS `debtor_1102050101_216`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_216` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `cr` double(15,2) DEFAULT NULL,
  `anywhere` double(15,2) DEFAULT NULL,
  `ppfs` double DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` decimal(10,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_217`
--

DROP TABLE IF EXISTS `debtor_1102050101_217`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_217` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `cr` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` decimal(10,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_301`
--

DROP TABLE IF EXISTS `debtor_1102050101_301`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_301` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_302`
--

DROP TABLE IF EXISTS `debtor_1102050101_302`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_302` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_303`
--

DROP TABLE IF EXISTS `debtor_1102050101_303`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_303` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_304`
--

DROP TABLE IF EXISTS `debtor_1102050101_304`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_304` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `income_pttype` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_307`
--

DROP TABLE IF EXISTS `debtor_1102050101_307`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_307` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_308`
--

DROP TABLE IF EXISTS `debtor_1102050101_308`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_308` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `income_pttype` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_309`
--

DROP TABLE IF EXISTS `debtor_1102050101_309`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_309` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `ppfs` double DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_310`
--

DROP TABLE IF EXISTS `debtor_1102050101_310`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_310` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_401`
--

DROP TABLE IF EXISTS `debtor_1102050101_401`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_401` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `ofc` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_402`
--

DROP TABLE IF EXISTS `debtor_1102050101_402`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_402` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_501`
--

DROP TABLE IF EXISTS `debtor_1102050101_501`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_501` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_502`
--

DROP TABLE IF EXISTS `debtor_1102050101_502`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_502` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_503`
--

DROP TABLE IF EXISTS `debtor_1102050101_503`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_503` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_504`
--

DROP TABLE IF EXISTS `debtor_1102050101_504`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_504` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_701`
--

DROP TABLE IF EXISTS `debtor_1102050101_701`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_701` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_702`
--

DROP TABLE IF EXISTS `debtor_1102050101_702`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_702` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_703`
--

DROP TABLE IF EXISTS `debtor_1102050101_703`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_703` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050101_704`
--

DROP TABLE IF EXISTS `debtor_1102050101_704`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050101_704` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_106`
--

DROP TABLE IF EXISTS `debtor_1102050102_106`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_106` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `mobile_phone_number` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `paid_money` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_106_tracking`
--

DROP TABLE IF EXISTS `debtor_1102050102_106_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_106_tracking` (
  `tracking_id` int(10) NOT NULL AUTO_INCREMENT,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_date` date DEFAULT NULL,
  `tracking_type` varchar(100) DEFAULT NULL,
  `tracking_no` varchar(100) DEFAULT NULL,
  `tracking_officer` varchar(100) DEFAULT NULL,
  `tracking_note` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tracking_id`) USING BTREE,
  KEY `vn` (`vn`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=310 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_107`
--

DROP TABLE IF EXISTS `debtor_1102050102_107`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_107` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `mobile_phone_number` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `paid_money` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_107_tracking`
--

DROP TABLE IF EXISTS `debtor_1102050102_107_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_107_tracking` (
  `tracking_id` int(10) NOT NULL AUTO_INCREMENT,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_date` date DEFAULT NULL,
  `tracking_type` varchar(100) DEFAULT NULL,
  `tracking_no` varchar(100) DEFAULT NULL,
  `tracking_officer` varchar(100) DEFAULT NULL,
  `tracking_note` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tracking_id`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_108`
--

DROP TABLE IF EXISTS `debtor_1102050102_108`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_108` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_109`
--

DROP TABLE IF EXISTS `debtor_1102050102_109`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_109` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_110`
--

DROP TABLE IF EXISTS `debtor_1102050102_110`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_110` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `ofc` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_111`
--

DROP TABLE IF EXISTS `debtor_1102050102_111`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_111` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_602`
--

DROP TABLE IF EXISTS `debtor_1102050102_602`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_602` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` varchar(100) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_603`
--

DROP TABLE IF EXISTS `debtor_1102050102_603`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_603` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_801`
--

DROP TABLE IF EXISTS `debtor_1102050102_801`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_801` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `lgo` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` decimal(10,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_802`
--

DROP TABLE IF EXISTS `debtor_1102050102_802`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_802` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_803`
--

DROP TABLE IF EXISTS `debtor_1102050102_803`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_803` (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `ofc` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `ppfs` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(15,2) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_1102050102_804`
--

DROP TABLE IF EXISTS `debtor_1102050102_804`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_1102050102_804` (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(100) DEFAULT NULL,
  `regdate` date DEFAULT NULL,
  `regtime` time DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `pttype` varchar(100) DEFAULT NULL,
  `hospmain` varchar(100) DEFAULT NULL,
  `hipdata_code` varchar(100) DEFAULT NULL,
  `pdx` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `income` double(15,2) DEFAULT NULL,
  `rcpt_money` double(15,2) DEFAULT NULL,
  `kidney` double(15,2) DEFAULT NULL,
  `other` double(15,2) DEFAULT NULL,
  `debtor` double(15,2) DEFAULT NULL,
  `debtor_change` double(15,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `charge_date` date DEFAULT NULL,
  `charge_no` varchar(100) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `receive_date` date DEFAULT NULL,
  `receive_no` varchar(100) DEFAULT NULL,
  `receive` double(100,2) DEFAULT NULL,
  `repno` varbinary(15) DEFAULT NULL,
  `adj_inc` decimal(10,2) DEFAULT NULL,
  `adj_dec` decimal(10,2) DEFAULT NULL,
  `adj_note` varchar(255) DEFAULT NULL,
  `adj_date` date DEFAULT NULL,
  `debtor_lock` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  KEY `an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtor_acc_ledger`
--

DROP TABLE IF EXISTS `debtor_acc_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtor_acc_ledger` (
  `budget_year` int(11) NOT NULL COMMENT 'ปีงบประมาณ',
  `month_no` int(11) NOT NULL COMMENT 'ใช้เรียง Tab เดือน (1-12 ตามปีงบประมาณ)',
  `acc_code` varchar(50) NOT NULL COMMENT 'รหัสผังบัญชี',
  `vst_month` char(7) NOT NULL COMMENT 'เดือน/ปีใช้ดึงข้อมูล (YYYY-MM)',
  `acc_name` varchar(255) NOT NULL COMMENT 'ชื่อผังบัญชี',
  `balance_old` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดยกมา',
  `debt_new` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดตั้งหนี้ในเดือน',
  `debt_receive` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดรับชำระในเดือน',
  `debt_adj_dec` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดปรับลดในเดือน',
  `debt_adj_inc` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดปรับเพิ่มในเดือน',
  `balance_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดคงเหลือยกไป',
  `adj_note` text DEFAULT NULL COMMENT 'หมายเหตุการปรับปรุง',
  `aging_90` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '<= 90 วัน',
  `aging_365` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '91 - 365 วัน',
  `aging_over` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '> 365 วัน',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`budget_year`,`month_no`,`acc_code`),
  KEY `debtor_acc_ledger_budget_year_index` (`budget_year`),
  KEY `debtor_acc_ledger_acc_code_index` (`acc_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drugcat_chi`
--

DROP TABLE IF EXISTS `drugcat_chi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `drugcat_chi` (
  `hospdrugcode` varchar(255) DEFAULT NULL,
  `productcat` varchar(255) DEFAULT NULL,
  `tmtid` varchar(255) DEFAULT NULL,
  `specprep` varchar(255) DEFAULT NULL,
  `genericname` varchar(255) DEFAULT NULL,
  `tradename` varchar(255) DEFAULT NULL,
  `dfscode` varchar(255) DEFAULT NULL,
  `dosageform` varchar(255) DEFAULT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `unitprice` double DEFAULT NULL,
  `distributor` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `ised` varchar(255) DEFAULT NULL,
  `ndc24` varchar(255) DEFAULT NULL,
  `packsize` varchar(255) DEFAULT NULL,
  `packprice` varchar(255) DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `datechange` date DEFAULT NULL,
  `dateupdate` date DEFAULT NULL,
  `dateeffective` date DEFAULT NULL,
  `ised_approved` varchar(255) DEFAULT NULL,
  `ndc24_approved` varchar(255) DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `ised_status` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL,
  KEY `drugcat_chi_hospdrugcode_index` (`hospdrugcode`),
  KEY `drugcat_chi_date_approved_index` (`date_approved`),
  KEY `drugcat_chi_updateflag_index` (`updateflag`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drugcat_fdh`
--

DROP TABLE IF EXISTS `drugcat_fdh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `drugcat_fdh` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hospdrugcode` varchar(50) DEFAULT NULL,
  `productcat` varchar(50) DEFAULT NULL,
  `tmtid` varchar(50) DEFAULT NULL,
  `specprep` varchar(255) DEFAULT NULL,
  `genericname` varchar(255) DEFAULT NULL,
  `tradename` varchar(255) DEFAULT NULL,
  `dfscode` varchar(50) DEFAULT NULL,
  `dosageform` varchar(255) DEFAULT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `unitprice` decimal(15,4) DEFAULT NULL,
  `distributor` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `ised` varchar(50) DEFAULT NULL,
  `ndc24` varchar(50) DEFAULT NULL,
  `packsize` varchar(100) DEFAULT NULL,
  `packprice` decimal(15,4) DEFAULT NULL,
  `updateflag` varchar(10) DEFAULT NULL,
  `datechange` date DEFAULT NULL,
  `dateupdate` date DEFAULT NULL,
  `dateeffective` date DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `ised_status` varchar(50) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL,
  `date_import` date DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `hospcode` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hospdrugcode` (`hospdrugcode`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=445 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drugcat_nhso`
--

DROP TABLE IF EXISTS `drugcat_nhso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `drugcat_nhso` (
  `hospdrugcode` varchar(255) DEFAULT NULL,
  `productcat` varchar(255) DEFAULT NULL,
  `tmtid` varchar(255) DEFAULT NULL,
  `specprep` varchar(255) DEFAULT NULL,
  `genericname` varchar(255) DEFAULT NULL,
  `tradename` varchar(255) DEFAULT NULL,
  `dfscode` varchar(255) DEFAULT NULL,
  `dosageform` varchar(255) DEFAULT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `unitprice` double(15,2) DEFAULT NULL,
  `distributor` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `ised` varchar(255) DEFAULT NULL,
  `ndc24` varchar(255) DEFAULT NULL,
  `packsize` varchar(255) DEFAULT NULL,
  `packprice` varchar(255) DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `datechange` date DEFAULT NULL,
  `dateupdate` date DEFAULT NULL,
  `dateeffective` date DEFAULT NULL,
  `ised_approved` varchar(255) DEFAULT NULL,
  `ndc24_approved` varchar(255) DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `ised_status` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eclaim_status`
--

DROP TABLE IF EXISTS `eclaim_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eclaim_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospcode` varchar(5) DEFAULT NULL COMMENT 'รหัสสถานพยาบาล 5 หลัก',
  `eclaim_no` varchar(100) DEFAULT NULL COMMENT 'เลขที่เคลม (E-Claim)',
  `patient_type` varchar(10) DEFAULT NULL COMMENT 'ประเภทผู้ป่วย (OPD/IPD)',
  `hipdata` varchar(255) DEFAULT NULL COMMENT 'สิทธิการรักษา',
  `cid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-สกุล',
  `hn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vstdate` date DEFAULT NULL COMMENT 'วันที่เข้ารับบริการ/Admit',
  `vsttime` time DEFAULT NULL COMMENT 'เวลาเข้ารับบริการ/Admit',
  `dchdate` date DEFAULT NULL COMMENT 'วันที่จำหน่าย',
  `dchtime` time DEFAULT NULL COMMENT 'เวลาจำหน่าย',
  `status` varchar(100) DEFAULT NULL COMMENT 'สถานะการส่งข้อมูล',
  `recorder` varchar(255) DEFAULT NULL COMMENT 'ผู้บันทึก/ส่ง',
  `tran_id` varchar(100) DEFAULT NULL COMMENT 'รหัส Tran_id สทป.',
  `net_charge` double DEFAULT NULL COMMENT 'ค่าใช้จ่ายสุทธิ',
  `claim_amount` double DEFAULT NULL COMMENT 'ยอดที่ขอเก็บ',
  `rep` varchar(100) DEFAULT NULL COMMENT 'REP',
  `stm` varchar(100) DEFAULT NULL COMMENT 'STM',
  `seq` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_detail` text DEFAULT NULL COMMENT 'รายละเอียดการตรวจสอบ',
  `deny_warning` text DEFAULT NULL COMMENT 'คำเตือน/ปฏิเสธจ่าย',
  `channel` varchar(50) DEFAULT NULL COMMENT 'ช่องทางที่นำเข้า (API/Excel)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hospcode` (`hospcode`),
  KEY `idx_cid` (`cid`),
  KEY `idx_hn` (`hn`),
  KEY `idx_an` (`an`),
  KEY `idx_vstdate` (`vstdate`),
  KEY `idx_vsttime` (`vsttime`),
  KEY `idx_dchdate` (`dchdate`),
  KEY `idx_dchtime` (`dchtime`),
  KEY `idx_hn_vstdate` (`hn`,`vstdate`),
  KEY `idx_seq` (`seq`),
  KEY `idx_hipdata` (`hipdata`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=33594 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `edc_approve_list`
--

DROP TABLE IF EXISTS `edc_approve_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `edc_approve_list` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(150) DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `approve_code` varchar(50) DEFAULT NULL,
  `app_code` varchar(50) DEFAULT NULL,
  `ref_no` varchar(50) DEFAULT NULL,
  `trans_type` varchar(50) DEFAULT NULL,
  `inv_no` varchar(50) DEFAULT NULL,
  `terminal_id` varchar(50) DEFAULT NULL,
  `merchant_id` varchar(50) DEFAULT NULL,
  `edc_type` varchar(50) DEFAULT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `post_date` date DEFAULT NULL,
  `post_time` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `approve_code` (`approve_code`),
  KEY `idx_vstdate_cid` (`vstdate`,`cid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fdh_claim_status`
--

DROP TABLE IF EXISTS `fdh_claim_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fdh_claim_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seq` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hcode` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `process_status` varchar(10) DEFAULT NULL,
  `status_message_th` varchar(255) DEFAULT NULL,
  `stm_period` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_hn` (`hn`) USING BTREE,
  KEY `idx_an` (`an`) USING BTREE,
  KEY `idx_seq` (`seq`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=58853 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hosfin_dtl_mappings`
--

DROP TABLE IF EXISTS `hosfin_dtl_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hosfin_dtl_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_code` varchar(30) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `account_code` varchar(30) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `composite_group_acc` (`group_code`,`account_code`),
  KEY `group_code` (`group_code`),
  KEY `account_code` (`account_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=16327 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hosfin_trial_balance`
--

DROP TABLE IF EXISTS `hosfin_trial_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hosfin_trial_balance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `acc_year` int(11) NOT NULL,
  `acc_month` tinyint(4) NOT NULL,
  `acc_period` varchar(10) NOT NULL,
  `main_account_code` varchar(30) DEFAULT NULL,
  `account_code` varchar(30) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `debit_bf` decimal(15,2) DEFAULT 0.00,
  `credit_bf` decimal(15,2) DEFAULT 0.00,
  `debit_month` decimal(15,2) DEFAULT 0.00,
  `credit_month` decimal(15,2) DEFAULT 0.00,
  `debit_net` decimal(15,2) DEFAULT 0.00,
  `credit_net` decimal(15,2) DEFAULT 0.00,
  `import_filename` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_period` (`acc_period`),
  KEY `account_code` (`account_code`),
  KEY `composite_period_code` (`acc_period`,`account_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=10262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labcat_chi`
--

DROP TABLE IF EXISTS `labcat_chi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labcat_chi` (
  `lccode` varchar(255) DEFAULT NULL,
  `billgroup` varchar(255) DEFAULT NULL,
  `cscode` varchar(255) DEFAULT NULL,
  `tmlt` varchar(255) DEFAULT NULL,
  `loinc` varchar(255) DEFAULT NULL,
  `panel` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sflag` varchar(255) DEFAULT NULL,
  `chargecat` varchar(255) DEFAULT NULL,
  `unitprice` double DEFAULT NULL,
  `benefitplan` varchar(255) DEFAULT NULL,
  `reimbprice` double DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `updatebeg` varchar(255) DEFAULT NULL,
  `updateend` varchar(255) DEFAULT NULL,
  `rpdatebeg` varchar(255) DEFAULT NULL,
  `rpdateend` varchar(255) DEFAULT NULL,
  `dateupd` varchar(255) DEFAULT NULL,
  `hcode` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labcat_fdh`
--

DROP TABLE IF EXISTS `labcat_fdh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labcat_fdh` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `benefitplan` varchar(255) DEFAULT NULL,
  `cscode` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `unitprice` double DEFAULT NULL,
  `gyear` varchar(50) DEFAULT NULL,
  `updatebeg` varchar(255) DEFAULT NULL,
  `updateend` varchar(255) DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `tmlt` varchar(255) DEFAULT NULL,
  `tmlt_name` text DEFAULT NULL,
  `lccode` varchar(255) DEFAULT NULL,
  `loinc` varchar(255) DEFAULT NULL,
  `exception` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lccode` (`lccode`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labcat_nhso`
--

DROP TABLE IF EXISTS `labcat_nhso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labcat_nhso` (
  `lccode` varchar(255) DEFAULT NULL,
  `billgroup` varchar(255) DEFAULT NULL,
  `cscode` varchar(255) DEFAULT NULL,
  `tmlt` varchar(255) DEFAULT NULL,
  `loinc` varchar(255) DEFAULT NULL,
  `panel` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sflag` varchar(255) DEFAULT NULL,
  `chargecat` varchar(255) DEFAULT NULL,
  `unitprice` double DEFAULT NULL,
  `benefitplan` varchar(255) DEFAULT NULL,
  `reimbprice` double DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `updatebeg` varchar(255) DEFAULT NULL,
  `updateend` varchar(255) DEFAULT NULL,
  `rpdatebeg` varchar(255) DEFAULT NULL,
  `rpdateend` varchar(255) DEFAULT NULL,
  `dateupd` varchar(255) DEFAULT NULL,
  `hcode` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labcat_ss`
--

DROP TABLE IF EXISTS `labcat_ss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labcat_ss` (
  `lccode` varchar(255) DEFAULT NULL,
  `billgroup` varchar(255) DEFAULT NULL,
  `cscode` varchar(255) DEFAULT NULL,
  `tmlt` varchar(255) DEFAULT NULL,
  `loinc` varchar(255) DEFAULT NULL,
  `panel` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sflag` varchar(255) DEFAULT NULL,
  `chargecat` varchar(255) DEFAULT NULL,
  `unitprice` varchar(255) DEFAULT NULL,
  `benefitplan` varchar(255) DEFAULT NULL,
  `reimbprice` varchar(255) DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `updatebeg` varchar(255) DEFAULT NULL,
  `updateend` varchar(255) DEFAULT NULL,
  `rpdatebeg` varchar(255) DEFAULT NULL,
  `rpdateend` varchar(255) DEFAULT NULL,
  `dateupd` varchar(255) DEFAULT NULL
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labcat_tmt`
--

DROP TABLE IF EXISTS `labcat_tmt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labcat_tmt` (
  `lab_code` varchar(255) DEFAULT NULL,
  `lab_name` varchar(255) DEFAULT NULL,
  `lab_type` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `lab_price` varchar(255) DEFAULT NULL,
  `component` varchar(255) DEFAULT NULL,
  `scale` varchar(255) DEFAULT NULL,
  `specimen` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `cscode` varchar(255) DEFAULT NULL,
  `tmlt` varchar(255) DEFAULT NULL,
  `loinc_num` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lccode` varchar(255) DEFAULT NULL,
  `billgroup` varchar(255) DEFAULT NULL,
  `loinc` varchar(255) DEFAULT NULL,
  `panel` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sflag` varchar(255) DEFAULT NULL,
  `chargecat` varchar(255) DEFAULT NULL,
  `unitprice` double DEFAULT NULL,
  `benefitplan` varchar(255) DEFAULT NULL,
  `reimbprice` double DEFAULT NULL,
  `updateflag` varchar(255) DEFAULT NULL,
  `updatebeg` varchar(255) DEFAULT NULL,
  `updateend` varchar(255) DEFAULT NULL,
  `rpdatebeg` varchar(255) DEFAULT NULL,
  `rpdateend` varchar(255) DEFAULT NULL,
  `dateupd` varchar(255) DEFAULT NULL,
  `hcode` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `stm_filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lccode` (`lccode`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=484 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_hn_mappings`
--

DROP TABLE IF EXISTS `lookup_hn_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_hn_mappings` (
  `hn_hosxp` varchar(20) NOT NULL,
  `hn_ofc_kidney` varchar(20) DEFAULT NULL,
  `pt_name` varchar(190) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`hn_hosxp`),
  UNIQUE KEY `lookup_hn_mappings_hn_ofc_kidney_unique` (`hn_ofc_kidney`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_hospcode`
--

DROP TABLE IF EXISTS `lookup_hospcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_hospcode` (
  `hospcode` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hospcode_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hmain_ucs` varchar(1) DEFAULT NULL,
  `hmain_sss` varchar(1) DEFAULT NULL,
  `in_province` varchar(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`hospcode`) USING BTREE,
  KEY `hospcode` (`hospcode`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_icd10`
--

DROP TABLE IF EXISTS `lookup_icd10`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_icd10` (
  `icd10` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pp` varchar(1) DEFAULT NULL,
  `ods` varchar(1) DEFAULT NULL,
  `ods_p` varchar(1) DEFAULT NULL,
  `kidney` varchar(1) DEFAULT NULL,
  `hiv` varchar(1) DEFAULT NULL,
  `tb` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`icd10`) USING BTREE,
  KEY `icd10` (`icd10`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_icd10_chi`
--

DROP TABLE IF EXISTS `lookup_icd10_chi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_icd10_chi` (
  `code` varchar(10) NOT NULL,
  `accpdx` varchar(5) DEFAULT NULL,
  `code_cat` varchar(5) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `lookup_icd10_chi_code_index` (`code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_icd9_sss`
--

DROP TABLE IF EXISTS `lookup_icd9_sss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_icd9_sss` (
  `code` varchar(255) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `ortime` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_icode`
--

DROP TABLE IF EXISTS `lookup_icode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_icode` (
  `icode` varchar(10) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `nhso_adp_code` varchar(100) DEFAULT NULL,
  `uc_cr` varchar(1) DEFAULT NULL,
  `ppfs` varchar(1) DEFAULT NULL,
  `herb32` varchar(1) DEFAULT NULL,
  `kidney` varchar(1) DEFAULT NULL,
  `ems` varchar(1) DEFAULT NULL,
  `sss_hc` varchar(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`icode`) USING BTREE,
  KEY `icode` (`icode`) USING BTREE,
  KEY `uc_cr` (`uc_cr`) USING BTREE,
  KEY `ppfs` (`ppfs`) USING BTREE,
  KEY `herb` (`herb32`) USING BTREE,
  KEY `kidney` (`kidney`) USING BTREE,
  KEY `ems` (`ems`) USING BTREE,
  KEY `lookup_icode_sss_hc_index` (`sss_hc`),
  KEY `lookup_icode_nhso_adp_code_index` (`nhso_adp_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_nhso_adp_code`
--

DROP TABLE IF EXISTS `lookup_nhso_adp_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_nhso_adp_code` (
  `nhso_adp_code` varchar(50) NOT NULL,
  `nhso_adp_type_id` int(11) NOT NULL,
  `nhso_adp_code_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price_ucs` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_ofc` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_sss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_lgo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_fs` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_ucep` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ins_ucs` varchar(10) DEFAULT '',
  `ins_ofc` varchar(10) DEFAULT '',
  `fs` varchar(10) DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`nhso_adp_code`,`nhso_adp_type_id`),
  KEY `lookup_nhso_adp_code_category_index` (`category`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_nhso_adp_type`
--

DROP TABLE IF EXISTS `lookup_nhso_adp_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_nhso_adp_type` (
  `nhso_adp_type_id` int(11) NOT NULL,
  `nhso_adp_type_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`nhso_adp_type_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_sss_equipdev_aipn`
--

DROP TABLE IF EXISTS `lookup_sss_equipdev_aipn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_sss_equipdev_aipn` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `billgroup` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `rate` decimal(15,2) DEFAULT NULL,
  `rate2` decimal(15,2) DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `daterev` date DEFAULT NULL,
  `dateeff` date DEFAULT NULL,
  `dateexp` date DEFAULT NULL,
  `lastupd` varchar(50) DEFAULT NULL,
  `dtcond` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_equipdev_aipn_billgroup_index` (`billgroup`),
  KEY `sss_equipdev_aipn_code_index` (`code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lookup_ward`
--

DROP TABLE IF EXISTS `lookup_ward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lookup_ward` (
  `ward` varchar(2) NOT NULL,
  `ward_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_normal` varchar(1) DEFAULT NULL,
  `ward_m` varchar(1) DEFAULT NULL,
  `ward_f` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_vip` varchar(1) DEFAULT NULL,
  `ward_lr` varchar(1) DEFAULT NULL,
  `ward_homeward` varchar(1) DEFAULT NULL,
  `bed_qty` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ward`) USING BTREE,
  KEY `ward` (`ward`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `main_setting`
--

DROP TABLE IF EXISTS `main_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_setting` (
  `name_th` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nhso_endpoint`
--

DROP TABLE IF EXISTS `nhso_endpoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nhso_endpoint` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `firstName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `mainInscl` varchar(255) DEFAULT NULL,
  `mainInsclName` varchar(255) DEFAULT NULL,
  `subInscl` varchar(255) DEFAULT NULL,
  `subInsclName` varchar(255) DEFAULT NULL,
  `serviceDateTime` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `sourceChannel` varchar(255) DEFAULT NULL,
  `claimCode` varchar(255) DEFAULT NULL,
  `claimType` varchar(255) DEFAULT NULL,
  `claim_status` varchar(20) DEFAULT NULL,
  `saved_at` datetime DEFAULT NULL,
  `nhso_response` text DEFAULT NULL,
  `statusAuthen` tinyint(4) DEFAULT NULL,
  `statusMessage` varchar(255) DEFAULT NULL,
  `sex` varchar(50) DEFAULT NULL,
  `birthDate_year` int(11) DEFAULT NULL,
  `birthDate_month` int(11) DEFAULT NULL,
  `nation_code` varchar(20) DEFAULT NULL,
  `nation_descriptionTh` varchar(255) DEFAULT NULL,
  `province_id` varchar(20) DEFAULT NULL,
  `province_name` varchar(255) DEFAULT NULL,
  `hcode` varchar(20) DEFAULT NULL,
  `hname` varchar(255) DEFAULT NULL,
  `serviceName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `claimCode` (`claimCode`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `nhso_endpoint_cid_index` (`cid`),
  KEY `nhso_endpoint_vstdate_index` (`vstdate`),
  KEY `nhso_endpoint_claimcode_index` (`claimCode`),
  KEY `nhso_endpoint_cid_vstdate_index` (`cid`,`vstdate`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=46788 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(100) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`) USING BTREE,
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_bkk`
--

DROP TABLE IF EXISTS `rep_bkk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_bkk` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_bkkexcel`
--

DROP TABLE IF EXISTS `rep_bkkexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_bkkexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_bmt`
--

DROP TABLE IF EXISTS `rep_bmt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_bmt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_bmtexcel`
--

DROP TABLE IF EXISTS `rep_bmtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_bmtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_lgo`
--

DROP TABLE IF EXISTS `rep_lgo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_lgo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_lgoexcel`
--

DROP TABLE IF EXISTS `rep_lgoexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_lgoexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_ofc`
--

DROP TABLE IF EXISTS `rep_ofc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_ofc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=4067 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_ofc_csop`
--

DROP TABLE IF EXISTS `rep_ofc_csop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_ofc_csop` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_file` varchar(150) DEFAULT NULL,
  `repline` int(11) DEFAULT NULL,
  `vn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invno` varchar(100) DEFAULT NULL,
  `dttran` datetime DEFAULT NULL,
  `dttran_date` date DEFAULT NULL,
  `dttran_time` time DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `error_codes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rep_date` date DEFAULT NULL,
  `rep_time` time DEFAULT NULL,
  `rep_no` varchar(50) DEFAULT NULL,
  `stat` varchar(10) DEFAULT NULL,
  `station` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rep_ofc_csop_vn_index` (`vn`),
  KEY `rep_ofc_csop_hn_index` (`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_ofcexcel`
--

DROP TABLE IF EXISTS `rep_ofcexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_ofcexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_pvt`
--

DROP TABLE IF EXISTS `rep_pvt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_pvt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_pvtexcel`
--

DROP TABLE IF EXISTS `rep_pvtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_pvtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_srt`
--

DROP TABLE IF EXISTS `rep_srt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_srt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_srtexcel`
--

DROP TABLE IF EXISTS `rep_srtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_srtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_sss`
--

DROP TABLE IF EXISTS `rep_sss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_sss` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_sss_aipn`
--

DROP TABLE IF EXISTS `rep_sss_aipn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_sss_aipn` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_file` varchar(100) DEFAULT NULL,
  `repno` varchar(50) DEFAULT NULL,
  `rep_date` date DEFAULT NULL,
  `rep_time` time DEFAULT NULL,
  `repline` int(11) DEFAULT NULL,
  `pcode` varchar(10) DEFAULT NULL,
  `tcode` varchar(10) DEFAULT NULL,
  `iptype` varchar(10) DEFAULT NULL,
  `care_as` varchar(10) DEFAULT NULL,
  `ss` varchar(10) DEFAULT NULL,
  `hmain` varchar(50) DEFAULT NULL,
  `hcare` varchar(50) DEFAULT NULL,
  `an` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` varchar(50) DEFAULT NULL,
  `drg` varchar(20) DEFAULT NULL,
  `rw` decimal(10,4) DEFAULT NULL,
  `adjrw` decimal(10,4) DEFAULT NULL,
  `st` varchar(20) DEFAULT NULL,
  `sst` varchar(20) DEFAULT NULL,
  `pt` varchar(20) DEFAULT NULL,
  `amt` decimal(15,2) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `error_codes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_aipn_rep_rep_file_index` (`rep_file`),
  KEY `sss_aipn_rep_an_index` (`an`),
  KEY `sss_aipn_rep_hn_index` (`hn`),
  KEY `sss_aipn_rep_pid_index` (`pid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_sss_ssop`
--

DROP TABLE IF EXISTS `rep_sss_ssop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_sss_ssop` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_file` varchar(100) NOT NULL,
  `repline` int(11) DEFAULT NULL,
  `hcode` varchar(10) DEFAULT NULL,
  `hmain` varchar(10) DEFAULT NULL,
  `vn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invno` varchar(30) DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` varchar(20) DEFAULT NULL,
  `dttran` date DEFAULT NULL,
  `dttran_date` date DEFAULT NULL,
  `dttran_time` time DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `claim_price` decimal(10,2) DEFAULT NULL,
  `error_codes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rep_date` date DEFAULT NULL,
  `rep_time` time DEFAULT NULL,
  `rep_no` varchar(50) DEFAULT NULL,
  `stat` varchar(10) DEFAULT NULL,
  `station` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_ssop_rep_vn_index` (`vn`),
  KEY `sss_ssop_rep_hn_index` (`hn`),
  KEY `sss_ssop_rep_pid_index` (`pid`),
  KEY `sss_ssop_rep_dttran_date_index` (`dttran_date`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=21970 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_sssexcel`
--

DROP TABLE IF EXISTS `rep_sssexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_sssexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_ucs`
--

DROP TABLE IF EXISTS `rep_ucs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_ucs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`),
  KEY `repno` (`repno`),
  KEY `tran_id` (`tran_id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=4120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rep_ucsexcel`
--

DROP TABLE IF EXISTS `rep_ucsexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rep_ucsexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_filename` varchar(100) DEFAULT NULL,
  `rep_type` varchar(10) DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `repno` varchar(100) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) DEFAULT NULL,
  `pt_name` varchar(150) DEFAULT NULL,
  `pt_type` varchar(20) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `net_compensate_nhso` double DEFAULT NULL,
  `net_compensate_employer` double DEFAULT NULL,
  `compensate_from` varchar(100) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `main_fund` varchar(100) DEFAULT NULL,
  `sub_fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer_type` varchar(100) DEFAULT NULL,
  `has_right` varchar(100) DEFAULT NULL,
  `use_right` varchar(100) DEFAULT NULL,
  `chk` varchar(100) DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `subinscl` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hmain` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `rg1` varchar(100) DEFAULT NULL,
  `hmain2` varchar(100) DEFAULT NULL,
  `prov2` varchar(100) DEFAULT NULL,
  `rg2` varchar(100) DEFAULT NULL,
  `dmis_hmain3` varchar(100) DEFAULT NULL,
  `da` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` double DEFAULT NULL,
  `ca_type` varchar(100) DEFAULT NULL,
  `charge_non_vehicle_drug_device` double DEFAULT NULL,
  `charge_vehicle_drug_device` double DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `charge_central_reimburse` double DEFAULT NULL,
  `self_pay` double DEFAULT NULL,
  `payrate_point` double DEFAULT NULL,
  `delay_ps` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw_nhso` double DEFAULT NULL,
  `adjrw2` double DEFAULT NULL,
  `compensate_amount` double DEFAULT NULL,
  `act_amount` double DEFAULT NULL,
  `salary_percent` varchar(100) DEFAULT NULL,
  `salary_amount` double DEFAULT NULL,
  `compensate_after_salary` double DEFAULT NULL,
  `hc_iphc` double DEFAULT NULL,
  `hc_ophc` double DEFAULT NULL,
  `ae_opae` double DEFAULT NULL,
  `ae_ipnb` double DEFAULT NULL,
  `ae_ipuc` double DEFAULT NULL,
  `ae_ip3sss` double DEFAULT NULL,
  `ae_ip7sss` double DEFAULT NULL,
  `ae_carae` double DEFAULT NULL,
  `ae_caref` double DEFAULT NULL,
  `ae_caref_puc` double DEFAULT NULL,
  `inst_opinst` double DEFAULT NULL,
  `inst_ipinst` double DEFAULT NULL,
  `ip_ipaec` double DEFAULT NULL,
  `ip_ipaer` double DEFAULT NULL,
  `ip_ipinrgc` double DEFAULT NULL,
  `ip_ipinrgr` double DEFAULT NULL,
  `ip_ipinspsn` double DEFAULT NULL,
  `ip_ipprcc` double DEFAULT NULL,
  `ip_ipprcc_puc` double DEFAULT NULL,
  `ip_ipbkk_inst` double DEFAULT NULL,
  `ip_ip_ontop` double DEFAULT NULL,
  `dmis_cataract` double DEFAULT NULL,
  `dmis_ssj_workload` double DEFAULT NULL,
  `dmis_hosp_workload` double DEFAULT NULL,
  `dmis_catinst` double DEFAULT NULL,
  `dmis_rc` double DEFAULT NULL,
  `dmis_rc_workload` double DEFAULT NULL,
  `dmis_rcuhosc` double DEFAULT NULL,
  `dmis_rcuhosc_workload` double DEFAULT NULL,
  `dmis_rcuhosr` double DEFAULT NULL,
  `dmis_rcuhosr_workload` double DEFAULT NULL,
  `dmis_llop` double DEFAULT NULL,
  `dmis_llrgc` double DEFAULT NULL,
  `dmis_llrgr` double DEFAULT NULL,
  `dmis_lp` double DEFAULT NULL,
  `dmis_stroke_stemi_drug` double DEFAULT NULL,
  `dmis_dmidml` double DEFAULT NULL,
  `dmis_pp` double DEFAULT NULL,
  `dmis_dmishd` double DEFAULT NULL,
  `dmis_dmicnt` double DEFAULT NULL,
  `dmis_palliative_care` double DEFAULT NULL,
  `dmis_dm` double DEFAULT NULL,
  `drug` double DEFAULT NULL,
  `opbkk_hc` double DEFAULT NULL,
  `opbkk_dent` double DEFAULT NULL,
  `opbkk_drug` double DEFAULT NULL,
  `opbkk_fs` double DEFAULT NULL,
  `opbkk_others` double DEFAULT NULL,
  `opbkk_hsub` double DEFAULT NULL,
  `opbkk_nhso` double DEFAULT NULL,
  `deny_hc` varchar(100) DEFAULT NULL,
  `deny_ae` varchar(100) DEFAULT NULL,
  `deny_inst` varchar(100) DEFAULT NULL,
  `deny_ip` varchar(100) DEFAULT NULL,
  `deny_dmis` varchar(100) DEFAULT NULL,
  `base_rate_old` double DEFAULT NULL,
  `base_rate_add` double DEFAULT NULL,
  `base_rate_net` double DEFAULT NULL,
  `fs` double DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `audit_results` varchar(255) DEFAULT NULL,
  `pay_pattern` varchar(100) DEFAULT NULL,
  `seq_no` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_lt` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sss_chronic`
--

DROP TABLE IF EXISTS `sss_chronic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sss_chronic` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rep_file` varchar(100) NOT NULL,
  `repline` int(11) DEFAULT NULL,
  `hcode` varchar(10) DEFAULT NULL,
  `hmain` varchar(10) DEFAULT NULL,
  `invno` varchar(30) DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` varchar(20) DEFAULT NULL,
  `dttran` date DEFAULT NULL,
  `section_type` varchar(10) NOT NULL,
  `dx` varchar(255) DEFAULT NULL,
  `drug` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_chronic_hn_index` (`hn`),
  KEY `sss_chronic_dttran_index` (`dttran`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=3429 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sss_chronic_register`
--

DROP TABLE IF EXISTS `sss_chronic_register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sss_chronic_register` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `import_file` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `chronic_code` varchar(20) DEFAULT NULL,
  `case_id` varchar(50) DEFAULT NULL,
  `cid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_date` date DEFAULT NULL,
  `confirm_round` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_chronic_register_cid_index` (`cid`),
  KEY `sss_chronic_register_code_index` (`chronic_code`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=7127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bkk`
--

DROP TABLE IF EXISTS `stm_bkk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bkk` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_vstdate_hn` (`vstdate`,`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bkk_kidney`
--

DROP TABLE IF EXISTS `stm_bkk_kidney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bkk_kidney` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `datetimeadm` (`datetimeadm`),
  KEY `idx_hn_datetimeadm` (`hn`,`datetimeadm`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bkk_kidneyexcel`
--

DROP TABLE IF EXISTS `stm_bkk_kidneyexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bkk_kidneyexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` varchar(100) DEFAULT NULL,
  `receive_total` varchar(100) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bkkexcel`
--

DROP TABLE IF EXISTS `stm_bkkexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bkkexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bmt`
--

DROP TABLE IF EXISTS `stm_bmt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bmt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_vstdate_hn` (`vstdate`,`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bmt_kidney`
--

DROP TABLE IF EXISTS `stm_bmt_kidney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bmt_kidney` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `datetimeadm` (`datetimeadm`),
  KEY `idx_hn_datetimeadm` (`hn`,`datetimeadm`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bmt_kidneyexcel`
--

DROP TABLE IF EXISTS `stm_bmt_kidneyexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bmt_kidneyexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` varchar(100) DEFAULT NULL,
  `receive_total` varchar(100) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_bmtexcel`
--

DROP TABLE IF EXISTS `stm_bmtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_bmtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_lgo`
--

DROP TABLE IF EXISTS `stm_lgo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_lgo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `dep` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `compensate_treatment` double(15,2) DEFAULT NULL,
  `compensate_nhso` double(15,2) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer` varchar(100) DEFAULT NULL,
  `have_rights` varchar(100) DEFAULT NULL,
  `use_rights` varchar(100) DEFAULT NULL,
  `main_rights` varchar(100) DEFAULT NULL,
  `secondary_rights` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `hospcode` varchar(100) DEFAULT NULL,
  `hospname` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` varchar(100) DEFAULT NULL,
  `charge_treatment` double(15,2) DEFAULT NULL,
  `charge_pp` double(15,2) DEFAULT NULL,
  `withdraw` varchar(100) DEFAULT NULL,
  `non_withdraw` varchar(100) DEFAULT NULL,
  `pay` varchar(100) DEFAULT NULL,
  `payrate` double(100,0) DEFAULT NULL,
  `delay` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `act` double(15,2) DEFAULT NULL,
  `case_iplg` double(15,2) DEFAULT NULL,
  `case_oplg` double(15,2) DEFAULT NULL,
  `case_palg` double(15,2) DEFAULT NULL,
  `case_inslg` double(15,2) DEFAULT NULL,
  `case_otlg` double(15,2) DEFAULT NULL,
  `case_pp` double(15,2) DEFAULT NULL,
  `case_drug` double(15,2) DEFAULT NULL,
  `deny_iplg` varchar(100) DEFAULT NULL,
  `deny_oplg` varchar(100) DEFAULT NULL,
  `deny_palg` varchar(100) DEFAULT NULL,
  `deny_inslg` varchar(100) DEFAULT NULL,
  `deny_otlg` varchar(100) DEFAULT NULL,
  `ors` varchar(100) DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `audit_results` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1044 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_lgo_kidney`
--

DROP TABLE IF EXISTS `stm_lgo_kidney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_lgo_kidney` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `dep` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `compensate_kidney` double(15,2) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `datetimeadm` (`datetimeadm`) USING BTREE,
  KEY `idx_cid_datetimeadm` (`cid`,`datetimeadm`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1853 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_lgo_kidneyexcel`
--

DROP TABLE IF EXISTS `stm_lgo_kidneyexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_lgo_kidneyexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `dep` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `compensate_kidney` varchar(100) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_lgoexcel`
--

DROP TABLE IF EXISTS `stm_lgoexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_lgoexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `dep` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `compensate_treatment` double(15,2) DEFAULT NULL,
  `compensate_nhso` double(15,2) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `fund` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `refer` varchar(100) DEFAULT NULL,
  `have_rights` varchar(100) DEFAULT NULL,
  `use_rights` varchar(100) DEFAULT NULL,
  `main_rights` varchar(100) DEFAULT NULL,
  `secondary_rights` varchar(100) DEFAULT NULL,
  `href` varchar(100) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `prov1` varchar(100) DEFAULT NULL,
  `hospcode` varchar(100) DEFAULT NULL,
  `hospname` varchar(100) DEFAULT NULL,
  `proj` varchar(100) DEFAULT NULL,
  `pa` varchar(100) DEFAULT NULL,
  `drg` varchar(100) DEFAULT NULL,
  `rw` varchar(100) DEFAULT NULL,
  `charge_treatment` double(15,2) DEFAULT NULL,
  `charge_pp` double(15,2) DEFAULT NULL,
  `withdraw` varchar(100) DEFAULT NULL,
  `non_withdraw` varchar(100) DEFAULT NULL,
  `pay` varchar(100) DEFAULT NULL,
  `payrate` double(100,0) DEFAULT NULL,
  `delay` varchar(100) DEFAULT NULL,
  `delay_percent` varchar(100) DEFAULT NULL,
  `ccuf` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `act` double(15,2) DEFAULT NULL,
  `case_iplg` double(15,2) DEFAULT NULL,
  `case_oplg` double(15,2) DEFAULT NULL,
  `case_palg` double(15,2) DEFAULT NULL,
  `case_inslg` double(15,2) DEFAULT NULL,
  `case_otlg` double(15,2) DEFAULT NULL,
  `case_pp` double(15,2) DEFAULT NULL,
  `case_drug` double(15,2) DEFAULT NULL,
  `deny_iplg` varchar(100) DEFAULT NULL,
  `deny_oplg` varchar(100) DEFAULT NULL,
  `deny_palg` varchar(100) DEFAULT NULL,
  `deny_inslg` varchar(100) DEFAULT NULL,
  `deny_otlg` varchar(100) DEFAULT NULL,
  `ors` varchar(100) DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `audit_results` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ofc`
--

DROP TABLE IF EXISTS `stm_ofc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ofc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `act` double(15,2) DEFAULT NULL,
  `receive_room` double(15,2) DEFAULT NULL,
  `receive_instument` double(15,2) DEFAULT NULL,
  `receive_drug` double(15,2) DEFAULT NULL,
  `receive_treatment` double(15,2) DEFAULT NULL,
  `receive_car` double(15,2) DEFAULT NULL,
  `receive_waitdch` double(15,2) DEFAULT NULL,
  `receive_other` double(15,2) DEFAULT NULL,
  `receive_total` double(15,2) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE,
  KEY `idx_vstdate_hn` (`vstdate`,`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=43907 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ofc_cipn`
--

DROP TABLE IF EXISTS `stm_ofc_cipn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ofc_cipn` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `round_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `an` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `namepat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datedsc` date DEFAULT NULL,
  `ptype` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drg` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjrw` double(6,5) DEFAULT NULL,
  `amreimb` double(15,2) DEFAULT NULL,
  `amlim` double(15,2) DEFAULT NULL,
  `pamreim` double(15,2) DEFAULT NULL,
  `gtotal` double(15,2) DEFAULT NULL,
  `receive_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_file_an` (`stm_filename`,`an`) USING BTREE,
  KEY `idx_an` (`an`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ofc_csop`
--

DROP TABLE IF EXISTS `stm_ofc_csop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ofc_csop` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `round_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stm_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acc_period` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `station` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hreg` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `invno` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double(15,2) DEFAULT NULL,
  `paid` double(15,2) DEFAULT NULL,
  `extp_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extp_amount` double(15,2) DEFAULT NULL,
  `rid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cstat` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hdflag` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receive_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_csop` (`invno`) USING BTREE,
  KEY `csop_hn` (`hn`) USING BTREE,
  KEY `csop_vstdate` (`vstdate`) USING BTREE,
  KEY `csop_vsttime` (`vsttime`) USING BTREE,
  KEY `idx_vstdate_hn` (`vstdate`,`hn`),
  KEY `idx_hn_vstdate` (`hn`,`vstdate`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=4647 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ofcexcel`
--

DROP TABLE IF EXISTS `stm_ofcexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ofcexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `act` double(15,2) DEFAULT NULL,
  `receive_room` double(15,2) DEFAULT NULL,
  `receive_instument` double(15,2) DEFAULT NULL,
  `receive_drug` double(15,2) DEFAULT NULL,
  `receive_treatment` double(15,2) DEFAULT NULL,
  `receive_car` double(15,2) DEFAULT NULL,
  `receive_waitdch` double(15,2) DEFAULT NULL,
  `receive_other` double(15,2) DEFAULT NULL,
  `receive_total` double(15,2) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_pvt`
--

DROP TABLE IF EXISTS `stm_pvt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_pvt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_vstdate_hn` (`vstdate`,`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_pvtexcel`
--

DROP TABLE IF EXISTS `stm_pvtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_pvtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_seamless_dmis`
--

DROP TABLE IF EXISTS `stm_seamless_dmis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_seamless_dmis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospcode` varchar(5) DEFAULT NULL,
  `pttype_name` varchar(150) DEFAULT NULL,
  `repno` varchar(50) DEFAULT NULL,
  `trans_id` varchar(100) NOT NULL,
  `hn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptname` varchar(250) DEFAULT NULL,
  `send_date` date DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `claim_type_name` varchar(255) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `price_unit` double DEFAULT NULL,
  `price_ceiling` double DEFAULT NULL,
  `claim_price` double DEFAULT NULL,
  `ps_code` varchar(50) DEFAULT NULL,
  `pay_percent` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `deny_code` varchar(50) DEFAULT NULL,
  `deny_warning` text DEFAULT NULL,
  `dmis_group` varchar(50) DEFAULT NULL,
  `excel_filename` varchar(255) DEFAULT NULL,
  `round_no` varchar(50) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rehab_code` varchar(50) DEFAULT NULL,
  `rehab_name` varchar(255) DEFAULT NULL,
  `sub_hospcode` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trans_id` (`trans_id`),
  KEY `hn` (`hn`),
  KEY `an` (`an`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `repno` (`repno`),
  KEY `dmis_group` (`dmis_group`),
  KEY `round_no` (`round_no`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=16766 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_srt`
--

DROP TABLE IF EXISTS `stm_srt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_srt` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`),
  KEY `idx_vstdate_hn` (`vstdate`,`hn`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_srtexcel`
--

DROP TABLE IF EXISTS `stm_srtexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_srtexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `adjrw` varchar(100) DEFAULT NULL,
  `charge` double DEFAULT NULL,
  `act` double DEFAULT NULL,
  `receive_room` double DEFAULT NULL,
  `receive_instument` double DEFAULT NULL,
  `receive_drug` double DEFAULT NULL,
  `receive_treatment` double DEFAULT NULL,
  `receive_car` double DEFAULT NULL,
  `receive_waitdch` double DEFAULT NULL,
  `receive_other` double DEFAULT NULL,
  `receive_total` double DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `an` (`an`),
  KEY `hn` (`hn`),
  KEY `cid` (`cid`),
  KEY `vstdate` (`vstdate`),
  KEY `vsttime` (`vsttime`),
  KEY `dchdate` (`dchdate`),
  KEY `dchtime` (`dchtime`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_sss_aipn`
--

DROP TABLE IF EXISTS `stm_sss_aipn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_sss_aipn` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stm_file` varchar(100) DEFAULT NULL,
  `round_no` varchar(50) DEFAULT NULL,
  `hn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `dateadm` date DEFAULT NULL,
  `datedsc` date DEFAULT NULL,
  `ft` varchar(10) DEFAULT NULL,
  `bf` varchar(10) DEFAULT NULL,
  `drg` varchar(20) DEFAULT NULL,
  `rw` decimal(10,4) DEFAULT NULL,
  `adjrw` decimal(10,4) DEFAULT NULL,
  `hmain` varchar(50) DEFAULT NULL,
  `hcode` varchar(50) DEFAULT NULL,
  `hproc` varchar(50) DEFAULT NULL,
  `careas` varchar(10) DEFAULT NULL,
  `sc` varchar(10) DEFAULT NULL,
  `ed` varchar(10) DEFAULT NULL,
  `due` decimal(15,2) DEFAULT NULL,
  `reimb` decimal(15,2) DEFAULT NULL,
  `receive_total` decimal(15,2) DEFAULT NULL,
  `nreimb` decimal(15,2) DEFAULT NULL,
  `copay` decimal(15,2) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `pp` varchar(10) DEFAULT NULL,
  `rid` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_aipn_stm_stm_file_index` (`stm_file`),
  KEY `sss_aipn_stm_an_index` (`an`),
  KEY `sss_aipn_stm_hn_index` (`hn`),
  KEY `sss_aipn_stm_pid_index` (`pid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=254 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_sss_kidney`
--

DROP TABLE IF EXISTS `stm_sss_kidney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_sss_kidney` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stm_filename` varchar(100) DEFAULT NULL,
  `round_no` varchar(30) DEFAULT NULL,
  `hcode` varchar(100) DEFAULT NULL,
  `hname` varchar(100) DEFAULT NULL,
  `stmdoc` varchar(100) DEFAULT NULL,
  `station` varchar(100) DEFAULT NULL,
  `hreg` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invno` varchar(100) DEFAULT NULL,
  `dttran` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `amount` double(15,2) DEFAULT NULL,
  `epopay` double(15,2) DEFAULT NULL,
  `epoadm` double(15,2) DEFAULT NULL,
  `paid` varchar(100) DEFAULT NULL,
  `rid` varchar(100) DEFAULT NULL,
  `hdflag` varchar(255) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `idx_hn_vstdate` (`hn`,`vstdate`),
  KEY `idx_cid_vstdate` (`cid`,`vstdate`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1041 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_sss_ssop`
--

DROP TABLE IF EXISTS `stm_sss_ssop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_sss_ssop` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stm_file` varchar(100) NOT NULL,
  `vn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` varchar(20) DEFAULT NULL,
  `invno` varchar(30) DEFAULT NULL,
  `dttran` datetime DEFAULT NULL,
  `dttran_date` date DEFAULT NULL,
  `dttran_time` time DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `rid` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sss_ssop_stm_hn_index` (`hn`),
  KEY `sss_ssop_stm_pid_index` (`pid`),
  KEY `sss_ssop_stm_invno_index` (`invno`),
  KEY `sss_ssop_stm_vn_index` (`vn`),
  KEY `sss_ssop_stm_dttran_date_index` (`dttran_date`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2731 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ucs`
--

DROP TABLE IF EXISTS `stm_ucs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ucs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `fund_ip_act` double(15,2) DEFAULT NULL,
  `fund_ip_adjrw` varchar(100) DEFAULT NULL,
  `fund_ip_ps` varchar(100) DEFAULT NULL,
  `fund_ip_ps2` varchar(100) DEFAULT NULL,
  `fund_ip_ccuf` varchar(100) DEFAULT NULL,
  `fund_ip_adjrw2` varchar(100) DEFAULT NULL,
  `fund_ip_payrate` double(15,2) DEFAULT NULL,
  `fund_ip_salary` double(15,2) DEFAULT NULL,
  `fund_compensate_salary` double(15,2) DEFAULT NULL,
  `receive_op` double(15,2) DEFAULT NULL,
  `receive_ip_compensate_cal` double(15,2) DEFAULT NULL,
  `receive_ip_compensate_pay` double(15,2) DEFAULT NULL,
  `receive_hc_hc` double(15,2) DEFAULT NULL,
  `receive_hc_drug` double(15,2) DEFAULT NULL,
  `receive_ae_ae` double(15,2) DEFAULT NULL,
  `receive_ae_drug` double(15,2) DEFAULT NULL,
  `receive_inst` double(15,2) DEFAULT NULL,
  `receive_dmis_compensate_cal` double(15,2) DEFAULT NULL,
  `receive_dmis_compensate_pay` double(15,2) DEFAULT NULL,
  `receive_dmis_drug` double(15,2) DEFAULT NULL,
  `receive_palliative` double(15,2) DEFAULT NULL,
  `receive_dmishd` double(15,2) DEFAULT NULL,
  `receive_pp` double(15,2) DEFAULT NULL,
  `receive_fs` double(15,2) DEFAULT NULL,
  `receive_opbkk` double(15,2) DEFAULT NULL,
  `receive_total` double(15,2) DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `covid` varchar(100) DEFAULT NULL,
  `resources` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE,
  KEY `idx_cid_vstdate` (`cid`,`vstdate`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=72782 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ucs_kidney`
--

DROP TABLE IF EXISTS `stm_ucs_kidney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ucs_kidney` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` double(15,2) DEFAULT NULL,
  `receive_total` double(15,2) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `receive_no` varchar(20) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `receipt_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `datetimeadm` (`datetimeadm`) USING BTREE,
  KEY `idx_cid_datetimeadm` (`cid`,`datetimeadm`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=52729 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ucs_kidneyexcel`
--

DROP TABLE IF EXISTS `stm_ucs_kidneyexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ucs_kidneyexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` date DEFAULT NULL,
  `hd_type` varchar(100) DEFAULT NULL,
  `charge_total` varchar(100) DEFAULT NULL,
  `receive_total` varchar(100) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stm_ucsexcel`
--

DROP TABLE IF EXISTS `stm_ucsexcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stm_ucsexcel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `round_no` varchar(30) DEFAULT NULL,
  `repno` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `tran_id` varchar(100) DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pt_name` varchar(100) DEFAULT NULL,
  `datetimeadm` datetime DEFAULT NULL,
  `vstdate` date DEFAULT NULL,
  `vsttime` time DEFAULT NULL,
  `datetimedch` datetime DEFAULT NULL,
  `dchdate` date DEFAULT NULL,
  `dchtime` time DEFAULT NULL,
  `maininscl` varchar(100) DEFAULT NULL,
  `projcode` varchar(100) DEFAULT NULL,
  `charge` double(15,2) DEFAULT NULL,
  `fund_ip_act` double(15,2) DEFAULT NULL,
  `fund_ip_adjrw` varchar(100) DEFAULT NULL,
  `fund_ip_ps` varchar(100) DEFAULT NULL,
  `fund_ip_ps2` varchar(100) DEFAULT NULL,
  `fund_ip_ccuf` varchar(100) DEFAULT NULL,
  `fund_ip_adjrw2` varchar(100) DEFAULT NULL,
  `fund_ip_payrate` double(15,2) DEFAULT NULL,
  `fund_ip_salary` double(15,2) DEFAULT NULL,
  `fund_compensate_salary` double(15,2) DEFAULT NULL,
  `receive_op` double(15,2) DEFAULT NULL,
  `receive_ip_compensate_cal` double(15,2) DEFAULT NULL,
  `receive_ip_compensate_pay` double(15,2) DEFAULT NULL,
  `receive_hc_hc` double(15,2) DEFAULT NULL,
  `receive_hc_drug` double(15,2) DEFAULT NULL,
  `receive_ae_ae` double(15,2) DEFAULT NULL,
  `receive_ae_drug` double(15,2) DEFAULT NULL,
  `receive_inst` double(15,2) DEFAULT NULL,
  `receive_dmis_compensate_cal` double(15,2) DEFAULT NULL,
  `receive_dmis_compensate_pay` double(15,2) DEFAULT NULL,
  `receive_dmis_drug` double(15,2) DEFAULT NULL,
  `receive_palliative` double(15,2) DEFAULT NULL,
  `receive_dmishd` double(15,2) DEFAULT NULL,
  `receive_pp` double(15,2) DEFAULT NULL,
  `receive_fs` double(15,2) DEFAULT NULL,
  `receive_opbkk` double(15,2) DEFAULT NULL,
  `receive_total` double(15,2) DEFAULT NULL,
  `va` varchar(100) DEFAULT NULL,
  `covid` varchar(100) DEFAULT NULL,
  `resources` varchar(100) DEFAULT NULL,
  `stm_filename` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `an` (`an`) USING BTREE,
  KEY `hn` (`hn`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `vstdate` (`vstdate`) USING BTREE,
  KEY `vsttime` (`vsttime`) USING BTREE,
  KEY `dchdate` (`dchdate`) USING BTREE,
  KEY `dchtime` (`dchtime`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subinscl`
--

DROP TABLE IF EXISTS `subinscl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subinscl` (
  `code` varchar(2) NOT NULL DEFAULT '',
  `name` varchar(200) DEFAULT NULL,
  `maininscl` varchar(10) DEFAULT '',
  `note` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` varchar(1) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `cid` varchar(13) DEFAULT NULL,
  `allow_nhso_endpoint` varchar(1) NOT NULL DEFAULT 'N',
  `allow_debtor_acc` varchar(1) NOT NULL DEFAULT 'N',
  `allow_receipt` varchar(1) NOT NULL DEFAULT 'N',
  `allow_debtor_lock` varchar(1) NOT NULL DEFAULT 'N',
  `allow_debtor` varchar(1) NOT NULL DEFAULT 'N',
  `allow_mishos` varchar(1) NOT NULL DEFAULT 'N',
  `allow_claim_ip` varchar(1) NOT NULL DEFAULT 'N',
  `allow_claim_op` varchar(1) NOT NULL DEFAULT 'N',
  `allow_emr` varchar(1) NOT NULL DEFAULT 'N',
  `allow_check` varchar(1) NOT NULL DEFAULT 'N',
  `allow_import` varchar(1) NOT NULL DEFAULT 'N',
  `allow_home` varchar(1) NOT NULL DEFAULT 'N',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `allow_aopod_death` varchar(1) NOT NULL DEFAULT 'N',
  `allow_check_right` varchar(1) NOT NULL DEFAULT 'N',
  `allow_hosfin` varchar(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-08-21 15:48:25


--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2014_10_12_100000_create_password_resets_table', 1),
(4, '2019_08_19_000000_create_failed_jobs_table', 1),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(6, '2026_02_19_000000_baseline_existing_tables', 1),
(7, '2026_02_19_000001_legacy_hrims_update', 1),
(8, '2026_02_19_000002_refactor_main_setting_primary_key', 1),
(9, '2026_03_03_203228_create_eclaim_status_table', 2),
(10, '2026_03_12_203849_create_debtor_acc_ledger_table', 3),
(11, '2026_06_13_000000_drop_stm_ofc_kidney_table', 4),
(12, '2026_06_24_000000_add_timestamps_to_debtor_tables', 5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
