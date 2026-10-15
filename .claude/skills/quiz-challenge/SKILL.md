---
name: quiz-challenge
description: "「分类知识挑战」答题功能全栈指南。覆盖：数据库表（cmf_quiz_question/cmf_quiz_log）、PC 后台题库管理 + 答题记录（app/admin/controller/QuizController.php + 模板）、小程序端答题页（小程序/pages/dtsh/quiz.vue）、小程序 API（api/wxapp/controller/QuizController.php）、菜单与权限。规则：随机抽 5 题、每天限答 1 次、每题 2 积分（满分 10）、答完展示得分与答案解析。当需要修改答题规则、题库管理、积分发放、答题记录导出，或排查『未定义变量: data』『菜单不显示』『答题接口 500』等问题时调用。"
---

# 分类知识挑战（答题功能）全栈指南

这是垃圾分类里的 **每日随机答题**（`cmf_quiz_question` / `pages/dtsh/quiz`），不是七贤岭学考（`qxl_*`，见 `knowledge-config` 的 [qixianling.md](../knowledge-config/qixianling.md)），也不是打卡前的学考门禁。

## 0. 业务规则

- **随机组卷**：每次从启用题目中随机抽 5 道。
- **每日限答 1 次**：以 `cmf_quiz_log.user_id + quiz_date` 唯一索引兜底，同天重复提交会被拦截。
- **积分规则**：每答对 1 题得 2 积分，满分 10 积分，答错 0 分。
- **积分发放**：通过 `AccountLogModel::add_account_log(type=xxx)` 写入积分流水（具体 type 值见小程序 API 代码）。
- **答题结束**：展示答对题数、获得积分、每题正确答案与用户选项对比。
- **题库内容**：必须是分类知识真题。不要放 `1+1=` 这类演示算术题。

## 1. 数据库表

```sql
-- 题目表
cmf_quiz_question
  id              INT PK
  title           VARCHAR(255)   题目内容
  option_a/b/c/d  VARCHAR(255)   四个选项（C/D 可为空）
  correct_option  CHAR(1)        正确答案 A/B/C/D
  status          TINYINT(1)     1启用 0禁用
  list_order      INT            排序
  create_time / update_time  INT

-- 答题记录表
cmf_quiz_log
  id              INT PK
  user_id         INT            关联 cmf_member.id
  quiz_date       DATE           答题日期（用于唯一索引）
  total_count     TINYINT        总题数（默认 5）
  correct_count   TINYINT        答对题数
  point           INT            获得积分
  detail          TEXT           答题详情 JSON（每题 user_option / correct_option）
  create_time     INT
  UNIQUE KEY user_date (user_id, quiz_date)
```

> 建表 SQL 位于 `data/sql/quiz_challenge.sql`（但该文件被 git 忽略，不会自动部署，需在生产库手工执行）。

## 2. PC 后台 —— 题库管理 + 答题记录

### 2.1 控制器 `app/admin/controller/QuizController.php`

| 方法 | 路由 | 职责 |
|---|---|---|
| `index()` | `/admin/Quiz/index` | 题库列表（搜索/分页/状态筛选） |
| `add()` | `/admin/Quiz/add` | 新增题目（GET 显示表单 / POST 保存） |
| `edit()` | `/admin/Quiz/edit` | 编辑题目（复用 add.html 模板） |
| `delete()` | `/admin/Quiz/delete` | 单题删除 |
| `delete_all()` | `/admin/Quiz/delete_all` | 批量删除 |
| `listOrder()` | `/admin/Quiz/listOrder` | 排序 |
| `setStatus()` | `/admin/Quiz/setStatus` | 启用/禁用 |
| `record()` | `/admin/Quiz/record` | 答题记录列表 |
| `export_record()` | `/admin/Quiz/export_record` | 导出答题记录 Excel |

**关键坑：`add()` 必须 assign `data` 变量**

```php
public function add()
{
    if ($this->request->isPost()) { /* ... */ }
    // ✅ 必须 assign，否则模板里 $data 报"未定义变量"
    $this->assign('data', [
        'title' => '', 'option_a' => '', 'option_b' => '',
        'option_c' => '', 'option_d' => '',
        'correct_option' => '', 'list_order' => 0,
    ]);
    return $this->fetch();
}
```

`edit()` 是 `assign('data', $question)` 再 `fetch('add')`，共用同一模板。

### 2.2 模板

| 模板文件 | 用途 |
|---|---|
| `public/themes/admin_fengiy/admin/quiz/index.html` | 题库列表页 |
| `public/themes/admin_fengiy/admin/quiz/add.html` | 新增/编辑题目表单（共用） |
| `public/themes/admin_fengiy/admin/quiz/record.html` | 答题记录列表 |

模板里输出表单值统一用 ThinkCMF 语法：
```html
<input value="{$data.option_a|default=''}" />
<option value="A" <if condition="$data.correct_option eq 'A'">selected="selected"</if>>A</option>
```
> `|default=''` 是 ThinkCMF 模板的兜底符，等价于 `isset(...) ? ... : ''`。

### 2.3 菜单（cmf_admin_menu）

```sql
-- 父菜单："垃圾分类活动"（parent_id=0, name='垃圾分类活动'）
-- 子菜单挂在它下面，用 SELECT 动态取 parent_id，不要写死
INSERT INTO `cmf_admin_menu`
(`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT p.id, 1, 1, 30, 'admin', 'Quiz', 'index', '', '分类知识挑战', 'question-circle', '分类知识挑战：管理题库'
FROM `cmf_admin_menu` p WHERE p.parent_id=0 AND p.name='垃圾分类活动' LIMIT 1;

INSERT INTO `cmf_admin_menu`
(`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT p.id, 1, 1, 31, 'admin', 'Quiz', 'record', '', '答题记录', 'list-alt', '分类知识挑战用户答题记录'
FROM `cmf_admin_menu` p WHERE p.parent_id=0 AND p.name='垃圾分类活动' LIMIT 1;
```

**权限**：超管（id=1 / id=14）免检自动可见；其他角色需在「角色管理 → 设置权限」里勾选 `admin/quiz/index` 和 `admin/quiz/record`。

语言包 key（可选，不影响显示）：`data/lang/zh-cn/admin_menu.php`
```php
'ADMIN_QUIZ_INDEX' => '分类知识挑战',
'ADMIN_QUIZ_RECORD' => '答题记录',
```

## 3. 小程序 API —— `api/wxapp/controller/QuizController.php`

| 接口 | 方法 | 职责 |
|---|---|---|
| `get_quiz` | POST | 随机抽题 + 检查今日是否已答 |
| `submit_quiz` | POST | 判分 + 发积分 + 写记录 |
| `check_today` | POST | 仅查今日状态 |

**关键实现要点**：
- `get_quiz`：先查 `cmf_quiz_log` 今日记录，已答则返回 `already_done`；未答则 `ORDER BY RAND() LIMIT 5` 抽题，**不返回 correct_option**（防前端作弊）。
- `submit_quiz`：接收用户答案数组，逐题比对 `correct_option`，计算 `correct_count × 2` 积分，用 `cmf_quiz_log` 的 `user_date` 唯一索引防并发重复插入。
- 所有接口都需要 openid 鉴权（继承 `AuthController`）。

## 4. 小程序前端 —— `小程序/pages/dtsh/quiz.vue`

**页面结构**：
1. 欢迎/规则说明 → 开始答题
2. 答题中：单选 + 上一题/下一题 + 进度
3. 提交后：得分展示 + 答案解析列表 + 返回

**关键 data**：
```js
{
  questions: [],        // 题目列表
  currentIndex: 0,      // 当前题号
  userAnswers: [],      // 用户选项
  result: null,          // 提交后结果
  alreadyDone: false,   // 今日已答
  todayResult: null     // 已答时展示
}
```

**关键方法**：
- `loadQuiz()` → 调 `$u.api.getQuiz()`
- `submit()` → 调 `$u.api.submitQuiz({ answers })`
- `checkToday()` → 调 `$u.api.checkTodayQuiz()`

## 5. 小程序 API 调用注册 —— `小程序/api/wxapp.js`

```js
getQuiz: (data) => http.post('/wxapp/quiz/get_quiz', data),
submitQuiz: (data) => http.post('/wxapp/quiz/submit_quiz', data),
checkTodayQuiz: (data) => http.post('/wxapp/quiz/check_today', data),
```

## 6. 路由注册 —— `小程序/pages.json`

```json
{
  "path": "pages/dtsh/quiz",
  "style": { "navigationBarTitleText": "分类知识挑战" }
}
```
在 `pages/dtsh/` 同级位置注册即可。

## 7. 关键坑：application/json body 中嵌套 JSON 字符串

**场景**：`submit_quiz` 接口的 `answers` 参数是一个数组，但前端 uView 用 `uni.$u.post` 默认会 `JSON.stringify` 整个 body。

```js
// ❌ 错误写法：多包一层 JSON.stringify，body 变成 {"answers":"[{\"qid\":1}]"}
this.$u.api.submitQuiz({ answers: JSON.stringify(answersArr) });

// ✅ 正确写法：直接传数组，body 变成 {"answers":[{"qid":1}]}
this.$u.api.submitQuiz({ answers: answersArr });
```

**后端**：优先用 `file_get_contents('php://input')` 读原始 body（**不要只依赖 `$request->param()`**，因为 `Content-Type: application/json` 的请求不会自动填充 `$_POST`，不同 TP 版本对 json body 的 param() 行为不一致），然后做双层 `json_decode` 兼容数组和字符串两种形式：

```php
$rawBody = file_get_contents('php://input');
$rawParams = $rawBody ? json_decode($rawBody, true) : [];
$answers = $rawParams['answers'] ?? $this->request->param('answers', '');

if (is_string($answers)) {
    $answers = json_decode($answers, true);  // 兼容前端多包一层的写法
}
```

## 8. 排查指南

| 现象 | 原因 | 修复 |
|---|---|---|
| 点"添加题目"报 `未定义变量: data` | `add()` 没 assign `data` | 见 2.1 代码，assign 空数组 |
| 后台看不到菜单 | 菜单没插 / 非超管未授权 | 执行 2.3 的 SQL；角色管理勾选权限 |
| 菜单点进去 404 | 控制器代码没部署到服务器 | `curl -I /admin/Quiz/index.html` → 302=已部署，404=未部署 |
| 接口 500 | 控制器 `initialize()` 访问级别 | 必须是 `public function initialize()` 且调 `parent::initialize()` |
| 接口返回"用户不存在" | 没传 openid 请求头 | 小程序端自动带，curl 测试时需加 `-H 'openid: xxx'` |
| 提交答案返回"答案格式错误" | 前端 JSON.stringify 双层序列化 + 后端 param() 取不到 json body | 前端去掉 stringify；后端用 `file_get_contents('php://input')` 自行解析 |
| 同天能重复答题 | 唯一索引没建 | 确认 `cmf_quiz_log` 有 `UNIQUE KEY user_date (user_id, quiz_date)` |
| 积分没发放 | AccountLogModel 调用 type 不对 | 检查 API 控制器里的 type 常量是否与积分配置一致 |

## 8. 部署检查清单

- [ ] 建表 SQL 已在生产库执行（`cmf_quiz_question` + `cmf_quiz_log`）
- [ ] 菜单行已插入 `cmf_admin_menu`
- [ ] 控制器代码已部署到服务器（curl 验证 302）
- [ ] 模板文件已部署
- [ ] 非超管角色已授权
- [ ] 小程序 `pages.json` / `api/wxapp.js` 已注册
- [ ] 小程序端已重新编译

## 相关 skill

- 后台新增页面三件套：`pc-admin-new-page`
- 菜单增删改与权限：`pc-admin-menu-manage`
- 生产库变更与部署验证：`prod-db-change-and-deploy-verify`
- 改在哪一端（小程序 / 小程序接口 / PC 后台）：`code-location-guide`
- 后台列表加列/搜索/导出：`pc-admin-add-column` / `pc-admin-search-export`
