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

 Date: 19/07/2025 22:58:53
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
  `icd10` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `pp` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`icd10`) USING BTREE,
  INDEX `icd10`(`icd10`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of lookup_icd10
-- ----------------------------
INSERT INTO `lookup_icd10` VALUES ('Z00', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z000', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z001', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z002', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z003', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z004', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z005', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z006', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z008', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z01', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z010', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z011', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z013', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z014', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z015', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z016', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z017', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z018', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z019', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z02', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z020', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z021', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z022', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z023', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z024', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z025', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z026', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z027', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z028', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z029', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z03', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z030', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z031', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z032', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z033', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z034', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z035', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z036', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z038', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z039', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z10', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z100', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z101', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z102', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z103', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z108', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z11', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z110', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z111', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z112', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z113', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z114', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z115', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z116', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z118', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z119', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z12', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z120', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z121', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z122', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z123', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z124', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z125', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z126', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z128', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z129', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z13', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z130', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z131', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z132', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z133', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z134', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z135', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z136', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z137', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z138', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z139', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z20', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z200', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z201', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z202', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z204', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z205', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z206', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z207', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z208', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z209', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z23', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z230', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z231', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z232', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z233', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z234', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z235', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z236', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z237', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z238', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z24', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z240', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z241', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z243', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z244', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z245', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z246', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z25', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z250', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z251', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z258', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z26', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z260', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z268', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z269', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z27', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z270', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z271', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z272', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z273', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z274', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z278', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z279', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z28', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z280', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z281', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z282', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z288', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z289', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z29', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z291', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z292', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z298', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z299', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z30', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z300', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z301', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z302', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z303', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z304', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z305', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z308', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z309', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z32', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z320', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z321', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z34', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z340', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z348', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z349', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z35', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z350', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z351', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z352', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z353', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z354', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z355', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z356', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z357', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z358', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z359', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z36', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z360', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z361', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z362', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z363', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z364', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z365', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z368', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z369', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z39', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z390', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z391', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z392', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z55', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z550', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z551', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z552', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z553', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z554', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z558', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z559', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z56', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z560', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z561', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z562', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z563', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z564', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z565', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z566', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z567', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z57', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z570', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z571', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z572', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z573', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z574', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z575', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z576', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z577', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z578', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z579', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z58', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z580', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z581', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z582', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z583', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z584', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z585', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z586', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z587', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z588', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z589', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z59', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z590', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z591', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z592', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z593', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z594', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z595', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z596', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z597', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z598', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z599', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z60', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z600', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z601', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z602', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z603', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z604', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z605', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z608', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z609', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z61', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z610', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z611', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z612', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z613', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z614', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z615', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z616', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z617', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z618', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z619', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z62', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z620', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z621', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z622', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z623', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z624', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z625', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z626', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z628', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z629', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z63', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z630', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z631', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z632', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z633', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z634', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z635', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z636', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z637', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z638', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z639', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z64', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z640', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z641', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z642', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z643', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z644', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z65', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z650', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z651', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z652', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z653', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z654', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z655', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z658', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z659', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z70', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z700', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z701', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z702', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z703', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z708', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z709', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z71', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z710', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z711', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z712', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z713', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z714', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z715', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z716', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z717', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z718', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z719', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z72', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z720', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z721', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z722', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z723', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z724', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z725', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z726', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z728', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z729', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z73', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z730', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z731', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z732', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z733', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z734', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z735', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z736', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z738', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z739', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z75', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z750', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z751', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z752', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z753', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z754', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z755', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z758', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z759', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z76', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z760', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z761', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z762', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z763', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z764', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z765', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z768', 'Y');
INSERT INTO `lookup_icd10` VALUES ('Z769', 'Y');

-- ----------------------------
-- Table structure for lookup_icode
-- ----------------------------
DROP TABLE IF EXISTS `lookup_icode`;
CREATE TABLE `lookup_icode`  (
  `icode` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `nhso_adp_code` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `uc_cr` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ppfs` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `herb32` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `kidney` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ems` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`icode`) USING BTREE,
  INDEX `icode`(`icode`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

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
-- Table structure for main_setting
-- ----------------------------
DROP TABLE IF EXISTS `main_setting`;
CREATE TABLE `main_setting`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of main_setting
-- ----------------------------
INSERT INTO `main_setting` VALUES (1, 'จำนวนเตียง', 'bed_qty', '30');
INSERT INTO `main_setting` VALUES (2, 'Token Authen Kiosk สปสช.', 'token_authen_kiosk_nhso', '');
INSERT INTO `main_setting` VALUES (3, 'Telegram Token', 'telegram_token', '');
INSERT INTO `main_setting` VALUES (4, 'Telegram Chat ID Notify_Summary', 'telegram_chat_id', '');
INSERT INTO `main_setting` VALUES (5, 'ค่า K ', 'k_value', '1.25');
INSERT INTO `main_setting` VALUES (6, 'Base Rate UCS ในเขต', 'base_rate', '8350');
INSERT INTO `main_setting` VALUES (7, 'Base Rate UCS นอกเขต', 'base_rate2', '9600');
INSERT INTO `main_setting` VALUES (8, 'Base Rate OFC', 'base_rate_ofc', '6200');
INSERT INTO `main_setting` VALUES (9, 'Base Rate LGO', 'base_rate_lgo', '6194');
INSERT INTO `main_setting` VALUES (10, 'Base Rate SSS', 'base_rate_sss', '6200');

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
  `charge` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_act` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_adjrw` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ps2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_ccuf` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_adjrw2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_payrate` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_ip_salary` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fund_compensate_salary` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_op` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_ip_compensate_cal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_ip_compensate_pay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_hc_hc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_hc_drug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_ae_ae` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_ae_drug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_inst` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_dmis_compensate_cal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_dmis_compensate_pay` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_dmis_drug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_palliative` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_dmishd` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_pp` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_fs` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_opbkk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `receive_total` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `va` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `covid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `resources` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `stm_filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
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
INSERT INTO `users` VALUES (1, 'Admin H-RiMS', 'admin@amnat.com', '$2y$10$Cxdkrfs.MtyRKCDBJhgnPO7WSumIihGVncBRfA9ZaiG9LojoqHMsO', 'Y', 'admin', '2025-05-01 16:02:36', '2025-05-01 16:02:36');

SET FOREIGN_KEY_CHECKS = 1;
