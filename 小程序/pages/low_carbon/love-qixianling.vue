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

		<!-- 头部 Banner -->
		<view class="qxl-hero">
			<view class="qxl-hero-overlay"></view>
			<view class="qxl-hero-content">
				<text class="qxl-hero-title">爱护七贤岭</text>
				<text class="qxl-hero-subtitle">一起守护这片绿水青山</text>
			</view>
		</view>

		<!-- 4 个分类入口卡片 -->
		<view class="qxl-cate-wrap">
			<view class="qxl-cate-card" v-for="(cate, idx) in qxl_categories" :key="cate.id"
				:style="{background: cate.bgColor}" @click="handleCateTap(cate)">
				<view class="qxl-cate-left">
					<view class="qxl-cate-emoji">{{cate.emoji}}</view>
					<text class="qxl-cate-name">{{cate.name}}</text>
					<text class="qxl-cate-sub">{{cate.subtitle}}</text>
					<view class="qxl-cate-btn">
						<text>查看活动</text>
						<text class="qxl-cate-arrow">›</text>
					</view>
				</view>
				<view class="qxl-cate-right">
					<view class="qxl-cate-img-wrap">
						<image class="qxl-cate-img" :src="cate.thumb || cate.defaultThumb" mode="aspectFill"></image>
					</view>
				</view>
			</view>
		</view>

		<!-- 提示 -->
		<view class="qxl-footer">
			<text class="qxl-footer-text">点击分类进入对应的主题活动</text>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				// 4 个七贤岭子分类，community_cate_id = 6,7,8,9
				qxl_categories: [
					{ id: 6, name: '发现七贤岭', subtitle: '探索自然，发现美好', emoji: '🔍', bgColor: 'linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%)', thumb: '', defaultThumb: '' },
					{ id: 7, name: '玩在七贤岭', subtitle: '亲子玩乐，互动体验', emoji: '🎮', bgColor: 'linear-gradient(135deg, #34C759 0%, #28A745 100%)', thumb: '', defaultThumb: '' },
					{ id: 8, name: '记录七贤岭', subtitle: '用镜头记录每一刻', emoji: '📷', bgColor: 'linear-gradient(135deg, #FF9500 0%, #E68900 100%)', thumb: '', defaultThumb: '' },
					{ id: 9, name: '守护七贤岭', subtitle: '守护绿水青山，从我做起', emoji: '🌱', bgColor: 'linear-gradient(135deg, #AF52DE 0%, #8B41C2 100%)', thumb: '', defaultThumb: '' },
				],
				activeCache: {}, // 缓存每个分类下的第一个活动 ID
			};
		},
		methods: {
			async handleCateTap(cate) {
				try {
					const res = await this.$u.api.get_new_active_list({
						community_cate_id: cate.id,
						page_size: 1,
					})
					const list = res.data?.data || []
					if (!list.length) {
						this.$u.toast('该分类下暂无活动')
						return
					}
					const firstId = list[0].id
					// 直接跳转活动详情，不再进入列表页
					this.$u.route(`/pages/low_carbon/lsxf/details-new?id=${firstId}`)
				} catch (e) {
					this.$u.toast('加载失败，请重试')
				}
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
	/* 背景渐变，从 hero 图过渡 */
	background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0) 100%);
}
.fb-nav-back {
	width: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.fb-nav-title {
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	flex: 1;
	text-align: center;
	text-shadow: 0 2rpx 4rpx rgba(0,0,0,0.3);
}
.fb-nav-placeholder { width: 80rpx; }

/* ===== Hero Banner ===== */
.qxl-hero {
	position: relative;
	height: 360rpx;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	margin-top: -88rpx; /* 让 hero 延伸到导航栏后面 */
	padding-top: 88rpx;
}
.qxl-hero-overlay {
	position: absolute;
	top: 0; left: 0; right: 0; bottom: 0;
	background: url('') center/cover no-repeat;
	opacity: 0.3;
}
.qxl-hero-content {
	position: relative;
	z-index: 2;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	color: #fff;
}
.qxl-hero-title {
	font-size: 48rpx;
	font-weight: 800;
	letter-spacing: 4rpx;
	text-shadow: 0 4rpx 8rpx rgba(0,0,0,0.25);
}
.qxl-hero-subtitle {
	font-size: 26rpx;
	margin-top: 12rpx;
	opacity: 0.9;
	letter-spacing: 2rpx;
}

/* ===== 4 分类卡片 ===== */
.qxl-cate-wrap {
	padding: 24rpx 16rpx 16rpx;
	margin-top: -40rpx; /* 向上叠一点 hero */
	position: relative;
	z-index: 10;
}
.qxl-cate-card {
	display: flex;
	align-items: stretch;
	border-radius: 20rpx;
	overflow: hidden;
	margin-bottom: 20rpx;
	min-height: 200rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
}
.qxl-cate-card:active { opacity: 0.9; }
.qxl-cate-left {
	flex: 1;
	padding: 28rpx 28rpx 28rpx 28rpx;
	display: flex;
	flex-direction: column;
	color: #fff;
}
.qxl-cate-emoji {
	font-size: 48rpx;
	margin-bottom: 8rpx;
}
.qxl-cate-name {
	font-size: 34rpx;
	font-weight: 700;
	letter-spacing: 2rpx;
}
.qxl-cate-sub {
	font-size: 22rpx;
	margin-top: 6rpx;
	opacity: 0.85;
	flex: 1;
}
.qxl-cate-btn {
	display: inline-flex;
	align-items: center;
	align-self: flex-start;
	height: 52rpx;
	padding: 0 20rpx;
	background: rgba(255,255,255,0.25);
	border-radius: 26rpx;
	font-size: 24rpx;
	font-weight: 500;
	margin-top: 12rpx;
}
.qxl-cate-arrow {
	margin-left: 6rpx;
	font-size: 28rpx;
	font-weight: 300;
}
.qxl-cate-right {
	width: 200rpx;
	position: relative;
}
.qxl-cate-img-wrap {
	position: absolute;
	top: 20rpx;
	right: -10rpx;
	width: 180rpx;
	height: 160rpx;
	border-radius: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.2);
	background: rgba(255,255,255,0.2);
}
.qxl-cate-img {
	width: 100%;
	height: 100%;
}

/* ===== 底部提示 ===== */
.qxl-footer {
	text-align: center;
	padding: 40rpx 0 100rpx;
}
.qxl-footer-text {
	font-size: 24rpx;
	color: #8A8A8A;
}
</style>
