---
name: project-map
description: "xiayuxuan.com 三端代码位置决策与项目总览。ThinkCMF6 二次开发，不是标准前后端分离。改代码、修 bug、加功能、调页面/接口、排查问题时必须先用本 skill 判断改小程序前端、小程序接口还是 PC 后台；并据此选择对应领域 skill。也用于理解垃圾分类打卡、活动、积分、督导、知识学习等业务边界。"
---

# 项目地图（先选端，再选 skill）

本项目是 **ThinkCMF6 / ThinkPHP6 二次开发**，一个仓库里三套代码，**互不混改**。

| 端 | 技术栈 | 根目录 | 说明 |
|----|--------|--------|------|
| 小程序前端 | uni-app + Vue + uView | `小程序/` | 独立 uni-app 工程 |
| 小程序接口 | ThinkPHP 6 API | `api/wxapp/` | REST，PHP |
| PC 管理后台 | ThinkPHP 服务端渲染 | `app/admin/` + `public/themes/admin_fengiy/` | 模板引擎，不是 SPA |

## 判定流程

1. 小程序里出问题，还是浏览器 `/admin/` 出问题？
2. 前端还是后端？
   - 小程序：UI / 交互 / 拼参 / 本地存储 → `小程序/`；数据 / 规则 / 返回值 / DB → `api/wxapp/`
   - PC：页面渲染 / UI / 分页条 → 模板；列表 / 搜索 / 导出 / 业务 → `app/admin/`
3. 在对应目录找同类已有实现，**不要引入新架构**。

## 目录速查

**小程序前端** `小程序/`：`pages/`、`components/`、`api/*.js`、`common/`、`utils/`、`pages.json`

**小程序接口** `api/wxapp/`：`controller/`、`model/`、`validate/`

**PC 后台**：控制器 `app/admin/controller/`（多继承 `ToolController`）、模型 `app/admin/model/`、模板 `public/themes/admin_fengiy/admin/<控制器>/<动作>.html`、路由 `data/route/route.php`、菜单表 `cmf_admin_menu`

## 改哪一端

| 症状 | 改哪里 |
|------|--------|
| 小程序 UI / 交互 / 路由 / 本地存储 | `小程序/` |
| 小程序 404/500、字段缺失、积分订单库存签到计算错 | `api/wxapp/` |
| PC `/admin/...` 页面 | 模板 `public/themes/admin_fengiy/` |
| PC 列表搜索导出分页 | `app/admin/controller/` |
| PC 菜单权限 | DB `cmf_admin_menu` → 读 `pc-admin` |

## 禁止混改

| 错误 | 正确 |
|------|------|
| 数据不对先改 `.vue` | 先看接口，再改 `api/wxapp/` |
| UI 错去改接口 | 改 `小程序/` |
| PC 问题去改小程序 | 改后台模板/控制器 |
| 小程序直连 DB | 只能调 `api/wxapp/` |
| 在 `api/wxapp` 用 `ToolController` 的 setWhere/setIndex | 那是 PC 后台基类 |
| 把后台模板当 Vue/React 写 | 用 `{:url()}` / `{$var}` / `<foreach>` / `<include>` |

## 业务一句话

社区环保小程序：五个 Tab（首页 / 活动 / 健康 / 积分权益 / 我的）。核心链路是 **学分类 → 考试 → 拍照打卡得积分**；活动可小程序参加或扫码参加；积分可兑商城；另有志愿督导、知识学习、论坛、随手拍整改。表结构与规则见 `business-data-model`。

## 整理后的 skill 目录（只保留这些入口）

改代码前按场景 **Read 对应 SKILL.md**（细节在各目录的 reference 文件里，需要再读）：

| skill | 何时读 |
|-------|--------|
| `project-map`（本文件） | 任何改动：先定端 |
| `business-overview` | 纯分段业务全景，复制给其他 AI 用 |
| `business-data-model` | 表结构、积分/用户/活动/打卡关系 |
| `dev-environment` | 本机查库、PHP 路径、PowerShell、生产库变更、临时脚本 |
| `cos-storage` | 腾讯云 COS 桶配置、local_upload_to_cos 断点同步、小程序图床域名 |
| `mp-conventions` | 小程序通用写法：uView、分包、图片 URL、render 崩溃、页面守卫 |
| `mp-home-activity` | 首页、活动 Tab、详情、扫码参加、置顶、学考打卡门禁、新手引导 |
| `mp-points-mall` | 积分权益 Tab（`mall.vue` 顶栏无悬浮钮、分类/地图、闲置扁平分包）；主文档 `mall-ui-spec.md` |
| `mp-secondhand-market` | 社区闲置 + 绿色二手市集互跳、介绍页、线下交接规则；接后端兑换前必读 |
| `mp-map-location` | 地图、定位、经纬度、腾讯地图 key |
| `pc-admin` | 后台菜单/新页/列表列/搜索导出/批量删/表单保存/删除校验/看板/更新记录 |
| `knowledge-config` | 分类知识文章、七贤岭学考融合 |
| `quiz-challenge` | 每日分类知识挑战（随机 5 题） |
| `shot-report` | 随手拍举报与后台审核（通过后发整改任务） |
| `volunteer-task` | 整改任务发布/领取/提交记录；照片 JSON、log 状态 0/1/2/3 |
| `field-worker-workpad` | 外勤 Pad 督导台 |
| `oa-portal` | 手机 OA 独立登录与工作台 |
| `ai-talk` | 免登录 public/ai_talk 群聊 + 智谱 GLM 讨论助手 |
| `cron-task-system` | 定时任务（当前多为历史参考） |
| `py-desktop-tool` | `py/` 桌面小工具 |

旧 skill 名（如 `pc-admin-menu-manage`、`mp-index-layout`）已并入上表对应目录的 markdown，不要再找独立 skill。

## 全局约定

- 面向用户的功能改动完成后，按 `pc-admin` 的 [update-log.md](../pc-admin/update-log.md) **默认写一条 `cmf_update_log`**（只改 skill/注释/临时脚本不写）。
- PHP 语法上限 7.4；MySQL 5.7；本机 `php` 不在 PATH，用法见 `dev-environment`。
- `data/sql/` 默认不被 git 跟踪，建表/菜单 SQL 必须在生产库手工执行。
