---
name: mp-learn-exam-clock-gate
description: "垃圾分类「学习 → 考试 → 打卡」准入链路全栈指南。记录规则（学一次考一次即可：is_learn=1 且 is_answer=1 后永久放行）、三处入口的判定差异（首页 index.vue 快捷打卡 goToClock/goClockCheck 点击时刷新、活动页 low_carbon.vue onShow 强制拦截、学习页 flhd/list.vue 学完出现的「去答题考试」按钮）、相关页面职责（flhd/list、flhd/details、answer/examine?id=1、flhd/ljfldk）、后端 is_learn/is_answer 计算（UserController::get_user_info）与状态刷新时机。当需要修改/排查垃圾分类学习、考试、打卡准入，或遇到『学完不跳考试/没有考试入口』『学完返回首页点打卡仍提示去学习』『考试通过后仍无法打卡』时调用。"
---

# 垃圾分类「学习 → 考试 → 打卡」准入链路

## 0. 业务规则

- **学一次、考一次即可**：用户 `is_learn=1` 且 `is_answer=1` 后永久放行，之后所有打卡入口直接进打卡页，不再拦截。
- 未学完 → 引导去**学习**；学完未考过 → 引导去**考试**；两者都通过 → **打卡**。
- 三个动作对应三个页面：学习 `pages/flhd/list`、考试 `pages/answer/examine?id=1`（垃圾分类考试固定 `id=1`）、打卡 `pages/flhd/ljfldk`。

## 1. 三处入口（判定逻辑分散，差异要注意）

| 入口 | 触发时机 | 未学完（`is_learn=0`） | 学完未考（`is_answer=0`） | 都通过 |
|---|---|---|---|---|
| 首页快捷打卡卡 `pages/index/index.vue` | **点击「快捷打卡」时** | `showModal` 提示 → `/pages/flhd/list` | `showModal` 提示 → `/pages/answer/examine?id=1` | `/pages/flhd/ljfldk` |
| 活动页 `pages/low_carbon/low_carbon.vue` | **`onShow` 强制检查**（进页面即拦） | `$u.route('/pages/flhd/list')` | `$u.route('/pages/answer/examine?id=1')` | 不拦截 |
| 学习页 `pages/flhd/list.vue` | 学完全部指导后 | —— | 顶部显示「去答题考试」按钮 | 按钮自动隐藏 |

> 活动页的完整拦截代码（含 banner 跳转标记优先、`_isChecking` 防重入）见 skill `activity-tabbar-layout`，此处不重复。

### 首页 `goToClock` / `goClockCheck`（`小程序/pages/index/index.vue`）

```js
goToClock() {
    if (!this.isLogin) return this.$u.route('/pages/public/login')
    this.goClockCheck()
},
async goClockCheck() {
    // 关键：先拉最新用户信息，避免在 flhd/list 学完后返回首页时 is_learn/is_answer 状态过期
    let info = this.userInfo
    try {
        let res = await this.$u.api.getUserInfo()
        info = res.data || {}
        this.$u.vuex('userInfo', info)
    } catch (e) { console.log('刷新用户状态失败:', e) }
    if (!info.is_learn) { /* showModal「请先学习垃圾分类指导…」→ /pages/flhd/list */ return }
    if (!info.is_answer) { /* showModal「学习已完成，请通过…考试」→ /pages/answer/examine?id=1 */ return }
    this.$u.route('/pages/flhd/ljfldk')
}
```

## 2. 各页面职责

### 2.1 `pages/flhd/list.vue`（学习列表，学→考的衔接点）

- `onShow` 每次重置 `waste_list/page/learnAll/is_answer` 并重新 `get_waste_list()`（从详情页返回会自动刷新进度）。
- **学完全部判定**：`waste_count > 0 && user_count >= waste_count` → `learnAll = true`。
- 学完后调 `getUserAnswerState()`：请求 `getUserInfo()` 拿 `is_answer`，并 `this.$u.vuex('userInfo', info)` **主动刷新全局用户状态**（供首页/活动页入口判断最新状态）。
- 考试入口：`v-if="learnAll && !is_answer"` 的「去答题考试」按钮 → `/pages/answer/examine?id=1`；考过后自动隐藏。
- 列表每条：`item.is_study==0` 绿色「开始学习」/`==1` 灰色「开始学习」，都跳 `/pages/flhd/details?id=xx`。

### 2.2 `pages/flhd/details.vue`（单条学习）

展示指导内容；详情接口会自动写入 `waste_user` 学习记录（见 `activity-tabbar-layout` 的 `get_waste_list` 说明），返回列表后 `onShow` 刷新进度。

### 2.3 `pages/answer/examine.vue`（考试页）

- `onLoad(option)`：`this.answer_id = option.id`（垃圾分类固定 `1`）。
- 答题：`get_question_one` 拉题 → 选项 → `add_answer_one`（返回 `next/answer_status/score/pass/desc/option`）→ `next==0` 显示「下一题」，最后一题显示「完成答题」→ 弹出成绩弹窗 `mask_show`。
- `navigateBack()` 是**通过后的关键分支**：

```js
navigateBack() {
    if (this.pass) {
        this.mask_show2 = true   // 「恭喜已经激活垃圾分类打卡等功能」+「立即打卡」按钮
        this.pass = 0
    } else {
        if (this.answer_id != 1) this.$u.func.prePage().get_problem_list(this.answer_id)
        uni.navigateBack()
    }
},
redirectTo() { uni.redirectTo({ url: '/pages/flhd/ljfldk' }) }  // 「立即打卡」
```

> 通过后走 `uni.redirectTo`（替换当前页），返回时不会回到考试页。

### 2.4 `pages/flhd/ljfldk.vue`（打卡入口页，不是列表）

**两步引导、种类预选、record/choice 拆分、add.vue 传参**的完整说明见 [clock-hub-wizard.md](clock-hub-wizard.md)。此处只记与「准入」相关的边界：

- 自身守卫：未登录 → `/pages/public/login`；无 `community_id` → `/pages/member/info`。
- 页内 `gateTip` 条可点去学/考，但**主拦截仍在首页/活动页**；考试通过后「立即打卡」可直接 `redirectTo` 进本页。
- 本页**不是**打卡列表；学考通过后的打卡流程从本页选场景/种类再进 `add`。

## 3. 后端（`api/wxapp/controller/UserController.php::get_user_info`）

| 字段 | 计算口径 |
|---|---|
| `is_answer` | `AnswerModel` 中 `status=3`（及格）且 `type=1`（垃圾分类考试）且 `user_id` 有记录 → `1`，否则 `0` |
| `is_learn` | `WasteModel::count() > 0 && WasteUserModel::where('user_id')->count() >= waste_count` → `1`，否则 `0` |
| `clock_count` | 连续打卡天数（`getNowContinueSignDays`，forum type=2 的 create_time 列表），未达环保达人时计算 |
| `continuous_check` / `remaining_count` | 环保达人所需连续天数配置 / 还差几天 |

学习进度接口 `get_waste_list`：列表返回 `user_count`（已学条目数）、`waste_count`（总条目数）、`list`（每条带 `is_study`）；详情模式自动写入 `waste_user` 学习记录。

## 4. 状态刷新时机（最容易踩的坑）

`is_learn` / `is_answer` 来自 `getUserInfo`，是**快照**，三处配合刷新才不出错：

1. **学习页学完** → `getUserAnswerState()` 主动 `$u.vuex('userInfo', info)`；
2. **首页点击打卡** → `goClockCheck` 先 `await getUserInfo()` 再判定（即使前一步没刷新也能兜底）；
3. **活动页** → `onShow` 每次重新拉取后判定。

若改动首页把 `await getUserInfo()` 去掉，就会出现「学完/考完返回首页点打卡仍提示去学习」的假性 bug。

## 5. 常见问题排查

| 现象 | 优先排查 |
|---|---|
| 学完后停在列表页、没有考试入口 | `learnAll` 判定（`waste_count` 是否为 0、`user_count` 计数是否含所有条目）；或 `is_answer` 已为 1（按钮按设计隐藏） |
| 学完返回首页点打卡仍提示「请先学习」 | `goClockCheck` 里是否 `await getUserInfo()` 刷新了状态；学习页是否 `$u.vuex('userInfo', ...)` |
| 考试通过后点打卡仍被拦 | 后端 `is_answer` 依赖 `AnswerModel` 落库（`status=3`、`type=1`）是否成立 |
| 考试通过后没进打卡页 | 看 `navigateBack` 的 `pass` 分支是否进入 `mask_show2`，「立即打卡」是否 `redirectTo('/pages/flhd/ljfldk')` |

## 相关技能

- [clock-hub-wizard.md](clock-hub-wizard.md)：打卡入口场景引导、上报类型预选、列表页拆分。
- `activity-tabbar-layout`：活动页 `onShow` 强制学习/考试拦截 + `is_learn`/`is_answer`/`is_exam` 字段定义 + `get_waste_list` 学习记录。
- `mp-index-layout`：首页结构，含问候/快捷打卡卡 `hello-card` 的字段与逻辑。
- `business-data-model`：`cmf_waste` / `cmf_waste_cate` / `cmf_waste_user`（用户学习记录）表结构。
- `active-scan-join`：扫码参加活动的签到/打卡接口（与准入无关）。
