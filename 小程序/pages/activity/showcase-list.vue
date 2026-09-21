<template>
	<view class="showcase-page">
		<view class="page-header">
			<text class="page-title">活动风采</text>
			<text class="page-sub">记录社区活动与宣讲的精彩瞬间</text>
		</view>

		<view class="list-wrap">
			<view class="showcase-card" v-for="item in list" :key="item.id" @click="goDetail(item.id)">
				<view class="card-cover">
					<u-image width="100%" height="360" :src="item.cover" mode="aspectFill" border-radius="20rpx 20rpx 0 0"></u-image>
					<view class="photo-badge" v-if="item.photo_count > 1">
						<u-icon name="photo" size="24" color="#fff"></u-icon>
						<text>{{ item.photo_count }}张</text>
					</view>
				</view>
				<view class="card-body">
					<view class="card-title u-line-2">{{ item.title }}</view>
					<view class="card-meta">
						<text>{{ item.event_date_text }}</text>
						<text class="dot">·</text>
						<text>{{ item.view_count }}次浏览</text>
					</view>
					<view class="card-desc u-line-2" v-if="item.description">{{ item.description }}</view>
				</view>
			</view>

			<view class="empty-state" v-if="!loading && list.length === 0">
				<u-icon name="photo" size="100" color="#ccc"></u-icon>
				<view class="empty-text">暂无活动风采</view>
			</view>

			<view class="load-more" v-if="hasMore && list.length > 0">
				<u-loadmore :status="loading ? 'loading' : 'loadmore'" @loadmore="loadMore" />
			</view>
			<view class="no-more" v-if="!hasMore && list.length > 0">没有更多了</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			list: [],
			page: 1,
			hasMore: false,
			loading: false,
		};
	},
	onLoad() {
		this.refreshList();
	},
	onReachBottom() {
		this.loadMore();
	},
	methods: {
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
		getList() {
			if (this.loading) return;
			this.loading = true;
			this.$u.api.getActivityShowcaseList({ page: this.page }).then(res => {
				this.loading = false;
				if (res.code == 1) {
					const data = res.data || {};
					const rows = data.list || [];
					this.list = this.page === 1 ? rows : this.list.concat(rows);
					this.hasMore = data.has_more || false;
				}
			}).catch(() => {
				this.loading = false;
			});
		},
		goDetail(id) {
			this.$u.route('/pages/activity/showcase-detail?id=' + id);
		}
	}
};
</script>

<style lang="scss" scoped>
.showcase-page {
	min-height: 100vh;
	background: #f5f7f6;
}

.page-header {
	padding: 40rpx 30rpx 10rpx;
	background: linear-gradient(180deg, #e8f7eb 0%, #f5f7f6 100%);
}

.page-title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	color: #1a1a1a;
}

.page-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #7a857c;
}

.list-wrap {
	padding: 20rpx 30rpx 40rpx;
}

.showcase-card {
	margin-bottom: 28rpx;
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.08);
}

.card-cover {
	position: relative;
}

.photo-badge {
	position: absolute;
	right: 20rpx;
	bottom: 20rpx;
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 8rpx 16rpx;
	font-size: 22rpx;
	color: #fff;
	background: rgba(0, 0, 0, 0.45);
	border-radius: 999rpx;
}

.card-body {
	padding: 24rpx 28rpx 28rpx;
}

.card-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #222;
	line-height: 1.5;
}

.card-meta {
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #8a938c;

	.dot {
		margin: 0 10rpx;
	}
}

.card-desc {
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #666;
	line-height: 1.6;
}

.empty-state {
	padding: 120rpx 0;
	text-align: center;
}

.empty-text {
	margin-top: 20rpx;
	font-size: 28rpx;
	color: #999;
}

.load-more,
.no-more {
	padding: 20rpx 0 10rpx;
	text-align: center;
	font-size: 24rpx;
	color: #999;
}
</style>
