# 参考：表结构、状态、常见 Excel 表头

## 订单状态（cmf_shop_order.status）

| 值 | 含义 |
|----|------|
| 2 | 待发货 |
| 14 | 待发货（退款拒绝后） |
| 4 | 待收货（发货后目标状态） |

## 相关表

- `cmf_shop_order`：发货写字段 `express_name`, `express_sn`, `send_time`, `status`, `phone`
- `cmf_shop_order_detail`：`order_num`, `goods_name`（消歧）
- `cmf_shop_express`：`name`（与 `express_name` 一致）、`code`（微信发货用）

## 典型 Excel 表头（打单导出）

`店铺名称 | 订单号 | 系统单号 | … | 商品名称 | 数量 | 快递公司 | 快递单号 | … | 收货人 | 手机/电话 | 省 | 市 | 区 | 详细地址 | 打单时间`

脚本按表头名自动找列：`手机/电话`、`快递公司`、`快递单号`、`商品名称`、`收货人`。

## 后台等价操作

PC：`/admin/order/send` — POST 写入同上字段并 `status=4`，另会推微信物流。批量脚本默认只等价于写库部分。

## 排错

| 现象 | 处理 |
|------|------|
| 匹配 0 条 | 核对手机是否一致、订单是否仍为 2/14、是否已删 `delete_time` |
| 同号匹配错商品 | 加强 Excel 商品关键词或人工指定 order id |
| 快递公司报错 | 查 `cmf_shop_express`，缺则后台补或改映射表 |
| PhpSpreadsheet 报错 | 确认文件路径、xlsx 未损坏 |

## 环境（雨轩本机）

- 项目根：`d:/phpstudy_pro/WWW/xiayuxuan.com`
- PHP：`d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe`
- ThinkCMF 引导：`require vendor/autoload.php` + `new \think\App(); $app->initialize();`（Warning 可忽略）
