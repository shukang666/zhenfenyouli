<template>
	<view class="showcase-page">
		<view class="page-header">
			<text class="page-title">活动风采</text>
			<text class="page-sub">选择下方分类查看，照片与往期活动分开浏览</text>
		</view>

		<!-- 分类 Tab：同一时间只展示一类 -->
		<view class="type-tabs">
			<view
				class="type-tab"
				:class="{ 'type-tab--active': activeTab === 'manual' }"
				@click="switchTab('manual')"
			>
				<u-icon name="photo" :size="34" :color="activeTab === 'manual' ? '#18af38' : '#9aa89f'"></u-icon>
				<text class="type-tab__label">现场照片</text>
				<text class="type-tab__hint">运营上传</text>
			</view>
			<view
				class="type-tab"
				:class="{ 'type-tab--active': activeTab === 'archive' }"
				@click="switchTab('archive')"
			>
				<u-icon name="calendar" :size="34" :color="activeTab === 'archive' ? '#18af38' : '#9aa89f'"></u-icon>
				<text class="type-tab__label">往期活动</text>
				<text class="type-tab__hint">活动归档</text>
			</view>
		</view>

		<!-- 现场照片：双列相册 -->
		<view class="panel" v-show="activeTab === 'manual'">
			<view class="panel-tip panel-tip--photo">
				<text>此处仅展示后台单独上传的现场随拍与宣讲照片</text>
			</view>
			<view class="photo-grid" v-if="manualList.length > 0">
				<view
					class="photo-cell"
					v-for="item in manualList"
					:key="item.id"
					@click="onTapShowcase(item.id)"
				>
					<view class="photo-cell__cover">
						<u-image width="100%" height="260" :src="item.cover" mode="aspectFill" border-radius="16rpx"></u-image>
						<view class="photo-cell__count" v-if="item.photo_count > 1">{{ item.photo_count }}张</view>
					</view>
					<text class="photo-cell__title u-line-2">{{ item.title }}</text>
					<text class="photo-cell__date" v-if="item.event_date_text">{{ item.event_date_text }}</text>
				</view>
			</view>
			<view class="panel-empty" v-else-if="!manualLoading">
				<u-icon name="photo" size="88" color="#d0d8d3"></u-icon>
				<text class="panel-empty__title">暂无现场照片</text>
				<text class="panel-empty__sub">运营上传后会显示在本分类</text>
			</view>
			<view class="panel-loading" v-if="manualLoading && manualList.length === 0">
				<u-loading mode="flower" size="48"></u-loading>
			</view>
			<view class="list-foot" v-if="manualList.length > 0">
				<text v-if="manualLoading">加载中…</text>
				<text v-else-if="!manualHasMore">— 没有更多了 —</text>
				<text v-else>上拉加载更多</text>
			</view>
		</view>

		<!-- 往期活动：列表式 -->
		<view class="panel" v-show="activeTab === 'archive'">
			<view class="panel-tip panel-tip--activity">
				<text>此处为已结束的非周期性活动，系统自动生成的回顾</text>
			</view>
			<view class="activity-list" v-if="archiveList.length > 0">
				<view
					class="activity-item"
					v-for="item in archiveList"
					:key="item.id"
					@click="onTapShowcase(item.id)"
				>
					<view class="activity-item__thumb">
						<u-image width="200" height="140" :src="item.cover" mode="aspectFill" border-radius="12rpx"></u-image>
					</view>
					<view class="activity-item__body">
						<view class="activity-item__tag">往期活动</view>
						<text class="activity-item__title u-line-2">{{ item.title }}</text>
						<text class="activity-item__meta" v-if="item.event_date_text">结束于 {{ item.event_date_text }}</text>
						<text class="activity-item__desc u-line-2" v-if="item.description">{{ item.description }}</text>
					</view>
					<text class="activity-item__arrow">›</text>
				</view>
			</view>
			<view class="panel-empty" v-else-if="!archiveLoading">
				<u-icon name="calendar" size="88" color="#d0d8d3"></u-icon>
				<text class="panel-empty__title">暂无往期活动</text>
				<text class="panel-empty__sub">单次活动结束后会自动出现在这里</text>
			</view>
			<view class="panel-loading" v-if="archiveLoading && archiveList.length === 0">
				<u-loading mode="flower" size="48"></u-loading>
			</view>
			<view class="list-foot" v-if="archiveList.length > 0">
				<text v-if="archiveLoading">加载中…</text>
				<text v-else-if="!archiveHasMore">— 没有更多了 —</text>
				<text v-else>上拉加载更多</text>
			</view>
		</view>

		<view class="page-foot"></view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			activeTab: 'manual',
			manualList: [],
			manualPage: 1,
			manualHasMore: false,
			manualLoading: false,
			manualLoaded: false,
			archiveList: [],
			archivePage: 1,
			archiveHasMore: false,
			archiveLoading: false,
			archiveLoaded: false,
		};
	},
	onLoad(options) {
		if (options && options.tab === 'archive') {
			this.activeTab = 'archive';
		}
		this.ensureTabData(this.activeTab);
	},
	onPullDownRefresh() {
		this.reloadTab(this.activeTab).finally(() => {
			uni.stopPullDownRefresh();
		});
	},
	onReachBottom() {
		if (this.activeTab === 'manual') {
			this.loadManualMore();
		} else {
			this.loadArchiveMore();
		}
	},
	methods: {
		switchTab(tab) {
			if (this.activeTab === tab) return;
			this.activeTab = tab;
			this.ensureTabData(tab);
		},
		ensureTabData(tab) {
			if (tab === 'manual') {
				if (!this.manualLoaded) {
					this.reloadTab('manual');
				}
				return;
			}
			if (!this.archiveLoaded) {
				this.reloadTab('archive');
			}
		},
		reloadTab(tab) {
			if (tab === 'manual') {
				this.manualPage = 1;
				this.manualList = [];
				this.manualHasMore = false;
				return this.fetchManual(true);
			}
			this.archivePage = 1;
			this.archiveList = [];
			this.archiveHasMore = false;
			return this.fetchArchive(true);
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
				limit: 12,
				source: 'manual',
			}).then(res => {
				if (res.code == 1) {
					const data = res.data || {};
					const rows = data.list || [];
					this.manualList = reset ? rows : this.manualList.concat(rows);
					this.manualHasMore = !!data.has_more;
					this.manualLoaded = true;
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
				limit: 12,
				source: 'archive',
			}).then(res => {
				if (res.code == 1) {
					const data = res.data || {};
					const rows = data.list || [];
					this.archiveList = reset ? rows : this.archiveList.concat(rows);
					this.archiveHasMore = !!data.has_more;
					this.archiveLoaded = true;
				}
			}).catch(() => {}).finally(() => {
				this.archiveLoading = false;
			});
		},
		onTapShowcase(id) {
			const sid = Number(id);
			if (!sid) return;
			this.$u.route('/pages/activity/showcase-detail?id=' + sid);
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
	padding: 36rpx 30rpx 20rpx;
}

.page-title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	color: #2a322e;
}

.page-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 24rpx;
	color: #8a9690;
	line-height: 1.45;
}

.type-tabs {
	display: flex;
	margin: 0 30rpx 20rpx;
	padding: 8rpx;
	background: #fff;
	border-radius: 20rpx;
	border: 1rpx solid rgba(42, 50, 46, 0.08);
	box-shadow: 0 4rpx 16rpx rgba(42, 50, 46, 0.04);
}

.type-tab {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 20rpx 12rpx 18rpx;
	border-radius: 14rpx;
	transition: background 0.2s;

	&--active {
		background: #eef8f0;

		.type-tab__label {
			color: #147a2e;
		}
	}

	&__label {
		margin-top: 8rpx;
		font-size: 28rpx;
		font-weight: 600;
		color: #2a322e;
	}

	&__hint {
		margin-top: 4rpx;
		font-size: 20rpx;
		color: #9aa89f;
	}
}

.panel {
	padding: 0 30rpx;
}

.panel-tip {
	margin-bottom: 20rpx;
	padding: 16rpx 20rpx;
	border-radius: 12rpx;
	font-size: 22rpx;
	line-height: 1.5;

	&--photo {
		color: #5a6560;
		background: #fff;
		border-left: 6rpx solid #18af38;
	}

	&--activity {
		color: #5a6560;
		background: #faf9f5;
		border-left: 6rpx solid #c9a227;
	}
}

.photo-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}

.photo-cell {
	width: 48%;
	margin-bottom: 24rpx;

	&__cover {
		position: relative;
		border-radius: 16rpx;
		overflow: hidden;
	}

	&__count {
		position: absolute;
		right: 12rpx;
		bottom: 12rpx;
		padding: 4rpx 12rpx;
		font-size: 20rpx;
		color: #fff;
		background: rgba(0, 0, 0, 0.45);
		border-radius: 999rpx;
	}

	&__title {
		display: block;
		margin-top: 12rpx;
		font-size: 26rpx;
		font-weight: 600;
		color: #2a322e;
		line-height: 1.4;
	}

	&__date {
		display: block;
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9aa89f;
	}
}

.activity-list {
	padding-bottom: 8rpx;
}

.activity-item {
	display: flex;
	align-items: center;
	margin-bottom: 16rpx;
	padding: 20rpx;
	background: #fff;
	border-radius: 16rpx;
	border: 1rpx solid rgba(42, 50, 46, 0.06);

	&__thumb {
		flex-shrink: 0;
		width: 200rpx;
		height: 140rpx;
		border-radius: 12rpx;
		overflow: hidden;
		margin-right: 20rpx;
	}

	&__body {
		flex: 1;
		min-width: 0;
	}

	&__tag {
		display: inline-block;
		padding: 2rpx 10rpx;
		margin-bottom: 8rpx;
		font-size: 20rpx;
		color: #8a7340;
		background: #faf6ee;
		border-radius: 6rpx;
	}

	&__title {
		display: block;
		font-size: 28rpx;
		font-weight: 600;
		color: #2a322e;
		line-height: 1.4;
	}

	&__meta {
		display: block;
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #9aa89f;
	}

	&__desc {
		display: block;
		margin-top: 8rpx;
		font-size: 24rpx;
		color: #6a756f;
		line-height: 1.45;
	}

	&__arrow {
		flex-shrink: 0;
		margin-left: 8rpx;
		font-size: 32rpx;
		color: #d0d8d3;
	}
}

.panel-empty {
	padding: 80rpx 40rpx 100rpx;
	text-align: center;

	&__title {
		display: block;
		margin-top: 20rpx;
		font-size: 28rpx;
		color: #6a756f;
		font-weight: 500;
	}

	&__sub {
		display: block;
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #b4bdb7;
		line-height: 1.5;
	}
}

.panel-loading {
	padding: 80rpx 0;
	display: flex;
	justify-content: center;
}

.list-foot {
	padding: 16rpx 0 32rpx;
	text-align: center;
	font-size: 24rpx;
	color: #b4bdb7;
}

.page-foot {
	height: 24rpx;
}
</style>
