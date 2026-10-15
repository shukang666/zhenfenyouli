---
name: "mp-location-type-form"
description: "小程序表单根据类型字段动态切换 UI 和校验的通用模式。记录'一个 type 值决定展示哪套表单字段、提交时校验不同必填项'的实现，包括 v-if 分区渲染、独立校验逻辑、computed 动态控制提交按钮可用状态、复用现有接口加载下拉选项等。当遇到'根据类型展示不同表单''不同类型需要不同必填字段''选择小区内要选点、选商户要填地址'这类需求时调用。"
---

# 小程序表单根据类型动态切换 UI 和校验的通用模式

## 适用场景

业务表单中某条记录的"位置类型"不同，需要展示的字段和校验规则也不同。典型案例：

- 「随手拍」小区内（`location_type=1`）→ 选小区 + 选点位；非小区（`location_type=2`）→ 填商户名称 + 地址
- 活动报名按类型切换：个人报名 vs 团体报名
- 问题反馈按类型切换：建议 vs 投诉 vs 求助

核心诉求：**一个字段值控制多套表单切换，校验规则联动变化。**

## 通用实现结构

### 1. 模板层：v-if 分区渲染

用 `v-if` 按 type 各自包裹一套独立的表单块，不要让两套字段混在同一容器里：

```html
<!-- 位置类型切换 -->
<u-radio-group v-model="form.location_type">
    <u-radio :name="1">小区内</u-radio>
    <u-radio :name="2">非小区</u-radio>
</u-radio-group>

<!-- 小区内：选小区 + 选点位 -->
<view v-if="form.location_type == 1">
    <!-- 小区名称（只读展示） -->
    <view class="location-display">
        <u-icon name="home" size="28" color="#18AF38"></u-icon>
        <text class="location-text">{{ communityName || '未绑定小区' }}</text>
    </view>
    <!-- 点位选择（点开 picker） -->
    <view class="location-picker" @click="showPointPicker = true">
        <text v-if="form.point_name">{{ form.point_name }}</text>
        <text v-else class="location-placeholder">请选择点位</text>
        <u-icon name="arrow-right" size="24" color="#999"></u-icon>
    </view>
</view>

<!-- 非小区：商户名称 + 地址 -->
<view v-if="form.location_type == 2">
    <input v-model="form.merchant_name" placeholder="请输入商户名称" />
    <input v-model="form.address" placeholder="请输入详细地址" />
</view>
```

### 2. data 层：字段归一化

不管哪种类型，都在同一个 `form` 对象里平铺字段，互不污染：

```js
data() {
    return {
        form: {
            // 公共字段
            location_type: 1,   // 默认小区内
            image: '',
            remark: '',
            // 小区内专用
            community_id: 0,
            community_name: '',
            point_id: 0,
            point_name: '',
            // 非小区专用
            merchant_name: '',
            address: '',
        },
        // UI 状态
        communityId: 0,
        communityName: '',
        pointList: [],
        showPointPicker: false,
    }
}
```

### 3. 校验层：computed 动态汇总

根据 `location_type` 走不同校验规则，最后汇总成一个 `canSubmit` 控制按钮可用状态：

```js
computed: {
    canSubmit() {
        // 公共必填项
        if (!this.form.image) return false
        if (!this.form.remark) return false
        // 按类型分校验
        if (this.form.location_type == 1) {
            // 小区内：必选点位
            return this.form.point_id > 0
        } else {
            // 非小区：必填商户名和地址
            return this.form.merchant_name.trim() !== '' && this.form.address.trim() !== ''
        }
    }
}
```

> 为什么用 computed 而不是提交时校验：用户在选择类型的瞬间就能看到按钮变灰/变亮，交互反馈更及时；也避免用户填完后才发现"这个类型下另一个字段漏填"。

### 4. 提交层：字段全部透传

提交时把整个 form 传给后端，**不做按类型裁剪**——后端根据 `location_type` 决定校验哪些字段：

```js
async submit() {
    if (!this.canSubmit) {
        uni.showToast({ title: '请完善必填信息', icon: 'none' })
        return
    }
    let res = await this.$u.api.postShotReport(this.form)
    // 后续 toast / 返回
}
```

### 5. 接口复用：加载下拉选项

小区内的"点位"选项来自现有接口，直接复用，不用新开发：

```js
// 用户小区信息（自动加载）
async loadUserCommunity() {
    let res = await this.$u.api.getCommunityCateList()
    if (res.data && res.data.communityInfo) {
        this.communityId = res.data.communityInfo.id
        this.communityName = res.data.communityInfo.name
        this.form.community_id = res.data.communityInfo.id
        this.form.community_name = res.data.communityInfo.name
        // 小区加载完后自动加载点位列表
        this.loadPointList()
    }
},
// 点位列表
async loadPointList() {
    let res = await this.$u.api.getPointList({ community_id: this.communityId })
    this.pointList = res.data || []
},
```

### 6. 后端校验：按类型分支

后端 Controller 同样按 `location_type` 分支校验：

```php
$locationType = intval($this->request->param('location_type', 1));

// 读取所有字段（含两套）
$communityId   = intval($this->request->param('community_id', 0));
$pointId       = intval($this->request->param('point_id', 0));
$merchantName  = $this->request->param('merchant_name', '');
$address       = $this->request->param('address', '');

if ($locationType == 1) {
    if ($communityId <= 0) $this->error('请绑定小区');
    if ($pointId <= 0)     $this->error('请选择点位');
} else {
    if (empty($merchantName)) $this->error('请填写商户名称');
    if (empty($address))      $this->error('请填写详细地址');
}
```

## 数据库字段设计

在原有表上直接加列，不做拆表：

```sql
ALTER TABLE `cmf_shot_report` ADD COLUMN `location_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1小区内 2非小区';
ALTER TABLE `cmf_shot_report` ADD COLUMN `community_id` INT(11) NOT NULL DEFAULT '0' COMMENT '小区ID';
ALTER TABLE `cmf_shot_report` ADD COLUMN `community_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '小区名称';
ALTER TABLE `cmf_shot_report` ADD COLUMN `point_id` INT(11) NOT NULL DEFAULT '0' COMMENT '点位ID';
ALTER TABLE `cmf_shot_report` ADD COLUMN `point_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '点位名称';
ALTER TABLE `cmf_shot_report` ADD COLUMN `merchant_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '商户名称';
ALTER TABLE `cmf_shot_report` ADD COLUMN `address` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '详细地址';
```

> 不拆表的原因：随手拍整体记录数不大，类型切换只涉及一套表单的字段差异，没必要为小区内/非小区各自建表。

## 后台列表展示新字段

在 PC 后台列表加"位置信息"列，按类型展示不同内容（详见 `pc-admin-add-column` skill）：

```html
<td>
    <if condition="vo.location_type == 1">
        <span class="text-info">{$vo.community_name} · {$vo.point_name}</span>
    <else/>
        <span class="text-warning">{$vo.merchant_name} - {$vo.address}</span>
    </if>
</td>
```

## 注意事项

- **`v-if` 切换时字段不自动清空**：用户选了小区内填了商户名，再切回小区内时 `form.merchant_name` 还留着。如果介意可在 `@change` 里手动清空对面字段，**后端按类型校验字段即可，前端通常不需要清**。
- **初始类型**：`location_type` 给默认值（本案例默认 1=小区内），避免初始渲染时两个 `v-if` 块都不显示的空白期。
- **公共字段与类型字段别混**：公共字段（图片、备注）放 form 顶层，不要嵌套在 `form.community_info` 里面，否则模板里会写成 `v-model="form.community_info.point_id"` 层级过深。
- **按钮禁用 vs 校验提示**：用 `computed` 控制按钮禁用是体验优先；但提交时仍要在后端再校验一遍（前端校验可被绕过）。

## 相关 skill

- `pc-admin-add-column`：后台列表页同步加列展示新字段
- `prod-db-change-and-deploy-verify`：生产库加字段的流程
- `shot-report`：本模式的实际落地案例（随手拍位置信息扩展）
- `mp-uview-components`：u-radio-group / u-select / u-picker 等组件用法
