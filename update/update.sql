ALTER TABLE `cmf_auth_rule` MODIFY COLUMN `app` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则所属module' AFTER `status`;

-- 活动标签表新增 tag_type 字段：区分固定分类(1)和动态主题(2)
ALTER TABLE `cmf_active_tab` ADD COLUMN `tag_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '标签类型：1=固定分类，2=动态主题' AFTER `is_fixed`;

-- 商品兑换前置签到（require_active_id：用户在对应活动 active_clock 至少 1 次后可兑换）
ALTER TABLE `cmf_goods` ADD COLUMN `require_active_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '兑换需完成该签到活动至少1次签到，0无限制' AFTER `point`;

-- 活动形态：1正常活动 2线下活动（定位/口令签到）
ALTER TABLE `cmf_active` ADD COLUMN `event_kind` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1正常活动 2线下活动' AFTER `join_type`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_start` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '线下允许签到开始时间戳' AFTER `event_kind`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_end` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '线下允许签到结束时间戳' AFTER `offline_sign_start`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_password` varchar(32) NOT NULL DEFAULT '' COMMENT '线下口令签到兜底' AFTER `offline_sign_end`;
ALTER TABLE `cmf_active` ADD COLUMN `is_date_pending` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1活动时间待定' AFTER `date`;

-- 旧版独立签到菜单改名（幂等：已是新名则不再改）
UPDATE `cmf_admin_menu` SET `name` = '签到（已弃用）'
WHERE `app` = 'admin' AND `controller` = 'active' AND `action` = 'sign' AND `name` = '签到';
-- 已有存量标签默认为固定分类(tag_type=1)，如需将某些标签设为动态主题，请手动更新：
-- UPDATE `cmf_active_tab` SET `tag_type` = 2 WHERE `name` IN ('净美七贤岭', '金秋开学季');

-- 垃圾打卡投放场景（家庭/亲子/沿街/真分角等）
ALTER TABLE `cmf_forum` ADD COLUMN `clock_scene` varchar(50) NOT NULL DEFAULT '' COMMENT '打卡投放场景' AFTER `subject_community_id`;

-- 垃圾打卡教程（后台「垃圾分类活动-垃圾打卡-打卡教程」）
-- 小手拉大手活动分类（后台「小手大手分类」下拉）
ALTER TABLE `cmf_active` ADD COLUMN `xsdld_cate_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '小手拉大手分类' AFTER `community_cate_id`;
ALTER TABLE `cmf_active_community_cate` ADD COLUMN `cate_group` varchar(20) NOT NULL DEFAULT '' COMMENT 'qxl=七贤岭 xsdld=小手拉大手' AFTER `name`;

CREATE TABLE IF NOT EXISTS `cmf_clock_tutorial` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cate_key` varchar(20) NOT NULL DEFAULT '' COMMENT 'recycle/other/harm/food',
  `title` varchar(100) NOT NULL DEFAULT '',
  `intro` varchar(500) NOT NULL DEFAULT '',
  `examples` varchar(500) NOT NULL DEFAULT '',
  `photo_lines` text COMMENT '拍照要求，每行一条',
  `notice_lines` text COMMENT '注意事项，每行一条',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `list_order` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cate_key` (`cate_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='垃圾打卡页教程';