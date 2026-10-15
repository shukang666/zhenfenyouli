---
name: activity-tabbar-layout
description: "活动页标签功能全栈指南，覆盖小程序活动 tabbar 页面（小程序/pages/low_carbon/low_carbon.vue）的标签数量/顺序/绑定分类、页面左右联动布局与左侧标签栏折叠/展开（side-bar-col / side-toggle-btn），以及 PC 后台活动标签管理（/admin/active_tab/index.html）的修改与排错。含 cmf_active_tab 等表结构、数据库/后台/小程序接口/前端四层逻辑。当需要调整活动页标签数量顺序、绑定分类、折叠/展开左侧标签栏，或排查活动标签、活动分类、活动页左侧栏布局与悬浮按钮相关问题时调用。"
---

# 活动页标签功能（全栈同仓）

适用场景：涉及小程序"活动"tabbar 页面（`小程序/pages/low_carbon/low_carbon.vue`）的标签数量、顺序、绑定分类、或 PC 后台"活动标签"管理（`/admin/active_tab/index.html`）的修改/排错。本项目全栈同仓，本 skill 覆盖数据库表结构、PC 后台控制器/模板、小程序接口、小程序前端四层。

## 1. 数据库层

### `cmf_active_tab` 表（活动页标签配置）

| 字段 | 类型 | 默认 | 说明 |
|------|------|------|------|
| id | int(11) AI | - | 主键 |
| name | varchar(32) | NULL | 标签名 |
| **cate_id** | int(11) | 0 | 绑定的活动分类id，0=不绑定（所有标签含固定打卡标签都必须绑定） |
| **is_fixed** | tinyint(1) | 0 | 1=固定标签（第一个，不参与排序，但需绑分类）；0=普通标签 |
| status | tinyint(4) | 1 | 1启用 2禁用 |
| create_time / update_time / delete_time | int(11) | 0 | 时间戳 |
| list_order | int(11) | 100 | 排序，数字越小越靠前 |

### `cmf_active_cate` 表（活动分类，被 active_tab.cate_id 引用）

PC 后台"社区活动 → 活动分类"管理。模型 `app/admin/model/active/ActiveCateModel.php`。每条记录有 id/name/status/list_order，小程序"活动分类"区域和 active_tab 绑定时都从这里取。

### `cmf_active` 表（活动主表，注意字段名同名但含义不同）

active 表也有 `cate_id` 字段，表示**活动本身所属的分类**。`active_tab.cate_id`（标签绑定的分类）与 `active.cate_id`（活动所属分类）通过相同的分类ID关联——切换到某个普通标签时，前端用 `tab.cate_id` 调 `get_new_active_list`，接口内部用 `active.cate_id = tab.cate_id` 查出该分类下的所有活动。

#### `cmf_active` 关键字段（含 2026-09-01 新增）

| 字段 | 类型 | 默认 | 说明 |
|------|------|------|------|
| id | int(11) AI | - | 主键 |
| name | varchar(32) | NULL | 活动名 |
| type | int(1) | NULL | 1=社区活动（活动 tabbar 显示的类型） |
| **status** | tinyint(4) | 1 | **1=启用 2=禁用**（接口必须过滤 status=1，否则禁用活动会泄漏到前端） |
| **delete_time** | int(11) | 0 | 软删除时间戳，0=未删除（接口必须过滤 delete_time=0） |
| cate_id | int(11) | 0 | 活动所属分类id |
| is_put | tinyint(1) | 1 | 是否首页展示 |
| is_recommendation | int(11) | 0 | 是否推荐 |
| **is_month_recommend** | tinyint(1) | 0 | **是否本月推荐 0否 1是**（2026-09-01 新增，PC 后台开关配置，小程序活动页"本月推荐"标签查 is_month_recommend=1 的活动） |
| is_clock / is_badge | int(11) | 0 | 是否开启打卡 / 是否开启徽章 |
| list_order | int(11) | 100 | 排序，越小越靠前 |

#### 关键 bug 教训：活动列表接口漏过滤 status/delete_time（已修复 2026-09-01）

**症状**：后台把活动取消启用（status=2）后，小程序前端依然显示该活动。

**根因**：`api/wxapp/controller/ActiveController.php` 的 `get_new_active_list` 方法查询条件只过滤了 `type=1`，**漏了 `status=1` 和 `delete_time=0` 两个关键字段**，导致禁用和软删除的活动全部返回前端。

**修复**（已执行）：
```php
$where[] = ['type', '=', 1];
$where[] = ['status', '=', 1];       // 新增
$where[] = ['delete_time', '=', 0];  // 新增
```

**通用规则**：所有面向用户的活动列表查询接口（列表/推荐/本月推荐/首页展示）**必须同时过滤 `type=1 AND status=1 AND delete_time=0`**，缺一不可。按 id 查活动详情的接口不强求 status 过滤（用户已报名的链接需要能进去看）。

### 关键 bug 教训（已修复 2026-08-31）

**症状**：PC 后台 `/admin/active_tab/index.html` 编辑标签绑定分类后"没回显数据，没绑定成功"。

**根因**：生产库 `cmf_active_tab` 表是旧结构，**缺少 `cate_id` 和 `is_fixed` 两列**（虽然 `data/sql/active_tab.sql` 脚本里有定义，但脚本未在生产库执行或执行的是旧版本）。代码（Controller/Model/模板）都没问题，纯粹是表结构缺失字段。

**诊断方法**：连库 `SHOW COLUMNS FROM cmf_active_tab;` 验证字段是否存在。本地查库用远程地址 `124.223.56.252`（详见 `dev-environment` skill），不要用 `127.0.0.1`（本机不开 MySQL）。

**修复 SQL**（已执行）：
```sql
ALTER TABLE cmf_active_tab ADD COLUMN cate_id int(11) DEFAULT 0 COMMENT '绑定的活动分类id 0=不绑定' AFTER name;
ALTER TABLE cmf_active_tab ADD COLUMN is_fixed tinyint(1) DEFAULT 0 COMMENT '是否固定标签 1=固定(第一个标签,不参与排序/不绑分类)' AFTER cate_id;
UPDATE cmf_active_tab SET is_fixed=1, list_order=0 WHERE id=3;  -- "打卡"设为固定标签排第一
```

**通用排错规则**：当出现"保存成功但列表/编辑页不回显"且代码逻辑无误时，**优先怀疑表结构缺字段**，不要先怀疑代码。`SHOW COLUMNS` 比 `SELECT *` 更直观（SELECT * 会静默忽略不存在的字段，看不出差异）。

## 2. PC 后台层（`app/admin/` + 模板）

### 控制器：`app/admin/controller/ActiveTabController.php`（继承 ToolController）

通过钩子方法定制，不重写 update/updatePost/index：

| 钩子 | 作用 |
|------|------|
| `setWhere()` | 列表搜索条件（按 name 模糊） |
| `setIndex()` | 列表查询，`order('list_order asc, id asc')`，固定标签 list_order=0 永远排第一 |
| `get_params()` | 编辑页 assign `cate_list`（启用中的活动分类下拉） |
| `setData($item, $type)` | type=2（列表/编辑回显）时查 `cate_name`（分类名）赋给 item |
| `setParamData($params)` | 提交校验：所有标签（含固定打卡标签）若传了 cate_id 但为空，报错"请选择绑定的活动分类" |
| `setAddPost($params)` | 添加前校验：`count() >= 4` 报错"标签最多配置4个"，否则调父类 `model->save($params)` |

### ToolController 父类关键方法（`app/admin/controller/ToolController.php`）

- `index()` → `setIndex()->each(getList)` → `getList` 调 `setData($item, 2)` → fetch
- `update()` → `get_params()` + `setEdit()` + `getParamData(item)`（即 `setData(item,2)`）→ fetch
- `updatePost()` → 仅在 `isAjax()` 时处理：`setParamData($params)` 校验 → 空 id 走 `setAddPost`，非空走 `setEditPost`（`model->find(id)->save($params)`）
- **注意**：表单必须带 `js-ajax-form` 类名才会 ajax 提交，否则 updatePost 直接 `$this->error('非法请求')`

### 模型：`app/admin/model/active/ActiveTabModel.php`

```php
namespace app\admin\model\active;
class ActiveTabModel extends \think\Model {
    protected $name = 'active_tab';
}
```

仅指定表名，无 allowField 限制（默认所有字段可写）。

### 模板：`public/themes/admin_fengiy/admin/active_tab/`

- `index.html`：列表页。表头 排序/ID/名称/绑定分类/启用/操作。绑定分类列统一显示 `{$vo.cate_name}`（由 setData type=2 赋值，固定标签也显示分类名而非"—"）。固定标签排序列显示"固定"文字（不渲染输入框），普通标签渲染排序输入框。启用列是 layui switch，调 `setStatus`。操作列有 编辑/删除。
- `update.html`：编辑/添加页。所有标签（含固定打卡标签）都渲染分类下拉 `<select name="cate_id">`，编辑时用 `<eq name="item.cate_id" value="$cate.id">selected</eq>` 回显选中。提交时所有标签都带 cate_id，由 `setParamData` 校验非空。

### 后台菜单

挂在"社区活动"（app=admin, controller=active, action=default）下，路由 `/admin/active_tab/index`。菜单记录在 `cmf_admin_menu` 表。

## 3. 小程序接口层（`api/wxapp/`）

### `api/wxapp/controller/ActiveController.php`

| 方法 | 路由 | 作用 |
|------|------|------|
| `get_active_tab_list` | `/wxapp/active/get_active_tab_list` | 返回 status=1 的标签列表，`order('list_order asc, id asc')->limit(4)->select()`，默认 SELECT * 含 cate_id/is_fixed |
| `get_new_active_list` | `/wxapp/active/get_new_active_list` | 参数 `community_cate_id`/`keyword`/`is_recommendation`/`is_put`/`page_size`，按 `active.cate_id = community_cate_id` 查活动列表，each 里加 thumb/badge_image URL、active_count 参与人数、avatar_array 头像数组 |
| `get_waste_list` | `/wxapp/active/get_waste_list` | 垃圾分类指导列表/详情。列表模式返回 `user_count`（用户已学条目数，`waste_user` 表 where user_id 计数）+ `waste_count`（总条目数，`waste` 表计数）+ `list`（每条带 `is_study`）。详情模式自动写入 `waste_user` 学习记录。`is_learn` 计算复用此逻辑 |

模型：`api/wxapp/model/ActiveTabModel.php`（同 admin 端，`protected $name='active_tab'`）。小程序前端 API 封装在 `小程序/api/home.js`：`get_active_tab_list`、`get_new_active_list`、`get_new_cate_active_list`。

### `api/wxapp/controller/UserController.php` get_user_info

返回用户信息，包含活动页 onShow 强制检查所需的状态字段：

| 字段 | 来源 | 含义 |
|------|------|------|
| `is_learn` | `WasteModel::count()` vs `WasteUserModel::where('user_id')->count()` | 是否学完所有垃圾分类指导内容。`waste_count > 0 && user_count >= waste_count` 时为 1，否则 0 |
| `is_answer` | `AnswerModel` where status=3 type=1 user_id 计数 | 垃圾分类答题考试是否及格。有记录=1，无=0 |
| `is_exam` | `exam_gift` 表 where exam_id in (HealthHome type=1 的 id) openid 计数 | 健康管理问卷是否参与（非答题考试） |

模型：`app/admin/model/active/WasteModel.php`、`app/admin/model/active/WasteUserModel.php`（admin 端模型，api 端复用）。

## 4. 小程序前端层（`小程序/`）

### `小程序/pages/low_carbon/low_carbon.vue`（活动 tabbar 页）

模板五分支（顺序不可变，`v-else-if` 链按下列顺序判定）：
1. `v-if="current_tab == 0"`：第一个固定标签"打卡"——顶部写死"垃圾分类打卡"入口卡片（含立即参与按钮，`navigator('/pages/flhd/ljfldk',1)` 带 type==1 答题考试 gating）+ 下方动态活动列表（与中间分类标签共用 `active_list` 数组，调 `get_new_active_list({ community_cate_id: tab.cate_id })` 加载）
2. `v-else-if="is_zzhd_tab"`：自治活动标签——复刻 `pages/zzhd/zzhd.vue` 主体（顶部大图+文明督导通知+督导员/点位管理/商家自治三组卡片），切到时调 `get_shop_new()+getUserInfo()` 刷新商家信息和用户态
3. `v-else-if="is_sjhd_tab"`：社交活动标签——复刻 `pages/sjhd/sjhd.vue` 主体（顶部 banner+文明标兵/宣传教育卡片）+ 末尾"问卷调查"入口卡片（点击跳 `/pages/low_carbon/research` 独立列表页，不再预加载 health_list）
4. `v-else-if="!is_backup_tab"`：中间分类标签（后台配置，非自定义非备份）——显示该标签绑定分类下的活动列表，卡片样式参考 `active-list.vue`，调 `get_new_active_list({ community_cate_id: tab.cate_id, page })`，`onReachBottom` 分页
5. `v-else`（`is_backup_tab` 为 true）：最后一个"备份"标签——保留页面原内容（轮播+通知+两个 banner+活动分类卡片网格）

> **顺序很重要**：`is_zzhd_tab`/`is_sjhd_tab` 必须放在 `!is_backup_tab` 之前，否则自定义标签会被分类标签分支（`!is_backup_tab` 对自定义标签也成立）抢先匹配，导致自定义内容无法显示。

### 页面外侧布局与左侧标签栏折叠/展开（2026-09-10 新增）

活动页是"左侧标签栏 + 右侧内容区"的左右联动布局，折叠功能**只加在外层布局上**，与标签数据/接口无关。改动前后的结构差异：**原来 `scroll-view.side-bar` 直接是 `.layout-wrapper` 的子元素；现在外面多包了一层 `.side-bar-col`，并把宽度/背景/右边框/高度限制全部上移到这一层**。看到"标签栏宽度、边框、把手按钮位置"类问题，先想到这层结构。

```html
<view class="layout-wrapper" v-if="tab_list.length">
  <!-- 侧边标签栏：外层 col 控制宽度与折叠，折叠时宽度收为 0 -->
  <view class="side-bar-col" :class="{ collapsed: side_collapsed }">
    <scroll-view class="side-bar" scroll-y>
      <view class="tab-item" :class="{active: current_tab == index}" v-for="(tab,index) in tab_list"
        :key="tab.id" @click="switch_tab(index)">
        <view class="tab-name">{{tab.name}}</view>
      </view>
    </scroll-view>
  </view>
  <!-- 常驻悬浮把手：展开态吸在侧栏右边缘中部，折叠态贴屏幕左侧 -->
  <view class="side-toggle-btn" :class="{ collapsed: side_collapsed }" @click="toggle_side">
    <u-icon :name="side_collapsed ? 'arrow-right' : 'arrow-left'" size="22" color="#18AF38"></u-icon>
  </view>
  <!-- 右侧内容区：各标签 v-if / v-else-if 分支 -->
  <view class="content-area"> ... </view>
</view>
```

四层结构与职责（**改样式前先确认改的是哪一层**）：

| 元素 | 职责 | 关键样式 |
|------|------|----------|
| `.layout-wrapper` | flex 容器，左右联动 | `display:flex; align-items:flex-start; min-height: calc(100vh - 88rpx - var(--status-bar-height, 0px))` |
| `.side-bar-col` | 侧栏外层：**宽度、背景、右边框、高度限制、折叠过渡** | `width:180rpx; flex-shrink:0; overflow:hidden; transition: width .25s ease`；`.collapsed { width:0; border-right-color:transparent }` |
| `.side-bar`（scroll-view） | **只负责滚动**（宽度/高度继承外层） | `width:100%; min-height/max-height: calc(100vh - 88rpx - var(--status-bar-height, 0px)); overflow-y:auto` |
| `.side-toggle-btn` | 常驻悬浮把手（**不在侧栏 DOM 内**） | `position:fixed; top:50%; transform:translateY(-50%); left:180rpx; transition: left .25s ease`；`.collapsed { left:0 }` |
| `.content-area` | 右侧内容区，自动占满剩余宽度 | `flex:1; min-width:0; overflow-y:auto` |

状态与方法（`low_carbon.vue`）：

- data：`side_collapsed: false`（默认展开；**只存在于页面会话内，不做 storage 持久化**，切标签/返回不重置）
- methods：`toggle_side() { this.side_collapsed = !this.side_collapsed }`（就一个方法，收起和展开共用）
- 图标：展开态 `arrow-left`（点它收起）、折叠态 `arrow-right`（点它展开），均为 uView 自带图标（`小程序/uview-ui/iconfont.css` 中已确认存在 `uicon-arrow-left` / `uicon-arrow-right`），不要换成需要额外引入的图标
- 单按钮实现：**不是两个按钮 `v-if` 切换**，而是同一个 `.side-toggle-btn` 常驻，靠 `:name="side_collapsed ? 'arrow-right' : 'arrow-left'"` 切箭头 + `:class="{ collapsed }"` 换位置

**设计要点 / 必须同步的点（踩过的坑）**：

1. 把手是 `position: fixed` 且作为 `.layout-wrapper` 的直接子元素，**不在 `.side-bar-col` 内** —— 所以折叠时不会被 `overflow:hidden` 裁掉。**千万不要把 `.side-toggle-btn` 挪进 `.side-bar-col` 内部**，否则折叠后按钮被裁掉，页面再无展开入口。
2. 按钮的 `left` 必须与侧栏宽度**硬同步**：侧栏 `180rpx` ↔ 按钮展开态 `left: 180rpx`。以后若要改侧栏宽度，`.side-bar-col { width }` 和 `.side-toggle-btn { left }`（含注释里写的 180rpx）两处一起改；否则展开态按钮会脱离侧栏右边缘。
3. "吸附感"来自两段过渡同步：侧栏 `transition: width .25s ease` + 按钮 `transition: left .25s ease`，**两处时长/缓动必须一致（都 .25s ease）**，否则按钮会与侧栏边缘脱节、看起来一先一后。
4. `scroll-view.side-bar` 上**不要再写 `width`/`min-height`/`background`/`border-right`**（已上移到 `.side-bar-col`）；写重复会出现折叠后"内容没收干净 / 边框残留 / 折叠动画抖动"。
5. 侧栏高度公式 `calc(100vh - 88rpx - var(--status-bar-height, 0px))` 里的 `88rpx` 是自定义 tabbar 高度，`.side-bar-col` 与 `.side-bar` 都要同步；改一处会让侧栏与内容区高度不齐。
6. 折叠态按钮在 `left:0` 时右侧有 `border-radius: 0 24rpx 24rpx 0`（左直右圆），贴合屏幕左边缘，别改成四角圆角或右侧直角，否则看起来不像"把手"。

#### 折叠功能相关 bug 的排查顺序

出现"活动页左侧标签栏/把手按钮异常"时，按下面顺序定位，基本都是这层结构引起的：

1. 整个页面空白/只有内容区 → `.layout-wrapper` 上的 `v-if="tab_list.length"`（接口 `get_active_tab_list` 失败或 `.then` 里 push 出错时整块不渲染）
2. 标签栏宽度错乱、折叠后仍占位 → `.side-bar-col` 的 `width`/`.collapsed` 是否被覆盖，`overflow:hidden` 是否还在
3. 折叠后右侧内容没占满 → `.content-area` 的 `flex:1`、`.side-bar-col` 的 `flex-shrink:0`
4. 把手位置跑偏/不跟随侧栏 → `.side-toggle-btn` 的 `left` 是否与侧栏宽度一致、`transition` 是否被同类选择器覆盖、模板上 `:class="{ collapsed: side_collapsed }"` 是否还在
5. 点把手完全没反应（控制台报 `does not have a method "e0/e1"` 之类）→ 属内联事件 handler 未注册，见 skill `mp-render-crash-inline-event`（本功能的内联事件是 `@click="toggle_side"`）
6. 想恢复旧版（**旧版已废弃，勿按记忆改**）：曾经是"侧栏底部绝对定位的 `.side-collapse-btn`（带文字"收起"）+ 折叠后 `v-if` 显示的 `.side-expand-btn`"两个按钮，现已合并为单个 `.side-toggle-btn`，旧类名 CSS 已删除，不要再往回加

### onShow 强制学习/考试检查 + banner 跳转标记消费

用户进入活动页（onShow 触发）时，**优先**检查 banner 跳转标记，按顺序执行：

```js
onShow() {
    // 1. 优先检查 banner 跳转标记：从首页 banner 跳过来需要切到"本月推荐"标签
    let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
    if (jumpMonthlyRec) {
        // tab_list 已加载：立即消费标记并切换；未加载：保留标记，等 get_active_tab_list 回调消费
        if (this.tab_list.length > 0) {
            uni.removeStorageSync('jump_monthly_rec')
            this.current_tab = 0
            this.load_monthly_rec_list()
        }
        return  // ← 关键：跳过后续学习/考试检查
    }
    // 2. 原有强制学习/考试检查
    if (checkCommunity(this)) return
    if (!this.isLogin) return
    if (this._isChecking) return
    this._isChecking = true
    this.getUserInfo().then(() => {
        this._isChecking = false
        if (!this.isLogin) return
        if (!this.userInfo.is_learn) {
            this.$u.route('/pages/flhd/list')
        } else if (!this.userInfo.is_answer) {
            this.$u.route('/pages/answer/examine?id=1')
        }
    })
}
```

> **关键时序**：tabBar 页面 `onLoad` 只在首次创建时触发，之后切 tab 回来只触发 `onShow`。所以 banner 跳转标记必须在 `onShow` 最前面检查，且 `return` 在其他逻辑之前（学习/考试检查可能 redirect，不能干扰跳转）。`get_active_tab_list` 回调里也要检查一次（首次进入时 tab_list 还没加载完）。

### 首页 banner 跳转到本月推荐标签

**完整链路**（已打通）：
1. 首页 `pages/index/index.vue` 的 `adLink(e)` 方法检测 banner URL：只要 URL 包含 `low_carbon`，就 `uni.setStorageSync('jump_monthly_rec', 1)` + `uni.switchTab({ url: '/pages/low_carbon/low_carbon' })`
2. 活动页 `onShow`（或 `get_active_tab_list` 回调）检测 storage 标记 → `current_tab = 0`（本月推荐标签）→ `load_monthly_rec_list()`

```js
// 小程序/pages/index/index.vue adLink 方法
adLink(e) {
    let data = this.bannerList[e];
    if (!data.url) return
    let url = data.url
    // 只要是跳转到活动页（low_carbon），就设置标记，让活动页切到"本月推荐"标签
    if (url.indexOf('low_carbon') !== -1) {
        let tabUrl = url.split('?')[0]  // switchTab 不支持 query
        uni.setStorageSync('jump_monthly_rec', 1)
        uni.switchTab({ url: tabUrl })
        return
    }
    uni.navigateTo({ url: url, fail() { uni.switchTab({ url: url }) } })
}
```

> 后台 banner 主图 URL 直接配 `/pages/low_carbon/low_carbon` 就行，不需要加任何 query 参数。

### 标签数据来源与写死标签

`tab_list` 来自 `get_active_tab_list` 接口（最多4条，第一条是固定打卡标签），在 `.then` 里**按顺序**构造：
```js
// 1. 头部 unshift "本月推荐"写死标签（前端写死第一个，对应 is_month_recommend=1 的活动列表）
list.unshift({ id: -99, name: '本月推荐', cate_id: 0, is_fixed: 2, _is_custom: 'monthly_rec' })
// 2. 末尾 push 自治活动、社交活动
list.push({ id: -2, name: '自治活动', cate_id: 0, is_fixed: 2, _is_custom: 'zzhd' })
list.push({ id: -3, name: '社交活动', cate_id: 0, is_fixed: 2, _is_custom: 'sjhd' })
// 3. 备份标签已注释，不再显示
// list.push({ id: -1, name: '备份', cate_id: 0, is_fixed: 2, _is_backup: 1 })
this.tab_list = list
```
最终 tab 顺序：**本月推荐** → 后台分类标签（第1条是固定打卡）→ 自治活动 → 社交活动（备份已注释）。

> **重要：本月推荐 unshift 后，原 `current_tab == 0`（打卡标签）变成了 `current_tab == 1`**。模板里所有硬编码的 `current_tab == 0` 必须替换为 computed `is_daka_tab`（`current_tab === 1`），否则打卡标签的内容会显示到本月推荐标签下。

字段语义：
- `_is_custom: 'monthly_rec'|'zzhd'|'sjhd'`：自定义标签标记位
- `_is_backup: 1`：备份标签标记位（备份标签已注释不显示）
- `id: -99/-2/-3/-1`：负数避免与后台真实标签ID冲突
- `is_fixed: 2`：区别于第一个打卡标签的 `is_fixed: 1`（仅语义标记，不参与后台排序逻辑）
- **后台 `cmf_active_tab` 表里不存在这些写死标签**，仅前端多写死，PC 后台管理页看不到

### 自定义标签 computed 判断

```js
computed: {
  // 本月推荐标签（tab_list[0]，unshift 在头部）
  is_monthly_rec_tab() {
    return this.tab_list.length > 0
      && this.tab_list[this.current_tab]
      && this.tab_list[this.current_tab]._is_custom === 'monthly_rec'
  },
  // 打卡标签（后台第一个固定标签，unshift 本月推荐后变成 index===1）
  is_daka_tab() {
    return this.current_tab === 1
  },
  is_backup_tab() {  // 备份标签已注释，此 computed 保留供模板 v-else-if 链使用
    return this.tab_list.length > 0
      && this.current_tab == this.tab_list.length - 1
      && this.tab_list[this.current_tab]
      && this.tab_list[this.current_tab]._is_backup === 1
  },
  is_zzhd_tab() {
    return this.tab_list.length > 0
      && this.tab_list[this.current_tab]
      && this.tab_list[this.current_tab]._is_custom === 'zzhd'
  },
  is_sjhd_tab() {
    return this.tab_list.length > 0
      && this.tab_list[this.current_tab]
      && this.tab_list[this.current_tab]._is_custom === 'sjhd'
  }
}
```

### 模板五分支（顺序不可变，v-else-if 链按下列顺序判定）

1. `v-if="is_monthly_rec_tab"`：本月推荐标签——**顶部 banner 图**（后台轮播图管理 id=8 组配置，高度 50vh，不点击，`v-if="monthly_rec_banner"` 控制无图不显示）+ 置顶"垃圾分类打卡"固定入口卡片（带 `top-pinned-card` 置顶样式）+ 标题图 + 动态活动列表（`monthly_rec_list` 数组，调 `load_monthly_rec_list()` 传 `is_month_recommend=1` 加载）
2. `v-else-if="is_daka_tab"`：打卡标签——顶部写死"垃圾分类打卡"固定入口卡片（带 `top-pinned-card` 置顶样式）+ 下方动态活动列表（`active_list` 数组，调 `get_new_active_list({ community_cate_id: tab.cate_id })` 加载）
3. `v-else-if="is_zzhd_tab"`：自治活动标签——复刻 `pages/zzhd/zzhd.vue` 主体
4. `v-else-if="is_sjhd_tab"`：社交活动标签——复刻 `pages/sjhd/sjhd.vue` 主体 + 末尾"问卷调查"入口卡片
5. `v-else-if="!is_backup_tab"`：中间分类标签（用户新建的活动分类）——显示该标签绑定分类下的活动列表（**不显示置顶卡片**，置顶卡片仅在本月推荐和打卡标签显示）
6. `v-else`：备份标签（已注释，分支保留供后续恢复）

> **顺序很重要**：`is_monthly_rec_tab` 必须放第一个（v-if），`is_daka_tab` 紧随其后（v-else-if），否则本月推荐标签的内容会被打卡标签抢先匹配。`is_zzhd_tab`/`is_sjhd_tab` 必须放在 `!is_backup_tab` 之前。

### 置顶卡片样式（top-pinned-card）—— "垃圾分类打卡"固定入口卡片，所有活动标签都写死显示

"垃圾分类打卡"固定入口卡片在**所有活动分类标签**（本月推荐、打卡、中间分类标签）顶部都**写死静态加** `top-pinned-card` class，在右上角显示"置顶"标签。**不可配置，无需后台开关，用户新建的活动分类标签也会自动显示这张置顶卡片**：

```scss
.top-pinned-card {
    overflow: hidden;
    position: relative;
}
.top-pinned-card::before {
    content: '置顶';
    position: absolute;
    top: 0; right: 0; z-index: 10;
    padding: 6rpx 18rpx;
    font-size: 22rpx; line-height: 1.4;
    color: #fff;
    background: linear-gradient(135deg, #ff6b35 0%, #ff3d00 100%);
    border-bottom-left-radius: 14rpx;
    border-top-right-radius: 10rpx;
    box-shadow: 0 2rpx 6rpx rgba(255, 61, 0, .35);
}
```

> **设计决策（2026-09-01）**：置顶功能**不做后端可配置**。只有"垃圾分类打卡"这张固定入口卡片天然是"置顶"语义（它是写死的固定入口，永远在标签顶部），所以静态加 class 即可。后端不加 `is_top` 字段、PC 后台不加开关、接口排序不变（仍 `list_order asc, id desc`）。普通活动卡片**不加** `top-pinned-card` class。
>
> 如果未来确实需要后端控制活动置顶，再给 `cmf_active` 加 `is_top` 字段 + 接口排序 `is_top desc, list_order asc, id desc` + 前端动态 class 绑定。但目前未实施，保持简单。

### switch_tab 数据加载

- 第一个打卡标签（`index == 0`）：与中间分类标签走相同逻辑——清空 `active_list`/`active_page=1`/`active_total=0`，设 `current_cate_id = tab.cate_id`，调 `load_active_list()`。打卡标签顶部还保留写死的"垃圾分类打卡"入口卡片，列表显示在卡片下方
- 备份标签（`tab._is_backup === 1`）：不加载任何列表，显示页面原内容
- 自治活动标签（`tab._is_custom === 'zzhd'`）：若已登录调 `getUserInfo()` + `get_shop_new()`（复刻 zzhd.vue onShow 逻辑）
- 社交活动标签（`tab._is_custom === 'sjhd'`）：不预加载列表（问卷调查改为入口卡片，点击跳独立页）
- 中间分类标签：与打卡标签相同逻辑——清空状态、设 `current_cate_id = tab.cate_id`、调 `load_active_list()`
- `load_active_list()`：调 `get_new_active_list({ page, community_cate_id })`，第一页覆盖 `active_list`，后续页 push；`onReachBottom` 在打卡标签和中间分类标签下都生效（备份/自治/社交均早期 return）

### get_active_tab_list 默认加载首标签

`onLoad` 调 `get_active_tab_list()`，`.then` 内 push 自定义/备份标签后，**立即用第一条（打卡标签）的 cate_id 触发 `load_active_list()`**，让首屏就有数据：

```js
get_active_tab_list() {
    this.$u.api.get_active_tab_list().then(res => {
        let list = res.data || []
        list.push({ id: -2, name: '自治活动', ... })
        list.push({ id: -3, name: '社交活动', ... })
        this.tab_list = list
        // 默认加载第一个标签（打卡）的活动列表
        let firstTab = list[0]
        if (firstTab && firstTab.cate_id) {
            this.current_cate_id = firstTab.cate_id
            this.active_list = []; this.active_page = 1; this.active_total = 0
            this.load_active_list()
        }
    })
}
```

### 自定义标签方法（为避免与首页原有 navigator 冲突，用前缀区分）

`low_carbon.vue` 原有 `navigator(url, type)` 服务于第一个打卡标签（type==1 走答题考试 gating），**不能改名也不能合并逻辑**。自治/社交标签的跳转方法用前缀区分：

| 方法 | 来源 | 行为 |
|------|------|------|
| `zzhd_navigator(url, type)` | 复刻 zzhd.vue `navigator` | 登录校验 + 督导员权限校验（`check_status != 2 && !type` 拦截） |
| `sjhd_navigator(url)` | 复刻 sjhd.vue `navigator` | 直接 `$u.route(url)`，无任何校验（与 sjhd.vue 一致） |
| `goShopSign()` / `goApplyShop()` / `navigateToMiniProgram(appId)` / `get_shop_new()` | 复刻 zzhd.vue | 商家申请/打卡/跳小程序/拉商家信息 |
| `wj_btn(item)` / `get_health_home_list(type)` | 复刻 research.vue | 问卷跳 `/pages/health/participate?id=` / 拉问卷列表（type=2） |

> zzhd.vue 的 `navigator(url, type)` 中 type 真值表示跳过督导员校验（如督导员申请入口本身不需要先有督导员身份）；与 low_carbon.vue 首页 navigator 的 type==1（答题考试 gating）语义完全不同，**切勿合并**。

### 参考页面（独立路由仍存在，low_carbon.vue 只复刻模板作为标签内容，不改动原页面）

- `小程序/pages/zzhd/zzhd.vue`：自治活动独立页，onShow 调 `getUserInfo+get_shop_new`，navigator 带督导员权限校验
- `小程序/pages/sjhd/sjhd.vue`：社交活动独立页，无 onLoad/onShow 接口调用，navigator 无校验
- `小程序/pages/low_carbon/research.vue`：问卷调查独立页，onLoad 调 `get_health_home_list(2)`，`wj_btn` 跳 `/pages/health/participate?id=`
- `小程序/pages/low_carbon/active-list.vue`：独立活动列表页（用 z-paging），路由参数 `cate_id`，调 `get_new_active_list`。low_carbon.vue 中间标签的卡片样式参考此页，但用普通 view + onReachBottom 而非 z-paging
- `小程序/pages/low_carbon/lsxf/lsxf.vue`：另一参考，调 `get_active_list({ cate_id, page, type })`

## 5. 约束与不要做的事

- 不要删除第一个"打卡"标签顶部的"垃圾分类打卡"入口卡片（写死显示，是用户进入垃圾分类打卡的入口）；下方的动态活动列表可正常加载该标签绑定分类的活动
- 不要把"备份"/"自治活动"/"社交活动"三个写死标签写进 `cmf_active_tab` 表或 PC 后台管理页（仅前端写死，后台不可见）
- 不要让备份/自治/社交标签依赖 `cate_id` 加载分类活动列表（备份只显示页面原内容，自治/社交各有自己的数据加载逻辑）
- 不要改 zzhd.vue / sjhd.vue / research.vue 三个独立页面本身（它们作为独立路由仍然存在，low_carbon.vue 只复刻其模板作为标签内容）
- 不要把 `zzhd_navigator`/`sjhd_navigator` 与首页打卡标签的 `navigator(url, type)` 合并：首页 navigator 的 type==1 是答题考试 gating，zzhd 的 type 真值是跳过督导员校验，语义完全不同
- 不要给打卡/中间分类标签用 `z-paging`（页面已有 `onReachBottom`，用普通 view + 数组 push 即可）
- 不要在 `get_active_tab_list` 返回前就 push 自定义/备份标签（接口失败时 `tab_list` 为空，push 后会变成只有自定义+备份标签，首屏错乱；要在 `.then` 里拿到 list 后再 push）
- 不要调换 tab_list push 顺序：必须是 自治活动 → 社交活动 → 备份（顺序错乱会让 is_backup_tab 的 `current_tab == length-1` 判定失真）
- 后台所有标签（含固定打卡标签）提交时**必须绑定分类**（`setParamData` 校验）；固定标签表单也带 cate_id 字段，跳过校验的情况已废弃
- 不要在 onShow 强制检查里省略 `_isChecking` 标志位（onShow 可能在 getUserInfo 返回前被重复触发，会导致重复跳转）
- 不要让 `getUserInfo()` 不返回 promise（onShow 链式 .then 依赖该返回值才能拿到最新 vuex 状态做检查）
- 小程序活动页标签**最多 4 个**（`setAddPost` 校验，仅针对后台 `cmf_active_tab` 表；前端写死的自治/社交/备份三个不计入此限制）
- **改表结构前先 `SHOW COLUMNS` 确认字段缺失**，不要盲目 ALTER（生产库操作要谨慎）
- 修改 active_tab 表结构后，要同步检查 `data/sql/active_tab.sql` 脚本是否需要更新（保持脚本与生产库一致，方便新环境部署）
- 后台固定打卡标签原 cate_id=0 的记录需手动编辑绑定一个分类，否则前端打卡标签的动态活动列表会显示"暂无活动"（且后台提交会因 setParamData 校验报错）
- 不要把 `.side-toggle-btn`（折叠/展开把手）挪进 `.side-bar-col` 内部：它是 `position:fixed` 且必须在侧栏容器外，否则折叠时被 `overflow:hidden` 裁掉，页面失去展开入口
- 不要只单方面改侧栏宽度或把手 `left`：`.side-bar-col { width:180rpx }` 与 `.side-toggle-btn { left:180rpx }` 必须同步修改（含两处 `transition` 时长一致），否则把手不吸附在侧栏右边缘
- 不要给 `scroll-view.side-bar` 重新加 `width`/`background`/`border-right`/高度：这些已上移到 `.side-bar-col`，重复设置会导致折叠动画和边框表现异常
- 不要把折叠状态改成两个按钮 `v-if` 互斥、也不要做成 `storage` 持久化或与 `switch_tab` 联动：当前设计是单个常驻把手 + `side_collapsed` 会话内布尔值，默认展开
