-- 社区闲置改为居民间人民币自助交易（MySQL 5.7）
-- 支持生产库尚未建过社区闲置表的情况。
CREATE TABLE IF NOT EXISTS `cmf_secondhand_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '发布者 member.id',
  `title` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  `images` text COMMENT 'JSON 相对路径数组，最多6张',
  `point_price` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'legacy only',
  `price` decimal(9,2) unsigned NOT NULL DEFAULT 0.00 COMMENT '人民币售价',
  `contact` varchar(40) NOT NULL DEFAULT '' COMMENT '发布者联系方式',
  `scope_type` varchar(16) NOT NULL DEFAULT 'all' COMMENT 'community/parent/all',
  `scope_id` int(10) unsigned NOT NULL DEFAULT 0,
  `category` varchar(32) NOT NULL DEFAULT 'other',
  `condition` varchar(32) NOT NULL DEFAULT 'used',
  `area_text` varchar(120) NOT NULL DEFAULT '',
  `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0待审1在售2预订3关闭4拒绝',
  `reject_reason` varchar(200) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  `audit_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status_time` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='社区闲置物品';

CREATE TABLE IF NOT EXISTS `cmf_secondhand_intent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL DEFAULT 0,
  `buyer_user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `seller_user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `point_price` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT 'legacy only',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  `complete_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  KEY `idx_buyer` (`buyer_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='历史社区闲置积分意向';

-- 再执行兼容旧表的字段升级。
SET @db := DATABASE();
SET @has_price := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cmf_secondhand_item' AND COLUMN_NAME='price');
SET @sql := IF(@has_price=0, 'ALTER TABLE `cmf_secondhand_item` ADD COLUMN `price` decimal(9,2) unsigned NOT NULL DEFAULT 0.00 COMMENT ''人民币售价'' AFTER `point_price`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_contact := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cmf_secondhand_item' AND COLUMN_NAME='contact');
SET @sql := IF(@has_contact=0, 'ALTER TABLE `cmf_secondhand_item` ADD COLUMN `contact` varchar(40) NOT NULL DEFAULT '''' COMMENT ''发布者联系方式'' AFTER `price`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_scope_type := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cmf_secondhand_item' AND COLUMN_NAME='scope_type');
SET @sql := IF(@has_scope_type=0, 'ALTER TABLE `cmf_secondhand_item` ADD COLUMN `scope_type` varchar(16) NOT NULL DEFAULT ''all'' COMMENT ''community小区/parent社区/all七贤岭'' AFTER `contact`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_scope_id := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cmf_secondhand_item' AND COLUMN_NAME='scope_id');
SET @sql := IF(@has_scope_id=0, 'ALTER TABLE `cmf_secondhand_item` ADD COLUMN `scope_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''小区或社区ID'' AFTER `scope_type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 旧商品按积分定价，不能安全换算成人民币；先下架，避免被展示成 0 元。
UPDATE `cmf_secondhand_item` SET `status`=3, `update_time`=UNIX_TIMESTAMP()
WHERE `status` IN (1,2) AND `price`=0 AND `contact`='';

-- 注册原有后台审核页菜单；旧积分交换意向页不再挂菜单。
-- 与「商品列表」同级挂在「商品管理」下，勿把 parent_id 设为 Goods/index 自身 id（会导致商品列表只能展开、进不了列表页）
INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Goods' AND `action`='index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Goods' AND `action`='index' LIMIT 1
    )), 0) + 5,
    'admin','SecondhandItem','index','','社区闲置审核','','居民发布信息审核'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Goods' AND `action`='index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='SecondhandItem' AND `action`='index' LIMIT 1);

UPDATE `cmf_admin_menu` sh
INNER JOIN `cmf_admin_menu` g ON g.`app`='admin' AND g.`controller`='Goods' AND g.`action`='index'
SET sh.`parent_id` = g.`parent_id`
WHERE sh.`app`='admin' AND sh.`controller`='SecondhandItem' AND sh.`action`='index'
  AND sh.`parent_id` = g.`id`;
