---
name: "thinkphp-static-vs-instance-call"
description: "排查 ThinkPHP/ThinkCMF 项目中『SQLSTATE[42S02]: Base table or view not found: 1146 Table 'xxx_cmf_<classname>' doesn't exist』类错误。当实例化某个继承自 think\Model 的类、或对该实例调用一个本应是静态方法的方法时，必须用此 skill 检查。"
---

# ThinkPHP Model 静态方法 vs 实例方法调用陷阱

## 适用症状

执行某操作时报错：

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'xcxkf052_aubye_c.<cmf_小写类名>' doesn't exist
```

**关键特征**：报错的表名正好等于「表前缀 + 小写类名」，且这个表本不应该被查询。

## 根本原因

`think\Model` 内置 `__call` 魔术方法：

```php
public function __call($method, $args)
{
    if (isset(static::$macro[static::class][$method])) {
        return call_user_func_array(static::$macro[static::class][$method]->bindTo($this, static::class), $args);
    }
    if ('withattr' == strtolower($method)) {
        return call_user_func_array([$this, 'withAttribute'], $args);
    }
    return call_user_func_array([$this->db(), $method], $args);  // ← 关键
}
```

当对 Model 实例调用一个**它自身没有定义**的方法时：
1. ThinkPHP 调用 `$this->db()` 获取查询构造器（其中会绑定到当前模型对应的表）；
2. 把方法转发给查询构造器执行。

**模型表名推导规则**（默认 `protected $name = ''` 时）：
- 表名 = 表前缀 + 蛇形类名（不包含命名空间），例如 `CommonModel` → `cmf_common_model`。
- 子类可通过 `protected $name = 'xxx'` 覆盖（如 `protected $name = 'member_community'`）。
- 若类没有对应真实表，`__call` 转发后必然报「表不存在」。

## 何时触发

当**方法本来是 `static` 修饰的静态方法**，却被实例化后用 `$obj->method()` 调用时：
- 如果该静态方法是 `public`，PHP 通常直接调用静态方法，**理论上不会**触发 `__call`；
- 但实际项目中常见的是：**方法并未实际定义在子类**（只有父类签名），或者方法名拼写错误/被覆盖，最终落到 `__call`；
- 也可能是 Model 自身的初始化/魔术方法在构造时或后续链式调用中触发了 `__call`。

**保守原则**：只要方法在源码中声明为 `static`，就**必须**用 `::` 调用，**不要**先 `new` 再 `->` 调用。

## 排查步骤

1. **复现错误日志**：在 `data/runtime/log/YYYYMM/<最新>.log` 中搜索 `SQLSTATE` 或缺失表名，看上一个相邻 SQL，就能定位到出错的代码段。
2. **找报错的代码位置**：搜索代码库中对应当前控制器/动作的方法体。
3. **检查 CommonModel / ToolModel / 各种 *Model 的使用方式**：
   - 是否有 `$x = new SomeModel(); $x->staticMethod(...)` 的写法？
   - 是否对 `Model` 实例调用了一个**父类才有的 `static` 方法**？
4. **修正**：改为静态调用 `SomeModel::staticMethod(...)`，避免实例化与 `->` 调用。

## 正确写法 vs 错误写法

```php
// ❌ 错误（可能触发 __call → 走 db() → 查询不存在的表）
$cm = new CommonModel();
$cm->outData($filename, $list, $head, $keys);

// ✅ 正确（与同项目 ForumController::outData 一致）
CommonModel::outData($filename, $list, $head, $keys);
```

## 本项目相关参考实现

- `api/wxapp/model/CommonModel.php` 中的 `outData` 声明为 `static public function outData(...)`，本身只组装 Excel 并 `exit`，不需要任何 DB 访问，因此**应当只用静态调用**。
- `app/admin/controller/ForumController.php` 的 `outData` 方法展示了正确的静态调用模式：
  ```php
  CommonModel::outData($filename, $list, $head, $keys);
  exit;
  ```
- 反例（已修复）：`app/admin/controller/MemberController.php` 的 `outData` 方法原先 `new CommonModel()` 后 `->outData(...)` 导致了本文症状。

## 备用排查手段

如果上述都正确但仍报错，扩展检查：
- 是否在模型 `getAttr`/`getXxxAttr` 访问器中链式触发了其它模型实例化查询？
- 是否使用了 `with(['relation'])` 关联，而关联模型类名推导出的表确实不存在？
- 是否在 `__call` 链中误把字段名当方法名调用？
