ALTER TABLE `cmf_auth_rule` MODIFY COLUMN `app` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则所属module' AFTER `status`;

-- 活动标签表新增 tag_type 字段：区分固定分类(1)和动态主题(2)
ALTER TABLE `cmf_active_tab` ADD COLUMN `tag_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '标签类型：1=固定分类，2=动态主题' AFTER `is_fixed`;
-- 已有存量标签默认为固定分类(tag_type=1)，如需将某些标签设为动态主题，请手动更新：
-- UPDATE `cmf_active_tab` SET `tag_type` = 2 WHERE `name` IN ('净美七贤岭', '金秋开学季');