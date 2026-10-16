---
name: mp-map-location
description: "小程序 + PC 后台地图定位全链路指南。记录微信小程序原生定位能力（uni.openLocation 看位置/导航、uni.chooseLocation 选点、uni.getLocation 取当前定位，均免费且无需第三方 key）与 PC 后台地图（腾讯地图 GL JS 渲染 + WebService 地理编码换算地址↔坐标，需自建 key）的选型差异、坐标系（GCJ-02/BD-09）偏移问题、小程序位置权限与隐私声明配置（manifest.json permission + requiredPrivateInfos）、项目内各定位功能点与排错。当需要实现/修改地图定位展示、活动或门店位置导航、经纬度录入与换算，或排查『小程序地图位置偏移』『后台地图白屏』『定位不生效/无权限』『该不该申请地图 key』时调用。"
---

# 小程序 + PC 后台地图定位全链路

## 适用场景

需求形如："活动/门店详情要能看位置、导航"、"后台录入地点时要在网页地图上选点/搜地址"、"要不要去第三方平台开通地图 API"。

---

## 一、核心结论：哪端免费、哪端要 key

| 端 | 能力 | 实现方式 | 需第三方 key | 费用 |
|---|---|---|---|---|
| 小程序 | 看位置 / 导航 | `uni.openLocation` | 否 | 免费 |
| 小程序 | 选点（返回地址 + 经纬度） | `uni.chooseLocation` | 否 | 免费 |
| 小程序 | 取当前定位 | `uni.getLocation` | 否 | 免费 |
| 小程序 | 地址↔坐标换算、POI 搜索、路线 | 腾讯/高德小程序 SDK | 是 | 有免费额度 |
| PC 后台 | 网页渲染地图 | 腾讯地图 GL JS（`map.qq.com/api/gljs`） | 是（浏览器 key） | 有免费额度 |
| PC 后台 | 地址→坐标 / 坐标→地址 | 腾讯 WebService `apis.map.qq.com/ws/geocoder/v1` | 是（服务端 key） | 有免费额度 |

结论：

- 小程序里"看位置 / 选位置"直接用微信原生 API，**不需要任何 key、不花钱**。
- 只有"在 PC 后台网页里画地图"和"做地名↔经纬度的换算/搜索"才需要去腾讯位置服务（lbs.qq.com）开通 key。
- `uni.openLocation` **只认经纬度**，`address` 只是显示用的辅助文本；没有经纬度就打不开地图。

---

## 二、数据流（以活动为例）

后台录入 → 表存 `address` + `lng` + `lat` → 接口原样返回 → 小程序 `openLocation`。

- 活动表 `cmf_active` 相关字段：`address`、`lng`、`lat`。
- 接口 `api/wxapp/controller/ActiveController.php::get_new_active_detail`：`ActiveModel::where('id',$id)->find()` 返回全字段，`lat/lng/address` 原样下发，无需额外处理。
- 小程序 `小程序/pages/low_carbon/lsxf/details-new.vue`：

```js
openLocation(item) {
  uni.openLocation({
    latitude: item.lat * 1,
    longitude: item.lng * 1,
    address: item.address
  })
}
```

注意：字符串习惯是"经度,纬度"，但 `openLocation` 参数名是 `latitude`/`longitude`，不要传反。

---

## 三、坐标系（最容易踩的坑）

| 坐标系 | 使用方 |
|---|---|
| GCJ-02（火星坐标） | 微信小程序、腾讯地图、高德地图 |
| BD-09 | 百度地图 |
| WGS-84 | GPS 原始坐标 |

- 本项目统一用 **GCJ-02**。后台录入的经纬度必须来自**腾讯/高德坐标拾取器**（<https://lbs.qq.com/getPoint/>），**不能直接用百度地图复制的坐标**，否则在小程序地图上会偏移数百米。
- 经纬度保留 **6 位小数**即可（约 0.1m 精度）。

---

## 四、小程序位置权限与隐私配置

文件：`小程序/manifest.json` → `mp-weixin`：

```json
"permission": {
    "scope.userLocation": { "desc": "获取位置信息" }
},
"requiredPrivateInfos": [ "getLocation", "chooseLocation" ]
```

要点：

- 用到 `uni.getLocation` / `uni.chooseLocation` 时，必须声明 `requiredPrivateInfos`，否则微信会报"接口未声明"或直接调用失败。
- `uni.openLocation`（打开地图查看/导航）不属于隐私采集接口，**不需要**加入 `requiredPrivateInfos`。
- 除 manifest 外，还需在微信公众平台「设置 → 服务内容声明 → 用户隐私保护指引」中勾选"位置信息"，审核通过后 `getLocation`/`chooseLocation` 才能正常返回。
- 用户拒绝授权后要引导去设置页重新授权，可参考项目里 `小程序/utils/qqmap/index.js`、`小程序/utils/amap/index.js` 的 `setAuthorize` 封装。

---

## 五、项目内定位功能点清单

| 位置 | 文件 | 用法 |
|---|---|---|
| 活动详情-活动地点 | `小程序/pages/low_carbon/lsxf/details-new.vue` | `uni.openLocation(info)`，用 `item.lat/lng/address` |
| 商家入驻-门店位置 | `小程序/pages/settled/settled.vue` | `uni.chooseLocation` 写 `form.address/lng/lat` |
| 订单/商城-门店导航 | `小程序/pages/mall/add_order.vue`、`pages/order_list/details.vue`、`pages/shop_order/details.vue` | `uni.openLocation(lat, lng, name)` |
| 后台活动编辑-地图选点/搜地址 | `public/themes/admin_fengiy/admin/active/update.html` | 腾讯 GL JS + `tool/search_address`、`tool/reverse_address` |
| 后台经纬度录入 | 同上 | 见 skill `pc-admin-lnglat-input` |
| 未启用的地图 SDK | `小程序/utils/qqmap/`、`小程序/utils/amap/`（`main.js` 中已注释） | key 取 `小程序/common/settings.js` 的 `lbsKEY`（当前为空字符串），需要时再启用 |

---

## 六、后台地图接入（简述）

- 渲染地图：`<script src="https://map.qq.com/api/gljs?v=1.exp&key=浏览器key"></script>`，再 `new TMap.Map(container, {...})`。
- 地址→坐标：`app/admin/controller/ToolController.php::search_address`，GET `apis.map.qq.com/ws/geocoder/v1/?key=&address=`。
- 坐标→地址：`ToolController::reverse_address`，GET `...?location=lat,lng&key=&get_poi=1`。
- 服务端 key 配置：`data/config/wxapp.php` 的 `tx_map_key`，代码里用 `config('wxapp.tx_map_key')` 读取。
- 注意：`search_address` / `reverse_address` 无鉴权，浏览器 key 也直接硬编码在 HTML 里（浏览器 key 本就暴露）。需在腾讯位置服务后台配置好 referer 白名单与配额，避免被刷。

---

## 七、排错速查

| 现象 | 原因与处理 |
|---|---|
| 小程序地图位置偏移几百米 | 录的是百度坐标系，改用腾讯/高德拾取器坐标（第三节） |
| 后台地图区域白屏 | GL JS key 未开通/被禁用/referer 白名单不含后台域名，或脚本未加载 |
| 后台点"搜索"无反应 | `search_address` 的 key 无效，或未先选省市区（`searchAddress()` 要求先选地区） |
| `chooseLocation`/`getLocation` 失败 | manifest 未声明 `requiredPrivateInfos`，或公众平台隐私指引未勾选位置 |
| `openLocation` 打不开/无反应 | `lat/lng` 为空或传反；确认该条数据已录入经纬度 |

---

## 相关技能

- `pc-admin-lnglat-input`：后台经纬度手填/粘贴组件的实现细节。
- `pc-admin-form-save-chain`：后台表单字段保存链路（字段能否落库）。
- `code-location-guide`：三端代码修改位置决策。
- `business-data-model`：活动表等实体字段与关联。
