---
name: "pc-admin-problem-exam"
description: "PC 后台「答题考试」（Problem 模块）与答题记录弹窗、导出。覆盖 type 分栏、index1 重定向、模板 params.type 默认值、course/answer 与 outAnswer 复用关系。改 /admin/problem、答题记录导出、未定义 type 报错时调用。"
---

# 答题考试（Problem）后台

## 和「分类知识挑战」的区别

| 模块 | 菜单/入口 | 控制器 | 数据表 | skill |
|------|-----------|--------|--------|-------|
| **答题考试** | `/admin/problem/index1.html` 等 | `ProblemController` | `cmf_problem`（模型 `ProblemModel`）、题目 `QuestionController`、答卷 `xx_answer` | 本文 |
| **分类知识挑战** | Quiz 菜单 | `QuizController` | `cmf_quiz_question` / `cmf_quiz_log` | `quiz-challenge` |

不要混用导出接口：答题考试答卷导出走 `CourseController::outAnswer`，不是 `QuizController::export_record`。

---

## URL 与模板（没有 index1.html）

侧栏常见 action：

- `Problem/index1` → **redirect** 到 `Problem/index?type=1`（答题考试）
- `Problem/index2` → `type=2`
- `Problem/index3` → `type=3`

**唯一列表模板**：`public/themes/admin_fengiy/admin/problem/index.html`  
**控制器**：`app/admin/controller/ProblemController.php`

`index2.html` / `index1.html` **不存在**，不要去找。

---

## `type` 参数（必记）

列表模板里多处使用 `$params['type']`（Tab 链接、搜索 form action、编辑链接、`setStatus` 的 `pro_type`）。

- 菜单若配置成 `Problem/index` 且 **param 为空**，请求里没有 `type`，会报 **`未定义数组索引: type`**。
- **约定**：`ProblemController::index()` 对 `type` 做默认值 **`1`**，并 **`$params['type'] = $type`**，列表 **始终** `where type = $type`。
- 运维侧栏建议：`controller=Problem`，`action=index1`（或 `index` + `param` 里 `type=1`），与默认行为一致。

---

## 列表页操作链

文件：`problem/index.html`

| 按钮 | 实际打开 | 说明 |
|------|----------|------|
| 题目 | `question/index?pid={problem.id}` | 该试卷下的题目 |
| 答题记录 | `course/answer?exam_id={problem.id}` | **exam_id = problem 主键**，不是课程 ID |
| 编辑/删除 | `problem/update`、`problem/delete` | 带 `type` |

启用开关：`setStatus`，`type=1` 时若当前无题目会提示「暂无问题请先添加问题！」。

---

## 答题记录弹窗与导出

- **列表页**：`public/themes/admin_fengiy/admin/course/answer.html`
- **控制器**：`CourseController::answer()`（分页列表）
- **详情嵌套弹窗**：`course/answerLog?answer_id=` → `answer_log.html`

### 导出

- **接口**：`CourseController::outAnswer()`（GET，与列表同一套 `exam_id` / `keyword` / 可选 `shop_id`）
- **按钮**：`answer.html` 搜索栏「导出」，仅在 `exam_id` 非空时显示；**`target="_blank"`** 新开标签下载，避免 iframe 内导航卡住
- **实现**：入口先 **`session_write_close()`**（否则与答题记录弹窗 iframe 并发时导出请求易一直 pending）；直连 join 查答卷、**一次 in 查询**拉齐 `xx_answer_log`，流式 **CSV**（UTF-8 BOM，Excel 可直接打开）；题目 `xx_question` **按 `id` 排序**（无 `list_order` 字段）
- **表头与列表对齐**：ID、昵称、姓名、手机号、考试名称（`problem_name`）、答题数、得分、答题时间 + 各题题干列
- **关键字**：与列表一致 `b.true_name|b.nickname|b.mobile`；**不要**在 export 里用未 join 的别名（历史坑：`c.title`）
- **无数据**：`$this->error('暂无数据可导出')`

改列表列时，**thead、tbody、outAnswer 的 `$head/$keys` 三处一起改**（见 `search-export.md`）。

### 删除单条答卷

`CourseController::answer_delete`：删 `xx_answer` 及对应 `xx_answer_log`。

---

## 小程序侧（排查数据时）

- 模型：`api/wxapp/model/AnswerModel.php`（表 `xx_answer`），`exam_id` 指向 problem id
- 接口：`api/wxapp/controller/AnswerController.php`

---

## 常见报错

| 现象 | 原因 | 处理 |
|------|------|------|
| `未定义数组索引: type` | 打开 `problem/index` 无 type | 控制器默认 type=1 并写入 params；或改菜单为 index1 |
| 导出关键字搜不到 / SQL 报错 | outAnswer 用了不存在的 join 字段 | 与 `answer()` 用同一套 `$map` 条件 |
| 点答题记录 404 | 改错成不存在的模板 | 用 `course/answer`，不是 problem 下新 action |
