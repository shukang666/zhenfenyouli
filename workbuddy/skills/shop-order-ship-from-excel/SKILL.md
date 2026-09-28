---
name: shop-order-ship-from-excel
description: xiayuxuan.com 积分商城：根据发货 Excel 批量匹配待发货订单，写入快递公司与单号并改为待收货。Trigger phrases: 批量发货, 运单号导入, 订单发货 Excel, 华月运单, 待发货填物流, shop order ship, 发货表格.
agent_created: true
---

# 商城订单：Excel 批量发货（xiayuxuan.com）

在 **ThinkCMF6 项目 `xiayuxuan.com`** 中，用打单/仓库导出的 **xlsx** 为后台「订单中心」里 **待发货** 订单填物流并改为 **待收货**。

## 何时启用本 Skill

用户提到：发货表、运单号、申通、待发货批量处理、把 Excel 和订单对上，或 @ 了 `.xlsx` 发货文件。

## 用户只需提供

1. **Excel 文件路径**（可拖拽到 WorkBuddy，或放在 `public/` 下）
2. 可选：**只处理表中部分行**（如仅「华月/月饼」）、是否 **同步微信发货**（默认 **否**）
3. 明确一句：**先 dry-run** 或 **直接执行**（未说明时 **必须先 dry-run 并汇报匹配表**）

## 你必须遵守的流程

### 1. 读表

- 用 `PhpOffice\PhpSpreadsheet\IOFactory` 读 xlsx（项目 `vendor` 已有）。
- 读第一行表头，映射列（列名可能略有差异）：
  - **手机/电话** → 匹配键
  - **快递公司**、**快递单号** → 写入订单
  - **商品名称** → 同号多单消歧用
  - **收货人** → 人工核对用

### 2. 匹配规则（固定）

| 项 | 规则 |
|----|------|
| 订单范围 | `cmf_shop_order`：`delete_time=0`，`status IN (2,14)`（待发货） |
| 关联键 | 收货手机：去非数字后与 `shop_order.phone` 全量匹配 |
| 同号多单 | 用 Excel **商品名** 关键词在 `shop_order_detail.goods_name` 里匹配（如 华月、松鼠、月饼、480） |
| 已有单号 | `express_sn` 非空 → **跳过**，不覆盖 |
| 快递公司 | 写入 `express_name`，须存在于 `cmf_shop_express.name`；常见映射：`申通线下` → `申通快递`（执行前 `SELECT name FROM cmf_shop_express` 核对） |
| 更新字段 | `express_name`, `express_sn`, `send_time`=当前时间戳, `status`=**4**（待收货） |
| 子订单号 | 库存/明细用 `order_num`，**不是** `pay_order_num`（本任务只改主订单发货字段） |

### 3. Dry-run 输出（必须）

表格或列表：**Excel 行号 | 手机 | 收货人 | 匹配 order id | 商品名 | 快递公司 | 单号 | 备注**  
并统计：匹配成功数、未匹配、跳过（已有物流）、同号消歧说明。

### 4. 写库

- **事务**内批量 `UPDATE`。
- 使用项目内脚本（推荐）：

```powershell
cd d:/phpstudy_pro/WWW/xiayuxuan.com
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe workbuddy/skills/shop-order-ship-from-excel/scripts/bulk_ship_from_excel.php --file="public/你的文件.xlsx" --dry-run
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe workbuddy/skills/shop-order-ship-from-excel/scripts/bulk_ship_from_excel.php --file="public/你的文件.xlsx" --apply
```

- `php` 不在 PATH，必须用上述 **phpstudy 绝对路径**。
- 不要改 `.env`；本地 `.env` 连生产库，写库即生效。
- **不要**用 PowerShell 直连 `mysql.exe` 写中文。

### 5. 默认不做

- 不调用微信 `uploadShippingInfo`（除非用户明确要求，再参考 `app/admin/controller/OrderController.php` 的 `send()`）。
- 不删订单、不改退款/库存。
- 不把一次性逻辑散落到多处；优先跑/改本 skill 目录下脚本。

## 详细字段与排错

见同目录 [reference.md](reference.md)。

## 给用户的一键任务模板（复制后改路径）

```text
【项目】d:/phpstudy_pro/WWW/xiayuxuan.com
【Skill】shop-order-ship-from-excel
【文件】@public/（你的发货.xlsx）
【要求】按 SKILL 先 dry-run 列出匹配结果；我确认后加 --apply 写库。快递公司「申通线下」映射为库里「申通快递」。不同步微信发货。
```
