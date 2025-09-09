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

 Date: 09/09/2025 12:46:37
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

SET FOREIGN_KEY_CHECKS = 1;
