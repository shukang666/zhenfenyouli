<template>
	<view class="container">
		<!-- 自定义导航栏 -->
		<view class="fb-navbar">
			<view class="fb-nav-back" @click="navigateBack">
				<u-icon name="arrow-left" size="44" color="#fff"></u-icon>
			</view>
			<text class="fb-nav-title">爱护七贤岭</text>
			<view class="fb-nav-placeholder"></view>
		</view>

		<!-- 头部 Hero -->
		<view class="qxl-hero">
			<view class="qxl-hero-content">
				<text class="qxl-hero-title">爱护七贤岭</text>
				<text class="qxl-hero-subtitle">一起守护这片绿水青山</text>
			</view>
		</view>

		<!-- 4 个分类 section，每个下面直接列活动卡片 -->
		<view class="qxl-body">
			<view class="qxl-section" v-for="cate in qxl_categories" :key="cate.id">
				<!-- section 标题条 -->
				<view class="qxl-section-head">
					<view class="qxl-section-dot" :style="{background: cate.accent}"></view>
					<text class="qxl-section-emoji">{{cate.emoji}}</text>
					<text class="qxl-section-name">{{cate.name}}</text>
					<text class="qxl-section-sub">{{cate.subtitle}}</text>
				</view>

				<!-- 活动卡片列表 -->
				<view class="qxl-section-list" v-if="cate.activities && cate.activities.length">
					<view class="qxl-act-card" v-for="act in cate.activities" :key="act.id"
						@click="gotoDetail(act.id)">
						<view class="qxl-act-cover">
							<u-image width="100%" height="100%" :src="act.thumb" mode="aspectFill"></u-image>
							<view class="qxl-act-badge" v-if="act.active_count > 0">
								<text>{{act.active_count}}人参与</text>
							</view>
						</view>
						<view class="qxl-act-body">
							<view class="qxl-act-title u-line-1">{{act.name}}</view>
							<view class="qxl-act-meta">
								<view class="qxl-act-meta-item">
									<u-icon name="clock" size="20" color="#8A8A8A"></u-icon>
									<text>{{act.date | date('yy-mm-dd')}}</text>
								</view>
								<view class="qxl-act-meta-item" v-if="act.address && act.address.trim()">
									<u-icon name="map" size="20" color="#8A8A8A"></u-icon>
									<text class="u-line-1">{{act.address}}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<!-- 暂无活动 -->
				<view class="qxl-empty" v-else-if="!cate.loading">
					<text class="qxl-empty-text">暂无活动，敬请期待</text>
				</view>

				<!-- 加载中 -->
				<view class="qxl-empty" v-else>
					<text class="qxl-empty-text">加载中...</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				qxl_categories: [
					{ id: 6, name: '发现七贤岭', subtitle: '探索自然，发现美好', emoji: '🔍', accent: '#1877F2', bgColor: 'linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%)', activities: [], loading: true },
					{ id: 7, name: '玩在七贤岭', subtitle: '亲子玩乐，互动体验', emoji: '🎮', accent: '#34C759', bgColor: 'linear-gradient(135deg, #34C759 0%, #28A745 100%)', activities: [], loading: true },
					{ id: 8, name: '记录七贤岭', subtitle: '用镜头记录每一刻', emoji: '📷', accent: '#FF9500', bgColor: 'linear-gradient(135deg, #FF9500 0%, #E68900 100%)', activities: [], loading: true },
					{ id: 9, name: '守护七贤岭', subtitle: '守护绿水青山，从我做起', emoji: '🌱', accent: '#AF52DE', bgColor: 'linear-gradient(135deg, #AF52DE 0%, #8B41C2 100%)', activities: [], loading: true },
				],
			};
		},
		onLoad() {
			this.loadAllSections()
		},
		methods: {
			// 并行加载 4 个分类的活动
			async loadAllSections() {
				const tasks = this.qxl_categories.map(cate =>
					this.fetchCateActivities(cate.id).then(list => {
						cate.activities = list
						cate.loading = false
					}).catch(() => {
						cate.activities = []
						cate.loading = false
					})
				)
				await Promise.all(tasks)
				// 触发 Vue 响应式更新
				this.qxl_categories = [...this.qxl_categories]
			},
			fetchCateActivities(communityCateId) {
				return this.$u.api.get_new_active_list({
					community_cate_id: communityCateId,
					page_size: 20,
				}).then(res => {
					return res.data?.data || []
				})
			},
			gotoDetail(id) {
				this.$u.route(`/pages/low_carbon/lsxf/details-new?id=${id}`)
			},
			navigateBack() {
				uni.navigateBack()
			},
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
	background: transparent;
	padding: 0 16rpx;
	height: 88rpx;
	padding-top: env(safe-area-inset-top);
	background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0) 100%);
}
.fb-nav-back { width: 80rpx; display: flex; align-items: center; justify-content: center; }
.fb-nav-title {
	font-size: 34rpx; font-weight: 700; color: #fff;
	flex: 1; text-align: center; text-shadow: 0 2rpx 4rpx rgba(0,0,0,0.3);
}
.fb-nav-placeholder { width: 80rpx; }

/* ===== Hero ===== */
.qxl-hero {
	position: relative;
	height: 320rpx;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	margin-top: -88rpx;
	padding-top: 88rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #fff;
}
.qxl-hero-title {
	font-size: 44rpx; font-weight: 800; letter-spacing: 4rpx;
	text-shadow: 0 4rpx 8rpx rgba(0,0,0,0.25);
}
.qxl-hero-subtitle {
	font-size: 26rpx; margin-top: 10rpx; opacity: 0.9; letter-spacing: 2rpx;
}

/* ===== Body ===== */
.qxl-body {
	padding: 24rpx 16rpx 100rpx;
	margin-top: -24rpx;
	position: relative;
	z-index: 10;
}

/* ===== Section ===== */
.qxl-section {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx 20rpx 8rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.06);
	border: 1rpx solid #E4E6EB;
}
.qxl-section-head {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 4rpx 4rpx 16rpx;
	border-bottom: 1rpx solid #F0F2F5;
	margin-bottom: 16rpx;
}
.qxl-section-dot {
	width: 8rpx; height: 28rpx; border-radius: 4rpx;
}
.qxl-section-emoji {
	font-size: 32rpx;
}
.qxl-section-name {
	font-size: 30rpx; font-weight: 700; color: #050505;
}
.qxl-section-sub {
	font-size: 22rpx; color: #8A8A8A; margin-left: 4rpx;
}

/* ===== 活动卡片（横向一行） ===== */
.qxl-section-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.qxl-act-card {
	display: flex;
	background: #FAFBFC;
	border-radius: 16rpx;
	overflow: hidden;
	border: 1rpx solid #EDEEF0;
}
.qxl-act-card:active { opacity: 0.88; }
.qxl-act-cover {
	width: 200rpx; height: 160rpx;
	position: relative; flex-shrink: 0; background: #F0F2F5;
}
.qxl-act-badge {
	position: absolute; left: 8rpx; bottom: 8rpx;
	height: 30rpx; padding: 0 12rpx;
	background: rgba(0,0,0,0.55); border-radius: 15rpx;
	font-size: 18rpx; color: #fff; line-height: 30rpx;
}
.qxl-act-body {
	flex: 1; padding: 16rpx 18rpx;
	display: flex; flex-direction: column; justify-content: space-between;
	min-width: 0;
}
.qxl-act-title {
	font-size: 28rpx; font-weight: 600; color: #050505;
	line-height: 1.4;
}
.qxl-act-meta {
	display: flex; flex-direction: column; gap: 6rpx;
	margin-top: 8rpx;
}
.qxl-act-meta-item {
	display: flex; align-items: center; gap: 4rpx;
	font-size: 22rpx; color: #65676B;
	min-width: 0;
}
.qxl-act-meta-item text {
	flex: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}

/* ===== 空态 ===== */
.qxl-empty {
	padding: 30rpx 0; text-align: center;
}
.qxl-empty-text {
	font-size: 24rpx; color: #8A8A8A;
}
</style>
