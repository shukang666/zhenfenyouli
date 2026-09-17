<template>
	<view class="qxl-knowledge-page">

		<!-- 顶部 Hero -->
		<view class="kb-hero">
			<text class="kb-hero-title">七贤岭学习</text>
			<text class="kb-hero-subtitle">了解七贤岭，守护绿水青山</text>
		</view>

		<!-- 分类 Tab -->
		<view class="kb-tabs" v-if="categories.length > 0">
			<scroll-view scroll-x="true" class="kb-tabs-scroll">
				<view class="kb-tab-item" :class="{active: currentCategory === ''}" @click="switchCategory('')">
					<text>全部</text>
				</view>
				<view class="kb-tab-item" v-for="cat in categories" :key="cat" :class="{active: currentCategory === cat}" @click="switchCategory(cat)">
					<text>{{cat}}</text>
				</view>
			</scroll-view>
		</view>

		<!-- 知识卡片列表 -->
		<view class="kb-body">
			<view class="kb-card" v-for="item in list" :key="item.id" @click="goDetail(item.id)">
				<view class="kb-card-cover" v-if="item.image">
					<u-image width="100%" height="100%" :src="item.image" mode="aspectFill"></u-image>
				</view>
				<view class="kb-card-cover kb-card-cover-empty" v-else>
					<u-icon name="file-text" size="40" color="#ccc"></u-icon>
				</view>
				<view class="kb-card-body">
					<view class="kb-card-title u-line-1">{{item.title}}</view>
					<view class="kb-card-meta">
						<u-icon name="eye" size="20" color="#8A8A8A"></u-icon>
						<text>{{item.view_count}}</text>
						<text class="kb-card-dot">·</text>
						<text>{{item.create_time_friendly}}</text>
					</view>
				</view>
			</view>

			<!-- 加载更多 / 空态 -->
			<view class="kb-footer" v-if="loading">
				<u-loading mode="circle" color="#18AF38" size="40"></u-loading>
				<text class="kb-footer-text">加载中...</text>
			</view>
			<view class="kb-footer" v-else-if="!hasMore && list.length > 0">
				<text class="kb-footer-text">没有更多了</text>
			</view>
			<view class="kb-empty" v-else-if="!loading && list.length === 0">
				<u-icon name="file-text" size="80" color="#ddd"></u-icon>
				<text class="kb-empty-text">暂无内容，敬请期待</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [],
				categories: [],
				currentCategory: '',
				page: 1,
				hasMore: true,
				loading: false,
			};
		},
		onLoad() {
			this.refreshList();
		},
		onReachBottom() {
			if (this.hasMore && !this.loading) {
				this.loadMore();
			}
		},
		methods: {
			refreshList() {
				this.page = 1;
				this.hasMore = true;
				this.list = [];
				this.getList();
			},
			loadMore() {
				this.page++;
				this.getList();
			},
			switchCategory(cat) {
				if (this.currentCategory === cat) return;
				this.currentCategory = cat;
				this.refreshList();
			},
			getList() {
				if (this.loading) return;
				this.loading = true;
				this.$u.api.getQxlKnowledgeList({
					category: this.currentCategory,
					page: this.page,
				}).then(res => {
					this.loading = false;
					if (res.code == 1) {
						const data = res.data;
						if (this.page === 1) {
							this.list = data.list || [];
							this.categories = data.categories || [];
						} else {
							this.list = this.list.concat(data.list || []);
						}
						this.hasMore = data.has_more || false;
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			goDetail(id) {
				this.$u.route(`/pages/activity/qxl-knowledge-detail?id=${id}`);
			},
			navigateBack() {
				uni.navigateBack();
			},
		}
	}
</script>

<style lang="scss" scoped>
.qxl-knowledge-page {
	min-height: 100vh;
	background: #F0F2F5;
}

/* 导航栏 */
.kb-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	padding: 0 16rpx;
	height: 88rpx;
	padding-top: env(safe-area-inset-top);
}
.kb-nav-back { width: 80rpx; display: flex; align-items: center; justify-content: center; }
.kb-nav-title {
	font-size: 34rpx; font-weight: 700; color: #fff;
	flex: 1; text-align: center;
}
.kb-nav-placeholder { width: 80rpx; }

/* Hero */
.kb-hero {
	padding: 40rpx 30rpx 30rpx;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	margin-top: -88rpx;
	padding-top: 108rpx;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}
.kb-hero-title {
	font-size: 36rpx; font-weight: 800; color: #fff;
	letter-spacing: 4rpx;
}
.kb-hero-subtitle {
	font-size: 24rpx; color: rgba(255,255,255,0.85);
	letter-spacing: 2rpx;
}

/* Tabs */
.kb-tabs {
	background: #fff;
	border-bottom: 1rpx solid #F0F2F5;
}
.kb-tabs-scroll {
	white-space: nowrap;
	padding: 0 20rpx;
}
.kb-tab-item {
	display: inline-block;
	padding: 20rpx 24rpx;
	font-size: 26rpx;
	color: #65676B;
	position: relative;
}
.kb-tab-item.active {
	color: #18AF38;
	font-weight: 700;
}
.kb-tab-item.active::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 4rpx;
	background: #18AF38;
	border-radius: 2rpx;
}

/* Body */
.kb-body {
	padding: 20rpx 16rpx 60rpx;
}
.kb-card {
	display: flex;
	background: #fff;
	border-radius: 16rpx;
	margin-bottom: 16rpx;
	padding: 16rpx;
	gap: 16rpx;
}
.kb-card:active { opacity: 0.88; }
.kb-card-cover {
	width: 180rpx; height: 140rpx;
	border-radius: 12rpx; overflow: hidden;
	flex-shrink: 0;
}
.kb-card-cover-empty {
	background: #F5F5F5;
	display: flex;
	align-items: center;
	justify-content: center;
}
.kb-card-body {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.kb-card-title {
	font-size: 28rpx; font-weight: 600; color: #050505;
	line-height: 1.4;
}
.kb-card-meta {
	display: flex;
	align-items: center;
	gap: 4rpx;
	font-size: 22rpx; color: #8A8A8A;
}
.kb-card-dot {
	margin: 0 6rpx;
}

/* Footer */
.kb-footer {
	padding: 30rpx 0;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10rpx;
}
.kb-footer-text {
	font-size: 24rpx; color: #8A8A8A;
}
.kb-empty {
	padding: 80rpx 0;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.kb-empty-text {
	font-size: 26rpx; color: #8A8A8A;
}
</style>
