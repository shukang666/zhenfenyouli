---
name: business-data-model
description: "项目业务数据模型与程序逻辑总览。从数据库结构 + 代码逻辑角度描述社区环保小程序 + PC 后台管理系统：用户体系（cmf_member/cmf_user/小区关联）、垃圾分类打卡与督导、社区低碳活动、环保积分商城、问卷调查、会员等级与 VIP、商家入驻、社区论坛等核心实体表结构与关联关系。当需要理解项目整体数据模型、确认表间关联或跨模块排查数据问题时调用。"
---

# 业务数据模型与程序逻辑 SKILL

> 本文档从**数据库结构 + 代码逻辑**角度描述系统的完整业务模型，帮助 AI 理解数据流动和程序逻辑。

---

## 一、系统概述

### 1.1 系统定位
这是一个**社区环保小程序 + PC 后台管理系统**，核心功能包括：
- 垃圾分类打卡与督导
- 社区低碳活动管理
- 环保积分商城（积分兑换商品）
- 问卷调查（健康/环保）
- 会员等级与 VIP 套餐
- 商家入驻与订单管理
- 社区论坛（健康生活互助）
- 社区活动策划与执行

### 1.2 技术架构
- **PC 后台**：ThinkPHP + CMF，路径 `app/admin/`
- **小程序 API**：ThinkPHP，路径 `api/wxapp/`
- **数据库**：MySQL，表前缀 `cmf_`

管理员账号在 `cmf_user`。同一套账密会进四个入口，**登录态必须分开**：电脑后台和驾驶舱用 `ADMIN_ID`，外勤 Pad 用 `FIELD_WORKER_*`，手机 OA 用 `OA_*`。OA 地址 `/admin/oa/login`，细节见 `oa-portal`。

### 1.3 核心实体关系图

```
┌─────────────────────────────────────────────────────────────────────┐
│                          用户体系                                    │
│  cmf_member  ←  cmf_user                                            │
│  (小程序用户)      (后台管理员)                                       │
│      │                                                               │
│      ├── cmf_member_community → cmf_member_community_cate           │
│      │   (用户-小区关联)         (小区分类:点位/桶站点)               │
│      │                                                               │
│      ├── cmf_member_community_parent (社区/街道办)                   │
│      │                                                               │
│      ├── cmf_user_account_log (积分/余额变动日志)                    │
│      │                                                               │
│      ├── cmf_member_level (用户等级体系)                             │
│      │                                                               │
│      ├── cmf_member_check (督导员申请审核)                           │
│      │                                                               │
│      └── cmf_member_reservation (预约记录)                           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                          活动体系                                    │
│  cmf_active (活动主表)  ←  cmf_active_cate (活动分类)               │
│      │                                                               │
│      ├── cmf_active_user (用户参与活动记录)                          │
│      ├── cmf_active_sign (签到/报名记录)                             │
│      ├── cmf_active_clock (打卡记录)                                 │
│      ├── cmf_active_clock_action (打卡动作配置)  ← 新               │
│      │   ├── cmf_active_clock_action_form (表单字段)                 │
│      │   ├── cmf_active_clock_action_user (用户表单提交)             │
│      │   ├── cmf_active_clock_push (推送消息配置)                    │
│      │   └── cmf_active_clock_push_user (推送目标记录)               │
│      ├── cmf_active_tab (活动 Tab 配置)                              │
│      ├── cmf_active_community_cate (社区活动分类)                    │
│      └── cmf_member_badge (用户勋章)                                 │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      论坛/帖子体系                                   │
│  cmf_forum (帖子主表)  ←  cmf_forum_cate (分类，pid=2为健康生活社区) │
│      │                                                               │
│      ├── cmf_forum_comment (评论表,关联 info_id)                    │
│      ├── cmf_forum_like (点赞表, rid + type + forum_type)           │
│      ├── cmf_forum_handle (处理记录 - 用于点位巡检/桶边督导)         │
│      ├── cmf_forum_msg_log (消息日志)                                │
│      ├── cmf_forum_msg_user (用户消息)                               │
│      └── cmf_forum_delete_log (删除日志)                             │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      商城/订单体系                                   │
│  cmf_shop_goods  ←  cmf_shop_category                               │
│      │                                                               │
│      ├── cmf_shop_goods_sku (SKU)                                   │
│      ├── cmf_shop_order  ←  cmf_shop (商家表)                       │
│      │   └── cmf_shop_order_detail (订单详情)                       │
│      ├── cmf_shop_cart (购物车)                                     │
│      ├── cmf_shop_address (收货地址)                                 │
│      └── cmf_shop_express (快递)                                    │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     会员/优惠券体系                                  │
│  cmf_vip (VIP套餐)                                                  │
│  cmf_coupon (优惠券)  ←  (coupon_type 1答题券 2商家券)              │
│  cmf_coupon_user (用户优惠券)                                       │
│  cmf_member_level (用户等级)                                        │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     垃圾分类知识体系                                  │
│  cmf_waste (分类知识)  ←  cmf_waste_cate (知识分类)                 │
│  cmf_waste_cate1 (物品分类一级:回收垃圾/有害/厨余/其他)             │
│  cmf_waste_user (用户学习记录)                                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     问卷调查体系                                     │
│  cmf_exam (问卷/活动配置)  ←  cmf_exam_cate                        │
│  cmf_exam_question (题目)                                           │
│  cmf_exam_record (答题记录)                                         │
│  cmf_exam_code (激活码)                                             │
│  cmf_exam_gift (奖品记录)                                           │
│  cmf_exam_user (用户)                                               │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     县/村风采体系                                    │
│  cmf_zl_cate (分类)                                                 │
│  cmf_zl_village (村庄内容)                                          │
│  cmf_xx_question (问卷题目,区别于 exam)                             │
│  cmf_xx_answer (问卷答案)                                           │
│  cmf_xx_answer_item (选项)                                          │
│  cmf_xx_bank (题库)                                                 │
│  cmf_xx_problem (问题反馈)                                          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 二、核心业务逻辑详解

### 2.1 会员体系 (cmf_member)

#### 2.1.1 表结构要点
```sql
cmf_member (
    id,                  -- 主键
    nickname,            -- 昵称（注册默认「微信用户_{id}」，微信授权后为微信昵称）
    true_name,           -- 真实姓名
    type,                -- 1:普通用户 2:业务员 3:分销员
    mobile,              -- 手机号
    community_parent_id, -- 所属社区/街道ID
    community_id,        -- 所属小区ID（核心字段）
    shop_balance_total,  -- 商家累计余额
    shop_balance,        -- 商家可用余额
    balance,             -- 用户现金余额
    pid,                 -- 上级推荐人ID（一级）
    spid,                -- 上上级ID（二级分销）
    openid,              -- 微信 openid
    point,               -- 低碳币/积分
    vcode,               -- 个人推广码
    avatar,              -- 头像
    status,              -- 0:禁用 1:正常
    level,               -- 用户等级（自动升级）
    is_exam,             -- 1:未答题 2:已答题（垃圾分类考试）
    is_environmental,    -- 是否为环保达人（连续打卡达标自动授予）
    is_supervisor,       -- 是否为督导员（需审核）
    is_merchant,         -- 是否为商户
    vip_indate,          -- VIP 有效期（时间戳）
    sn,                  -- 用户编号
    ali_account,         -- 支付宝账号（提现用）
    ali_name             -- 支付宝姓名
)
```

#### 2.1.2 业务规则
- **注册**：用户首次进入小程序时通过 `openid` 自动创建 `cmf_member` 记录，随后写入默认资料 `true_name`/`nickname = '微信用户_' + id`、`avatar`（取后台 `set_config.app_logo`，无则用微信默认头像，见 `PublicController::login`）；微信授权登录时改用微信昵称/头像
- **昵称/头像**：个人页 `pages/user/info.vue` 通过 `updateMember({ nickname, avatar })` 修改，成功后重新 `getUserInfo` 刷新。小程序端展示统一取 `userInfo.nickname`（首页问候卡、论坛/打卡列表等都用它，**不是 `true_name`**）
- **小区绑定**：用户必须绑定 `community_id` 才能参与垃圾分类打卡
- **等级系统**：`MemberLevelModel::isLevel($user_id)` 根据积分自动升级
- **环保达人**：垃圾分类打卡（forum type=2）连续 N 天后自动设置 `is_environmental=1`
- **推广码**：每个用户有唯一 `vcode`，用于分销追踪
- **分销层级**：`pid`（一级）、`spid`（二级）

#### 2.1.3 关联关系
```
cmf_member.community_id → cmf_member_community.id
cmf_member.community_parent_id → cmf_member_community_parent.id
cmf_member.id = cmf_user_account_log.user_id (积分日志)
cmf_member.id = cmf_forum.user_id (帖子)
cmf_member.id = cmf_shop_order.user_id (订单)
```

---

### 2.2 活动体系 (cmf_active)

#### 2.2.1 表结构要点
```sql
cmf_active (
    id,
    name,                -- 活动名称
    thumb,               -- 封面图
    type,                -- 1:社区活动 2:环保主题日活动（枚举）
    cate_id,             -- 活动分类ID → cmf_active_cate.id
    community_cate_id,   -- 社区活动分类ID
    is_put,              -- 1:不推荐 2:首页推荐
    is_clock,            -- 是否需要打卡
    clock_start_time,    -- 打卡开始时间
    clock_end_time,      -- 打卡结束时间
    sign_day,            -- 连续签到天数要求（默认5天）
    is_badge,            -- 是否有徽章奖励
    badge_name,          -- 徽章名称
    badge_image,         -- 徽章图标
    is_recommendation,   -- 是否推荐 1:是
    point,               -- 报名消耗积分
    send_point,          -- 签到赠送积分
    share_point,         -- 分享活动获得积分
    role,                -- 支持参与的身份列表 (如 "1,2,3,4")
    address,             -- 活动地址
    lat, lng,            -- 活动坐标
    phone,               -- 联系方式
    qr_code,             -- 小程序码路径
    content,             -- 活动详情 (HTML)
    day_type,            -- 日期类型（半天/全天）
    area_range           -- 地区范围
)
```

#### 2.2.2 业务规则
- **活动类型**：从代码中可以看到 `type=1` 为"社区活动"，`type=2` 为"环保主题日活动"
- **报名与签到**：`cmf_active_sign` 记录用户报名；`cmf_active_clock` 记录每日打卡
- **连续签到判断**：通过 `sign_day` 配置需要的连续天数，代码中 `MemberBadgeModel` 会检查
- **活动打卡动作配置**：`cmf_active_clock_action` 表配置了打卡后的行为（表单/推送）
- **徽章授予**：连续签到达标后，系统自动授予徽章到 `cmf_member_badge`

#### 2.2.3 打卡动作配置（新开发功能）
```
cmf_active_clock_action -- 一个活动可配置多个动作
  ↓
cmf_active_clock_action_form -- 动作关联的表单字段
  ├── type: radio（单选按钮）/ textarea（多行文本）/ text（纯文本说明）
  ↓
cmf_active_clock_action_user -- 用户提交了表单的记录

cmf_active_clock_push -- 动作关联的推送消息配置
  ├── 可配置动态字段（时间、地点等）
  ├── 通过微信服务消息推送给已订阅用户
  ↓
cmf_active_clock_push_user -- 推送目标用户记录
```

> 后台"打卡"菜单容易混淆，区分见 `admin-page-locator`：
> 社区活动→打卡记录（`ActiveClock/index`，读写 `cmf_active_clock`）、社区活动→打卡动作（`active_clock_action`，读写 `cmf_active_clock_action*`）、垃圾分类活动→垃圾打卡（`forum/index2`，读写 `cmf_forum` type=2）、自治活动→商户打卡（`handle/index`）。

---

### 2.3 论坛/帖子体系 (cmf_forum)

#### 2.3.1 表结构要点 - 核心 type 字段
```sql
cmf_forum (
    id,
    type,                -- 帖子类型（核心字段！）
                         -- 1: 健康生活社区
                         -- 2: 垃圾打卡
                         -- 3: 点位巡检
                         -- 4: 桶边督导
                         -- 5: 互助平台
                         -- 6: 优秀事迹
                         -- 7: 环保论坛
    cate_id,             -- 分类ID → cmf_forum_cate.id
    cate_child_id,       -- 子分类ID
    user_id,             -- 发布者ID
    community_id,        -- 小区ID
    subject_community_id,-- 提交时的小区ID
    title,               -- 标题
    content,             -- 文字内容
    image,               -- 多图（JSON数组）
    image_one,           -- 点位照片（巡检用）
    image_two,           -- 投放指南（巡检用）
    image_three,         -- 垃圾桶照片（巡检用）
    status,              -- 0:待支付 1:待审核 2:审核通过 3:审核失败
    is_top,              -- 1:不置顶 2:置顶
    is_anonymous,        -- 1:不匿名 2:匿名
    is_admin,            -- 1:用户 2:平台官方
    like_total,          -- 点赞数（冗余统计）
    view_num,            -- 浏览量
    point,               -- 低碳币（垃圾分类首次打卡赠送）
    price,               -- 价格（互助平台可用）
    top_end_time,        -- 置顶到期时间
    msg                  -- 附加消息
)
```

#### 2.3.2 各类帖子逻辑

**type=2 垃圾打卡**：
- 用户每天可发布一次，提交垃圾分类照片
- 首次发布赠送积分（`carbon` 配置项控制金额）
- 连续打卡 N 天后自动升级为环保达人（`is_environmental=1`）
- 需绑定小区才能打卡

**type=3 点位巡检**：
- 检查分类投放点位是否合规
- 需提交三张照片：点位照片、投放指南、垃圾桶照片
- 巡检间隔时间由 `cmf_member_community_cate.inspection_hour` 控制
- 巡检成功赠送积分 `inspection_point`

**type=4 桶边督导**：
- 督导员在垃圾桶旁边指导居民投放
- 类似巡检，但侧重督导工作记录

**type=1 健康生活社区**：
- 居民发帖交流健康/生活话题
- 发帖赠送积分（`health_point` 配置项控制）

**type=5 互助平台**：
- 居民互助信息发布
- 可有偿互助（price 字段）

**type=6 优秀事迹**：
- 环保优秀行为记录

---

### 2.4 商城/订单体系

#### 2.4.1 订单状态流转
```
状态码: 1待付款 → 2已付款/待发货 → 4已发货/待收货 → 6已收货/待评价 → 8已完成
                 ↓
              10未付款取消
              12退款申请
              14退款不通过
              16退款通过
```

#### 2.4.2 订单关键字段
```sql
cmf_shop_order (
    shop_id,             -- 商家ID（0表示平台自营）
    user_id,             -- 用户ID
    order_num,           -- 唯一订单号
    status,              -- 订单状态（见上）
    pay_type,            -- 0未支付 1微信 2余额 3积分
    order_type,          -- 1普通订单 2积分订单
    pay_log_type,        -- 1购物车订单号 2单个订单号
    goods_price,         -- 商品总价
    express_price,       -- 运费
    total_price,         -- 商品+运费
    coupon_price,        -- 优惠券抵扣
    price,               -- 最终支付价
    balance_pay,         -- 余额支付金额
    point_pay,           -- 积分抵扣金额
    point,               -- 赠送积分
    point_amount,        -- 积分抵扣后金额
    discount_price,      -- 折扣金额
    delivery_type,       -- 1快递 2自提
    position_id,         -- 自提点ID
    province/city/county,-- 省市区
    address,             -- 详细地址
    username,            -- 收货人
    phone,               -- 联系电话
    express_name,        -- 快递公司
    express_sn,          -- 快递单号
    purchase_link,       -- 1688采购链接（后台可见）
    delivery_time        -- 自提时间
)
```

---

### 2.5 积分系统

#### 2.5.1 积分来源与 `cmf_user_account_log`
```sql
cmf_user_account_log (
    user_id,             -- 用户ID
    account_type,        -- 1余额 2积分/低碳币 3商家余额
    change_amount,       -- 变动金额
    left_amount,         -- 变动后余额
    type,                -- 操作类型码（见下）
    remark,              -- 备注说明
    param,               -- JSON扩展参数
    from_uid,            -- 资金来源用户ID
    order_num,           -- 关联订单号
    is_op,               -- 2:手动操作（后台调节）
    delete_time          -- 删除标记
)
```

#### 2.5.2 常见 type 操作码
| type | 含义 | 触发场景 |
|------|------|----------|
| 200 | 低碳币增加 | 发布健康生活帖子、首次垃圾分类打卡 |
| 222 | 积分增加 | 点位巡检 |
| 222 | 积分变动 | 多种积分操作 |

---

### 2.6 垃圾分类知识体系

#### 2.6.1 数据结构
```sql
cmf_waste (分类知识文章)
  ├── cate_id → cmf_waste_cate (知识分类:分类活动等)
  ├── content: 文章内容
  ├── thumb: 封面图
  │
cmf_waste_cate1 (物品分类一级)
  ├── pid: 上级ID
  ├── 示例: 回收垃圾、有害垃圾、厨余垃圾、其他垃圾
  │
cmf_waste_user (用户学习记录)
  ├── user_id → 用户
  ├── waste_id → cmf_waste (学过的知识)
  └── cate_id → cmf_waste_cate
```

---

### 2.7 优惠券体系

#### 2.7.1 优惠券类型
```sql
cmf_coupon (
    coupon_type,         -- 1答题券（平台发） 2商家券
    type,                -- 1通用 2品类 3单品
    discount_type,       -- 1满减券 2现金券 3折扣券
    amount,              -- 面额/折扣金额
    full_amount,         -- 满额要求
    count,               -- 剩余数量（负数表示已超额领取）
    limit,               -- 每人限领数
    start_time/end_time, -- 活动时间
    indate_day,          -- 领取后有效天数
    goods_id,            -- 适用商品
    cate_id,             -- 适用品类
    shop_id              -- 商家ID
)
```

#### 2.7.2 用户优惠券状态
```
cmf_coupon_user (
    user_id,
    coupon_id,
    coupon_type,
    status,              -- 0删除 1正常
    used                 -- 0未使用 1已使用 2已过期
)
```

---

### 2.8 问卷调查体系 (cmf_exam)

#### 2.8.1 双重体系说明
系统有两套问卷/答题体系：
1. **cmf_exam 体系**：完整的问卷系统，支持抽奖、健康问卷、预约等
2. **cmf_xx_question 体系**：简化的问答系统，用于县/村风采模块

#### 2.8.2 cmf_exam 关键字段
```sql
cmf_exam (
    is_exam,             -- 1问卷 2预约
    is_repeat,           -- 1不可重复答 2可重复答
    is_demand,           -- 1具体诊疗需求 2否
    status,              -- 1开启 2关闭
    health_id,           -- 关联健康ID
    gift_point,          -- 赠送积分
    gift_name,           -- 奖品名称
    age,                 -- 男性中奖年龄段
    gage,                -- 女性中奖年龄段
    skin,                -- 肤质中奖选项
    cate_id,             -- 分类
    option,              -- JSON配置选项
    secret,              -- 隐私协议
    is_secret            -- 1显示隐私协议 2不显示
)
```

---

### 2.9 小区体系

#### 2.9.1 层级关系
```
cmf_member_community_parent (社区/街道，如"七贤岭街道")
        ↓
cmf_member_community_cate (小区分类，如"督导点位""投放点")
        ↓
cmf_member_community (小区，如"中铁诺德滨海花园")
        ↓
cmf_member (用户绑定社区)
```

#### 2.9.2 cmf_member_community_cate - 巡检配置
```sql
cmf_member_community_cate (
    inspection_hour,     -- 巡检间隔（小时）
    inspection_point     -- 巡检奖励积分
)
```

---

### 2.10 健康管理体系

```sql
cmf_health_disease (健康疾病)
  ├── cate_id → cmf_health_disease_cate
cmf_health_home (首页配置)
```

---

### 2.11 VIP 套餐

```sql
cmf_vip (
    vip_name,            -- 套餐名称
    price,               -- 价格
    month,               -- 月数
    give_balance,        -- 赠送金额
    sell_count,          -- 销售计数
    status               -- 状态
)
```

---

## 三、API 接口与数据流向

### 3.1 小程序端核心接口

| 模块 | Controller | 核心接口 |
|------|-----------|---------|
| 活动 | `api/wxapp/controller/ActiveController.php` | `get_active_list`, `add_active_user`, `get_active_user_list` |
| 论坛 | `api/wxapp/controller/ForumController.php` | `add_forum`, `get_forum_list`, `forum_like` |
| 商城 | `api/wxapp/controller/ShopController.php` | `get_shop`, `get_shop_order_list`, `shop_send` |
| 订单 | `api/wxapp/controller/OrderController.php` | 订单创建、支付、退款 |
| 会员 | 通过 `cmf_member` 操作 | 登录、积分、余额 |
| VIP | `api/wxapp/controller/VipController.php` | 套餐购买、记录 |
| 考试 | `api/wxapp/controller/ExamController.php` | 答题、结果 |

### 3.2 后台管理端

| 模块 | Controller | 文件路径 |
|------|-----------|---------|
| 活动 | `ActiveController.php` | app/admin/controller/ |
| 论坛 | `ForumController.php` | app/admin/controller/ |
| 打卡记录 | `ActiveClockController.php` | app/admin/controller/ |
| 打卡动作 | `ActiveClockActionController.php` | app/admin/controller/ |
| 订单 | `ActiveController.php` | app/admin/controller/ |

---

## 四、关键配置项（cmf_option）

```
set_config: {
    health_point,        -- 健康生活发帖积分
    carbon,              -- 垃圾分类首次打卡积分
    continuous_check,    -- 连续签到天数（环保达人判定）
    platform_mobile      -- 客服电话
}
```

从代码中 `cmf_get_option('set_config')` 读取这些配置。

---

## 五、常见业务操作对应表

### 5.1 用户进入小程序
```
1. 通过 openid 查找/创建 cmf_member 记录
2. 更新 last_login_time
3. 返回用户信息 (point, balance, community_id 等)
```

### 5.2 发布垃圾分类打卡 (forum type=2)
```
1. 检查 community_id 是否存在
2. 检查是否今日已打卡
3. 插入 cmf_forum (type=2, status=1待审核)
4. 如果是今日首次：
   - forge.point = carbon（积分）
   - 增加 user_account_log（积分变动）
5. 检查连续打卡天数
   - 如果达标且 is_environmental=0：设置 is_environmental=1
```

### 5.3 创建活动
```
1. 后台创建 cmf_active 记录
2. 配置打卡时间 (clock_start_time, clock_end_time)
3. 配置连续签到天数 (sign_day)
4. 配置身份限制 (role)
5. 如需打卡动作：在 cmf_active_clock_action 配置
```

### 5.4 提交订单
```
1. 验证商品库存（cmf_shop_goods_sku）
2. 创建 cmf_shop_order (status=1待付款)
3. 创建 cmf_shop_order_detail（订单商品明细）
4. 支付回调：
   - 更新 status=2
   - 记录 user_account_log（积分/余额变动）
   - 增加 goods 销量
5. 发货：更新 status=4 + express 信息
6. 收货：更新 status=6
7. 完成：更新 status=8
```

### 5.5 申请成为督导员
```
1. 用户填写督导员申请 → cmf_member_check
2. 后台审核通过
3. 更新 cmf_member.is_supervisor = 1
```

---

## 六、编码规范约定

### 6.1 布尔字段惯用法
系统中广泛使用 `tinyint(1)` 表示布尔值：
- `1` = 否 / 默认
- `2` = 是

例如：
- `is_top = 2` 表示置顶
- `is_admin = 2` 表示平台官方
- `is_anonymous = 2` 表示匿名
- `status = 1` 表示启用

### 6.2 软删除
大多数表使用 `delete_time` 字段实现软删除：
- `delete_time = 0` → 正常
- `delete_time > 0` → 已删除（Unix 时间戳）

### 6.3 时间存储
所有时间字段统一使用 **Unix 时间戳**（int 类型），不使用 datetime。

### 6.4 图片存储
- **单图**：`varchar(255)`，存储相对路径，如 `xcxkf052/admin/20260901/xxx.jpg`
- **多图**：`text` 类型，存储 JSON 数组字符串，如 `["http://oss.ausite.cn/xxx.jpg"]`
- 前端展示时需要调用 `cmf_get_asset_url()` 拼接完整 URL

### 6.5 外键惯例
系统不使用数据库外键约束，外键关系通过字段命名约定实现：
- `user_id` → `cmf_member.id`
- `active_id` → `cmf_active.id`
- `shop_id` → `cmf_shop.id`
- `coupon_id` → `cmf_coupon.id`
- `community_id` → `cmf_member_community.id`

---

## 七、新增功能开发指南

### 7.1 开发打卡动作配置时的注意事项

1. 活动主表已有 `is_clock` 字段标识是否需要打卡
2. 打卡动作表 (`cmf_active_clock_action`) 通过 `active_id` 关联
3. 一个活动可配置多个动作，支持上移下移排序
4. 两种通知方式：
   - **即时表单通知**：打卡后让居民填写表单（`cmf_active_clock_action_form`）
   - **滞后服务消息推送**：先记录，后续通过 `cmf_active_clock_push` 推送

### 7.2 后台表命名规范
- 主表：`cmf_xxx`
- 详情表：`cmf_xxx_detail` 或 `cmf_xxx_xxx`
- 关系表：`cmf_xxx_yyy`
- 日志表：`cmf_xxx_log`

---

## 八、常见查询参考

### 8.1 查询用户积分变动记录
```sql
SELECT * FROM cmf_user_account_log 
WHERE user_id = ? AND account_type = 2 
ORDER BY create_time DESC;
```

### 8.2 查询活动的所有参与者
```sql
SELECT m.nickname, m.avatar, au.* 
FROM cmf_active_user au
JOIN cmf_member m ON au.user_id = m.id
WHERE au.active_id = ? AND au.type = 1
ORDER BY au.create_time DESC;
```

### 8.3 查询用户当月的垃圾分类打卡记录
```sql
SELECT * FROM cmf_forum 
WHERE user_id = ? 
  AND type = 2 
  AND create_time BETWEEN UNIX_TIMESTAMP(DATE_FORMAT(NOW(), '%Y-%m-01')) AND UNIX_TIMESTAMP(LAST_DAY(NOW()))
ORDER BY create_time DESC;
```

### 8.4 查询小区的督导点位
```sql
SELECT * FROM cmf_member_community_cate 
WHERE community_id = ? AND inspection_hour > 0
ORDER BY list_order ASC;
```

---

## 九、数据总结

| 数据库文件 | 路径 |
|-----------|------|
| 建表 SQL | `data/sql/` |
| 数据库配置 | `data/config/database.php` |
| 数据库结构 JSON（临时） | `db_structure.json` |
