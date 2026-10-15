---
name: qixianling-learn-exam
description: 七贤岭学考融合（边读边测、过关解锁下一篇）。改「我和七贤岭」入口、篇章题目绑定、通关进度或学考页时读。
---

# 七贤岭学考融合

和「学会分类知识」「分类知识挑战」「学分类打卡门禁」都不是同一条链路。文章表、题库、进度表都带 `qxl_` 前缀，控制器是 `QxlKnowledge` / `QxlQuiz`。

## 产品规则

- 入口：`pages/activity/love-qixianling` 里「认识七贤岭」（分类 id=6）**一张**「七贤岭学考」卡片，不再拆成学习和考试两个入口。
- 学考页：`pages/activity/qxl-learn-exam`。按 `cmf_qxl_knowledge.list_order, id` 排篇章：先读本篇正文，文下考**本篇绑定的题**；**全对才过关**，才解锁下一篇；错了对照原文立刻重考。
- 积分：本篇**首次过关**按题 ×2 分（`AccountLogModel` type=201，remark 带文章标题）。已过关再进只回看，不再发分。
- 旧页 `qxl-knowledge` / `qxl-quiz` 的 `onLoad` 会 `redirectTo` 到学考页；旧每日考接口 `get_quiz` / `submit_quiz` / `check_today` 仍在，**不要再接新入口**。
- 题目必须绑 `knowledge_id`。没绑定的题不会出现在学考路径里。

## 表

```
cmf_qxl_knowledge     学习文章（结构近似 cmf_knowledge）
cmf_qxl_quiz_question 题库；knowledge_id 关联文章，0=未绑定
cmf_qxl_chapter_log   单篇通关  UNIQUE(user_id, knowledge_id)
                      passed=1 才算过关；可反复考，行会 update
cmf_qxl_quiz_log      旧版「每日随机 5 题」记录，学考融合不再写它
```

加 `knowledge_id`、建 `cmf_qxl_chapter_log` 必须在生产库执行（本机 `.env` 已连生产则改一次即生效）。MySQL 5.7 没有 `ADD COLUMN IF NOT EXISTS`。

## 改哪一端

| 端 | 文件 |
|----|------|
| 枢纽入口 | `小程序/pages/activity/love-qixianling.vue` |
| 学考页 | `小程序/pages/activity/qxl-learn-exam.vue` |
| 路由 / API 注册 | `小程序/pages.json`（分包 `pages/activity`）、`小程序/api/wxapp.js` |
| 路径+正文+题目 | `api/wxapp/controller/QxlKnowledgeController.php` → `get_learn_path` |
| 提交本篇 | `api/wxapp/controller/QxlQuizController.php` → `submit_chapter` |
| 后台文章 | `app/admin/controller/QxlKnowledgeController.php` |
| 后台题库 | `app/admin/controller/QxlQuizController.php` + `public/themes/admin_fengiy/admin/qxl_quiz/`（出题必选所属文章） |

## 接口

`GET /wxapp/qxl_knowledge/get_learn_path`

- 可不登录：第一篇解锁，进度全是未过关。
- 已过关/当前篇带 `content`；**仅未过关的当前篇**带 `questions`（不含 `correct_option`）；未解锁不给正文和题。
- 返回 `chapters, passed_count, total_count, all_passed, current_id`。

`POST /wxapp/qxl_quiz/submit_chapter`  body：`{ knowledge_id, answers: [{qid, answer}] }`

- 必须登录（`checkUser`）。
- **先读一次** `php://input` 再取 `knowledge_id` 和 `answers`（json body 不能读两次）。
- 校验：文章存在、上一篇已过关、题目都属于该 `knowledge_id`、题数答全。
- 全对 `passed=1`；未全对也可写 log（`passed=0`），前端提示对照原文重选。

前端：

```js
getQxlLearnPath: () => uni.$u.get('/wxapp/qxl_knowledge/get_learn_path'),
submitQxlChapter: (data) => uni.$u.post('/wxapp/qxl_quiz/submit_chapter', data),
```

`answers` **直接传数组**，不要再 `JSON.stringify` 一层。

## 前端注意

- 学考页禁止模板内联赋值（`@click="x = false"`），选项用 `selectAnswer(chapterId, qid, key)`；`answersMap` / `expandedMap` 初值 `{}`。
- 已过关篇章默认收起，点标题回看；进行中展开正文 + 测验。
- 枢纽卡片进度来自同一 `get_learn_path`，在 `love-qixianling` 的 `onShow` 里拉。

## 排查

| 现象 | 原因 |
|------|------|
| 学考页空白 / 接口 404 | 控制器没部署到服务器 |
| 某篇没有测验 | 题库 `knowledge_id` 未绑这篇，或 `status≠1` |
| 下一篇一直锁 | 本篇未全对；或 `cmf_qxl_chapter_log.passed` 仍是 0 |
| 提交「答案为空」 | json body 被读两次，或前端把 answers 又 stringify 了 |
| 积分重复发 | 只应在该篇首次 `passed=1` 且此前 point=0 时发；不要改回每日考入口 |
| 点入口仍进两个旧页 | 小程序未重新编译；旧页应 redirect 到 `qxl-learn-exam` |
