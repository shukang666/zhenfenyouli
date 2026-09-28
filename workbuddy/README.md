# WorkBuddy Skills（xiayuxuan.com）

本目录技能包符合 [WorkBuddy Skills](https://github.com/yinqd3/workbuddy-skills) 约定：每个子目录含 `SKILL.md` 入口，可复制到 WorkBuddy 技能目录后通过 **触发词** 自动启用。

## 导入到 WorkBuddy

### 方式一：复制整个技能目录（推荐）

```powershell
# Windows 示例（按你本机 WorkBuddy 配置调整目标路径）
xcopy /E /I "d:\phpstudy_pro\WWW\xiayuxuan.com\workbuddy\skills\shop-order-ship-from-excel" "%USERPROFILE%\.workbuddy\skills\shop-order-ship-from-excel\"
```

Mac/Linux：

```bash
cp -r workbuddy/skills/shop-order-ship-from-excel ~/.workbuddy/skills/
```

复制后 **重启 WorkBuddy** 或 **重新加载 Skills**。

### 方式二：不导入，直接在仓库里用

在 WorkBuddy 中 **打开本项目文件夹** `xiayuxuan.com`，任务里 @ 引用：

- `workbuddy/skills/shop-order-ship-from-excel/SKILL.md`
- 你的发货 `.xlsx`

并说明：「按 shop-order-ship-from-excel skill 执行」。

## 已包含技能

| 目录 | 用途 | 触发词示例 |
|------|------|------------|
| [shop-order-ship-from-excel](skills/shop-order-ship-from-excel/SKILL.md) | 发货 Excel → 待发货订单填物流 → 待收货 | 批量发货、运单号导入、华月运单 |

## 发任务示例（复制到 WorkBuddy 输入框）

```text
打开项目 d:/phpstudy_pro/WWW/xiayuxuan.com，启用 shop-order-ship-from-excel。
@public/华月36份运单号(1).xlsx
先 dry-run 匹配结果；我说「执行」后再 --apply 写库。不同步微信发货。
```
