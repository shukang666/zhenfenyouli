<template>
	<view class="knowledge-page">
		<u-navbar title-color="#fff" title="学会分类知识" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552 green-bg"></view>

		<!-- 分类标签 -->
		<view class="category-tabs" v-if="categories.length > 0">
			<scroll-view scroll-x class="tabs-scroll">
				<view
					class="tab-item"
					:class="{ active: currentCategory === '' }"
					@click="switchCategory('')"
				>全部</view>
				<view
					v-for="cat in categories"
					:key="cat"
					class="tab-item"
					:class="{ active: currentCategory === cat }"
					@click="switchCategory(cat)"
				>{{cat}}</view>
			</scroll-view>
		</view>

		<!-- 知识列表 -->
		<view class="u-p-30 list-container">
			<view class="knowledge-card" v-for="item in list" :key="item.id" @click="goDetail(item.id)">
				<u-image width="160" height="160" :src="$u.func.resolveAssetUrl(item.image) || '/static/images/wenxintishi.png'" mode="aspectFill" border-radius="12"></u-image>
				<view class="card-info u-flex-1 u-m-l-20">
					<view class="card-title u-font-bold u-line-2">{{item.title}}</view>
					<view class="card-meta u-font-24 u-type-info u-m-t-12">
						<text v-if="item.category" class="card-category">{{item.category}}</text>
						<text class="u-m-l-20">{{item.create_time_friendly}}</text>
					</view>
					<view class="card-bottom u-flex u-row-between u-m-t-20">
						<text class="u-font-22 u-type-info">{{item.view_count}}次浏览</text>
						<view class="read-more">阅读 <u-icon name="arrow-right" size="22" color="#18AF38"></u-icon></view>
					</view>
				</view>
			</view>

			<!-- 空态 -->
			<view class="empty-state" v-if="!loading && list.length === 0">
				<u-icon name="file-text" size="100" color="#ccc"></u-icon>
				<view class="u-font-28 u-type-info u-m-t-20">暂无分类知识内容</view>
			</view>

			<!-- 加载更多 -->
			<view class="load-more u-p-30 u-text-center" v-if="hasMore && list.length > 0">
				<u-loadmore :status="loading ? 'loading' : 'loadmore'" @loadmore="loadMore" />
			</view>
			<view class="u-text-center u-font-24 u-type-info u-p-30" v-if="!hasMore && list.length > 0">
				没有更多了
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
				keyword: '',
				page: 1,
				hasMore: false,
				total: 0,
				loading: false,
			};
		},
		onShow() {
			this.refreshList();
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({ url: '/pages/index/index' });
					}
				});
			},
			refreshList() {
				this.page = 1;
				this.list = [];
				this.getList();
			},
			loadMore() {
				if (this.hasMore && !this.loading) {
					this.page++;
					this.getList();
				}
			},
			switchCategory(cat) {
				if (this.currentCategory === cat) return;
				this.currentCategory = cat;
				this.refreshList();
			},
			getList() {
				if (this.loading) return;
				this.loading = true;
				this.$u.api.getKnowledgeList({
					category: this.currentCategory,
					keyword: this.keyword,
					page: this.page
				}).then(res => {
					this.loading = false;
					if (res.code == 1) {
						const data = res.data || {};
						if (this.page === 1) {
							this.list = data.list || [];
							this.categories = data.categories || [];
						} else {
							this.list = this.list.concat(data.list || []);
						}
						this.total = data.total || 0;
						this.hasMore = data.has_more || false;
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			goDetail(id) {
				this.$u.route('/pages/dtsh/knowledge-detail?id=' + id);
			}
		}
	}
</script>

<style lang="scss" scoped>
.knowledge-page {
	min-height: 100vh;
}

.green-bg {
	z-index: -1;
	top: 0; left: 0; right: 0;
	background: linear-gradient(to bottom, #2fb74c, #EFF9F1);
}

.category-tabs {
	padding: 20rpx 30rpx 10rpx;

	.tabs-scroll {
		white-space: nowrap;
	}

	.tab-item {
		display: inline-block;
		padding: 12rpx 32rpx;
		margin-right: 16rpx;
		font-size: 26rpx;
		color: #666;
		background: #fff;
		border-radius: 32rpx;
		border: 2rpx solid #eee;

		&.active {
			background: #18AF38;
			color: #fff;
			border-color: #18AF38;
		}
	}
}

.list-container {
	min-height: 500rpx;
}

.knowledge-card {
	display: flex;
	align-items: stretch;
	padding: 24rpx;
	margin-bottom: 24rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);

	.card-info {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
	}

	.card-title {
		font-size: 28rpx;
		color: #333;
		line-height: 1.5;
		min-height: 84rpx;
	}

	.card-meta {
		.card-category {
			background: #E8F7EB;
			color: #18AF38;
			padding: 2rpx 16rpx;
			border-radius: 8rpx;
			font-size: 22rpx;
		}
	}

	.read-more {
		font-size: 24rpx;
		color: #18AF38;
	}
}

.empty-state {
	text-align: center;
	padding: 100rpx 0;
}

.load-more {
	padding: 30rpx 0;
}
</style>
