<template>
	<view class="container">
		<!-- 自定义导航栏 -->
		<view class="fb-navbar">
			<view class="fb-nav-back" @click="navigateBack">
				<u-icon name="arrow-left" size="44" color="#050505"></u-icon>
			</view>
			<text class="fb-nav-title">{{cate_name}}</text>
			<view class="fb-nav-placeholder"></view>
		</view>

		<!-- 公益行动特殊：显示垃圾分类 + 我和七贤岭 入口 -->
		<view class="fb-special-entries" v-if="cate_id === 5">
			<view class="fb-special-entry fb-s-lgfl" @click="gotoFlhd">
				<text class="fb-s-entry-title">垃圾分类</text>
				<text class="fb-s-entry-sub">学习分类 · 拍照打卡</text>
				<text class="fb-s-entry-arrow">›</text>
			</view>
			<view class="fb-special-entry fb-s-qxl" @click="gotoQxl">
				<text class="fb-s-entry-title">我和七贤岭</text>
				<text class="fb-s-entry-sub">发现 · 玩 · 记录 · 守护</text>
				<text class="fb-s-entry-arrow">›</text>
			</view>
		</view>

		<!-- 活动列表 -->
		<view class="fb-act-list">
			<view class="fb-act-card" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
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
						<view class="fb-meta-item" v-if="item.address && item.address.trim()">
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
	export default {
		data() {
			return {
				cate_id: 0,
				cate_name: '',
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
			};
		},
		onLoad(options) {
			this.cate_id = parseInt(options.cate_id || 0)
			this.cate_name = decodeURIComponent(options.name || '活动')
			this.loadActiveList()
		},
		methods: {
			loadActiveList() {
				if (this.active_loading) return
				this.active_loading = true

				this.$u.api.get_new_active_list({
					page: this.active_page,
					community_cate_id: this.cate_id,
				}).then(res => {
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

			gotoFlhd() {
				this.$u.route('/pages/flhd/ljfldk')
			},
			gotoQxl() {
				this.$u.route('/pages/activity/love-qixianling')
			},
			navigateBack() {
				uni.navigateBack()
			},
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

/* ===== 导航栏 ===== */
.fb-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #fff;
	border-bottom: 1rpx solid #E4E6EB;
	padding: 0 16rpx;
	height: 88rpx;
	padding-top: env(safe-area-inset-top);
}
.fb-nav-back {
	width: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.fb-nav-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #050505;
	flex: 1;
	text-align: center;
}
.fb-nav-placeholder {
	width: 80rpx;
}

/* ===== 公益行动特殊入口 ===== */
.fb-special-entries {
	display: flex;
	gap: 16rpx;
	padding: 16rpx;
}
.fb-special-entry {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: center;
	padding: 28rpx 24rpx;
	border-radius: 16rpx;
	position: relative;
}
.fb-special-entry:active { opacity: 0.85; }
.fb-s-lgfl {
	background: linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%);
	color: #fff;
}
.fb-s-qxl {
	background: linear-gradient(135deg, #34C759 0%, #28A745 100%);
	color: #fff;
}
.fb-s-entry-title {
	font-size: 30rpx;
	font-weight: 700;
}
.fb-s-entry-sub {
	font-size: 22rpx;
	opacity: 0.9;
	margin-top: 4rpx;
}
.fb-s-entry-arrow {
	position: absolute;
	right: 20rpx;
	top: 50%;
	transform: translateY(-50%);
	font-size: 40rpx;
	font-weight: 300;
	opacity: 0.9;
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
.fb-act-card:active { opacity: 0.92; }
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
.fb-act-avatars { display: flex; }
.fb-act-avatar {
	width: 24rpx; height: 24rpx;
	border-radius: 50%;
	margin-left: -6rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.fb-act-avatar:first-child { margin-left: 0; }
.fb-act-badge-text {
	color: #fff;
	font-size: 20rpx;
	font-weight: 500;
	margin-left: 6rpx;
}
.fb-act-body { padding: 20rpx 24rpx 22rpx; }
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
.fb-meta-item .u-icon { margin-right: 4rpx; }
.fb-meta-text {
	font-size: 22rpx;
	color: #65676B;
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.fb-empty { text-align: center; padding: 100rpx 0; }
.fb-empty-text { font-size: 28rpx; color: #65676B; }
.fb-load-tip { text-align: center; padding: 30rpx 0; font-size: 24rpx; color: #65676B; }
</style>
