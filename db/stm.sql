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

 Date: 09/09/2025 12:42:04
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

SET FOREIGN_KEY_CHECKS = 1;
