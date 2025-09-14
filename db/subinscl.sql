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

 Date: 13/09/2025 11:58:01
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for subinscl
-- ----------------------------
DROP TABLE IF EXISTS `subinscl`;
CREATE TABLE `subinscl`  (
  `code` varchar(2) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL DEFAULT '',
  `name` varchar(200) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `maininscl` varchar(10) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT '',
  `note` varchar(100) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of subinscl
-- ----------------------------
INSERT INTO `subinscl` VALUES ('A1', 'สิทธิข้าราชการการเมือง', 'BFC', NULL);
INSERT INTO `subinscl` VALUES ('D1', 'สิทธิหลักประกันสุขภาพแห่งชาติ (ผู้ประกันตนคนพิการ)', 'DIS', NULL);
INSERT INTO `subinscl` VALUES ('E1', 'สิทธิเบิกหน่วยงานรัฐ (ตนเอง)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('E2', 'สิทธิเบิกหน่วยงานรัฐ (บุคคลในครอบครัว)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('E3', 'สิทธิเบิกหน่วยงานรัฐ (ผู้รับเบี้ยหวัดบำนาญ)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('E4', 'สิทธิเบิกหน่วยงานรัฐ (บุคคลในครอบครัวผู้รับเบี้ยหวัดบำนาญ)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('Z3', 'บุคลากรสำนักงานหลักประกันสุขภาพแห่งชาติ', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('L1', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(ข้าราชการ/พนักงาน/ลูกจ้างประจำ/ครูผู้ดูแลเด็ก/ครูผู้ช่วย)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L2', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(บุคคลในครอบครัว)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L3', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(ผู้รับเบี้ยหวัดบำนาญ)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L4', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(บุคคลในครอบครัวผู้รับเบี้ยหวัดบำนาญ)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L5', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(ข้าราชการการเมือง)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L6', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น(บุคคลในครอบครัวข้าราชการการเมือง)', 'LGO', '');
INSERT INTO `subinscl` VALUES ('L9', 'สิทธิสวัสดิการพนักงานส่วนท้องถิ่น (ยังไม่ระบุตำแหน่ง)', 'LGO', NULL);
INSERT INTO `subinscl` VALUES ('N1', 'แรงงานต่างด้าวเด็ก อายุ 0-7 ปีบริบูรณ์', 'NRH', NULL);
INSERT INTO `subinscl` VALUES ('N2', 'แรงงานต่างด้าวทั่วไป อายุเกิน 7 ปี', 'NRH', NULL);
INSERT INTO `subinscl` VALUES ('N3', 'แรงงานต่างด้าวที่รอเข้าระบบประกันสังคม', 'NRH', NULL);
INSERT INTO `subinscl` VALUES ('N4', 'แรงงานต่างด้าวทั่วไป อายุเกิน 7 ปี (ตรวจสุขภาพที่รพ.อื่น)', 'NRH', NULL);
INSERT INTO `subinscl` VALUES ('N5', 'แรงงานต่างด้าวที่รอเข้าระบบประกันสังคม (ตรวจสุขภาพที่รพ.อื่น)', 'NRH', NULL);
INSERT INTO `subinscl` VALUES ('B1', 'สิทธิเบิกกรุงเทพมหานคร(ข้าราชการ)', 'BKK', '');
INSERT INTO `subinscl` VALUES ('B2', 'สิทธิเบิกกรุงเทพมหานคร(ลูกจ้างประจำ)', 'BKK', '');
INSERT INTO `subinscl` VALUES ('B3', 'สิทธิเบิกกรุงเทพมหานคร(ผู้รับเบี้ยหวัดบำนาญ)', 'BKK', '');
INSERT INTO `subinscl` VALUES ('B4', 'สิทธิเบิกกรุงเทพมหานคร(บุคคลในครอบครัว)', 'BKK', '');
INSERT INTO `subinscl` VALUES ('B5', 'สิทธิเบิกกรุงเทพมหานคร(บุคคลในครอบครัวผู้รับเบี้ยหวัดบำนาญ)', 'BKK', '');
INSERT INTO `subinscl` VALUES ('B6', 'สิทธิเบิกบุคคลในครอบครัวลูกจ้างชั่วคราวกรุงเทพมหานคร (เบิกใบเสร็จ/หนังสือรับรองสิทธิ)', 'BKK', NULL);
INSERT INTO `subinscl` VALUES ('C1', 'สิทธิเบิกหน่วยงานตนเองหรือรัฐวิสาหกิจ(เจ้าหน้าที่)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('C2', 'สิทธิเบิกหน่วยงานตนเองหรือรัฐวิสาหกิจ(พนักงาน)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('C3', 'สิทธิเบิกหน่วยงานตนเองหรือรัฐวิสาหกิจ(ผู้รับเบี้ยหวัดบำนาญ)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('C4', 'สิทธิเบิกจากหน่วยงานตนเองหรือรัฐวิสาหกิจ(บุคคลในครอบครัว)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('C5', 'สิทธิเบิกจากหน่วยงานตนเองหรือรัฐวิสาหกิจ (บุคคลในครอบครัวผู้รับเบี้ยหวัดบำนาญ)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('C6', 'สิทธิเบิกจากหน่วยงานตนเองหรือรัฐวิสาหกิจ (กรณีได้รับสิทธิเฉพาะหน่วยงาน)', 'GOF', '');
INSERT INTO `subinscl` VALUES ('G1', 'สิทธิหน่วยงานรัฐอื่น (ไม่สังกัดกรมบัญชีกลาง)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('O1', 'สิทธิเบิกกรมบัญชีกลาง(ข้าราชการ)', 'OFC', '');
INSERT INTO `subinscl` VALUES ('O2', 'สิทธิเบิกกรมบัญชีกลาง(ลูกจ้างประจำ)', 'OFC', '');
INSERT INTO `subinscl` VALUES ('O3', 'สิทธิเบิกกรมบัญชีกลาง(ผู้รับเบี้ยหวัดบำนาญ)', 'OFC', '');
INSERT INTO `subinscl` VALUES ('O4', 'สิทธิเบิกกรมบัญชีกลาง(บุคคลในครอบครัว)', 'OFC', '');
INSERT INTO `subinscl` VALUES ('O5', 'สิทธิเบิกกรมบัญชีกลาง(บุคคลในครอบครัวผู้รับเบี้ยหวัดบำนาญ)', 'OFC', '');
INSERT INTO `subinscl` VALUES ('O7', 'สิทธิเบิกกรมบัญชีกลาง(รอกรมบัญชีกลางยืนยันสิทธิ)', 'OFC', NULL);
INSERT INTO `subinscl` VALUES ('P1', 'สิทธิครูเอกชน', 'PVT', NULL);
INSERT INTO `subinscl` VALUES ('P2', 'สิทธิครูเอกชน (เบิกส่วนเกินหนึ่งแสนบาทจากกรมบัญชีกลาง)', 'PVT', NULL);
INSERT INTO `subinscl` VALUES ('P3', 'สิทธิครูเอกชน (เบิกส่วนเกินหนึ่งแสนบาทจาก อปท.)', 'PVT', NULL);
INSERT INTO `subinscl` VALUES ('S6', 'สิทธิเบิกกองทุนประกันสังคม (ทุพพลภาพ)', 'SSI', NULL);
INSERT INTO `subinscl` VALUES ('S1', 'สิทธิเบิกกองทุนประกันสังคม(ผู้ประกันตน)', 'SSS', '');
INSERT INTO `subinscl` VALUES ('S2', 'สิทธิเบิกกองทุนประกันสังคม(คู่สมรสผู้ประกันตน)', 'SSS', '');
INSERT INTO `subinscl` VALUES ('S3', 'สิทธิเบิกกองทุนประกันสังคม(บุตรผู้ประกันตน)', 'SSS', '');
INSERT INTO `subinscl` VALUES ('S4', 'สิทธิเบิกกองทุนประกันสังคม (เบิกส่วนต่างกรมบัญชีกลางได้เฉพาะกรณี)', 'SSS', NULL);
INSERT INTO `subinscl` VALUES ('ST', 'สิทธิเบิกงานประกันสุขภาพกระทรวงสาธารณสุข', 'STP', '');
INSERT INTO `subinscl` VALUES ('89', 'ช่วงอายุ 12-59 ปี', 'UCS', '');
INSERT INTO `subinscl` VALUES ('60', 'อาสาสมัครมาเลเรีย', 'WEL', '');
INSERT INTO `subinscl` VALUES ('61', 'บุคคลในครอบครัวของอาสาสมัครมาเลเรีย', 'WEL', '');
INSERT INTO `subinscl` VALUES ('62', 'ช่างสุขภัณฑ์หมู่บ้าน', 'WEL', '');
INSERT INTO `subinscl` VALUES ('63', 'บุคคลในครอบครัวของช่างสุขภัณฑ์หมู่บ้าน', 'WEL', '');
INSERT INTO `subinscl` VALUES ('64', 'ผู้บริหารโรงเรียนและครูของโรงเรียนเอกชนที่สอนศาสนาอิสลาม', 'WEL', '');
INSERT INTO `subinscl` VALUES ('65', 'บุคคลในครอบครัวของผู้บริหารโรงเรียนและครูของโรงเรียนเอกชนที่สอนศาสนาอิสลาม', 'WEL', '');
INSERT INTO `subinscl` VALUES ('66', 'ผู้ได้รับพระราชทานเหรียญราชการชายแดน', 'WEL', '');
INSERT INTO `subinscl` VALUES ('67', 'ผู้ได้รับพระราชทานเหรียญพิทักษ์เสรีชน', 'WEL', '');
INSERT INTO `subinscl` VALUES ('68', 'สมาชิกผู้บริจาคโลหิตของสภากาชาดไทย ซึ่งบริจาคโลหิตตั้งแต่ 18 ครั้ง ขึ้นไป', 'WEL', '');
INSERT INTO `subinscl` VALUES ('69', 'หมออาสาหมู่บ้านตามโครงการของกระทรวงกลาโหม', 'WEL', '');
INSERT INTO `subinscl` VALUES ('70', 'อาสาสมัครคุมประพฤ กระทรวงยุติธรรม', 'WEL', '');
INSERT INTO `subinscl` VALUES ('71', 'เด็กอายุไม่เกิน 12 ปีบริบูรณ์', 'WEL', '');
INSERT INTO `subinscl` VALUES ('72', 'ผู้มีรายได้น้อย', 'WEL', '');
INSERT INTO `subinscl` VALUES ('73', 'นักเรียนมัธยมศึกษาตอนต้น', 'WEL', '');
INSERT INTO `subinscl` VALUES ('74', 'ผู้พิการ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('75', 'ทหารผ่านศึกชั้น 1-3 ที่มีบัตรทหารผ่านศึก รวมถึงผู้ได้รับพระราชทานเหรียญชัยสมรภูมิ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('76', 'พระภิกษุ สามเณร แม่ชี นักบวช และนักพรตในพระพุทธศาสนาซึ่งมีหนังสือสุทธิรับรอง', 'WEL', '');
INSERT INTO `subinscl` VALUES ('77', 'ผู้มีอายุเกิน 60 ปีบริบูรณ์', 'WEL', '');
INSERT INTO `subinscl` VALUES ('80', 'บุคคลในครอบครัวทหารผ่านศึกชั้น 1-3 รวมถึงผู้ได้รับพระราชทานเหรียญสมรภูมิ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('81', 'ผู้นำชุมชน (กำนัน สารวัตรกำนัน ผู้ใหญ่บ้าน ผู้ช่วยผู้ใหญ่บ้านและแพทย์ประจำตำบล)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('82', 'อาสาสมัครสาธารณสุขประจำหมู่บ้าน (อสม.) หรือ อาสาสมัครสาธารณสุขกรุงเทพมหานคร', 'WEL', '');
INSERT INTO `subinscl` VALUES ('83', 'ผู้นำศาสนาอิสลาม ( อิหม่าม คอเต็บ บิหลั่น)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('84', 'บุคคลในครอบครัวของผู้นำศาสนาอิสลามของผู้นำศาสนาอิสลาม ( อิหม่าม คอเต็บ บิหลั่น)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('85', 'ผู้ได้รับพระราชทานเหรียญงานพระราชสงครามในทวีปยุโรป', 'WEL', '');
INSERT INTO `subinscl` VALUES ('86', 'บุคคลในครอบครัวของผู้ได้รับพระราชทานเหรียญงานพระราชสงครามในทวีปยุโรป', 'WEL', '');
INSERT INTO `subinscl` VALUES ('87', 'บุคคลในครอบครัวของผู้นำชุมชน (กำนัน สารวัตรกำนัน ผู้ใหญ่บ้าน ผู้ช่วยผู้ใหญ่บ้านและแพทย์ประจำตำบล)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('88', 'บุคคลในครอบครัวของอาสาสมัครสาธารณสุขประจำหมู่บ้าน (อสม.) หรือ อาสาสมัครสาธารณสุขกรุงเทพมหานคร', 'WEL', '');
INSERT INTO `subinscl` VALUES ('90', 'ทหารเกณฑ์', 'WEL', '');
INSERT INTO `subinscl` VALUES ('91', 'ผู้ที่พำนักในสถานที่ภายใต้การดูแลของส่วนราชการ(ราชทัณฑ์)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('92', 'ผู้ที่พำนักในสถานที่ภายใต้การดูแลของส่วนราชการ (สถานพินิจและสถานสงเคราะห์)', 'WEL', '');
INSERT INTO `subinscl` VALUES ('93', 'นักเรียนทหาร', 'WEL', '');
INSERT INTO `subinscl` VALUES ('94', 'ทหารผ่านศึกชั้น 4 ที่มีบัตรทหารผ่านศึก รวมถึงผู้ได้รับพระราชทานเหรียญชัยสมรภูมิ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('95', 'บุคคลในครอบครัวทหารผ่านศึกชั้น 4 รวมถึงผู้ได้รับพระราชทานเหรียญสมรภูมิ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('96', 'ทหารพราน', 'WEL', '');
INSERT INTO `subinscl` VALUES ('97', 'บุคคลในครอบครัวทหารของกรมสวัสดิการ 3 เหล่าทัพ', 'WEL', '');
INSERT INTO `subinscl` VALUES ('98', 'บุคคลในครอบครัวทหารผ่านศึกนอกประจำการบัตรชั้นที่ 1', 'WEL', '');
INSERT INTO `subinscl` VALUES ('G2', 'สิทธิเบิกสถาบันการแพทย์ฉุกเฉินแห่งชาติ (ตนเอง)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('G3', 'สิทธิเบิกการไฟฟ้าฝ่ายผลิตแห่งประเทศไทย (ตนเอง)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('G4', 'สิทธิเบิกการไฟฟ้าฝ่ายผลิตแห่งประเทศไทย (บุคคลในครอบครัว)', 'GOF', NULL);
INSERT INTO `subinscl` VALUES ('G5', 'สิทธิเบิกองค์การขนส่งมวลชนกรุงเทพ (ตนเอง)', 'BMT', NULL);
INSERT INTO `subinscl` VALUES ('G6', 'สิทธิเบิกองค์การขนส่งมวลชนกรุงเทพ (บุคคลในครอบครัว)', 'BMT', NULL);
INSERT INTO `subinscl` VALUES ('S5', 'สิทธิเบิกกองทุนประกันสังคม (เบิกส่วนต่างกรุงเทพมหานคร)', 'SSS', NULL);
INSERT INTO `subinscl` VALUES ('S7', 'สิทธิเบิกกองทุนประกันสังคม(เบิกส่วนต่างองค์การขนส่งมวลชนกรุงเทพ)', 'SSS', NULL);
INSERT INTO `subinscl` VALUES ('V1', 'สิทธิเบิกองค์การสงเคราะห์ทหารผ่านศึก (ผู้ได้รับพระราชทานเหรียญชัยสมรภูมิ)', 'WVO', NULL);
INSERT INTO `subinscl` VALUES ('V2', 'สิทธิเบิกองค์การสงเคราะห์ทหารผ่านศึก (ทายาทผู้ได้รับพระราชทานเหรียญชัยสมรภูมิ)', 'WVO', NULL);

SET FOREIGN_KEY_CHECKS = 1;
