---
name: "mp-community-selector"
description: "小区选择弹窗组件模式。记录用户个人页小区搜索选择弹窗的实现，包括内嵌下拉面板、分页加载、选择后自动保存等。当需要在小程序实现类似的选择弹窗（带搜索、分页、自动保存）时调用。"
---

# 小区选择弹窗组件模式

## 适用场景

本项目用户个人页（`pages/user/info.vue`）需要用户选择所属小区，实现了一个内嵌下拉选择面板，支持：
- 关键字搜索（忽略特殊符号模糊匹配）
- 分页加载（上拉加载更多）
- 选择后自动保存用户信息
- 弹窗打开/关闭动画

当需要在小程序实现类似的选择组件（如选择小区、选择地址、选择门店等）时参考此模式。

## 核心实现

### 1. 组件结构

```
┌─────────────────────────────────┐
│  触发区域（点击打开选择面板）      │
│  显示当前选中的小区名称            │
└─────────────────────────────────┘
              ↓ 点击
┌─────────────────────────────────┐
│  半透明遮罩层（点击关闭）          │
│  ┌───────────────────────────┐  │
│  │  搜索框                   │  │
│  ├───────────────────────────┤  │
│  │  小区列表（可滚动）        │  │
│  │  - 小区1                  │  │
│  │  - 小区2                  │  │
│  │  - ...                    │  │
│  │  （上拉加载更多）          │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

### 2. 小程序前端实现

#### Vue 模板

```vue
<!-- 小程序/pages/user/info.vue -->
<template>
    <view>
        <!-- 触发区域 -->
        <view class="community-trigger" @click="toggleCommunityDropdown">
            <text>{{ selectedCommunity || '请选择小区' }}</text>
            <u-icon name="arrow-down"></u-icon>
        </view>

        <!-- 小区选择下拉面板 -->
        <view class="community-dropdown-mask" v-if="showCommunityDropdown" 
              @click="closeCommunityDropdown">
            <view class="community-dropdown" @click.stop>
                <!-- 搜索框 -->
                <view class="search-box">
                    <input type="text" v-model="communityKeyword" 
                           placeholder="搜索小区名称" confirm-type="search"
                           @input="onCommunitySearch" />
                </view>
                
                <!-- 小区列表 -->
                <scroll-view scroll-y class="community-dropdown-list" 
                             @scrolltolower="loadMoreCommunities">
                    <view class="community-dropdown-item" 
                          v-for="item in communityList" :key="item.id"
                          @click="selectCommunity(item)">
                        <text>{{ item.name }}</text>
                        <text class="parent-name" v-if="item.parent_name">
                            ({{ item.parent_name }})
                        </text>
                    </view>
                    
                    <!-- 加载更多提示 -->
                    <view class="load-more" v-if="communityLoading">加载中...</view>
                    <view class="load-more" v-if="!communityHasMore">没有更多了</view>
                </scroll-view>
            </view>
        </view>
    </view>
</template>
```

#### Vue Script

```vue
<script>
export default {
    data() {
        return {
            showCommunityDropdown: false,  // 是否显示选择面板
            selectedCommunity: '',         // 当前选中的小区名
            communityKeyword: '',          // 搜索关键字
            communityList: [],             // 小区列表
            communityPage: 1,              // 当前页码
            communityPageSize: 20,         // 每页数量
            communityTotal: 0,             // 总数
            communityLoading: false,       // 加载中
        }
    },
    computed: {
        communityHasMore() {
            return this.communityList.length < this.communityTotal
        }
    },
    methods: {
        // 打开/关闭选择面板
        toggleCommunityDropdown() {
            this.showCommunityDropdown = !this.showCommunityDropdown
            if (this.showCommunityDropdown) {
                // 打开时重置并加载数据
                this.communityPage = 1
                this.communityList = []
                this.loadCommunities()
            }
        },
        
        // 关闭选择面板
        closeCommunityDropdown() {
            this.showCommunityDropdown = false
        },
        
        // 搜索输入事件（防抖）
        onCommunitySearch: debounce(function() {
            this.communityPage = 1
            this.communityList = []
            this.loadCommunities()
        }, 300),
        
        // 加载小区列表
        async loadCommunities() {
            if (this.communityLoading) return
            this.communityLoading = true
            
            try {
                let res = await this.$u.api.searchCommunities({
                    keyword: this.communityKeyword,
                    page: this.communityPage,
                    page_size: this.communityPageSize
                })
                
                let data = res.data || []
                if (this.communityPage === 1) {
                    this.communityList = data.list || []
                } else {
                    this.communityList = this.communityList.concat(data.list || [])
                }
                this.communityTotal = data.total || 0
                this.communityPage++
            } catch (e) {
                this.$u.toast('加载失败')
            } finally {
                this.communityLoading = false
            }
        },
        
        // 上拉加载更多
        loadMoreCommunities() {
            if (this.communityHasMore && !this.communityLoading) {
                this.loadCommunities()
            }
        },
        
        // 选择小区
        selectCommunity(item) {
            this.selectedCommunity = item.name
            this.closeCommunityDropdown()
            // 自动保存用户信息
            this.saveCommunityId(item.id)
        },
        
        // 保存小区ID到用户信息
        async saveCommunityId(communityId) {
            try {
                await this.$u.api.saveUserInfo({ community_id: communityId })
                // 更新 vuex 状态
                this.$store.commit('updateUserInfo', { community_id: communityId })
                this.$u.toast('保存成功')
            } catch (e) {
                this.$u.toast('保存失败')
            }
        }
    }
}
</script>
```

#### Vue Style

```vue
<style lang="scss" scoped>
.community-dropdown-mask {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 99;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.community-dropdown {
    margin-top: 180rpx;
    width: 690rpx;
    background-color: #fff;
    border-radius: 12rpx;
    padding: 20rpx;
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.community-dropdown-list {
    max-height: 500rpx;
    margin-top: 10rpx;
}

.community-dropdown-item {
    padding: 20rpx 0;
    border-bottom: 1rpx solid #f7f7f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    &:last-child {
        border-bottom: none;
    }
    
    .parent-name {
        font-size: 24rpx;
        color: #999;
    }
}
</style>
```

### 3. 小程序接口层

#### API 封装

```js
// 小程序/api/wxapp.js
searchCommunities(data) {
    return this.$u.get('/wxapp/forum/searchCommunities', data)
}
```

#### 接口实现

```php
// api/wxapp/controller/ForumController.php
public function searchCommunities()
{
    $keyword = $this->request->param('keyword', '', 'trim');
    $page = $this->request->param('page', 1);
    $page_size = $this->request->param('page_size', 20);
    
    // 关键字去除特殊符号，仅保留中文、字母、数字
    $pureKeyword = preg_replace('/[^\p{Han}a-zA-Z0-9]/u', '', $keyword);
    
    // 忽略的特殊符号列表
    $ignoreSymbols = [
        ' ', '　', '·', '•', '‧', '・', '∙', '･',
        '.', '．', '。', ',', '，',
        '-', '–', '—', '－', '_', '~', '～',
        '(', ')', '（', '）', '[', ']', '【', '】',
        '"', '"', ''', ''',
    ];
    
    $nameExpr = 'c.name';
    foreach ($ignoreSymbols as $symbol) {
        $nameExpr = "REPLACE({$nameExpr}, '{$symbol}', '')";
    }
    
    $communityModel = new CommunityModel();
    $list = $communityModel->alias('c')
        ->leftJoin('member_community_parent p', 'p.id = c.pid')
        ->field('c.id, c.name, c.pid, p.name as parent_name')
        ->when($pureKeyword !== '', function ($query) use ($nameExpr, $pureKeyword) {
            $query->whereRaw("{$nameExpr} LIKE :keyword", ['keyword' => '%' . $pureKeyword . '%']);
        })
        ->order('c.list_order asc')
        ->paginate([
            'page' => $page,
            'list_rows' => $page_size,
        ]);
    
    $this->success('获取成功', [
        'list' => $list->items(),
        'total' => $list->total(),
    ]);
}
```

## 4. 防抖工具函数

```js
// 小程序/utils/debounce.js
export function debounce(fn, delay = 300) {
    let timer = null
    return function(...args) {
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
            fn.apply(this, args)
        }, delay)
    }
}
```

## 5. 使用模式总结

### 触发选择面板的方式

| 方式 | 适用场景 | 实现 |
|------|---------|------|
| 点击触发 | 表单字段选择 | 点击输入框/按钮打开下拉面板 |
| 页面加载触发 | 强制选择 | onLoad 时自动打开，遮罩层不可关闭 |

### 数据加载策略

| 策略 | 适用场景 | 实现 |
|------|---------|------|
| 打开时加载 | 数据量小 | toggle 时重置并加载第一页 |
| 搜索时加载 | 需要搜索 | 搜索关键字变化时重置并加载 |
| 滚动加载更多 | 数据量大 | scrolltolower 触发加载下一页 |

### 选择后操作

| 操作 | 说明 |
|------|------|
| 仅前端更新 | 只更新 data，不调用接口 |
| 自动保存 | 选择后立即调接口保存 |
| 确认后保存 | 选择后需点确认按钮才保存 |

## 6. 注意事项

### 交互细节

- ✅ 遮罩层点击关闭（`@click="closeCommunityDropdown"`）
- ✅ 面板内部阻止冒泡（`@click.stop`）
- ✅ 搜索防抖（300ms）
- ✅ 加载中状态（避免重复请求）
- ✅ 没有更多数据提示
- ❌ 不要在面板打开时加载全部数据（分页）
- ❌ 不要忘记阻止冒泡（点击面板会触发遮罩关闭）

### 性能优化

- 搜索关键字为空时可以不请求接口（或返回热门/默认数据）
- 列表使用 `key` 绑定 id，避免重复渲染
- 滚动加载时判断 `hasMore`，避免无意义请求

## 7. 扩展场景

此模式可扩展用于：

| 场景 | 修改点 |
|------|--------|
| 选择收货地址 | 接口改为地址接口，字段改为 address |
| 选择门店 | 接口改为门店接口，增加距离排序 |
| 选择商品 | 接口改为商品接口，增加图片展示 |
| 选择分类 | 接口改为分类接口，支持多级 |

## 相关 Git 提交

- `ca96874` - 用户个人页新增小区搜索选择弹窗并支持分页加载
- `8a5760f` - 优化小区搜索匹配逻辑，支持忽略特殊符号模糊匹配
