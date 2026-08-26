---
name: "thinkcmf-admin-list-page"
description: "在 ThinkCMF6/ThinkPHP6 后台列表页（如 member/index、forum/index、news/index）增加功能时使用：包含分页跳转、搜索条件透传、Excel 导出按钮的统一实现模式。"
---

# ThinkCMF 后台列表页通用改造模式

## 适用场景

为基于 `ToolController` 的后台业务列表页（位于 `public/themes/admin_fengiy/admin/<controller>/index.html`）追加：
- 分页处的「输入页码跳转」
- 顶部搜索表单 + URL 参数透传
- 「导出」按钮 → 调用 `outData` 输出 Excel

## 1. 分页跳转输入框（与 Bootstrap pagination 对齐）

项目分页渲染输出 `<ul class="pagination"><li>...</li></ul>`（Bootstrap 3 风格，外层模板通常包一层 `<div class="pagination">{$page}</div>`）。
每个 `<li><a>` 默认 padding 6px 12px、line-height 1.42857、border 1px solid #ddd，总高约 36px。

**对齐做法**：把跳转组件放在 `.pagination` 容器**内部**紧邻 `<ul>` 之后，用 Bootstrap 标准 `.form-control` + `.btn .btn-default` 类，height 设为 34px（+2px border = 36px，与 pagination li a 等高），视觉上与分页条融为一体。

```html
<!-- 分页 -->
<div class="pagination">
    {$page}
    <div class="pagination-jump" style="display:inline-block; vertical-align:middle; margin-left:10px;">
        <span style="vertical-align:middle;">第</span>
        <input type="text" id="jump_page_input" class="form-control"
               style="width:50px; display:inline-block; height:34px; text-align:center; padding:6px;"
               value="{:input('page/d',1)}">
        <span style="vertical-align:middle;">页</span>
        <button type="button" class="btn btn-default" id="jump_page_btn"
                style="height:34px; padding:6px 12px;">跳转</button>
    </div>
</div>
<script>
(function(){
    var input = document.getElementById('jump_page_input');
    var btn = document.getElementById('jump_page_btn');
    if (!input || !btn) return;
    function jump(){
        var p = parseInt(input.value, 10);
        if (isNaN(p) || p < 1) p = 1;
        // 解析当前 query string，剔除旧 page，保留所有其它筛选/排序参数
        var qs = location.search ? location.search.substring(1) : '';
        var params = qs.split('&').filter(function(x){ return x && x.indexOf('page=') !== 0; });
        params.push('page=' + p);
        location.href = location.pathname + '?' + params.join('&') + location.hash;
    }
    btn.addEventListener('click', jump);
    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); jump(); }
    });
})();
</script>
```

要点：
- 用 `location.search` 解析原 query string，剔除旧 `page=`，追加新的，**保留所有其它筛选/排序参数**；
- 顺带保留 `location.hash`（业务页在 iframe 中常以 hash 形式被路由）；
- **不要**用 `.btn-sm` / `.input-sm`（高度 30px），与 pagination 不一致；用默认 `.form-control` + `.btn`（34px）才对齐。

## 2. 搜索表单与导出按钮

顶部搜索表单使用 GET 提交，导出按钮用 `url()` + `input('get.')` 把当前搜索条件透传到 `outData` action：

```html
<form class="well form-inline" method="get" action="{:url('index')}">
    <input type="text" class="form-control" name="keyword" placeholder="昵称/手机号" value="{:input('get.keyword','')}">
    <select class="form-control" name="community_id">
        <option value="">全部小区</option>
        <foreach name="communities" item="c">
            <option value="{$c.id}" <if condition="input('get.community_id') eq $c.id">selected</if>>{$c.name}</option>
        </foreach>
    </select>
    <button class="btn btn-primary" type="submit">搜索</button>
    <a class="btn btn-success" href="{:url('outData', input('get.'))}">导出</a>
</form>
```

注意：`url('outData', input('get.'))` 中 `url()` 会自动把驼峰 action 名转下划线路由（`out_data`）。

## 3. 控制器端实现（关键：与 index 完全对齐）

**核心原则**：`outData` 的筛选条件、排序、字段映射必须与 `index` 完全一致，否则导出的数据与列表看到的不一致。

```php
public function outData(){
    // 1. 复用 index 的所有筛选参数
    $params = $this->request->param();
    $keyword = $this->request->param('keyword','','trim');
    $sortField = $this->request->param('sort_field','id','trim');
    $sortOrder = $this->request->param('sort_order','desc','trim');

    // 2. 与 index 完全一致的 where 条件
    $map = [];
    $map[] = ['a.status','>',0];  // 与 index 一致
    if(!empty($keyword)){
        $map[] = ['a.nickname|a.mobile|a.true_name|a.sn','like',"%".$keyword."%"];
    }
    if(!empty($params['community_id'])){
        $map[] = ['community_id','=',$params['community_id']];
    }
    if(!empty($params['type'])){
        $map[] = ['a.type','=',$params['type']];
    }

    // 3. 与 index 一致的排序字段白名单
    if (!in_array($sortField, ['id', 'point', 'create_time'])) {
        $sortField = 'id';
    }
    $list_order = 'a.' . $sortField . ' ' . $sortOrder;

    // 4. 用 MemberModel::getInfo($map, -1, 0, $field, $list_order) 取全量数据
    //    注意：MemberModel::getInfo 签名为 (map, page_size, id=0, field='a.*', list_order='a.id desc')
    //    page_size=-1 表示不分页（全部导出）
    $Model = new MemberModel();
    $list = $Model->getInfo($map, -1, 0, 'a.*', $list_order);

    // 5. 补齐 index 模板里展示的关联字段（如 community_name）
    foreach($list as &$item){
        $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
        $item['community_parent_name'] = CommunityParentModel::where('id',$item['community_parent_id'])->value('name','');
        $item['community_name'] = CommunityModel::where('id',$item['community_id'])->value('name','');
    }

    // 6. 表头与 keys 必须一一对应
    $filename = '用户列表' . date('Y-m-d');
    $head = ['用户ID','昵称','社区','小区','手机号','积分','注册日期'];
    $keys = ['id','nickname','community_parent_name','community_name','mobile','point','create_time'];
    // ⚠️ 必须静态调用，详见 thinkphp-static-vs-instance-call skill
    CommonModel::outData($filename, $list, $head, $keys);
}
```

**CommonModel::outData 当前签名**（`api/wxapp/model/CommonModel.php`）：
```php
static public function outData($name, $data, $head, $keys, $types = [], $image_array = [])
```
- `$types`：可选，元素值为 `2` 时该列作为图片列处理（Drawing 插入）；
- `$image_array`：可选，临时图片路径数组，脚本结束时通过 `register_shutdown_function` 自动 `unlink`；
- 内部已有 PhpSpreadsheet 内存优化（`setPreCalculateFormulas(false)` / `setIncludeCharts(false)` / `disconnectWorksheets` / `gc_collect_cycles`），但**默认仍可能突破 256M**，所以函数起始处必须加 `@ini_set('memory_limit', '-1'); @set_time_limit(0);`。

## 4. 重要约束

- ✅ 导出动作最后调用必须用 `CommonModel::outData(...)` 静态调用（详见 `thinkphp-static-vs-instance-call` skill）。
- ✅ Excel 列与表头键序必须一致。
- ✅ 在循环中查询小区名称等关联字段时使用 `CommunityModel::where(...)->value(...)` 静态调用，不要 `new CommunityModel()`。
- ✅ `input('get.')` 透传保证导出与列表的筛选条件一致。

## 5. PhpSpreadsheet 内存耗尽排查（已修复并写入 `CommonModel::outData`）

**症状**：点击导出后浏览器显示「网页似乎有问题，或者可能已永久移动到新的 Web 地址」，F12 看到的是 HTTP 500 / 空响应；查 ThinkPHP 日志 `data/runtime/log/YYYYMM/<最新>.log` 没有 `SQLSTATE`，但响应体首行是：

```
Fatal error: Allowed memory size of 268435456 bytes exhausted (tried to allocate 20480 bytes)
in D:\...\vendor\phpoffice\phpspreadsheet\src\PhpSpreadsheet\Collection\Cells.php on line 157
```

**根因**：`CommonModel::outData` 内部 `new Spreadsheet()` + 循环 `setCellValue` 时，PhpSpreadsheet 把每个单元格都缓存为对象，几千行 × 数列很容易突破 256M 默认上限。

**修复**：在 `CommonModel::outData` 起始处加：

```php
@ini_set('memory_limit', '-1');
@set_time_limit(0);
```

PHP 在请求结束时自动释放内存，无需手动 unset。修复后单次 1MB+ Excel 可正常下载。

## 6. 分页类扩展（如需更大改动）

`vendor/topthink/think-orm/src/paginator/driver/Bootstrap.php` 提供 Bootstrap 风格渲染。若需加入「跳转输入框」到分页 HTML 内部，可重写 `render`/`getLinks` 方法或在视图模板中追加（推荐后者，避免改动 vendor）。

## 7. 测试要点

- 验证搜索 + 分页组合：在第二页修改筛选条件后是否回到第一页。
- 验证导出文件内容与当前列表筛选条件一致。
- 验证 URL 中 `page=` 在跳转时被正确替换而非叠加。
- **验证导出在大数据量场景下不报内存耗尽**：用 `curl -s -o out.bin -w "%{http_code}" http://host/admin/<controller>/outData.html` 命令测试，HTTP 200 + 文件以 `PK`（ZIP/xlsx 签名）开头即成功。
