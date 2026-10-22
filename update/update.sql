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

-- 活动 Tab 专题分会场：hiking / flea_market / zhenfen_class（空=非专题分会场）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'spot_key');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `spot_key` varchar(32) NOT NULL DEFAULT '''' COMMENT ''活动专题分会场 hiking/flea_market/zhenfen_class'' AFTER `xsdld_cate_id`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 我和七贤岭页主推位：moment_100=「100个瞬间」（全站仅一项；学考为固定入口不占此字段）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'qxl_hub_slot');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `qxl_hub_slot` varchar(32) NOT NULL DEFAULT '''' COMMENT ''我和七贤岭页位 moment_100'' AFTER `spot_key`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

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

-- 活动场景：用户自定义真分点（家庭/亲子/沿街，每用户独立）
CREATE TABLE IF NOT EXISTS `cmf_clock_scene_point` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hub_key` varchar(20) NOT NULL DEFAULT '' COMMENT 'home/qinzi/street',
  `point_name` varchar(32) NOT NULL DEFAULT '' COMMENT '真分点名称，如厨房桶',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'basic_select type=2',
  `cate_name` varchar(32) NOT NULL DEFAULT '' COMMENT '种类名称冗余',
  `list_order` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user_hub` (`user_id`,`hub_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景真分点（用户级）';

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_forum' AND COLUMN_NAME = 'clock_point_id');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_forum` ADD COLUMN `clock_point_id` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''场景真分点id'' AFTER `clock_sub_scene`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_clock_scene_point' AND COLUMN_NAME = 'source');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_clock_scene_point` ADD COLUMN `source` tinyint(1) NOT NULL DEFAULT ''0'' COMMENT ''0用户自建 1后台配置'' AFTER `cate_name`, ADD COLUMN `remark` varchar(255) NOT NULL DEFAULT '''' COMMENT ''商铺性质/备注'' AFTER `source`, ADD COLUMN `admin_id` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''后台操作人'' AFTER `remark`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1
    )), 0) + 1,
    'admin','Forum','clock_scene_point_index','','场景回收点配置','','沿街/办公统一回收点（按用户人工配置）'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_point_index' LIMIT 1);

-- 分类学习 cmf_waste：追加真分说明/为什么/怎么做/收获 四条（可重复执行见 data/seed_waste_zhenfen_guides.php）；小程序 get_waste_list 按 create_time desc 展示

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_clock_scene_point' AND COLUMN_NAME = 'point_image');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_clock_scene_point` ADD COLUMN `point_image` varchar(255) NOT NULL DEFAULT '''' COMMENT ''真分点环境照片'' AFTER `cate_name`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 活动显示形式：1配置式 2Web式（H5 + 小程序 web-view）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'display_mode');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `display_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT ''1配置式 2Web式'' AFTER `join_type`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'web_url');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `web_url` varchar(500) NOT NULL DEFAULT '''' COMMENT ''Web式活动H5完整URL'' AFTER `display_mode`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- Web 式活动绑定的选择题库与答题记录
CREATE TABLE IF NOT EXISTS `cmf_active_web_quiz_question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '活动ID',
  `title` varchar(500) NOT NULL DEFAULT '' COMMENT '题干',
  `option_a` varchar(255) NOT NULL DEFAULT '',
  `option_b` varchar(255) NOT NULL DEFAULT '',
  `option_c` varchar(255) NOT NULL DEFAULT '',
  `option_d` varchar(255) NOT NULL DEFAULT '',
  `correct_option` char(1) NOT NULL DEFAULT 'A' COMMENT 'A/B/C/D',
  `explain_text` varchar(800) NOT NULL DEFAULT '' COMMENT '答案简要解释',
  `list_order` int(10) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web式活动选择题库';

CREATE TABLE IF NOT EXISTS `cmf_active_web_quiz_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `correct_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `total_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `is_perfect` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `point_awarded` decimal(10,2) NOT NULL DEFAULT 0.00,
  `detail` text COMMENT 'JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_active` (`user_id`,`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web式活动答题记录';

-- Web 式活动：七贤岭影像分享（照片 / 点赞 / 周报）
CREATE TABLE IF NOT EXISTS `cmf_active_web_photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `image` varchar(500) NOT NULL DEFAULT '',
  `intro` varchar(500) NOT NULL DEFAULT '',
  `category` varchar(20) NOT NULL DEFAULT 'other' COMMENT 'humanity/scenery/food/other',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审1通过2拒绝',
  `like_count` int(10) unsigned NOT NULL DEFAULT 0,
  `period_key` varchar(16) NOT NULL DEFAULT '' COMMENT '周期如2026-W13',
  `point_awarded` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus_point_awarded` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '高赞额外积分',
  `share_token` varchar(32) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `audit_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active_status` (`active_id`,`status`),
  KEY `idx_user_day` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web活动用户上传照片';

CREATE TABLE IF NOT EXISTS `cmf_active_web_photo_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `photo_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_photo_user` (`photo_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web活动照片点赞';

CREATE TABLE IF NOT EXISTS `cmf_active_web_photo_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `period_key` varchar(16) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary_html` mediumtext,
  `cover_image` varchar(500) NOT NULL DEFAULT '',
  `photo_ids` varchar(500) NOT NULL DEFAULT '' COMMENT 'JSON id列表',
  `share_token` varchar(32) NOT NULL DEFAULT '',
  `is_home_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1活动首页置顶周报',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active_period` (`active_id`,`period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web活动周期影像周报';

-- 七贤岭绿色二手集市
CREATE TABLE IF NOT EXISTS `cmf_flea_market_session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `title` varchar(120) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `date_text` varchar(64) NOT NULL DEFAULT '',
  `brief` varchar(500) NOT NULL DEFAULT '',
  `start_time` int(10) unsigned NOT NULL DEFAULT 0,
  `end_time` int(10) unsigned NOT NULL DEFAULT 0,
  `list_order` int(10) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), KEY `idx_active` (`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_flea_market_vendor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `session_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `contact_name` varchar(64) NOT NULL DEFAULT '',
  `contact_mobile` varchar(20) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `audit_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_session_user` (`session_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cmf_flea_market_highlight` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `session_id` int(10) unsigned NOT NULL DEFAULT 0,
  `vendor_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `image` varchar(500) NOT NULL DEFAULT '',
  `price_text` varchar(120) NOT NULL DEFAULT '',
  `exchange_text` varchar(120) NOT NULL DEFAULT '',
  `point_spent` decimal(10,2) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Web 式活动不参与配置式「线下活动」签到，历史误标 event_kind=2 的纠正为正常活动
UPDATE `cmf_active` SET `event_kind` = 1 WHERE `display_mode` = 2 AND `event_kind` = 2 AND `delete_time` = 0;

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'rules_url');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `rules_url` varchar(500) NOT NULL DEFAULT '''' COMMENT ''活动规则独立页URL'' AFTER `web_url`');
PREPARE stmt FROM @__sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'safety_url');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `safety_url` varchar(500) NOT NULL DEFAULT '''' COMMENT ''安全注意事项独立页URL'' AFTER `rules_url`');
PREPARE stmt FROM @__sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 活动：是否周期性（结束后不进风采，详情提示等待下期）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_active' AND COLUMN_NAME = 'is_recurring');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_active` ADD COLUMN `is_recurring` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''1周期性活动'' AFTER `is_date_pending`');
PREPARE stmt FROM @__sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 活动风采：关联来源活动（自动归档）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_activity_showcase' AND COLUMN_NAME = 'active_id');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_activity_showcase` ADD COLUMN `active_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''来源活动ID，0为手工'' AFTER `id`');
PREPARE stmt FROM @__sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 活动时间待定：用户「感兴趣」（类似上映前「想看」）
CREATE TABLE IF NOT EXISTS `cmf_active_interest` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_active_user` (`active_id`,`user_id`),
  KEY `idx_active_id` (`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='活动感兴趣（时间待定）';

-- 沿街商铺：统一投放点 + 用户认领商户身份
CREATE TABLE IF NOT EXISTS `cmf_street_shop_drop_point` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_type` varchar(32) NOT NULL DEFAULT '' COMMENT '商铺类型 key，同类型可共用本点',
  `shop_type_name` varchar(32) NOT NULL DEFAULT '' COMMENT '类型名称冗余',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '小程序展示名称',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址说明',
  `lng` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `lat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'basic_select type=2',
  `cate_name` varchar(32) NOT NULL DEFAULT '',
  `list_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_shop_type_status` (`shop_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='沿街统一投放点';

CREATE TABLE IF NOT EXISTS `cmf_street_shop_merchant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `shop_name` varchar(64) NOT NULL DEFAULT '' COMMENT '用户认领的商铺名',
  `shop_type` varchar(32) NOT NULL DEFAULT '',
  `shop_type_name` varchar(32) NOT NULL DEFAULT '',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cate_name` varchar(32) NOT NULL DEFAULT '',
  `drop_point_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user` (`user_id`),
  KEY `idx_drop_point` (`drop_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='沿街商铺用户认领';

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1
    )), 0) + 2,
    'admin','Forum','street_shop_drop_point_index','','沿街统一投放点','','按商铺类型配置经纬度与地址'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='street_shop_drop_point_index' LIMIT 1);

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1
    )), 0) + 3,
    'admin','Forum','street_shop_merchant_index','','沿街认领商户','','用户认领商铺列表'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='street_shop_merchant_index' LIMIT 1);

SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_clock_scene_point' AND COLUMN_NAME = 'community_id');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_clock_scene_point` ADD COLUMN `community_id` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''所属社区(办公统一回收点)'' AFTER `user_id`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

UPDATE `cmf_admin_menu` SET `name` = '沿街回收点配置', `remark` = '沿街商铺统一回收点（按用户人工配置）'
WHERE `app` = 'admin' AND `controller` = 'Forum' AND `action` = 'clock_scene_point_index' LIMIT 1;

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1
    )), 0) + 1,
    'admin','Forum','office_recycle_building_index','','办公大楼回收点','','社区办公大楼、回收点地址与示意图'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_scene_guide_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='office_recycle_building_index' LIMIT 1);

UPDATE `cmf_clock_scene_point` SET `community_id` = 0
WHERE `hub_key` = 'office' AND `source` = 1 AND `community_id` <> 0;

UPDATE `cmf_admin_menu` SET `remark` = '办公大楼、回收点地址与示意图（全站共用）'
WHERE `app` = 'admin' AND `controller` = 'Forum' AND `action` = 'office_recycle_building_index' LIMIT 1;

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `parent_id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1),
    1, 1,
    IFNULL((SELECT `list_order` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1), 0) - 1,
    'admin','Forum','user_clock_scene_point_index','','用户点位布置','','家庭/沿街用户小程序上传的布置点位'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='clock_tutorial_index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Forum' AND `action`='user_clock_scene_point_index' LIMIT 1);

-- 场景真分点：布置照片积分（后台用户点位布置列表展示）
SET @__col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_clock_scene_point' AND COLUMN_NAME = 'reward_point');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'ALTER TABLE `cmf_clock_scene_point` ADD COLUMN `reward_point` decimal(10,2) NOT NULL DEFAULT ''0.00'' COMMENT ''布置积分已发放数量'' AFTER `point_image`, ADD COLUMN `reward_log_id` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''对应cmf_user_account_log.id'' AFTER `reward_point`, ADD COLUMN `reward_time` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''布置积分发放时间'' AFTER `reward_log_id`');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 七贤岭学考：按板块通关（每模块随机抽 3 题）
SET @__col := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cmf_qxl_module_log');
SET @__sql := IF(@__col > 0, 'SELECT 1', 'CREATE TABLE `cmf_qxl_module_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `category` varchar(50) NOT NULL DEFAULT '''' COMMENT ''模块名：人文/地理等'',
  `total_count` int(11) unsigned NOT NULL DEFAULT 0,
  `correct_count` int(11) unsigned NOT NULL DEFAULT 0,
  `passed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `point` int(11) unsigned NOT NULL DEFAULT 0,
  `detail` text COMMENT ''最近一次提交详情JSON，过关后不再写入'',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_category` (`user_id`,`category`),
  KEY `passed` (`passed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=''七贤岭学考模块通关记录''');
PREPARE __s FROM @__sql; EXECUTE __s; DEALLOCATE PREPARE __s;

-- 社区闲置（积分权益 Tab · 二手交换）
CREATE TABLE IF NOT EXISTS `cmf_secondhand_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '发布者 member.id',
  `title` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  `images` text COMMENT 'JSON 相对路径数组，最多6张',
  `point_price` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'legacy only; community sales use RMB price',
  `price` decimal(9,2) unsigned NOT NULL DEFAULT 0.00 COMMENT '人民币售价，由买卖双方自行交易',
  `contact` varchar(40) NOT NULL DEFAULT '' COMMENT '发布者联系方式',
  `scope_type` varchar(16) NOT NULL DEFAULT 'all' COMMENT 'community小区/parent社区/all七贤岭',
  `scope_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '小区或社区ID，all为0',
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
  `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0登记1待交割2完成3取消',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  `complete_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  KEY `idx_buyer` (`buyer_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='社区闲置交换意向';

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

CREATE TABLE IF NOT EXISTS `cmf_active_web_one_plus_one_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `cycle_no` int(10) unsigned NOT NULL DEFAULT 1,
  `cycle_start` int(10) unsigned NOT NULL DEFAULT 0,
  `anchor_day1_at` int(10) unsigned NOT NULL DEFAULT 0,
  `deadline_at` int(10) unsigned NOT NULL DEFAULT 0,
  `completed_days` varchar(32) NOT NULL DEFAULT '',
  `zhenfen_map` text COMMENT 'JSON: day=>forum_id',
  `day_done_at` text COMMENT 'JSON: day=>完成时间戳',
  `share_ack_at` int(10) unsigned NOT NULL DEFAULT 0,
  `reward_300_at` int(10) unsigned NOT NULL DEFAULT 0,
  `reward_800_at` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_active` (`user_id`,`active_id`),
  KEY `idx_active` (`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='1+1 Web活动用户进度';

CREATE TABLE IF NOT EXISTS `cmf_active_web_one_plus_one_harmful_clock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `cycle_no` int(10) unsigned NOT NULL DEFAULT 1,
  `image` varchar(255) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active_user` (`active_id`,`user_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='1+1活动第1天有害垃圾Web打卡';

CREATE TABLE IF NOT EXISTS `cmf_active_web_one_plus_one_day1_pre_learn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `cycle_no` int(10) unsigned NOT NULL DEFAULT 1,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active_user_cycle` (`active_id`,`user_id`,`cycle_no`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='1+1活动第1天真分前置学习完成记录';

CREATE TABLE IF NOT EXISTS `cmf_active_web_one_plus_one_day3_quiz` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `cycle_no` int(10) unsigned NOT NULL DEFAULT 1,
  `passed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `detail` text COMMENT 'JSON: 每题选项与解析',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_active_user_cycle` (`active_id`,`user_id`,`cycle_no`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='1+1活动第3天H5答题记录';

INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT
    (SELECT `id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='ActiveController' AND `action`='index' LIMIT 1),
    1, 1,
    IFNULL((SELECT MAX(`list_order`) FROM `cmf_admin_menu` m WHERE m.`parent_id` = (
        SELECT `id` FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='ActiveController' AND `action`='index' LIMIT 1
    )), 0) + 8,
    'admin','ActiveOnePlusOneHarmful','index','','1+1有害垃圾打卡','','1+1活动第1天Web打卡记录'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='ActiveController' AND `action`='index' LIMIT 1)
  AND NOT EXISTS (SELECT 1 FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='ActiveOnePlusOneHarmful' AND `action`='index' LIMIT 1);

CREATE TABLE IF NOT EXISTS `cmf_active_web_zhenfen_gift_claim` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `active_id` int(10) unsigned NOT NULL DEFAULT 0,
  `goods_id` int(10) unsigned NOT NULL DEFAULT 0,
  `order_num` varchar(32) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_active` (`user_id`,`active_id`),
  KEY `idx_active` (`active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='三次真分 Web 活动兑换记录';
