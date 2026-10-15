---
name: active-scan-join
description: "活动扫码参加与扫码签到全链路。活动参加方式分「小程序参加」和「扫码参加」（cmf_active.join_type），扫码参加活动在小程序列表/搜索/推荐隐藏，仅能通过后台生成的活动专属小程序码扫码进入详情；小程序首页搜索框右侧扫码图标（uni.scanCode 解析 res.path/res.result）直达活动详情；扫码参加活动详情页按钮显示「立即签到」并免照片直接签到（复用打卡接口，仅后端放宽照片必填）。涉及 PC 后台参加方式/活动二维码配置（active/update.html、ActiveController 后台生成码）、api/wxapp ActiveController 过滤与免照片签到、小程序首页 index.vue（扫码入口）与活动详情页 details-new.vue。当修改扫码参加活动的展示/签到/二维码跳转，或遇到『添加活动报未定义变量 item』『扫码成功但不跳转』『扫码活动签到要求照片』等问题时调用。"
---

# 活动扫码参加 & 扫码签到功能

## 功能背景

活动在后台可选「参加方式」：

- `join_type = 1` **小程序参加**（默认）：活动在小程序内列表/搜索/本月推荐展示，用户直接在小程序参加/打卡。
- `join_type = 2` **扫码参加**：活动在小程序各列表隐藏，**只能通过扫描活动专属小程序码进入详情页**后参加。进入后点按钮显示「立即签到」，**免上传照片直接签到**（复用打卡数据逻辑）。

链路：PC 后台设置参加方式并自动生成活动小程序码 → 用户在线下用微信扫一扫扫海报码 → 直接打开小程序活动详情页；或在**小程序首页**顶部搜索框右侧点扫码图标，扫同一二维码进入详情 → 点「立即签到」即完成签到（积分/打卡记录照常写入）。

---

## 一、数据库

表 `cmf_active`（活动表）新增/依赖字段：

| 字段 | 说明 |
|---|---|
| `join_type` | tinyint，1=小程序参加（默认），2=扫码参加。扫码参加的活动小程序列表/搜索/推荐全部隐藏 |
| `qr_code` | varchar，活动专属小程序码图片的 OSS 相对路径（如 `xcxkf052/userShare/20260909/active_123.png`），保存活动后由系统自动生成，后台可预览下载 |

同一活动还可同时是打卡类（`is_clock=1`，自动报名、按日打卡/签到）或报名类（`is_clock=0`，手动报名），两者互不影响。

---

## 二、PC 后台

### 2.1 参加方式表单（添加/编辑共用 `update.html`）

文件：`public/themes/admin_fengiy/admin/active/update.html`

- 「参加方式」下拉 `name="join_type"`，`onchange="toggleJoinType(this.value)"` 切换显隐「活动二维码」区 `.join-type-scan`（仅扫码参加显示）。
- 帮助文案写明了两种方式的差异。
- 活动二维码区：已有 `qr_code` 显示预览图（`cmf_get_image_preview_url`）+ 提示可右键另存用于线下物料；没有则提示"保存后自动生成"。

> ⚠️ **易踩坑：添加活动报错 `未定义变量: item`**。添加/编辑共用一个 `update.html`，控制器 `app/admin/controller/ActiveController.php` 的 `update()` 在**无 id（添加）分支**原先不 `assign('item')`，编辑才有。模板里任何**裸读** `$item.xxx`（未包 `empty()`/`isset`/`|default`）在添加模式就会抛 `ErrorException`。
> 修复（已做，勿回退）：添加分支 `fetch()` 前必须 `$this->assign('item', []);`；新加的条件请一律写成 `isset($item['xx']) && $item['xx'] == n` 或 `!empty($item.xx) && ...`，禁止裸 `$item.xx == n`。
> 老坑位参考：分类下拉、社区分类下拉的 selected 判断此前就因此问题补过 `!empty()`。

### 2.2 小程序码生成

- 活动保存后（新建/编辑）调用 `CommonModel::setShareImage($scene, 'active_' . $活动ID, 'dz1111', $share_url)` 生成**微信小程序码**（`getwxacodeunlimit`）并上传 OSS、写入 `qr_code`。
- 小程序码参数：`page = pages/low_carbon/lsxf/details-new`，`scene = 活动ID`。即微信扫一扫这个码会直接打开活动详情页，`details-new.vue` 的 `onLoad` 用 `option.id || option.scene` 取活动 id。
- ⚠️ **路径不一致问题**：后台生码用的是旧路径 `pages/low_carbon/lsxf/details-new`，小程序分包优化后实际注册路径已改为 `pages/activity/lsxf/details-new`（pages.json）。但旧活动生成的码仍在使用，且微信扫码时 `res.path` 会返回码中编码的原始路径（旧路径）。**首页扫码解析必须同时兼容新旧两种路径**（详见第 4.1 节）。
- ⚠️ 历史 bug（同天活动码互相覆盖）：`image_name` 必须带活动 id（`active_{id}`），不能用固定名。排查/重新生成脚本详见 skill `active-qrcode-fix`。
- 后台列表页 `index.html` 展示二维码列，可查看/下载。

---

## 三、接口（`api/wxapp/controller/ActiveController.php`）

| 接口 | 与扫码参加的关系 |
|---|---|
| `get_new_active_list`（小程序活动列表/搜索页） | `where` 里固定 `join_type = 1`：扫码参加活动在**列表/搜索/本月推荐都不出现**，只能扫码进入 |
| 我参加的活动类查询（带 `is_my` 等分支） | 不按 join_type 过滤，用户已参加的扫码活动仍可查（`active_ids` 直查） |
| `get_new_active_detail` | **按 id 直查、不过滤 join_type**——扫码活动必须能拿到详情；打卡类活动会自动创建报名记录并返回 `sign_id/is_sign/is_sign_clock`；参与人数 `active_count` 的统计口径（必须在自动报名之后）见 skill `mp-active-detail-hero` |
| `post_new_sign_clock` | 打卡/签到接口。**照片校验逻辑（本次改动）**：`image_url` 为空时报"照片不能为空"，但活动 `join_type == 2` 时**免照片放行**（扫码签到无图也写打卡记录）。其余逻辑不变：写 `cmf_active_clock` 记录、`is_badge` 连续打卡徽章、`is_clock=1` 送积分（remark '垃圾打卡扣除'）、返回 `action_trigger` 供前端弹反馈表单/订阅授权 |

详情返回里活动行自带 `join_type` 字段，前端据此区分展示。

---

## 四、小程序端

### 4.1 扫码入口（`小程序/pages/index/index.vue`）

- **入口位置：首页顶部搜索框右侧**（原在活动页 `low_carbon.vue`，已迁移；活动页原处仅留注释说明去向）。该处加了一个 `scan` 图标（u-icon），`@click.stop="onScanQrcode"`（必须 `.stop`，外层是跳搜索页）。
- `onScanQrcode()` 调 `uni.scanCode`。**关键：扫后台活动码（小程序码/WX_CODE）时活动 id 不在 `res.result`，而在 `res.path`**，跳转逻辑（已修复兼容，勿回退）：
  1. 判 `res.path` 是否匹配 `/^pages\/(?:activity|low_carbon)\/lsxf\/details-new/` → **同时兼容新旧两种路径**（新路径 `pages/activity/lsxf/details-new` 是当前 pages.json 注册路径，旧路径 `pages/low_carbon/lsxf/details-new` 是历史生成的活动码路径）。匹配成功后从 path 解析 `scene` 参数，统一跳转到 `/pages/activity/lsxf/details-new?id=活动ID`；
  2. 否则兜底解析 `res.result`：纯数字 / `scene=数字` / `id=数字` → `$u.route('/pages/activity/lsxf/details-new?id=' + id)`；
  3. 都无 → toast「未识别到活动二维码」。
- ⚠️ **兼容原因**：后台 `ActiveController.php` 中生码的 `share_url` 是 `pages/low_carbon/lsxf/details-new`，旧活动生成的码仍在使用，前端必须能正确解析。
- H5 端用 `// #ifdef H5` 提示"扫码需在微信小程序内使用"，扫码调用包在 `// #ifndef H5`。

### 4.2 详情页扫码签到（`小程序/pages/low_carbon/lsxf/details-new.vue`）

本次改动核心，`join_type == 2` 的活动：

- computed `isScanJoin()`：`this.info.join_type == 2`。
- 打卡类（`is_clock==1`）按钮文案 `clockBtnText`：扫码活动显示 **「立即签到」/ 已签到 ✓**，普通活动仍「立即打卡 / 已打卡 ✓」。
- 报名类（`is_clock==0`）底部按钮文案同样按扫码活动显示「立即签到 / 已签到 ✓」（行为仍走 `handleSign` 报名接口，未变）。
- `handleClock()`：扫码活动点按钮**不再打开上传照片抽屉**，直接调 `post_new_sign_clock()`（`form.image_url` 留空即可，后端已放行）；普通活动原样弹 `sign_clock_show` 上传照片。
- 打卡成功弹窗文案随 `isScanJoin` 显示「签到成功! / 扫码签到成功，感谢参与~」，普通打卡不变。
- 签到成功后的 `action_trigger`（反馈表单/订阅授权）等流程与普通打卡完全一致，后台数据逻辑未另起炉灶。

---

## 五、排错速查

| 现象 | 原因与处理 |
|---|---|
| PC 后台"添加活动"报 `ErrorException: 未定义变量: item` | `update()` 添加分支未 `assign('item', [])`，且模板有裸读 `$item.xxx`。恢复第 2.1 节修复 |
| 小程序内扫码 toast 成功但**不跳转** | 只解析了 `res.result`。小程序码的页面信息在 **`res.path`**，按第 4.1 节双分支解析 |
| 扫旧活动码提示「未识别到活动二维码」 | 后台生码路径是旧路径 `pages/low_carbon/lsxf/details-new`，前端只匹配了新路径。按第 4.1 节兼容新旧两种路径（正则 `/^pages\/(?:activity|low_carbon)\/lsxf\/details-new/`） |
| 扫码活动点签到提示"照片不能为空" | 后端 `post_new_sign_clock` 的照片校验没对 `join_type==2` 放行（第 3 节），勿整体删照片校验否则普通打卡可无图打卡 |
| 扫码活动出现在小程序列表 | 检查对应列表接口 where 是否丢了 `join_type = 1` 过滤 |
| 找不到扫码图标 / 活动页搜索条消失 | 扫码入口已从活动页迁至首页 `index.vue` 顶部（见第 4.1 节），活动页不再有该入口 |

## 相关技能

- `active-qrcode-fix`：小程序码生成/覆盖 bug、OSS 重传脚本。
- `mp-image-url-complete`：接口返回图片 URL 补全规则（`qr_code` 出接口时用 `cmf_get_asset_url`）。
- `activity-tabbar-layout`：活动页 tab 布局与各标签列表（扫码活动在其中的展示边界）。
- `business-data-model` / `code-location-guide`：整体数据模型与三端定位。
- `mp-active-detail-hero`：详情页 hero 统计区字段绑定与参与人数计数口径。
