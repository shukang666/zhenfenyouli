ALTER TABLE `cmf_auth_rule` MODIFY COLUMN `app` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则所属module' AFTER `status`;

-- 活动标签表新增 tag_type 字段：区分固定分类(1)和动态主题(2)
ALTER TABLE `cmf_active_tab` ADD COLUMN `tag_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '标签类型：1=固定分类，2=动态主题' AFTER `is_fixed`;

-- 商品兑换前置签到（require_active_id：用户在对应活动 active_clock 至少 1 次后可兑换）
-- 若报 #1060 Duplicate column，说明字段已加过，可忽略
SET @__goods_req_active := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_goods' AND COLUMN_NAME = 'require_active_id'
);
SET @__sql_goods_req_active := IF(
  @__goods_req_active > 0,
  'SELECT ''cmf_goods.require_active_id already exists'' AS msg',
  'ALTER TABLE `cmf_goods` ADD COLUMN `require_active_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''兑换需完成该签到活动至少1次签到，0无限制'' AFTER `point`'
);
PREPARE __stmt_goods_req_active FROM @__sql_goods_req_active;
EXECUTE __stmt_goods_req_active;
DEALLOCATE PREPARE __stmt_goods_req_active;

-- 活动形态：1正常活动 2线下活动（定位/口令签到）
ALTER TABLE `cmf_active` ADD COLUMN `event_kind` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1正常活动 2线下活动' AFTER `join_type`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_start` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '线下允许签到开始时间戳' AFTER `event_kind`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_end` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '线下允许签到结束时间戳' AFTER `offline_sign_start`;
ALTER TABLE `cmf_active` ADD COLUMN `offline_sign_password` varchar(32) NOT NULL DEFAULT '' COMMENT '线下口令签到兜底' AFTER `offline_sign_end`;
ALTER TABLE `cmf_active` ADD COLUMN `is_date_pending` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1活动时间待定' AFTER `date`;

-- 旧版独立签到菜单改名（幂等：已是新名则不再改）
UPDATE `cmf_admin_menu` SET `name` = '签到（已弃用）'
WHERE `app` = 'admin' AND `controller` = 'active' AND `action` = 'sign' AND `name` = '签到';
-- 活动多日打卡挑战（社区活动 type=1，勾选后配置连续天数与达成奖励积分）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'is_clock_challenge');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `is_clock_challenge` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''1开启多日打卡挑战'' AFTER `is_clock`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'clock_challenge_days');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `clock_challenge_days` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''连续打卡目标天数'' AFTER `is_clock_challenge`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'clock_challenge_point');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `clock_challenge_point` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''达成挑战一次性奖励积分'' AFTER `clock_challenge_days`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active_sign' AND COLUMN_NAME = 'clock_challenge_rewarded');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active_sign` ADD COLUMN `clock_challenge_rewarded` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''多日挑战奖励已发放'' AFTER `end_time`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

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

-- 打卡教程改为富文本（后台 UEditor，小程序 u-parse 展示，可插图）
ALTER TABLE `cmf_clock_tutorial` ADD COLUMN `content` mediumtext COMMENT '教程富文本HTML' AFTER `title`;

-- 打卡教程按场景维度（cate_key 字段存 home/qinzi/street/zhenfenjiao，与垃圾种类解耦）
ALTER TABLE `cmf_clock_tutorial` MODIFY COLUMN `cate_key` varchar(20) NOT NULL DEFAULT '' COMMENT '场景 home/qinzi/street/zhenfenjiao';
UPDATE `cmf_clock_tutorial` SET `cate_key`='home' WHERE `cate_key`='recycle';
UPDATE `cmf_clock_tutorial` SET `cate_key`='qinzi' WHERE `cate_key`='harm';
UPDATE `cmf_clock_tutorial` SET `cate_key`='street' WHERE `cate_key`='food';
UPDATE `cmf_clock_tutorial` SET `cate_key`='zhenfenjiao' WHERE `cate_key`='other';

-- 活动打卡记录：经验分享（小程序打卡抽屉选填）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active_clock' AND COLUMN_NAME = 'experience_share');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active_clock` ADD COLUMN `experience_share` varchar(500) NOT NULL DEFAULT '''' COMMENT ''打卡经验分享'' AFTER `image_url`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 活动风采：正文富文本（UEditor）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_activity_showcase' AND COLUMN_NAME = 'content');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_activity_showcase` ADD COLUMN `content` mediumtext COMMENT ''活动正文富文本'' AFTER `description`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 活动页场景分类引导（子场景 + 种类 + 处理指引/问卷）
CREATE TABLE IF NOT EXISTS `cmf_clock_scene_guide` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hub_key` varchar(20) NOT NULL DEFAULT '' COMMENT 'home/qinzi/office/street',
  `sub_key` varchar(32) NOT NULL DEFAULT '' COMMENT '子场景键，空表示无',
  `cate_label` varchar(32) NOT NULL DEFAULT '' COMMENT '可回收/厨余/有害/不可回收',
  `handle_guide` text COMMENT '如何处理',
  `dispose_time` varchar(255) NOT NULL DEFAULT '' COMMENT '建议投放时间',
  `survey_question` varchar(255) NOT NULL DEFAULT '' COMMENT '问卷问题',
  `preset_tips` text COMMENT '推荐技巧 JSON 或每行一条',
  `learn_path` varchar(255) NOT NULL DEFAULT '/pages/flhd/list' COMMENT '学习更多跳转',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `list_order` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hub_sub_cate` (`hub_key`,`sub_key`,`cate_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景分类引导配置';

CREATE TABLE IF NOT EXISTS `cmf_clock_scene_survey_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hub_key` varchar(20) NOT NULL DEFAULT '',
  `sub_key` varchar(32) NOT NULL DEFAULT '',
  `cate_label` varchar(32) NOT NULL DEFAULT '',
  `user_choice` varchar(500) NOT NULL DEFAULT '',
  `custom_tip` varchar(500) NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景引导问卷记录';

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_forum' AND COLUMN_NAME = 'clock_sub_scene');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_forum` ADD COLUMN `clock_sub_scene` varchar(50) NOT NULL DEFAULT '''' COMMENT ''打卡子场景'' AFTER `clock_scene`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 后台菜单：场景分类引导（挂在与「打卡教程」相同父级下）
INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1
    )), 0) + 1,
    'admin','Forum','clock_scene_guide_index','','场景分类引导','','活动页场景入口引导文案'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1);

-- 督导监测：投放点/整改后/收运链条支持多图（历史单 URL 无需迁移）
SET @__tbl := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_supervision_monitor');
SET @__sql := IF(@__tbl > 0,
  'ALTER TABLE `cmf_supervision_monitor`
    MODIFY COLUMN `point_photo` text COMMENT ''投放点照片，单 URL 或 JSON 数组'',
    MODIFY COLUMN `point_rectify_photo` text COMMENT ''投放点整改后，单 URL 或 JSON 数组'',
    MODIFY COLUMN `transport_photo` text COMMENT ''收运链条照片，单 URL 或 JSON 数组''',
  'SELECT 1');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;