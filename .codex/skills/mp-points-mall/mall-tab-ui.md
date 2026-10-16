# 积分 Tab 分类与筛选逻辑

## primaryKey 模式

| primaryKey | 用户看到的 pill | 下方 UI | 列表筛选 |
|------------|-----------------|---------|----------|
| `all` | 全部 | 无 | 无 first/second |
| `geo` | 产区地图 | 中国地图 + 大区下产区 chip | `second_cate` = 选中产区 ID |
| `creative` | 综合文创 | 可选多个文创 chip | `second_cate` = 综合文创相关子类 |
| `first-{id}` | 后台一级类名 | 无 | `first_cate` = id |

## 综合文创

- 数据上仍是 `goods_category` 里「地理产区」下的子类（名称含「综合」等）  
- 从地图组件中移除「综合文创 / 全国」快捷按钮；「全国」改为文案链「查看全国产区」  
- 点 pill「综合文创」时 `applyCreativeSelection()` 选中第一个文创子类  

## 地图组件

- `mall-china-map`：canvas 分区，绿色同色系深浅（非彩虹）  
- `mapRegion`：`all` 或七大区 key；不应再作为长期 `creative` 入口  
- `zonesInRegion(cate_list, regionKey)` 计算 chip 列表  

## 后端扩展记录

`TabHomeService::buildMallTabHome` 增加 `first_cate` 与 `first_cate_list`；`fetchGoodsListPage` 支持 `a.first_cate` 条件。部署 API 后小程序传参才生效。
