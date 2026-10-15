---
name: mp-active-detail-popup
description: "活动详情页（小程序/pages/low_carbon/lsxf/details-new.vue）交互与弹窗体系。记录该页三个弹窗（tipShow 打卡/签到成功弹窗、sign_clock_show 上传照片弹窗、actionFormShow 活动反馈表单弹窗）的显隐状态、打卡/报名成功后的状态流转（action_trigger 的 notify_type=1 即时表单 1500ms 后弹出、notify_type=2 请求订阅授权后 500ms 刷新详情）、扫码参加活动（isScanJoin）的差异化行为。当修改活动详情页弹窗、打卡/签到/报名流程，或排查『弹窗关不掉』『成功后不刷新』『反馈表单不弹出』时调用。"
---

# 活动详情页（details-new.vue）交互与弹窗体系

文件：`小程序/pages/low_carbon/lsxf/details-new.vue`

## 1. 三个弹窗

| 弹窗 | 状态变量 | 组件 | 用途 |
|---|---|---|---|
| 打卡/签到成功 | `tipShow` | `u-mask` | 显示获得积分 + 「我知道了」 |
| 上传打卡照片 | `sign_clock_show` | `u-popup`(bottom) | 上传照片后「确认打卡」 |
| 活动反馈表单 | `actionFormShow` | `u-popup`(bottom) | `action_trigger` 即时表单 |

弹窗写法与关闭机制见 skill `mp-uview-components`。

`tipShow` 弹窗的关闭统一走方法 `closeTip()`（遮罩与「我知道了」都绑定它），**不要**写内联 `@click="tipShow = false"` —— 该页曾因此触发首次 render 崩溃（`actionTrigger: null` 时 `__map(null.forms)` 抛错），导致 `e0/e1` 未注册，点按钮报 `does not have a method "e1"`，详见 skill `mp-render-crash-inline-event`。

## 2. 打卡/签到成功后的状态流转（`post_new_sign_clock`）

```
调用接口 → sign_clock_show=false → showType=2 → tipShow=true
  ├─ 无 action_trigger → 500ms 后 get_new_active_detail 刷新详情
  └─ 有 action_trigger：
       ├─ notify_type==1（即时表单）→ initActionFormData()，1500ms 后 tipShow=false + actionFormShow=true
       ├─ notify_type==2（滞后服务消息）→ requestSubscribeMessage()，500ms 后刷新详情
       └─ 其它 → 500ms 后刷新详情
```

- `onHide` 里会 `this.tipShow = false`（切后台自动关弹窗）。
- 报名类走 `post_new_active_sign`：`toast` 后 `tipShow=false`，500ms 后刷新详情。

## 3. 扫码参加活动的差异（`isScanJoin`）

- `isScanJoin = this.info.join_type == 2`。
- `handleClock()`：扫码活动**免上传照片**，直接调 `post_new_sign_clock()`；普通活动弹 `sign_clock_show`。
- 按钮文案：`clockBtnText`（打卡类）、报名类按钮，扫码活动都显示「立即签到 / 已签到 ✓」。
- 成功弹窗文案随 `isScanJoin` 变化（「签到成功!」/「扫码签到成功，感谢参与~」）。

## 4. 排错

| 现象 | 排查 |
|---|---|
| 「我知道了」点不动，且控制台报 `does not have a method "e1"` | 内联事件 handler 未注册（首次 render 崩溃）。本页根因：`data.actionTrigger: null` + 模板 `v-for="... in actionTrigger.forms"` → render 顶部 `__map(null.forms)` 抛 TypeError。已修：模板改走 computed `actionFormFields` 兜底、遮罩与按钮统一绑 `closeTip()`。见 skill `mp-render-crash-inline-event` |
| 「我知道了」点不动，但控制台无上述报错 | 组件链路 / 节流 / DOM 遮挡问题，见 skill `mp-uview-components` 第 2/3 节 |
| 打卡成功后弹窗一闪而过/被覆盖 | 检查 `action_trigger.notify_type==1` 的 1500ms 定时器是否提前触发 `actionFormShow` |
| 成功后详情数据不更新 | 确认各分支都有 `get_new_active_detail(this.info.id)` 的 500ms 延时刷新 |
| 扫码活动仍要求照片 | 后端照片校验未对 `join_type==2` 放行，见 skill `active-scan-join` |

## 相关技能

- `active-scan-join`：扫码参加/签到全链路。
- `mp-uview-components`：弹窗组件行为与关闭排查。
- `mp-render-crash-inline-event`：内联事件 handler 未注册（`does not have a method "eN"`）的根因与修复。
- `business-data-model`：活动/打卡数据表关系。
- `mp-active-detail-hero`：hero 统计区字段绑定（积分奖励 / 人已参与）与详情接口参与人数计数口径。
