<template>
	<view class="dk-hub" v-if="!setting.app_test">
		<view class="dk-hero">
			<text class="dk-hero-kicker">每天拍一张分类投放</text>
			<text class="dk-hero-title">垃圾分类打卡</text>
			<text class="dk-hero-desc">选对类型、拍清楚、写一句说明，提交后积分到账。</text>
			<view class="dk-start" hover-class="dk-start--press" :hover-stay-time="80" @click="goClock">
				<u-icon name="camera-fill" size="40" color="#18AF38"></u-icon>
				<text class="dk-start-text">开始拍照打卡</text>
			</view>
		</view>

		<view class="dk-stats" v-if="isLogin">
			<view class="dk-stat">
				<text class="dk-stat-num">{{ userInfo.point || 0 }}</text>
				<text class="dk-stat-lab">我的积分</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ streak }}</text>
				<text class="dk-stat-lab">连续打卡天数</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ remainText }}</text>
				<text class="dk-stat-lab">距环保达人</text>
			</view>
		</view>

		<view class="dk-gate" v-if="isLogin && gateTip" @click="goGate">
			<text class="dk-gate-t">{{ gateTip }}</text>
			<text class="dk-gate-a">去完成 ›</text>
		</view>

		<view class="dk-block">
			<text class="dk-block-t">怎么打</text>
			<view class="dk-how">
				<view class="dk-how-item">
					<view class="dk-how-n">1</view>
					<view class="dk-how-body">
						<text class="dk-how-h">选垃圾类型</text>
						<text class="dk-how-s">可回收、厨余、有害或其他，和桶上的一致</text>
					</view>
				</view>
				<view class="dk-how-item">
					<view class="dk-how-n">2</view>
					<view class="dk-how-body">
						<text class="dk-how-h">对准再拍</text>
						<text class="dk-how-s">拍投放的垃圾本体，光线够、别太远、别被手挡住</text>
					</view>
				</view>
				<view class="dk-how-item">
					<view class="dk-how-n">3</view>
					<view class="dk-how-body">
						<text class="dk-how-h">写一句并提交</text>
						<text class="dk-how-s">用一句话说明分到哪一类，然后等积分</text>
					</view>
				</view>
			</view>
		</view>


		<view class="dk-block">
			<text class="dk-block-t">拍照注意</text>
			<view class="dk-tips">
				<text class="dk-tip">照片要能看清垃圾内容，分类要正确。</text>
				<text class="dk-tip">分错可能危害环境，也可能会被撤销积分。</text>
				<text class="dk-tip">投完再拍，不要拍空桶或和分类无关的图。</text>
			</view>
		</view>

		<view class="dk-block">
			<text class="dk-block-t">过往记录</text>
			<view class="dk-hist-row">
				<view class="dk-hist-btn dk-hist-btn--mine" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goMy">
					<text class="dk-hist-name">我的打卡</text>
					<text class="dk-hist-sub">只看自己交过的</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
				<view class="dk-hist-btn dk-hist-btn--pick" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goChoice">
					<text class="dk-hist-name">精选打卡</text>
					<text class="dk-hist-sub">看看别人怎么分</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				cate_list: []
			}
		},
		computed: {
			streak() {
				const u = this.userInfo || {}
				return u.clock_days || u.continuous_days || u.clock_count || 0
			},
			remainText() {
				const n = (this.userInfo && this.userInfo.remaining_count) || 0
				return n > 0 ? n + '天' : '已达成'
			},
			gateTip() {
				const u = this.userInfo || {}
				if (!u.is_learn) return '打卡前请先学完分类指导'
				if (!u.is_answer) return '学完了，还差一场分类考试'
				return ''
			}
		},
		onShow() {
			this.refreshUser()
			this.loadCate()
		},
		methods: {
			refreshUser() {
				if (!this.isLogin) return
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
				}).catch(() => {})
			},
			loadCate() {
				this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cate_list = res.data || []
				}).catch(() => {})
			},
			goClock() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				this.$u.route('/pages/flhd/add')
			},
			goGate() {
				if (!this.userInfo.is_learn) return this.$u.route('/pages/flhd/list')
				if (!this.userInfo.is_answer) return this.$u.route('/pages/answer/examine?id=1')
			},
			goMy() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route('/pages/flhd/record')
			},
			goChoice() {
				this.$u.route('/pages/flhd/choice')
			}
		}
	}
</script>

<style lang="scss" scoped>
.dk-hub {
	min-height: 100vh;
	padding: 24rpx 28rpx 80rpx;
	background: #e8f0ea;
}
.dk-hero {
	padding: 40rpx 32rpx 36rpx;
	border-radius: 28rpx;
	background: linear-gradient(145deg, #6dca7c 0%, #18AF38 78%);
	box-shadow: 0 16rpx 36rpx rgba(24, 175, 56, 0.26);
}
.dk-hero-kicker {
	display: block;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.86);
}
.dk-hero-title {
	display: block;
	margin-top: 10rpx;
	font-size: 44rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hero-desc {
	display: block;
	margin-top: 14rpx;
	font-size: 26rpx;
	line-height: 1.55;
	color: rgba(255, 255, 255, 0.92);
}
.dk-start {
	margin-top: 32rpx;
	height: 88rpx;
	border-radius: 44rpx;
	background: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
}
.dk-start--press {
	opacity: 0.9;
	transform: scale(0.98);
}
.dk-start-text {
	margin-left: 10rpx;
	font-size: 32rpx;
	font-weight: 800;
	color: #148a2e;
}
.dk-stats {
	margin-top: 24rpx;
	padding: 28rpx 12rpx;
	border-radius: 20rpx;
	background: #fff;
	display: flex;
	align-items: center;
}
.dk-stat {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.dk-stat-num {
	font-size: 36rpx;
	font-weight: 800;
	color: #148a2e;
}
.dk-stat-lab {
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.dk-stat-line {
	width: 1rpx;
	height: 56rpx;
	background: #e8eee9;
}
.dk-gate {
	margin-top: 16rpx;
	padding: 22rpx 24rpx;
	border-radius: 16rpx;
	background: #fff7e6;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.dk-gate-t {
	flex: 1;
	font-size: 26rpx;
	color: #8a5a00;
}
.dk-gate-a {
	margin-left: 16rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #c47b00;
}
.dk-block {
	margin-top: 28rpx;
}
.dk-block-t {
	display: block;
	margin-bottom: 16rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
}
.dk-block-hint {
	display: block;
	margin: -8rpx 0 16rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.dk-how {
	padding: 8rpx 24rpx 12rpx;
	border-radius: 20rpx;
	background: #fff;
}
.dk-how-item {
	display: flex;
	padding: 22rpx 0;
	border-bottom: 1rpx solid #f1f4f2;
}
.dk-how-item:last-child {
	border-bottom: none;
}
.dk-how-n {
	width: 48rpx;
	height: 48rpx;
	border-radius: 16rpx;
	background: #e8f7ea;
	color: #18AF38;
	font-size: 26rpx;
	font-weight: 800;
	text-align: center;
	line-height: 48rpx;
	flex-shrink: 0;
}
.dk-how-body {
	margin-left: 18rpx;
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
}
.dk-how-h {
	font-size: 28rpx;
	font-weight: 700;
	color: #1c2b1e;
}
.dk-how-s {
	margin-top: 6rpx;
	font-size: 24rpx;
	line-height: 1.5;
	color: #6b786f;
}
.dk-cates {
	display: flex;
	flex-wrap: wrap;
	margin: 0 -8rpx;
}
.dk-cate {
	margin: 0 8rpx 16rpx;
	padding: 14rpx 26rpx;
	border-radius: 12rpx;
	background: #fff;
	font-size: 26rpx;
	color: #2a4a32;
	font-weight: 600;
	box-shadow: 0 2rpx 10rpx rgba(20, 48, 28, 0.04);
}
.dk-tips {
	padding: 24rpx 28rpx;
	border-radius: 20rpx;
	background: #fff;
}
.dk-tip {
	display: block;
	font-size: 26rpx;
	line-height: 1.7;
	color: #4a5c4e;
}
.dk-hist-row {
	display: flex;
	margin: 0 -10rpx;
}
.dk-hist-btn {
	position: relative;
	overflow: hidden;
	flex: 1;
	margin: 0 10rpx;
	padding: 32rpx 24rpx 28rpx;
	border-radius: 24rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-hist-btn::before {
	content: '';
	position: absolute;
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.22);
	top: -50rpx;
	right: -36rpx;
	pointer-events: none;
}
.dk-hist-btn::after {
	content: '';
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.32);
	pointer-events: none;
}
.dk-hist-btn--mine {
	background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
	box-shadow: 0 10rpx 24rpx rgba(24, 175, 56, 0.28);
}
.dk-hist-btn--pick {
	background: linear-gradient(141deg, #7dd3c7 0%, #2aa8a0 100%);
	box-shadow: 0 10rpx 24rpx rgba(42, 168, 160, 0.26);
}
.dk-hist-btn--press {
	transform: scale(0.96);
	opacity: 0.92;
}
.dk-hist-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hist-sub {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.88);
}
.dk-hist-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 20rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #fff;
}
</style>
