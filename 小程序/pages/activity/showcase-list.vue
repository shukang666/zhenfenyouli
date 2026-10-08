<template>
	<view class="showcase-page">
		<view class="page-header">
			<text class="page-title">活动风采</text>
			<text class="page-sub">现场随拍与往期活动回顾分开展示</text>
		</view>

		<!-- 手工上传：现场随拍 -->
		<view class="section">
			<view class="section-head">
				<view class="section-head__main">
					<text class="section-head__title">现场随拍</text>
					<text class="section-head__sub">运营精选的活动与宣讲照片</text>
				</view>
			</view>
			<view class="list-wrap">
				<view
					class="showcase-card"
					v-for="item in manualList"
					:key="'m-' + item.id"
					@click="goDetail(item.id)"
				>
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

				<view class="section-empty" v-if="!manualLoading && manualList.length === 0">
					<text class="section-empty__text">暂无随拍内容</text>
				</view>

				<view class="section-more" v-if="manualHasMore && manualList.length > 0">
					<view class="more-btn" @click="loadManualMore">
						<text>{{ manualLoading ? '加载中…' : '加载更多随拍' }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 自动归档：往期活动 -->
		<view class="section section--archive">
			<view class="section-head">
				<view class="section-head__main">
					<text class="section-head__title">往期活动</text>
					<text class="section-head__sub">已结束的非周期性活动自动收录</text>
				</view>
			</view>
			<view class="list-wrap">
				<view
					class="showcase-card showcase-card--archive"
					v-for="item in archiveList"
					:key="'a-' + item.id"
					@click="goDetail(item.id)"
				>
					<view class="card-cover">
						<u-image width="100%" height="280" :src="item.cover" mode="aspectFill" border-radius="20rpx 20rpx 0 0"></u-image>
						<view class="archive-badge">活动回顾</view>
						<view class="photo-badge" v-if="item.photo_count > 1">
							<u-icon name="photo" size="24" color="#fff"></u-icon>
							<text>{{ item.photo_count }}张</text>
						</view>
					</view>
					<view class="card-body">
						<view class="card-title u-line-2">{{ item.title }}</view>
						<view class="card-meta">
							<text v-if="item.event_date_text">结束于 {{ item.event_date_text }}</text>
							<text v-else>活动回顾</text>
						</view>
						<view class="card-desc u-line-2" v-if="item.description">{{ item.description }}</view>
					</view>
				</view>

				<view class="section-empty" v-if="!archiveLoading && archiveList.length === 0">
					<text class="section-empty__text">暂无往期活动归档</text>
				</view>

				<view class="section-more" v-if="archiveHasMore && archiveList.length > 0">
					<view class="more-btn more-btn--plain" @click="loadArchiveMore">
						<text>{{ archiveLoading ? '加载中…' : '加载更多往期活动' }}</text>
					</view>
				</view>
			</view>
		</view>

		<view class="page-foot"></view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			manualList: [],
			manualPage: 1,
			manualHasMore: false,
			manualLoading: false,
			archiveList: [],
			archivePage: 1,
			archiveHasMore: false,
			archiveLoading: false,
		};
	},
	onLoad() {
		this.refreshAll();
	},
	onPullDownRefresh() {
		this.refreshAll().finally(() => {
			uni.stopPullDownRefresh();
		});
	},
	methods: {
		refreshAll() {
			this.manualPage = 1;
			this.archivePage = 1;
			this.manualList = [];
			this.archiveList = [];
			return Promise.all([this.fetchManual(true), this.fetchArchive(true)]);
		},
		loadManualMore() {
			if (!this.manualHasMore || this.manualLoading) return;
			this.manualPage++;
			this.fetchManual(false);
		},
		loadArchiveMore() {
			if (!this.archiveHasMore || this.archiveLoading) return;
			this.archivePage++;
			this.fetchArchive(false);
		},
		fetchManual(reset) {
			if (this.manualLoading) return Promise.resolve();
			this.manualLoading = true;
			return this.$u.api.getActivityShowcaseList({
				page: this.manualPage,
				limit: 10,
				source: 'manual',
			}).then(res => {
				if (res.code == 1) {
					const data = res.data || {};
					const rows = data.list || [];
					this.manualList = reset ? rows : this.manualList.concat(rows);
					this.manualHasMore = !!data.has_more;
				}
			}).catch(() => {}).finally(() => {
				this.manualLoading = false;
			});
		},
		fetchArchive(reset) {
			if (this.archiveLoading) return Promise.resolve();
			this.archiveLoading = true;
			return this.$u.api.getActivityShowcaseList({
				page: this.archivePage,
				limit: 10,
				source: 'archive',
			}).then(res => {
				if (res.code == 1) {
					const data = res.data || {};
					const rows = data.list || [];
					this.archiveList = reset ? rows : this.archiveList.concat(rows);
					this.archiveHasMore = !!data.has_more;
				}
			}).catch(() => {}).finally(() => {
				this.archiveLoading = false;
			});
		},
		goDetail(id) {
			this.$u.route('/pages/activity/showcase-detail?id=' + id);
		},
	},
};
</script>

<style lang="scss" scoped>
.showcase-page {
	min-height: 100vh;
	background: #f2f4f1;
}

.page-header {
	padding: 40rpx 30rpx 24rpx;
	background: linear-gradient(180deg, #e8f2ea 0%, #f2f4f1 100%);
}

.page-title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	color: #2a322e;
}

.page-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #8a9690;
	line-height: 1.45;
}

.section {
	margin-bottom: 8rpx;
}

.section--archive {
	padding-top: 8rpx;
}

.section-head {
	padding: 8rpx 30rpx 16rpx;

	&__title {
		display: block;
		font-size: 32rpx;
		font-weight: 600;
		color: #2a322e;
	}

	&__sub {
		display: block;
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #8a9690;
		line-height: 1.4;
	}
}

.list-wrap {
	padding: 0 30rpx 12rpx;
}

.showcase-card {
	margin-bottom: 24rpx;
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	border: 1rpx solid rgba(42, 50, 46, 0.06);
	box-shadow: 0 4rpx 16rpx rgba(42, 50, 46, 0.04);
}

.showcase-card--archive {
	box-shadow: none;
}

.card-cover {
	position: relative;
}

.archive-badge {
	position: absolute;
	left: 20rpx;
	top: 20rpx;
	padding: 6rpx 14rpx;
	font-size: 20rpx;
	color: #5a6b5e;
	background: rgba(255, 255, 255, 0.92);
	border-radius: 999rpx;
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
	padding: 22rpx 26rpx 26rpx;
}

.card-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #2a322e;
	line-height: 1.45;
}

.card-meta {
	margin-top: 10rpx;
	font-size: 24rpx;
	color: #8a9690;

	.dot {
		margin: 0 10rpx;
	}
}

.card-desc {
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #5a6560;
	line-height: 1.55;
}

.section-empty {
	padding: 48rpx 0 56rpx;
	text-align: center;

	&__text {
		font-size: 26rpx;
		color: #b4bdb7;
	}
}

.section-more {
	padding: 8rpx 0 28rpx;
}

.more-btn {
	height: 72rpx;
	border-radius: 16rpx;
	background: #18af38;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 26rpx;
	color: #fff;
	font-weight: 500;

	&--plain {
		background: #fff;
		color: #5a6560;
		border: 1rpx solid rgba(42, 50, 46, 0.1);
		font-weight: 400;
	}
}

.page-foot {
	height: 40rpx;
}
</style>
