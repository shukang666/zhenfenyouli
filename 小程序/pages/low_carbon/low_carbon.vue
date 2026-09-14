<template>
	<view class="container">
		<!-- 自定义顶部导航：搜索栏 -->
		<view class="fb-navbar">
			<view class="fb-navbar-inner">
				<view class="fb-search-box" @click="gotoSearch">
					<u-icon name="search" size="32" color="#65676B"></u-icon>
					<text class="fb-search-ph">搜索活动，福利名称</text>
				</view>
			</view>
		</view>

		<!-- 顶部 Banner 轮播 -->
		<view class="fb-banner-wrap" v-if="banner_list && banner_list.length">
			<swiper class="fb-banner-swiper" :indicator-dots="banner_list.length > 1"
				indicator-color="rgba(255,255,255,0.5)" indicator-active-color="#fff"
				autoplay circular :interval="4000" @change="onBannerChange">
				<swiper-item v-for="(banner, idx) in banner_list" :key="idx" @click="handleBannerTap(banner)">
					<view class="fb-banner-item">
						<u-image width="100%" height="100%" :src="banner.image" mode="aspectFill"></u-image>
					</view>
				</swiper-item>
			</swiper>
		</view>

		<!-- 5 大活动分类 -->
		<view class="fb-cate-grid">
			<view class="fb-cate-item" v-for="(cate, idx) in main_categories" :key="cate.id"
				@click="gotoCateList(cate)">
				<view class="fb-cate-icon" :style="{background: cate.bgColor}">
					<u-image width="48" height="48" :src="cate.icon" v-if="cate.icon"></u-image>
					<text class="fb-cate-emoji" v-else>{{cate.emoji}}</text>
				</view>
				<text class="fb-cate-name">{{cate.name}}</text>
			</view>
		</view>

		<!-- 2 个快捷入口 -->
		<view class="fb-entry-row">
			<view class="fb-entry-card fb-entry-lgfl" @click="gotoFlhd">
				<view class="fb-entry-text">
					<text class="fb-entry-title">垃圾分类</text>
					<text class="fb-entry-sub">学习分类 · 拍照打卡</text>
				</view>
				<text class="fb-entry-arrow">›</text>
			</view>
			<view class="fb-entry-card fb-entry-qxl" @click="gotoQxl">
				<view class="fb-entry-text">
					<text class="fb-entry-title">爱护七贤岭</text>
					<text class="fb-entry-sub">发现 · 玩 · 记录 · 守护</text>
				</view>
				<text class="fb-entry-arrow">›</text>
			</view>
		</view>

		<!-- 标签栏：热门活动 + 动态标签 -->
		<view class="fb-tab-bar">
			<scroll-view scroll-x class="fb-tab-scroll" :show-scrollbar="false" :scroll-into-view="'tab-' + current_tab" scroll-with-animation>
				<view class="fb-tab-list">
					<view class="fb-tab-item" :class="{'fb-tab-active': current_tab === 0}" id="tab-0"
						@click="switchTab(0)">
						<text class="fb-tab-text">热门活动</text>
					</view>
					<view class="fb-tab-item"
						:class="{'fb-tab-active': current_tab === idx + 1}"
						v-for="(tag, idx) in tag_list" :key="tag" :id="'tab-' + (idx + 1)"
						@click="switchTab(idx + 1)">
						<text class="fb-tab-text">{{tag}}</text>
					</view>
				</view>
			</scroll-view>
		</view>

		<!-- 活动列表 -->
		<view class="fb-act-list">
			<view class="fb-act-card" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<view class="fb-act-cover">
					<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
					<view class="fb-act-badge" v-if="item.active_count > 0">
						<view class="fb-act-avatars" v-if="item.avatar_array && item.avatar_array.length">
							<image :src="img" class="fb-act-avatar" mode="aspectFill"
								v-for="(img, i) in item.avatar_array.slice(0, 3)" :key="i"></image>
						</view>
						<text class="fb-act-badge-text">{{item.active_count}}人参与</text>
					</view>
				</view>
				<view class="fb-act-body">
					<view class="fb-act-title u-line-1">{{item.name}}</view>
					<view class="fb-act-meta">
						<view class="fb-meta-item">
							<u-icon name="clock" size="22" color="#65676B"></u-icon>
							<text class="fb-meta-text">{{item.date | date('yy-mm-dd')}}</text>
						</view>
						<view class="fb-meta-item fb-meta-loc" v-if="item.address && item.address.trim()">
							<u-icon name="map" size="22" color="#65676B"></u-icon>
							<text class="fb-meta-text u-line-1">{{item.address}}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 空态 -->
			<view class="fb-empty" v-if="active_list.length === 0 && !active_loading">
				<text class="fb-empty-text">暂无活动</text>
			</view>

			<!-- 加载提示 -->
			<view class="fb-load-tip" v-if="active_loading">加载中...</view>
			<view class="fb-load-tip"
				v-if="!active_loading && active_list.length > 0 && active_list.length >= active_total">没有更多了</view>
		</view>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'

	export default {
		data() {
			return {
				isLoading: true,
				banner_list: [],
				current_tab: 0,
				tag_list: [],
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
				// 5 大活动分类（硬编码，后台新增不显示）
				// community_cate_id 对应数据库 cmf_active_community_cate.id
				main_categories: [
					{ id: 1, name: '健康生活', emoji: '🏃', bgColor: 'linear-gradient(135deg, #E7F0FE, #D4E4FC)' },
					{ id: 2, name: '成长探索', emoji: '🌟', bgColor: 'linear-gradient(135deg, #E8F5E9, #C8E6C9)' },
					{ id: 3, name: '兴趣创造', emoji: '🎨', bgColor: 'linear-gradient(135deg, #FFF3E0, #FFE0B2)' },
					{ id: 4, name: '社邻行动', emoji: '🤝', bgColor: 'linear-gradient(135deg, #FCE4EC, #F8BBD9)' },
					{ id: 5, name: '公益行动', emoji: '💚', bgColor: 'linear-gradient(135deg, #E8EAF6, #C5CAE9)' },
				],
			};
		},
		onLoad(options) {
			this.loadAllData()
		},
		onShow() {
			this.updateTabBarSelected(1)
			if (checkCommunity(this)) return
			if (!this.isLogin) return
		},
		methods: {
			async loadAllData() {
				const startTime = Date.now()
				try {
					await Promise.all([
						this.loadBanners(),
						this.loadTags(),
						this.loadHotActivities(),
					])
				} catch (e) {
					console.log('数据加载失败:', e)
				} finally {
					const elapsed = Date.now() - startTime
					if (elapsed < 400) {
						setTimeout(() => { this.isLoading = false }, 400 - elapsed)
					} else {
						this.isLoading = false
					}
				}
			},

			// 加载 Banner（slide group id=9 活动页Banner）
			loadBanners() {
				return this.$u.api.find_slide({ id: 9 }).then(res => {
					this.banner_list = res.data || []
				}).catch(() => {
					this.banner_list = []
				})
			},

			// 加载活动标签（去重）
			loadTags() {
				return this.$u.api.get_active_tags_list().then(res => {
					this.tag_list = res.data || []
				}).catch(() => {
					this.tag_list = []
				})
			},

			// 加载热门活动（is_recommendation=1）
			loadHotActivities() {
				this.active_list = []
				this.active_page = 1
				this.active_total = 0
				this.loadActiveList()
			},

			// 加载活动列表（根据当前 tab 过滤）
			loadActiveList() {
				if (this.active_loading) return
				this.active_loading = true

				const params = { page: this.active_page }
				if (this.current_tab === 0) {
					// 热门活动
					params.is_recommendation = 1
				} else {
					// 按标签过滤
					const tag = this.tag_list[this.current_tab - 1]
					if (tag) params.tag = tag
				}

				this.$u.api.get_new_active_list(params).then(res => {
					let data = res.data || {}
					let list = data.data || []
					if (this.active_page == 1) {
						this.active_list = list
					} else {
						this.active_list.push(...list)
					}
					this.active_total = data.total || list.length
				}).catch(err => {
					this.$u.toast(err.msg)
				}).finally(() => {
					this.active_loading = false
				})
			},

			// 切换标签
			switchTab(index) {
				if (this.current_tab === index) return
				this.current_tab = index
				this.active_list = []
				this.active_page = 1
				this.active_total = 0
				this.loadActiveList()
			},

			// Banner 点击处理
			handleBannerTap(banner) {
				const url = banner.url || ''
				const target = banner.target || ''
				if (!url) return

				// 如果是完整页面路径，直接跳转
				if (url.startsWith('/pages/')) {
					if (target === 'switchTab') {
						uni.switchTab({ url })
					} else {
						uni.navigateTo({ url })
					}
					return
				}

				// 如果是数字，当作活动 ID
				if (/^\d+$/.test(url)) {
					this.$u.route(`/pages/low_carbon/lsxf/details-new?id=${url}`)
					return
				}

				// 如果是 active?id=xxx 格式
				const match = url.match(/id=(\d+)/)
				if (match) {
					this.$u.route(`/pages/low_carbon/lsxf/details-new?id=${match[1]}`)
					return
				}
			},

			// 点击分类 → 进入分类列表页
			gotoCateList(cate) {
				this.$u.route(`/pages/low_carbon/cate-list?cate_id=${cate.id}&name=${encodeURIComponent(cate.name)}`)
			},

			// 垃圾分类入口
			gotoFlhd() {
				this.$u.route('/pages/flhd/ljfldk')
			},

			// 爱护七贤岭入口
			gotoQxl() {
				this.$u.route('/pages/low_carbon/love-qixianling')
			},

			// 搜索
			gotoSearch() {
				this.$u.route('/pages/low_carbon/active-search')
			},

			// tabbar
			updateTabBarSelected(index) {
				if (typeof this.$mp.page.getTabBar === 'function' && this.$mp.page.getTabBar()) {
					this.$mp.page.getTabBar().setSelected(index)
				}
			},

			// Banner 切换（可选埋点）
			onBannerChange() {},
		},

		onReachBottom() {
			if (this.active_list.length >= this.active_total) return
			if (this.active_loading) return
			this.active_page++
			this.loadActiveList()
		}
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #F0F2F5;
}

/* ===== 自定义顶部导航 ===== */
.fb-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	background: #fff;
	border-bottom: 1rpx solid #E4E6EB;
	padding: 16rpx 24rpx;
	padding-top: calc(env(safe-area-inset-top) + 16rpx);
}
.fb-navbar-inner {
	display: flex;
	align-items: center;
}
.fb-search-box {
	flex: 1;
	display: flex;
	align-items: center;
	height: 72rpx;
	background: #F0F2F5;
	border-radius: 36rpx;
	padding: 0 24rpx;
	gap: 12rpx;
}
.fb-search-ph {
	font-size: 28rpx;
	color: #65676B;
}

/* ===== Banner 轮播 ===== */
.fb-banner-wrap {
	width: 100%;
	padding: 16rpx;
	box-sizing: border-box;
}
.fb-banner-swiper {
	width: 100%;
	height: 300rpx;
	border-radius: 16rpx;
	overflow: hidden;
}
.fb-banner-item {
	width: 100%;
	height: 100%;
}

/* ===== 5 大活动分类 ===== */
.fb-cate-grid {
	display: flex;
	justify-content: space-between;
	padding: 8rpx 24rpx 24rpx;
}
.fb-cate-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
}
.fb-cate-icon {
	width: 96rpx;
	height: 96rpx;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}
.fb-cate-emoji {
	font-size: 44rpx;
}
.fb-cate-name {
	font-size: 24rpx;
	color: #050505;
	font-weight: 500;
	white-space: nowrap;
}

/* ===== 2 个快捷入口 ===== */
.fb-entry-row {
	display: flex;
	gap: 16rpx;
	padding: 0 24rpx 24rpx;
}
.fb-entry-card {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}
.fb-entry-card:active {
	opacity: 0.85;
}
.fb-entry-lgfl {
	background: linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%);
	color: #fff;
}
.fb-entry-lgfl .fb-entry-title,
.fb-entry-lgfl .fb-entry-sub,
.fb-entry-lgfl .fb-entry-arrow {
	color: #fff;
}
.fb-entry-qxl {
	background: linear-gradient(135deg, #34C759 0%, #28A745 100%);
	color: #fff;
}
.fb-entry-qxl .fb-entry-title,
.fb-entry-qxl .fb-entry-sub,
.fb-entry-qxl .fb-entry-arrow {
	color: #fff;
}
.fb-entry-text {
	display: flex;
	flex-direction: column;
	gap: 4rpx;
}
.fb-entry-title {
	font-size: 30rpx;
	font-weight: 700;
}
.fb-entry-sub {
	font-size: 22rpx;
	opacity: 0.9;
}
.fb-entry-arrow {
	font-size: 40rpx;
	font-weight: 300;
}

/* ===== 标签栏 ===== */
.fb-tab-bar {
	position: sticky;
	top: 0;
	z-index: 20;
	background: #fff;
	border-top: 1rpx solid #E4E6EB;
	border-bottom: 1rpx solid #E4E6EB;
}
.fb-tab-scroll {
	white-space: nowrap;
	height: 80rpx;
}
.fb-tab-list {
	display: inline-flex;
	align-items: center;
	padding: 0 16rpx;
	height: 100%;
}
.fb-tab-item {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	height: 56rpx;
	padding: 0 24rpx;
	margin-right: 8rpx;
	border-radius: 28rpx;
	background: #F0F2F5;
	flex-shrink: 0;
	transition: all 0.2s;
}
.fb-tab-active {
	background: #E7F0FE;
}
.fb-tab-active .fb-tab-text {
	color: #1877F2;
	font-weight: 600;
}
.fb-tab-text {
	font-size: 26rpx;
	color: #65676B;
}

/* ===== 活动列表 ===== */
.fb-act-list {
	padding: 16rpx;
	padding-bottom: calc(88rpx + env(safe-area-inset-bottom));
}
.fb-act-card {
	background: #fff;
	border-radius: 16rpx;
	margin-bottom: 16rpx;
	overflow: hidden;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}
.fb-act-card:active {
	opacity: 0.92;
}
.fb-act-cover {
	position: relative;
	width: 100%;
	height: 320rpx;
	background: #F0F2F5;
}
.fb-act-badge {
	position: absolute;
	left: 12rpx;
	bottom: 12rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 36rpx;
	padding: 0 14rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 18rpx;
}
.fb-act-avatars {
	display: flex;
}
.fb-act-avatar {
	width: 24rpx;
	height: 24rpx;
	border-radius: 50%;
	margin-left: -6rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.fb-act-avatar:first-child {
	margin-left: 0;
}
.fb-act-badge-text {
	color: #fff;
	font-size: 20rpx;
	font-weight: 500;
	margin-left: 6rpx;
}
.fb-act-body {
	padding: 20rpx 24rpx 22rpx;
}
.fb-act-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #050505;
	line-height: 1.4;
}
.fb-act-meta {
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx;
	margin-top: 12rpx;
}
.fb-meta-item {
	display: flex;
	align-items: center;
	min-width: 0;
	flex: 1;
}
.fb-meta-item .u-icon {
	margin-right: 4rpx;
}
.fb-meta-text {
	font-size: 22rpx;
	color: #65676B;
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

/* ===== 空态/加载 ===== */
.fb-empty {
	text-align: center;
	padding: 100rpx 0;
}
.fb-empty-text {
	font-size: 28rpx;
	color: #65676B;
}
.fb-load-tip {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #65676B;
}
</style>
