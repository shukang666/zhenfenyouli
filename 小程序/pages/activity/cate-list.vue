<template>
	<view class="container">
		<!-- 公益行动特殊入口：垃圾分类活动包（与活动首页同款） -->
		<view class="fb-special-bundle" v-if="cate_id === 5" @click="gotoGarbageClassify">
			<view class="fb-special-bundle-inner">
				<view class="fb-special-bundle-copy">
					<text class="fb-special-bundle-badge">公益行动</text>
					<text class="fb-special-bundle-title">垃圾真分 · 打卡工作台</text>
					<text class="fb-special-bundle-sub">真分打卡 · 分类学习 · 社区专题</text>
					<view class="fb-special-bundle-cta">
						<text>立即进入</text>
						<text class="fb-special-bundle-arrow">›</text>
					</view>
				</view>
				<view class="fb-special-bundle-emoji">♻️</view>
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

			<view class="fb-empty" v-if="active_list.length === 0 && !active_loading && cate_id !== 5">
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
			// 动态设置导航栏标题为当前分类名
			uni.setNavigationBarTitle({ title: this.cate_name })
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

			gotoGarbageClassify() {
				this.$u.route('/pages/activity/garbage-classify');
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

/* ===== 公益行动特殊入口（垃圾分类活动包） ===== */
.fb-special-bundle {
  padding: 16rpx;
}
.fb-special-bundle-inner {
  position: relative;
  display: flex;
  align-items: center;
  padding: 30rpx 30rpx 30rpx 34rpx;
  border-radius: 20rpx;
  overflow: hidden;
  background: linear-gradient(135deg, #6a4f9e 0%, #8c6ec0 55%, #b594d7 100%);
  box-shadow: 0 12rpx 32rpx rgba(106, 79, 158, 0.28),
    inset 0 1rpx 0 rgba(255, 255, 255, 0.4);
}
.fb-special-bundle:active { opacity: 0.92; transform: scale(0.99); }
.fb-special-bundle-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8rpx;
}
.fb-special-bundle-badge {
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  font-size: 20rpx;
  font-weight: 700;
  color: #5a3d8a;
  background: rgba(255, 255, 255, 0.92);
  letter-spacing: 1rpx;
}
.fb-special-bundle-title {
  font-size: 34rpx;
  font-weight: 800;
  color: #fff;
  line-height: 1.25;
  text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.18);
}
.fb-special-bundle-sub {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.88);
}
.fb-special-bundle-cta {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  margin-top: 8rpx;
  padding: 9rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
  font-size: 22rpx;
  font-weight: 700;
}
.fb-special-bundle-arrow { font-size: 28rpx; font-weight: 300; }
.fb-special-bundle-emoji {
  flex-shrink: 0;
  font-size: 120rpx;
  line-height: 1;
  color: rgba(255, 255, 255, 0.85);
  filter: drop-shadow(0 4rpx 14rpx rgba(0, 0, 0, 0.2));
  margin-left: 12rpx;
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
