/*
 Navicat Premium Data Transfer

 Source Server         : 127.0.0.1
 Source Server Type    : MariaDB
 Source Server Version : 100017
 Source Host           : 127.0.0.1:3306
 Source Schema         : hrims

 Target Server Type    : MariaDB
 Target Server Version : 100017
 File Encoding         : 65001

 Date: 09/09/2025 12:47:20
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for budget_year
-- ----------------------------
DROP TABLE IF EXISTS `budget_year`;
CREATE TABLE `budget_year`  (
  `LEAVE_YEAR_ID` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `LEAVE_YEAR_NAME` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `DATE_BEGIN` date NULL DEFAULT NULL,
  `DATE_END` date NULL DEFAULT NULL,
  `ACTIVE` enum('True','False') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'False',
  `DAY_PER_YEAR` int(11) NULL DEFAULT 10,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`LEAVE_YEAR_ID`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of budget_year
-- ----------------------------
INSERT INTO `budget_year` VALUES ('2560', 'ปีงบประมาณ 2560', '2016-10-01', '2017-09-30', 'False', 10, '2022-01-07 04:02:42', '2020-08-19 11:45:13');
INSERT INTO `budget_year` VALUES ('2561', 'ปีงบประมาณ 2561', '2017-10-01', '2018-09-30', 'True', 10, '2022-10-03 06:09:43', '2020-08-19 11:45:13');
INSERT INTO `budget_year` VALUES ('2562', 'ปีงบประมาณ 2562', '2018-10-01', '2019-09-30', 'True', 10, '2022-10-03 06:09:43', '2020-08-19 11:45:13');
INSERT INTO `budget_year` VALUES ('2563', 'ปีงบประมาณ 2563', '2019-10-01', '2020-09-30', 'True', 10, '2022-10-03 06:09:42', '2020-08-19 11:45:13');
INSERT INTO `budget_year` VALUES ('2564', 'ปีงบประมาณ 2564', '2020-10-01', '2021-09-30', 'True', 10, '2022-05-26 08:37:48', '2020-09-02 09:09:46');
INSERT INTO `budget_year` VALUES ('2565', 'ปีงบประมาณ 2565', '2021-10-01', '2022-09-30', 'True', 10, '2021-01-13 04:53:34', '2020-10-19 13:05:21');
INSERT INTO `budget_year` VALUES ('2566', 'ปีงบประมาณ 2566', '2022-10-01', '2023-09-30', 'True', 10, '2022-09-19 02:29:24', '2022-08-05 08:54:11');
INSERT INTO `budget_year` VALUES ('2567', 'ปีงบประมาณ 2567', '2023-10-01', '2024-09-30', 'True', 10, '2023-09-25 07:36:47', '2023-09-25 07:36:33');
INSERT INTO `budget_year` VALUES ('2568', 'ปีงบประมาณ 2568', '2024-10-01', '2025-09-30', 'True', 10, '2024-09-11 15:22:35', '2024-09-11 15:22:32');

-- ----------------------------
-- Table structure for debtor_1102050101_103
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_103`;
CREATE TABLE `debtor_1102050101_103`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_109
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_109`;
CREATE TABLE `debtor_1102050101_109`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_201
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_201`;
CREATE TABLE `debtor_1102050101_201`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_202
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_202`;
CREATE TABLE `debtor_1102050101_202`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_203
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_203`;
CREATE TABLE `debtor_1102050101_203`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_209
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_209`;
CREATE TABLE `debtor_1102050101_209`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `pp` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_216
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_216`;
CREATE TABLE `debtor_1102050101_216`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `cr` double(15, 2) NULL DEFAULT NULL,
  `anywhere` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_217
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_217`;
CREATE TABLE `debtor_1102050101_217`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `cr` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_301
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_301`;
CREATE TABLE `debtor_1102050101_301`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_302
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_302`;
CREATE TABLE `debtor_1102050101_302`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_303
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_303`;
CREATE TABLE `debtor_1102050101_303`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_304
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_304`;
CREATE TABLE `debtor_1102050101_304`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `income_pttype` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_307
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_307`;
CREATE TABLE `debtor_1102050101_307`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_308
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_308`;
CREATE TABLE `debtor_1102050101_308`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `income_pttype` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_309
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_309`;
CREATE TABLE `debtor_1102050101_309`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_310
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_310`;
CREATE TABLE `debtor_1102050101_310`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_401
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_401`;
CREATE TABLE `debtor_1102050101_401`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `ofc` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_402
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_402`;
CREATE TABLE `debtor_1102050101_402`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_501
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_501`;
CREATE TABLE `debtor_1102050101_501`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_502
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_502`;
CREATE TABLE `debtor_1102050101_502`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_503
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_503`;
CREATE TABLE `debtor_1102050101_503`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_504
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_504`;
CREATE TABLE `debtor_1102050101_504`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_701
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_701`;
CREATE TABLE `debtor_1102050101_701`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_702
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_702`;
CREATE TABLE `debtor_1102050101_702`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_703
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_703`;
CREATE TABLE `debtor_1102050101_703`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050101_704
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050101_704`;
CREATE TABLE `debtor_1102050101_704`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_106
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_106`;
CREATE TABLE `debtor_1102050102_106`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mobile_phone_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `paid_money` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_106_tracking
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_106_tracking`;
CREATE TABLE `debtor_1102050102_106_tracking`  (
  `tracking_id` int(10) NOT NULL AUTO_INCREMENT,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_date` date NULL DEFAULT NULL,
  `tracking_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_officer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`tracking_id`) USING BTREE,
  INDEX `vn`(`vn`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_107
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_107`;
CREATE TABLE `debtor_1102050102_107`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mobile_phone_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `paid_money` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_107_tracking
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_107_tracking`;
CREATE TABLE `debtor_1102050102_107_tracking`  (
  `tracking_id` int(10) NOT NULL AUTO_INCREMENT,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_date` date NULL DEFAULT NULL,
  `tracking_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_officer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tracking_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`tracking_id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_108
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_108`;
CREATE TABLE `debtor_1102050102_108`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_109
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_109`;
CREATE TABLE `debtor_1102050102_109`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_602
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_602`;
CREATE TABLE `debtor_1102050102_602`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_603
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_603`;
CREATE TABLE `debtor_1102050102_603`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_801
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_801`;
CREATE TABLE `debtor_1102050102_801`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `lgo` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_802
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_802`;
CREATE TABLE `debtor_1102050102_802`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_803
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_803`;
CREATE TABLE `debtor_1102050102_803`  (
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `ofc` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `ppfs` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `charge_date` date NULL DEFAULT NULL,
  `charge_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `receive_date` date NULL DEFAULT NULL,
  `receive_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(15, 2) NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`vn`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for debtor_1102050102_804
-- ----------------------------
DROP TABLE IF EXISTS `debtor_1102050102_804`;
CREATE TABLE `debtor_1102050102_804`  (
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `regdate` date NULL DEFAULT NULL,
  `regtime` time(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `pttype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospmain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hipdata_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pdx` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `income` double(15, 2) NULL DEFAULT NULL,
  `rcpt_money` double(15, 2) NULL DEFAULT NULL,
  `kidney` double(15, 2) NULL DEFAULT NULL,
  `other` double(15, 2) NULL DEFAULT NULL,
  `debtor` double(15, 2) NULL DEFAULT NULL,
  `debtor_change` double(15, 2) NULL DEFAULT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive` double(100, 2) NULL DEFAULT NULL,
  `repno` varbinary(15) NULL DEFAULT NULL,
  `debtor_lock` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`an`) USING BTREE,
  INDEX `an`(`an`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for drugcat_aipn
-- ----------------------------
DROP TABLE IF EXISTS `drugcat_aipn`;
CREATE TABLE `drugcat_aipn`  (
  `id` int(11) NULL DEFAULT NULL,
  `Hospdcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Prodcat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Tmtid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Specprep` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Genname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Tradename` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Dsfcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Dosefm` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Strength` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `UnitPrice` double(15, 2) NULL DEFAULT NULL,
  `Distrb` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Manuf` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Ised` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Ndc24` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Packsize` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Packprice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `Updateflag` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `DateChange` datetime(0) NULL DEFAULT NULL,
  `DateUpdate` datetime(0) NULL DEFAULT NULL,
  `DateEffect` datetime(0) NULL DEFAULT NULL,
  `DateChk` datetime(0) NULL DEFAULT NULL,
  `Rp` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `stm_filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for drugcat_nhso
-- ----------------------------
DROP TABLE IF EXISTS `drugcat_nhso`;
CREATE TABLE `drugcat_nhso`  (
  `hospdrugcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `productcat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `tmtid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `specprep` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `genericname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `tradename` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `dfscode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `dosageform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `strength` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `unitprice` double(15, 2) NULL DEFAULT NULL,
  `distributor` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `manufacturer` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ised` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ndc24` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `packsize` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `packprice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updateflag` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `datechange` date NULL DEFAULT NULL,
  `dateupdate` date NULL DEFAULT NULL,
  `dateeffective` date NULL DEFAULT NULL,
  `ised_approved` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ndc24_approved` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `date_approved` date NULL DEFAULT NULL,
  `ised_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `stm_filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  INDEX `hospdrugcode`(`hospdrugcode`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for lookup_hospcode
-- ----------------------------
DROP TABLE IF EXISTS `lookup_hospcode`;
CREATE TABLE `lookup_hospcode`  (
  `hospcode` varchar(9) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `hospcode_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `hmain_ucs` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hmain_sss` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `in_province` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`hospcode`) USING BTREE,
  INDEX `hospcode`(`hospcode`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of lookup_hospcode
-- ----------------------------
INSERT INTO `lookup_hospcode` VALUES ('10703', 'โรงพยาบาลอำนาจเจริญ', NULL, 'Y', 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10985', 'โรงพยาบาลชานุมาน', NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10986', 'โรงพยาบาลปทุมราชวงศา', NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10987', 'โรงพยาบาลพนา', NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10988', 'โรงพยาบาลเสนางคนิคม', NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10989', 'โรงพยาบาลหัวตะพาน', 'Y', NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_hospcode` VALUES ('10990', 'โรงพยาบาลลืออำนาจ', NULL, NULL, 'Y', NULL, NULL);

-- ----------------------------
-- Table structure for lookup_icd10
-- ----------------------------
DROP TABLE IF EXISTS `lookup_icd10`;
CREATE TABLE `lookup_icd10`  (
  `icd10` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `pp` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ods` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ods_p` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `kidney` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hiv` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tb` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`icd10`) USING BTREE,
  INDEX `icd10`(`icd10`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of lookup_icd10
-- ----------------------------
INSERT INTO `lookup_icd10` VALUES ('1131', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('1132', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('1139', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4233', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4281', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4292', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4341', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4422', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4443', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4542', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4543', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4911', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4912', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4944', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4945', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4946', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4949', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4951', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4952', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('4959', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5185', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5186', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5187', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5188', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5293', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5294', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5297', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5298', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('53', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5301', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5302', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5303', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5304', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5305', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5306', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5307', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5308', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5309', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('531', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5311', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5312', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5313', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5314', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5315', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5316', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5317', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5321', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5329', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5331', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('5339', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('58', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('581', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('585', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('612', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6629', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6631', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6639', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6816', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6821', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6822', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6823', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('6829', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('781', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('85', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('911', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('9802', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('9803', NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('A15', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A150', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A151', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A152', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A153', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A154', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A155', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A156', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A157', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A158', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A159', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A16', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A160', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A161', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A162', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A163', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A164', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A165', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A167', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A168', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('A169', NULL, NULL, NULL, NULL, NULL, 'Y');
INSERT INTO `lookup_icd10` VALUES ('B20', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B200', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B201', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B202', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B203', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B204', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B205', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B206', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B207', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B208', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B209', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B21', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B210', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B211', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B212', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B213', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B217', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B218', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B219', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B22', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B220', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B221', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B222', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B227', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B23', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B230', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B231', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B232', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B233', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B238', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('B24', NULL, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO `lookup_icd10` VALUES ('C15', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('C16', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('C221', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('C23', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('C24', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('C25', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('D126', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('H110', '', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K600', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K601', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K602', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K603', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K610', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K611', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K612', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K613', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K614', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K620', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K621', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K635', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K800', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K801', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K802', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K803', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K804', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K805', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K820', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K828', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K831', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K838', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K860', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K861', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K868', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('K918', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('l850', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('l859', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('l864', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('l982', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('l983', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N185', NULL, NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N189', NULL, NULL, NULL, 'Y', NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N211', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N350', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N351', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N358', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N359', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('N61', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S421', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S422', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S423', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S424', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S427', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S428', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S429', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S520', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S521', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S522', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S523', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S524', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S525', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S526', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S527', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S528', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S529', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S620', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S621', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S624', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S627', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S820', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S821', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S822', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S823', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S824', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S825', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S826', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S827', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S828', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S829', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S920', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S921', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S922', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('S927', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('T181', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('T182', NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z00', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z000', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z001', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z002', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z003', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z004', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z005', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z006', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z008', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z01', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z010', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z011', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z013', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z014', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z015', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z016', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z017', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z018', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z019', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z02', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z020', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z021', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z022', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z023', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z024', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z025', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z026', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z027', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z028', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z029', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z03', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z030', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z031', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z032', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z033', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z034', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z035', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z036', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z038', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z039', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z10', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z100', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z101', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z102', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z103', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z108', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z11', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z110', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z111', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z112', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z113', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z114', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z115', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z116', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z118', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z119', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z12', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z120', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z121', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z122', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z123', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z124', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z125', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z126', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z128', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z129', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z13', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z130', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z131', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z132', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z133', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z134', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z135', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z136', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z137', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z138', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z139', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z20', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z200', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z201', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z202', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z204', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z205', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z206', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z207', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z208', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z209', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z23', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z230', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z231', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z232', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z233', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z234', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z235', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z236', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z237', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z238', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z24', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z240', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z241', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z243', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z244', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z245', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z246', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z25', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z250', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z251', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z258', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z26', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z260', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z268', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z269', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z27', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z270', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z271', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z272', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z273', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z274', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z278', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z279', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z28', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z280', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z281', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z282', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z288', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z289', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z29', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z291', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z292', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z298', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z299', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z30', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z300', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z301', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z302', 'Y', 'Y', NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z303', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z304', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z305', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z308', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z309', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z32', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z320', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z321', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z34', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z340', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z348', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z349', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z35', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z350', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z351', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z352', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z353', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z354', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z355', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z356', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z357', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z358', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z359', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z36', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z360', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z361', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z362', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z363', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z364', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z365', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z368', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z369', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z39', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z390', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z391', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z392', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z55', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z550', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z551', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z552', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z553', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z554', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z558', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z559', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z56', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z560', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z561', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z562', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z563', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z564', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z565', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z566', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z567', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z57', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z570', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z571', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z572', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z573', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z574', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z575', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z576', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z577', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z578', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z579', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z58', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z580', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z581', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z582', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z583', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z584', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z585', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z586', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z587', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z588', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z589', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z59', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z590', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z591', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z592', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z593', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z594', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z595', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z596', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z597', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z598', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z599', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z60', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z600', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z601', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z602', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z603', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z604', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z605', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z608', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z609', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z61', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z610', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z611', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z612', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z613', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z614', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z615', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z616', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z617', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z618', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z619', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z62', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z620', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z621', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z622', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z623', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z624', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z625', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z626', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z628', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z629', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z63', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z630', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z631', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z632', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z633', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z634', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z635', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z636', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z637', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z638', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z639', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z64', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z640', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z641', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z642', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z643', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z644', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z65', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z650', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z651', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z652', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z653', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z654', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z655', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z658', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z659', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z70', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z700', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z701', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z702', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z703', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z708', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z709', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z71', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z710', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z711', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z712', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z713', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z714', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z715', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z716', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z717', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z718', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z719', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z72', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z720', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z721', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z722', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z723', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z724', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z725', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z726', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z728', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z729', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z73', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z730', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z731', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z732', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z733', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z734', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z735', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z736', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z738', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z739', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z75', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z750', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z751', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z752', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z753', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z754', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z755', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z758', 'Y', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_icd10` VALUES ('Z759', 'Y', NULL, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for lookup_icode
-- ----------------------------
DROP TABLE IF EXISTS `lookup_icode`;
CREATE TABLE `lookup_icode`  (
  `icode` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `nhso_adp_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `uc_cr` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ppfs` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `herb32` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `kidney` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ems` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`icode`) USING BTREE,
  INDEX `icode`(`icode`) USING BTREE,
  INDEX `uc_cr`(`uc_cr`) USING BTREE,
  INDEX `ppfs`(`ppfs`) USING BTREE,
  INDEX `herb`(`herb32`) USING BTREE,
  INDEX `kidney`(`kidney`) USING BTREE,
  INDEX `ems`(`ems`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of lookup_icode
-- ----------------------------
INSERT INTO `lookup_icode` VALUES ('1000201', 'Morphine sulfate injectio(ยส.)', NULL, 'Y', '', '', '', '', '2025-07-14 00:38:09', '2025-07-16 22:13:29');
INSERT INTO `lookup_icode` VALUES ('1000202', 'Morphine  MST(ยส.)', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:38:38', '2025-07-14 00:38:38');
INSERT INTO `lookup_icode` VALUES ('1000203', 'Morphine sulfate SR', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:38:59', '2025-07-14 00:38:59');
INSERT INTO `lookup_icode` VALUES ('1000219', 'Oral contraceptives pills', 'FP003_1', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('1000323', 'ฟ้าทะลายโจร400 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1500004', 'เพชรสังฆาต500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1500005', 'ยาผสมเถาวัลย์เปรียง500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1500041', 'ยาอมประสะมะแว้ง200 mg/เม็ด', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1500052', 'Morphine 20 mg', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:39:17', '2025-07-14 00:39:17');
INSERT INTO `lookup_icode` VALUES ('1510002', 'MORPHINE 20 MG', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:39:36', '2025-07-14 00:39:36');
INSERT INTO `lookup_icode` VALUES ('1510021', 'Morphine SR (ยส.)', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:39:55', '2025-07-14 00:39:55');
INSERT INTO `lookup_icode` VALUES ('1520019', 'Clopidogrel', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:41:08', '2025-07-14 00:41:08');
INSERT INTO `lookup_icode` VALUES ('1530011', 'ขมิ้นชัน500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1530020', 'Morphine  syrup', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-07-14 00:40:17', '2025-07-14 00:40:17');
INSERT INTO `lookup_icode` VALUES ('1540030', 'Lynestrenol(DAILYTON)', 'FP003_2', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('1540031', 'Etonogestrel(Etoplan)', 'FP002_1', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('1550004', 'ยาประคบสมุนไพร200 g', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1550005', 'น้ำมันไพล20 ml', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1550006', 'ชุดอบสมุนไพร150 g', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1550008', 'ชาชงรางจืด(2 g : 5 ซองเล็ก)', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1570011', 'ชาชงหญ้าดอกขาว(2 g : 5 ซองเล็ก)', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1580001', 'คาลาไมน์พญายอ14 %', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1580011', 'StreptoKINASE', 'STEMI1', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('1600013', 'ยาปราบชมพูทวีป500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1600021', 'ยาไพล (ครีมไพล)30 g', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1600026', 'ชาชงบำรุงน้ำนม(2 g : 5 ซองเล็ก)', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1600039', 'ยาหม่องพญายอ10 g', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1600041', 'ยาหม่องไพล10 g (30% w/v จากน้ำมันไพล )', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630763', 'ศุขไสยาศน์ยาเข้าตำรับกัญชา', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630764', 'ตำรับยาทำลายพระสุเมรุ500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630877', 'ยาเขียวหอม500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630885', 'ยามันทธาตุ500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630891', 'ยาแก้ไอผสมมะขามป้อม  120 ml20% w/v', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630892', 'ยาประสะจันทน์แดง500 มิลลิกรัม', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630895', 'Epoetin alpha inj (Hypercrit)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:57:31', '2025-08-28 16:57:31');
INSERT INTO `lookup_icode` VALUES ('1630897', 'Epoetin alpha inj (Epiao)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:57:11', '2025-08-28 16:57:11');
INSERT INTO `lookup_icode` VALUES ('1630898', 'Epoetin alpha inj (Espogen)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:56:57', '2025-08-28 16:56:57');
INSERT INTO `lookup_icode` VALUES ('1630899', 'Epoetin alpha inj (Renogen)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:56:42', '2025-08-28 16:56:42');
INSERT INTO `lookup_icode` VALUES ('1630900', 'ยาธาตุบรรจบ500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630901', 'ยาสหัศธารา500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630902', 'ยาห้าราก500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630903', 'ยาจันทลีลา500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630904', 'ยาตรีผลา500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630905', 'ยาแก้ลมแก้เส้น500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630906', 'น้ำมันกัญชา (ตำรับหมอเดชา)10%  (THC 2.0 mg/ml)', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1630907', 'Epoetin alpha inj (Hemaplus)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:56:27', '2025-08-28 16:56:27');
INSERT INTO `lookup_icode` VALUES ('1630908', 'Epoetin alpha inj (Eposis)', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:56:12', '2025-08-28 16:56:12');
INSERT INTO `lookup_icode` VALUES ('1630953', 'ยาธาตุอบเชย120 ml', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('1631036', 'มะขามแขก500 mg', NULL, NULL, NULL, 'Y', NULL, NULL, '2025-06-28 12:05:52', '2025-06-30 00:28:18');
INSERT INTO `lookup_icode` VALUES ('3001540', 'ฟอกสีฟันที่ตาย หลังRCT ไม่ใช่การเสริมสวย (non vital bleaching)', '63130', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3001682', 'clavicle splint', '8601', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3001817', 'เฝือกพยุงคอ (Collar) ชนิดปรับได้', '8303', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003154', 'เฝือกพยุงระดับเอว (Lumbosacral support )', '8307', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003156', 'เฝือกพยุงคอ (Collar) ชนิดแข็ง', '8302', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003158', 'สายคล้องแขน (Arm sling)', '8602', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003159', 'อุปกรณ์พยุงข้อศอก (Elbow support) มีแกนด้านข้าง', '8603', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003160', 'อุปกรณ์พยุงข้อเข่า (Knee support) ไม่มีแกนด้านข้าง (ไม่รวมจากชนิดที่ทำจากผ้ายืด)', '8608', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003161', 'อุปกรณ์พยุงข้อเข่า (Knee support) มีแกนด้านข้าง (ไม่รวมจากชนิดที่ทำจากผ้ายืด)', '8607', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003162', 'อุปกรณ์พยุงส้นเท้าและฝ่าเท้าชนิดสำเร็จรูป', '8609', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003163', 'เครื่องช่วยเดินชนิด 4 ขา  (Pick-up-walker)', '8701', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003164', 'เครื่องช่วยเดินชนิด 4 ขา มีล้อ (Posterior Wheel Walker)', '8702', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003165', 'ไม้เท้า 1 ปุ่ม cane(1)', '8703', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003166', 'cane (3 หรือ 4 )', '8704', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003167', 'ไม้คำยัน', '8705', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003168', 'รองเท้าดัดแปลงสำหรับผู้ป่วยเบาหวาน ที่มีการชาที่เท้าหรือเท้าผิดรูป', '8806', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003172', 'ไม้เท้าสำหรับคนตาบอดพับได้', '8708', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003192', 'ครอบฟันแท้ (metal crown)', '9212', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003203', 'รื้อสะพานหรือครอบฟันหรือเดือย (เฉพาะabutment)', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003205', 'ซ่อมporcelain โดยใช้composite', '9214', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003206', 'ฟันเทียมถอดได้ทั้งขากรรไกร 1 ชิ้น บนหรือล่าง', '9202', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003208', 'ฟันปลอมบางส่วนถอดได้ฐานโลหะ(metallic partial denture)1-5 ซี่', 'XXXX2', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003209', 'ฟันปลอมบางส่วนถอดได้ฐานโลหะ(metallic partial denture)มากกว่า 5 ซี่', 'XXXX2', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003211', 'ฟันเทียมถอดได้ 1-5 ซี่', '9204', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003212', 'ฟันเทียมถอดได้มากกว่า 5 ซี่', '9205', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003237', 'ไม้เท้าอลูมิเนียมแบบสามขา', '8707', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003238', 'ไม้เท้าชนิด 3 หรือ 4 ปุ่ม', '8704', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003240', 'ไม้ค้ำยันรักแร้แบบอลูมิเนียม', '8706', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003241', 'รองเท้าคนพิการขนาดเล็ก ชนิดตัดเฉพาะราย', '8801', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003242', 'รองเท้าคนพิการขนาดกลาง ชนิดตัดเฉพาะราย', '8802', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003243', 'รองเท้าคนพิการขนาดใหญ่ ชนิดตัดเฉพาะราย', '8803', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003244', 'ค่าดัดแปลงรองเท้าคนพิการ', '8805', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003246', 'ที่ช่วยฝึกเดินแบบมีล้อขนาดกลาง (Anterior Wheel Walker )', '8709', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003247', 'ที่ช่วยฝึกเดินแบบมีล้อขนาดเล็ก (Anterior Wheel Walker)', '8710', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003248', 'เท้าเทียมที่ต้องใส่ร่วมกับขาเทียมแบบต่าง ๆ', '8209', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003250', 'สายเข็มขัดเทียม/สายยึดเบ้าขาเทียม', '8222', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003251', 'สายรัดกันเท้าตก', '8520', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003252', 'เสริมฝ่าเท้าส่วนหน้า', '8809', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003253', 'อุปกรณ์พยุงข้อศอก (Elbow support) ไม่มีแกนด้านข้าง', '8604', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003255', 'อุปกรณ์พยุงส้นเท้าและฝ่าเท้าชนิดหล่อพิเศษเฉพาะราย', '8610', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003256', 'แป้นสายเข็มขัด', '8223', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003257', 'พลาสติกดามข้อเท้า (Ankle-foot orthosis)', '8519', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003258', 'พลาสติกดามขาขนาดกลาง (กันเท้าตก)', '8512', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003259', 'พลาสติกดามขาขนาดใหญ่มีข้อเข่าล็อกได้', '8506', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003260', 'พลาสติกดามขาชนิดสั้นขนาดใหญ่ (กันเท้าตก)', '8514', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003261', 'พลาสติกดามขาเด็กขนาดกลางชนิดยาวมีข้อเข่าล็อกได้', '8504', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003266', 'เบ้าขาเทียมระดับสะโพก', '8221', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003269', 'ขาเทียมระดับสะโพกแกนใน', '8208', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003270', 'ขาเทียมระดับใต้เข่าแกนใน', '8203', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003271', 'ขาเทียมระดับเหนือเข่าแกนใน', '8206', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003272', 'ขาเทียมระดับเหนือเข่าแกนนอก', '8205', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003274', 'แขนเทียมต่ำกว่าระดับศอกส่วนปลายชนิดห้านิ้ว ไม่มีระบบการใช้งาน', '8102', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003275', 'เบ้าขาเทียมใต้เข่า/ข้อเท้า', '8218', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003276', 'เบ้าขาเทียมระดับเข่า', '8219', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003278', 'เบ้าขาเทียมเหนือเข่า', '8220', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003279', 'เบ้ารับน้ำหนักที่กระดูกก้นกบ', '8508', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003280', 'เบ้ารับน้ำหนักที่เอ็นสะบ้า (PTB)', '8509', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003281', 'แผ่นโลหะ/พลาสติกบังคับเชิงกรานเด็ก', '8605', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003282', 'แผ่นโลหะบังคับเชิงกรานผู้ใหญ่', '8606', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003284', 'รองเท้าคนพิการขนาดใหญ่พิเศษ ชนิดตัดเฉพาะราย', '8804', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003286', 'โลหะดามขาขนาดใหญ่มีข้อเข่าล็อกได้', '8507', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003287', 'โลหะดามขาชนิดสั้นขนาดใหญ่(กันเท้าตก)', '8518', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003288', 'โลหะดามขาเด็กขนาดกลางชนิดยาวมีข้อเข่าล็อกได้', '8505', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003289', 'โลหะดามขาเด็กเล็กชนิดยาวมีข้อเข่าล็อกได้', '8503', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003294', 'โลหะหรือพลาสติกดามหลังคด', '8306', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003295', 'พลาสติกดามขาเด็กเล็กชนิดสั้น (กันเท้าตก)', '8510', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003296', 'พลาสติกดามขาเด็กเล็กชนิดยาวมีข้อเข่าล็อกได้', '8502', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003298', 'เครื่องช่วยฟังสำหรับคนหูพิการ', '2502', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003299', 'ท่อที่ใส่เยื่อแก้วหู (Myringotomy tube)', '2503', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003302', 'อุปกรณ์พยุงส้นเท้าและฝ่าเท้าสำหรับผู้ป่วยเบาหวานชนิดหล่อพิเศษเฉพาะราย (Total Contact Insole/Orthosis)', '8612', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003303', 'รองเท้าสำหรับผู้ป่วยเบาหวานที่มีความเสี่ยงสูง และยังสามารถสวมใส่รองเท้าสำเร็จรูปได้', '8813', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003304', 'รองเท้าสำหรับผู้ป่วยเบาหวานที่มีความเสี่ยงสูง ที่มีเท้าผิดรูปจนไม่สามารถปรับรองเท้าสำเร็จรูปได้', '8814', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003305', 'โลหะ/พลาสติกดามข้อไหล่ ข้อมือ และข้อศอกผู้ใหญ่', '8402', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003375', 'การใช้ไตเทียม (Hemodialysis) - OP', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:55:56', '2025-08-28 16:55:56');
INSERT INTO `lookup_icode` VALUES ('3003381', 'เฝือกพยุงลำตัว', '8305', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003447', 'ไม้ค้ำยันรักแร้แบบไม้', '8711', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003449', 'เบาะรองนั่งสำหรับคนพิการ', '8903', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003451', 'ฟันเทียมถอดได้ทั้งปาก 2 ชิ้น บนและล่าง', '9203', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003619', 'สายต่อ ท่อใส่เข้าท้องแบบถาวรกับถุงน้ำยาแบบธรรมดา  (Transferred set)', '5606', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003620', 'ท่อใส่เข้าท้องสำหรับฟอกเลือดแทนไต แบบถาวร (Peritoneal Dialysis) ชนิดก้นหอย', '5605', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003653', 'เลนส์แก้วตาเทียม ชนิดพับได้ (foldable intraocular lens)', '2006', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003654', 'เลนส์แก้วตาเทียม ชนิดแข็งพับไม่ได้ (unfoldable intraocular lens)', '2007', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003714', 'ค่ารถพยาบาลหน่วยกู้ชีพระดับสูง (ALS)', NULL, '', '', '', '', 'Y', '2025-07-31 15:36:03', '2025-07-31 15:36:12');
INSERT INTO `lookup_icode` VALUES ('3003773', '30011-การบริการฝากครรภ์(ANC)', '30011', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003789', '12003-ค่าบริการเจาะเลือดจากหลอดเลือดดำ หลังอดอาหาร 8 ชม. Fasting Plasma Glucose : FPG สำหรับกลุ่มเสี่ยง 35-59 ปี (12-SCR)', '12003', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003790', '12004-ค่าบริการเจาะเลือดจากหลอดเลือดดำ หลังอดอาหาร 8 ชม. Total Cholresterol  หรือ HDL อายุ 45-59 ปี (12-SCR)', '12004', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003793', '30014-ค่าบริการทดสอบการตั้งครรภ์', '30014', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003794', '30015-ค่าบริการตรวจหลังคลอด (PNC:Postnatal care)', '30015', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003795', '30016-ค่ายาTriferdine (PNC:Postnatal care)', '30016', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003800', '13001-ค่าบริการคัดกรองโลหิตจางจากการขาดธาตุเหล็ก', '13001', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003801', '14001-ค่าบริการยาเม็ดเสริมธาตุเหล็ก (Ferrofolic)', '14001', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003802', '15001-ค่าบริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)', '15001', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003803', '90005-ค่าบริการคัดกรองมะเร็งลำไส้ใหญ่และลำไส้ตรง', '90005', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003806', 'DRUGP-จัดส่งยาทางไปรษณีย์', 'DRUGP', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003807', '30008-ตรวจสุขภาพช่องปาก(ANC)', '30008', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003808', '30009- ขัดทำความสะอาดฟัน(ANC)', '30009', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003809', '30010-ตรวจอัลตราซาวด์ หญิงตั้งครรภ์(ANC)', '30010', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003810', '30012- ค่าตรวจทางห้องปฏิบัติการในการฝากครรภ์ (LAB1)(ANC)', '30012', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003811', '30013-ค่าตรวจทางห้องปฏิบัติการในการฝากครรภ์(LAB2)(ANC)', '30013', NULL, 'Y', NULL, NULL, NULL, '2025-06-28 12:05:49', '2025-06-30 00:28:15');
INSERT INTO `lookup_icode` VALUES ('3003814', 'Eva001-ประเมินอาการ ผู้ปวย Pallative ที่บ้าน เมื่อปรับการรักษา', 'Eva001', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003815', 'Cons01-ให้คำแนะนำในการดูแลผู้ป่วย Pallative ที่บ้าน', 'Cons01', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003818', 'TELMED-Telehealth', 'TELMED', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003837', 'ท่อช่วยหายใจ (Endotracheal tube) ชนิดมี cuff', '3002', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003839', 'ครอบฟันน้ำนม (stainless steel crown)', '9211', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003840', 'เดือยฟัน (Pin Tooth) สิทธิข้าราชการ สิทธิ อปท.', '9214', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003864', 'สะพานฟันติดแน่น (Dental Bridge) รวมอุปกรณ์ต่างๆ ทั้งนี้ไม่รวมรากฟันเทียม', '9213', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003869', 'แผ่นปิดหน้าอกเพื่อรับหรือปล่อยกระแสไฟฟ้าในการกระตุกหัวใจ', '4511', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003870', 'ท่อระบายช่องอก', '3101', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003883', 'เฝือกพยุงคอ (Collar) ชนิดอ่อน', '8301', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003885', 'จัดส่งยาทางไปรษณีย์', 'DRUGP', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003931', '30001-Pallaitive Care - End Of Life Care', '30001', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003932', 'แผ่นใยสังเคราะห์แทนผนังท้องชนิดธรรมดา ความยาวไม่เกิน 15 เซนติเมตร', '5608', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003934', 'แผ่นใยสังเคราะห์แทนผนังท้องชนิดเมมเบรน ความยาวไม่เกิน 15 เซนติเมตร', '5611', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003935', 'สายสวนเข้าหลอดเลือดดำส่วนกลางชนิดใส่ผ่านหลอดเลือดดำบริเวณคอหรือไหล่', '4921', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003962', '80008-บริการในกลุ่ม GDM(ค่าสอน/ค่าStrip/OGTT)ตุลาคม-กันยายน', '80008', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3003994', 'เดือยฟัน (Post & Core) Composite core', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003996', 'เดือยฟัน (Post & Core) Pin สำเร็จร่วมกับ Composite core', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3003997', 'เดือยฟัน (Post & Core) Coping / Pin เหวี่ยง *ไม่รวมค่า Lab', NULL, 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-06-30 00:28:12');
INSERT INTO `lookup_icode` VALUES ('3004008', 'กายบริการยารักษาโรคอื่นที่จำเป็นที่เกิดจากบริการ CAPD', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:55:42', '2025-08-28 16:55:42');
INSERT INTO `lookup_icode` VALUES ('3004021', '80001-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip)  ตุลาคม', '80001', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004023', '80002-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip)  พฤศจิกายน', '80002', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004024', '80003-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip)  ธันวาคม', '80003', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004025', '80004-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip  มกราคม', '80004', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004026', '80005-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip) กุมภาพันธ์', '80005', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004027', '80006-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง/ค่า Strip มีนาคม-8636', '80006', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004028', '80007-บริการในกลุ่ม T1DM (ค่าสอน/ค่าตรวจคัดกรอง) เมษายน-กันยายน', '80007', 'Y', NULL, NULL, NULL, NULL, '2025-06-28 12:05:46', '2025-08-21 06:43:08');
INSERT INTO `lookup_icode` VALUES ('3004035', 'การใช้ไตเทียม (Hemodialysis) - IP', NULL, NULL, NULL, NULL, 'Y', NULL, '2025-08-28 16:55:22', '2025-08-28 16:55:22');
INSERT INTO `lookup_icode` VALUES ('3004265', 'สายให้อาหารผ่านรูจมูกสู่กระเพาะอาหาร (Nasogastric tube) ระยะยาว', '5101', 'Y', NULL, NULL, NULL, NULL, '2025-08-21 06:43:08', '2025-08-21 06:43:08');

-- ----------------------------
-- Table structure for lookup_ward
-- ----------------------------
DROP TABLE IF EXISTS `lookup_ward`;
CREATE TABLE `lookup_ward`  (
  `ward` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ward_m` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ward_f` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ward_vip` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ward_lr` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ward_homeward` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`ward`) USING BTREE,
  INDEX `ward`(`ward`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of lookup_ward
-- ----------------------------
INSERT INTO `lookup_ward` VALUES ('01', 'ผู้ป่วยใน สามัญ', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_ward` VALUES ('02', 'ห้องคลอด', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_ward` VALUES ('03', 'ผู้ป่วยใน VIP', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_ward` VALUES ('06', 'HomeWard', '', '', '', '', 'Y', NULL, '2025-07-25 19:38:27');
INSERT INTO `lookup_ward` VALUES ('07', 'Colonoscopy', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_ward` VALUES ('09', 'จักษุต้อกระจก', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `lookup_ward` VALUES ('10', 'ผู้ป่วยหนัก', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for main_setting
-- ----------------------------
DROP TABLE IF EXISTS `main_setting`;
CREATE TABLE `main_setting`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of main_setting
-- ----------------------------
INSERT INTO `main_setting` VALUES (1, 'IPD จำนวนเตียง', 'bed_qty', '30');
INSERT INTO `main_setting` VALUES (2, 'Token Authen Kiosk สปสช.', 'token_authen_kiosk_nhso', '');
INSERT INTO `main_setting` VALUES (3, 'Telegram Token', 'telegram_token', '');
INSERT INTO `main_setting` VALUES (4, 'Telegram Chat ID Notify_Summary', 'telegram_chat_id', '');
INSERT INTO `main_setting` VALUES (5, 'IPD ค่า K ', 'k_value', '1.25');
INSERT INTO `main_setting` VALUES (6, 'IPD BaseRate UCS ในเขต', 'base_rate', '8350');
INSERT INTO `main_setting` VALUES (7, 'IPD BaseRate UCS นอกเขต', 'base_rate2', '9600');
INSERT INTO `main_setting` VALUES (8, 'IPD BaseRate OFC', 'base_rate_ofc', '6200');
INSERT INTO `main_setting` VALUES (9, 'IPD BaseRate LGO', 'base_rate_lgo', '6194');
INSERT INTO `main_setting` VALUES (10, 'IPD BaseRate SSS', 'base_rate_sss', '6200');
INSERT INTO `main_setting` VALUES (11, 'สิทธิ พรบ. (รหัสสิทธิ HOSxP)', 'pttype_act', '29');
INSERT INTO `main_setting` VALUES (12, 'สิทธิ ปกส.กองทุนทดแทน (รหัสสิทธิ HOSxP)', 'pttype_sss_fund', '\"S6\",25,31');
INSERT INTO `main_setting` VALUES (13, 'สิทธิ ตรวจสุขภาพหน่วยงานภาครัฐ (รหัสสิทธิ HOSxP)', 'pttype_checkup', '14,27');
INSERT INTO `main_setting` VALUES (14, 'สิทธิ ประกันชีวิต iClaim (รหัสสิทธิ HOSxP)', 'pttype_iclaim', '26');
INSERT INTO `main_setting` VALUES (15, 'สิทธิ ปกส. 72 ชั่วโมงแรก (รหัสสิทธิ HOSxP)', 'pttype_sss_72', '32');
INSERT INTO `main_setting` VALUES (16, 'LAB Pregnancy Test (รหัส lab_items HOSxP)', 'lab_prt', '444');
INSERT INTO `main_setting` VALUES (17, 'ยา Clopidogrel (รหัส drugitems HOSxP)', 'drug_clopidogrel', '1520019');

-- ----------------------------
-- Table structure for nhso_endpoint
-- ----------------------------
DROP TABLE IF EXISTS `nhso_endpoint`;
CREATE TABLE `nhso_endpoint`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` varchar(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0',
  `firstName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `lastName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `mainInscl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `mainInsclName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `subInscl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `subInsclName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `serviceDateTime` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `sourceChannel` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `claimCode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `claimType` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_lgo
-- ----------------------------
DROP TABLE IF EXISTS `stm_lgo`;
CREATE TABLE `stm_lgo`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tran_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dep` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `compensate_treatment` double(15, 2) NULL DEFAULT NULL,
  `compensate_nhso` double(15, 2) NULL DEFAULT NULL,
  `error_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `service_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `refer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `have_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `use_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `main_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `secondary_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `href` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `prov1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `proj` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `drg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge_treatment` double(15, 2) NULL DEFAULT NULL,
  `charge_pp` double(15, 2) NULL DEFAULT NULL,
  `withdraw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `non_withdraw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `payrate` double(100, 0) NULL DEFAULT NULL,
  `delay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `delay_percent` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ccuf` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `act` double(15, 2) NULL DEFAULT NULL,
  `case_iplg` double(15, 2) NULL DEFAULT NULL,
  `case_oplg` double(15, 2) NULL DEFAULT NULL,
  `case_palg` double(15, 2) NULL DEFAULT NULL,
  `case_inslg` double(15, 2) NULL DEFAULT NULL,
  `case_otlg` double(15, 2) NULL DEFAULT NULL,
  `case_pp` double(15, 2) NULL DEFAULT NULL,
  `case_drug` double(15, 2) NULL DEFAULT NULL,
  `deny_iplg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_oplg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_palg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_inslg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_otlg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ors` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `va` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `audit_results` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_lgo_kidney
-- ----------------------------
DROP TABLE IF EXISTS `stm_lgo_kidney`;
CREATE TABLE `stm_lgo_kidney`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dep` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` date NULL DEFAULT NULL,
  `compensate_kidney` double(15, 2) NULL DEFAULT NULL,
  `note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `datetimeadm`(`datetimeadm`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_lgo_kidneyexcel
-- ----------------------------
DROP TABLE IF EXISTS `stm_lgo_kidneyexcel`;
CREATE TABLE `stm_lgo_kidneyexcel`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dep` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` date NULL DEFAULT NULL,
  `compensate_kidney` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_lgoexcel
-- ----------------------------
DROP TABLE IF EXISTS `stm_lgoexcel`;
CREATE TABLE `stm_lgoexcel`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tran_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dep` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `compensate_treatment` double(15, 2) NULL DEFAULT NULL,
  `compensate_nhso` double(15, 2) NULL DEFAULT NULL,
  `error_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `service_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `refer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `have_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `use_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `main_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `secondary_rights` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `href` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `prov1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hospname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `proj` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `drg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge_treatment` double(15, 2) NULL DEFAULT NULL,
  `charge_pp` double(15, 2) NULL DEFAULT NULL,
  `withdraw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `non_withdraw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `payrate` double(100, 0) NULL DEFAULT NULL,
  `delay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `delay_percent` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ccuf` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `act` double(15, 2) NULL DEFAULT NULL,
  `case_iplg` double(15, 2) NULL DEFAULT NULL,
  `case_oplg` double(15, 2) NULL DEFAULT NULL,
  `case_palg` double(15, 2) NULL DEFAULT NULL,
  `case_inslg` double(15, 2) NULL DEFAULT NULL,
  `case_otlg` double(15, 2) NULL DEFAULT NULL,
  `case_pp` double(15, 2) NULL DEFAULT NULL,
  `case_drug` double(15, 2) NULL DEFAULT NULL,
  `deny_iplg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_oplg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_palg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_inslg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deny_otlg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ors` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `va` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `audit_results` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ofc
-- ----------------------------
DROP TABLE IF EXISTS `stm_ofc`;
CREATE TABLE `stm_ofc`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `projcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `act` double(15, 2) NULL DEFAULT NULL,
  `receive_room` double(15, 2) NULL DEFAULT NULL,
  `receive_instument` double(15, 2) NULL DEFAULT NULL,
  `receive_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_treatment` double(15, 2) NULL DEFAULT NULL,
  `receive_car` double(15, 2) NULL DEFAULT NULL,
  `receive_waitdch` double(15, 2) NULL DEFAULT NULL,
  `receive_other` double(15, 2) NULL DEFAULT NULL,
  `receive_total` double(15, 2) NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ofc_kidney
-- ----------------------------
DROP TABLE IF EXISTS `stm_ofc_kidney`;
CREATE TABLE `stm_ofc_kidney`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stmdoc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `station` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hreg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `invno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dttran` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `amount` double(15, 2) NULL DEFAULT NULL,
  `paid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hdflag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ofcexcel
-- ----------------------------
DROP TABLE IF EXISTS `stm_ofcexcel`;
CREATE TABLE `stm_ofcexcel`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `projcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `act` double(15, 2) NULL DEFAULT NULL,
  `receive_room` double(15, 2) NULL DEFAULT NULL,
  `receive_instument` double(15, 2) NULL DEFAULT NULL,
  `receive_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_treatment` double(15, 2) NULL DEFAULT NULL,
  `receive_car` double(15, 2) NULL DEFAULT NULL,
  `receive_waitdch` double(15, 2) NULL DEFAULT NULL,
  `receive_other` double(15, 2) NULL DEFAULT NULL,
  `receive_total` double(15, 2) NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_sss_kidney
-- ----------------------------
DROP TABLE IF EXISTS `stm_sss_kidney`;
CREATE TABLE `stm_sss_kidney`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stmdoc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `station` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hreg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `invno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `dttran` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `amount` double(15, 2) NULL DEFAULT NULL,
  `epopay` double(15, 2) NULL DEFAULT NULL,
  `epoadm` double(15, 2) NULL DEFAULT NULL,
  `paid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `rid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hdflag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ucs
-- ----------------------------
DROP TABLE IF EXISTS `stm_ucs`;
CREATE TABLE `stm_ucs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tran_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `maininscl` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `projcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_act` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ccuf` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_adjrw2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_payrate` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_salary` double(15, 2) NULL DEFAULT NULL,
  `fund_compensate_salary` double(15, 2) NULL DEFAULT NULL,
  `receive_op` double(15, 2) NULL DEFAULT NULL,
  `receive_ip_compensate_cal` double(15, 2) NULL DEFAULT NULL,
  `receive_ip_compensate_pay` double(15, 2) NULL DEFAULT NULL,
  `receive_hc_hc` double(15, 2) NULL DEFAULT NULL,
  `receive_hc_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_ae_ae` double(15, 2) NULL DEFAULT NULL,
  `receive_ae_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_inst` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_compensate_cal` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_compensate_pay` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_palliative` double(15, 2) NULL DEFAULT NULL,
  `receive_dmishd` double(15, 2) NULL DEFAULT NULL,
  `receive_pp` double(15, 2) NULL DEFAULT NULL,
  `receive_fs` double(15, 2) NULL DEFAULT NULL,
  `receive_opbkk` double(15, 2) NULL DEFAULT NULL,
  `receive_total` double(15, 2) NULL DEFAULT NULL,
  `va` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `covid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `resources` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ucs_kidney
-- ----------------------------
DROP TABLE IF EXISTS `stm_ucs_kidney`;
CREATE TABLE `stm_ucs_kidney`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` date NULL DEFAULT NULL,
  `hd_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge_total` double(15, 2) NULL DEFAULT NULL,
  `receive_total` double(15, 2) NULL DEFAULT NULL,
  `note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `datetimeadm`(`datetimeadm`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ucs_kidneyexcel
-- ----------------------------
DROP TABLE IF EXISTS `stm_ucs_kidneyexcel`;
CREATE TABLE `stm_ucs_kidneyexcel`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` date NULL DEFAULT NULL,
  `hd_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge_total` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_total` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for stm_ucsexcel
-- ----------------------------
DROP TABLE IF EXISTS `stm_ucsexcel`;
CREATE TABLE `stm_ucsexcel`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `repno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `tran_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `hn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `an` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pt_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datetimeadm` datetime(0) NULL DEFAULT NULL,
  `vstdate` date NULL DEFAULT NULL,
  `vsttime` time(0) NULL DEFAULT NULL,
  `datetimedch` datetime(0) NULL DEFAULT NULL,
  `dchdate` date NULL DEFAULT NULL,
  `dchtime` time(0) NULL DEFAULT NULL,
  `maininscl` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `projcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `charge` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_act` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ccuf` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_adjrw2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_payrate` double(15, 2) NULL DEFAULT NULL,
  `fund_ip_salary` double(15, 2) NULL DEFAULT NULL,
  `fund_compensate_salary` double(15, 2) NULL DEFAULT NULL,
  `receive_op` double(15, 2) NULL DEFAULT NULL,
  `receive_ip_compensate_cal` double(15, 2) NULL DEFAULT NULL,
  `receive_ip_compensate_pay` double(15, 2) NULL DEFAULT NULL,
  `receive_hc_hc` double(15, 2) NULL DEFAULT NULL,
  `receive_hc_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_ae_ae` double(15, 2) NULL DEFAULT NULL,
  `receive_ae_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_inst` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_compensate_cal` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_compensate_pay` double(15, 2) NULL DEFAULT NULL,
  `receive_dmis_drug` double(15, 2) NULL DEFAULT NULL,
  `receive_palliative` double(15, 2) NULL DEFAULT NULL,
  `receive_dmishd` double(15, 2) NULL DEFAULT NULL,
  `receive_pp` double(15, 2) NULL DEFAULT NULL,
  `receive_fs` double(15, 2) NULL DEFAULT NULL,
  `receive_opbkk` double(15, 2) NULL DEFAULT NULL,
  `receive_total` double(15, 2) NULL DEFAULT NULL,
  `va` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `covid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `resources` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `an`(`an`) USING BTREE,
  INDEX `hn`(`hn`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `vstdate`(`vstdate`) USING BTREE,
  INDEX `vsttime`(`vsttime`) USING BTREE,
  INDEX `dchdate`(`dchdate`) USING BTREE,
  INDEX `dchtime`(`dchtime`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'Admin H-RiMS', 'admin@gmail.com', '$2y$10$Cxdkrfs.MtyRKCDBJhgnPO7WSumIihGVncBRfA9ZaiG9LojoqHMsO', 'Y', 'admin', '2025-05-01 16:02:36', '2025-05-01 16:02:36');

SET FOREIGN_KEY_CHECKS = 1;
