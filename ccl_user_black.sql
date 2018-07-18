/*
Navicat MySQL Data Transfer

Source Server         : localhost3308
Source Server Version : 50173
Source Host           : localhost:3308
Source Database       : wx_shop

Target Server Type    : MYSQL
Target Server Version : 50173
File Encoding         : 65001

Date: 2018-07-18 18:16:33
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for ccl_user_black
-- ----------------------------
DROP TABLE IF EXISTS `ccl_user_black`;
CREATE TABLE `ccl_user_black` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `brand_id` int(10) unsigned NOT NULL DEFAULT '0',
  `brand_name` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0白名单，1黑名单',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ccl_user_black
-- ----------------------------
INSERT INTO `ccl_user_black` VALUES ('2', '37', '8', '台湾南亚', '0');
