---
name: pc-admin-lnglat-input
description: "PC 后台经纬度手工录入/粘贴组件模式。记录在 ThinkCMF 后台表单中把只读经纬度改为「可直接粘贴坐标并自动解析」的实现：可见 lnglat 输入框 + lng/lat 隐藏域、兼容中英文逗号/分号/空格/顿号解析、范围校验与 6 位小数、失焦/回车/提交前解析、地图 key 失效时仍可手填保存。当需要在后台表单新增/修改经纬度录入、支持粘贴坐标，或排查『经纬度填了不生效』『地图点选不写隐藏域』『新增时经纬度校验被绕过』时调用。"
---

# PC 后台经纬度手工录入/粘贴组件

## 适用场景

后台表单需要录入"地点 + 经纬度"，希望运营能直接从坐标拾取器复制粘贴，而不是只靠地图点选。

参考实现：`public/themes/admin_fengiy/admin/active/update.html`。

---

## 一、表单结构

```html
<div class="form-group">
    <label class="col-sm-2 control-label"><span class="form-required">*</span>经纬度</label>
    <input type="hidden" value="{$item.lng??''}" name="lng" id="lng">
    <input type="hidden" value="{$item.lat??''}" name="lat" id="lat">
    <div class="col-md-6 col-sm-10">
        <input type="text" class="form-control" id="lnglat"
               placeholder="经度,纬度（例：121.523456,38.854321）" required
               value="{$item.lng??''},{$item.lat??''}">
        <p class="help-block">可直接粘贴坐标，格式「经度,纬度」…（提醒坐标来源，见 mp-map-location）</p>
    </div>
</div>
```

要点：

- 真正提交的是隐藏域 `name="lng"` / `name="lat"`；可见框 `id="lnglat"` 只做展示与输入。
- 可见框拼接格式固定为「经度,纬度」（英文逗号）。
- 帮助文案要提醒坐标来源（腾讯/高德拾取器），否则运营容易粘百度坐标导致小程序端偏移。

---

## 二、地图初始化与点选（腾讯 GL JS）

- 脚本：`<script src="https://map.qq.com/api/gljs?v=1.exp&key=..."></script>`。
- 以 `$("#lat").val()` / `$("#lng").val()` 作为中心点，为空则用默认值（如北京 `39.916706, 116.403119`）。
- 地图 `click` 事件：

```js
var clickHandler = function (evt) {
    var lat = evt.latLng.getLat().toFixed(6);
    var lng = evt.latLng.getLng().toFixed(6);
    $("#lnglat").val(lng + "," + lat);
    $("#lng").val(lng);      // 必须写隐藏域
    $("#lat").val(lat);      // 必须写隐藏域
    removeMarker("search_res");
    addMarker(new TMap.LatLng(lat, lng), "search_res");
    $.get("{:url('tool/reverse_address')}", {lnglat: lat + "," + lng}, function (res) {
        res = JSON.parse(res);
        if (res.status == 0) $("#address").val(res.result.formatted_addresses.recommend);
    });
};
```

注意：点选必须**同时**写隐藏域，只更新 `#lnglat` 会导致保存后经纬度为空（历史 bug）。

---

## 三、手填/粘贴解析（核心）

```js
function parseLngLatInput() {
    var rawVal = $("#lnglat").val();
    if (!rawVal || typeof rawVal !== 'string') return true;
    var val = rawVal.trim();
    if (val == '' || val == ',') return true;   // 空 / 占位：交给 required 校验
    var parts = val.split(/[,，;；\s、]+/).filter(function (s) { return s.trim() !== ''; });
    if (parts.length < 2) { layer.msg('经纬度格式不正确，请输入「经度,纬度」…'); return false; }
    var lng = parseFloat(parts[0]);
    var lat = parseFloat(parts[1]);
    if (isNaN(lng) || isNaN(lat)) { layer.msg('经纬度必须为数字'); return false; }
    if (lng < -180 || lng > 180 || lat < -90 || lat > 90) { layer.msg('经纬度超出范围…'); return false; }
    lng = lng.toFixed(6);
    lat = lat.toFixed(6);
    $("#lng").val(lng);
    $("#lat").val(lat);
    $("#lnglat").val(lng + "," + lat);
    // 地图可用则同步打点；地图 key 未开通时也不影响手填保存
    if (typeof TMap !== 'undefined' && typeof map !== 'undefined') {
        try {
            removeMarker("search_res");
            addMarker(new TMap.LatLng(parseFloat(lat), parseFloat(lng)), "search_res");
        } catch (e) {}
    }
    return true;
}
```

设计要点：

- 分隔符正则 `[,，;；\s、]+` 兼容中英文逗号、分号、空格、顿号；**经度在前、纬度在后**（与 `lnglat` 显示一致）。
- 范围校验后统一 `toFixed(6)`，保证入库精度一致。
- `typeof TMap !== 'undefined'` 保护：地图 key 没开通/脚本没加载时，手填依然能保存（地图只是增强，不是必需）。

触发时机：

```js
$(function () {
    var initVal = ($("#lnglat").val() || '').trim();
    if (initVal == ',' || initVal == '') { $("#lnglat").val(''); }   // 清占位
    $("#lnglat").on('blur', function () { parseLngLatInput(); });
    $("#lnglat").on('keydown', function (e) {
        if (e.keyCode == 13 || e.which == 13) { parseLngLatInput(); return false; }
    });
    $(".js-ajax-submit").on('click', function () { return parseLngLatInput(); });  // 保存前强制解析
});
```

---

## 四、初始化清理（必做）

新增时模板渲染出 `{$item.lng??''},{$item.lat??''}` = `","`，若不清成空字符串，`required` 会被这个非空值绕过，导致空坐标也能提交。

```js
var initVal = ($("#lnglat").val() || '').trim();
if (initVal == ',' || initVal == '') { $("#lnglat").val(''); }
```

---

## 五、排错速查

| 现象 | 原因与处理 |
|---|---|
| 手填了经纬度但保存后为空 | 解析函数没写隐藏域 `#lng/#lat`，或没在提交前调用 |
| 新增时经纬度不填也能提交 | `lnglat` 初值 `","` 未被清空，绕过了 `required`（第四节） |
| 地图点选后经纬度没变 | `clickHandler` 只写了 `#lnglat` 没写隐藏域 |
| 地图区域报错/白屏但表单还能保存 | 属预期：解析函数用 `typeof TMap` 保护；检查 GL JS key |
| 粘百度坐标后小程序端偏移 | 坐标系问题，见 skill `mp-map-location` |

---

## 相关技能

- `mp-map-location`：坐标系、小程序定位能力、后台地图 key 接入。
- `pc-admin-form-save-chain`：后台表单字段保存链路（字段能否落库取决于表单 name 与表字段一致）。
- `pc-admin-search-export`：后台列表搜索/导出模式。
