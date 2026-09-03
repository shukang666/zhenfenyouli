-- 对冲旧SQL并修复菜单结构
-- 1. 删除上次残留的旧菜单项（parent_id=0 的一级菜单，action=delete_log）
DELETE FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log';

-- 2. 插入正确的菜单项（挂在健康生活社区下，与垃圾打卡同级）
INSERT INTO `cmf_admin_menu` (`parent_id`, `app`, `controller`, `action`, `param`, `name`, `status`, `list_order`)
SELECT 
    (SELECT id FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='default' LIMIT 1) AS parent_id,
    'admin', 'forum', 'delete_log_index', '', '删除记录', 1, 100 
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT 1 FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log_index') t
);
