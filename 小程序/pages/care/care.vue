<template>
	<view class="care-page">
		<u-navbar :is-back="true" back-text="返回" title="关怀版" title-color="#fff"
			:background="{ background: '#18AF38' }" :border-bottom="false"
			@back="goBack"></u-navbar>

		<view class="care-hero">
			<view class="care-hero__orb care-hero__orb--a"></view>
			<view class="care-hero__orb care-hero__orb--b"></view>
			<view class="care-hero__orb care-hero__orb--c"></view>
			<view class="care-hero__body">
				<view class="care-badge">
					<view class="care-badge__dot"></view>
					<text class="care-badge__text">大字号 · 轻松操作</text>
				</view>
				<view class="care-hello">
					<text class="care-hello__hi">{{ greeting }}，</text>
					<text class="care-hello__name">{{ nickname }}</text>
				</view>
				<view class="care-date">
					<text class="care-date__text">{{ dateText }}</text>
				</view>
			</view>
		</view>

		<view class="care-sheet">
			<view class="care-sign">
				<view class="care-sign__top">
					<view class="care-sign__ring">
						<text class="care-sign__num">{{ signStatus.continue_days || 0 }}</text>
						<text class="care-sign__unit">天</text>
					</view>
					<view class="care-sign__meta">
						<text class="care-sign__title">每日签到</text>
						<text class="care-sign__desc">连续签到，积分天天领</text>
					</view>
				</view>
				<view
					class="care-cta"
					:class="{ 'care-cta--done': signStatus.today_signed }"
					@click="handleSign"
				>
					<text class="care-cta__text">{{ signStatus.today_signed ? '今日已签到' : '点击签到' }}</text>
				</view>
			</view>

			<view class="care-split">
				<view class="care-tile care-tile--action" @click="goClock">
					<view class="care-tile__icon care-tile__icon--mint">
						<u-icon name="photo-fill" size="40" color="#18AF38"></u-icon>
					</view>
					<text class="care-tile__name">垃圾分类</text>
					<text class="care-tile__hint">拍照打卡赚积分</text>
					<view class="care-tile__btn">
						<text class="care-tile__btn-text">去打卡</text>
					</view>
				</view>
				<view class="care-tile">
					<view class="care-tile__icon care-tile__icon--gold">
						<u-icon name="diamond-fill" size="40" color="#D4920A"></u-icon>
					</view>
					<text class="care-tile__name">我的积分</text>
					<view class="care-tile__point">
						<text class="care-tile__point-num">{{ userPoint }}</text>
						<text class="care-tile__point-unit">分</text>
					</view>
					<view class="care-tile__links">
						<text class="care-tile__link" @click="goPointDetail">明细</text>
						<view class="care-tile__sep"></view>
						<text class="care-tile__link" @click="goMall">兑换</text>
					</view>
				</view>
			</view>

			<view class="care-quick">
				<text class="care-quick__title">快捷功能</text>
				<view class="care-quick__grid">
					<view class="care-quick__item" @click="goCheck">
						<view class="care-quick__icon care-quick__icon--a">
							<u-icon name="checkmark-circle-fill" size="44" color="#18AF38"></u-icon>
						</view>
						<text class="care-quick__name">签到</text>
					</view>
					<view class="care-quick__item" @click="goRank">
						<view class="care-quick__icon care-quick__icon--b">
							<u-icon name="list-dot" size="44" color="#2B8CFF"></u-icon>
						</view>
						<text class="care-quick__name">排行榜</text>
					</view>
					<view class="care-quick__item" @click="goOrder">
						<view class="care-quick__icon care-quick__icon--c">
							<u-icon name="shopping-cart-fill" size="44" color="#E67A22"></u-icon>
						</view>
						<text class="care-quick__name">我的订单</text>
					</view>
					<view class="care-quick__item" @click="goHelp">
						<view class="care-quick__icon care-quick__icon--d">
							<u-icon name="question-circle-fill" size="44" color="#8B6CE8"></u-icon>
						</view>
						<text class="care-quick__name">帮助</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
	data() {
		return {
			signStatus: {
				today_signed: false,
				continue_days: 0
			},
			nickname: '',
			greeting: '',
			dateText: '',
			userPoint: 0
		}
	},
	onLoad() {
		this.setGreeting()
		this.getUserInfo()
		this.getSignStatus()
	},
	onShow() {
		this.getUserInfo()
	},
	methods: {
		setGreeting() {
			const hour = new Date().getHours()
			if (hour < 6) this.greeting = '凌晨好'
			else if (hour < 9) this.greeting = '早上好'
			else if (hour < 12) this.greeting = '上午好'
			else if (hour < 14) this.greeting = '中午好'
			else if (hour < 18) this.greeting = '下午好'
			else this.greeting = '晚上好'

			const week = ['日', '一', '二', '三', '四', '五', '六']
			const now = new Date()
			this.dateText = `${now.getFullYear()}年${now.getMonth() + 1}月${now.getDate()}日 星期${week[now.getDay()]}`
		},

		getUserInfo() {
			this.$u.api.getUserInfo().then(res => {
				const data = res.data || {}
				this.nickname = data.nickname || data.user_nickname || '环保达人'
				this.userPoint = data.point || 0
			}).catch(() => {
				this.nickname = '环保达人'
				this.userPoint = 0
			})
		},

		getSignStatus() {
			if (!this.isLogin) return
			this.$u.api.getSignStatus().then(res => {
				this.signStatus = res.data || { today_signed: false, continue_days: 0 }
			}).catch(() => {})
		},

		handleSign() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			if (this.signStatus.today_signed) {
				this.$u.toast('今日已签到啦，明天继续哦~')
				return
			}
			this.$u.api.dailySign().then(res => {
				this.signStatus.today_signed = true
				this.signStatus.continue_days = (this.signStatus.continue_days || 0) + 1
				this.$u.toast(`签到成功！获得 ${res.data.point || 0} 积分`)
				this.getUserInfo()
			}).catch(err => {
				this.$u.toast(err.msg || '签到失败，请稍后再试')
			})
		},

		goClock() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			this.$u.route('/pages/flhd/ljfldk')
		},

		goPointDetail() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			this.$u.route('/pages/points/points')
		},

		goMall() {
			this.$u.route({ type: 'switchTab', url: '/pages/mall/mall' })
		},

		goRank() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			this.$u.route('/pages/activity/rank')
		},

		goOrder() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			this.$u.route('/pages/order_list/order_list')
		},

		goCheck() {
			if (!this.isLogin) {
				this.$u.route('/pages/public/login')
				return
			}
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 300
			})
		},

		goHelp() {
			uni.showModal({
				title: '使用帮助',
				content: '● 每日签到：每天签到可获得积分\n● 垃圾分类：拍照上传分类成果赚积分\n● 积分使用：积分可在商城兑换礼品',
				showCancel: false,
				confirmText: '我知道了'
			})
		},

		goBack() {
			uni.navigateBack({ delta: 1 })
		}
	}
}
</script>

<style lang="scss" scoped>
.care-page {
	min-height: 100vh;
	background: #eef6ef;
	padding-bottom: 60rpx;
}

.care-hero {
	position: relative;
	overflow: hidden;
	background: linear-gradient(165deg, #18af38 0%, #12962e 58%, #0e7f26 100%);
	padding: 12rpx 40rpx 88rpx;
}

.care-hero__orb {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
}

.care-hero__orb--a {
	width: 220rpx;
	height: 220rpx;
	top: -80rpx;
	right: -40rpx;
}

.care-hero__orb--b {
	width: 140rpx;
	height: 140rpx;
	top: 80rpx;
	right: 120rpx;
	background: rgba(255, 255, 255, 0.08);
}

.care-hero__orb--c {
	width: 90rpx;
	height: 90rpx;
	bottom: 40rpx;
	left: -20rpx;
	background: rgba(255, 220, 80, 0.18);
}

.care-hero__body {
	position: relative;
	z-index: 1;
}

.care-badge {
	display: inline-flex;
	flex-direction: row;
	align-items: center;
	background: rgba(255, 255, 255, 0.18);
	border-radius: 999rpx;
	padding: 8rpx 20rpx 8rpx 12rpx;
	margin-bottom: 20rpx;
}

.care-badge__dot {
	width: 14rpx;
	height: 14rpx;
	border-radius: 50%;
	background: #ffd56a;
	margin-right: 10rpx;
}

.care-badge__text {
	font-size: 24rpx;
	color: #fff;
	letter-spacing: 2rpx;
}

.care-hello {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	align-items: baseline;
}

.care-hello__hi {
	font-size: 36rpx;
	color: rgba(255, 255, 255, 0.92);
	font-weight: 500;
}

.care-hello__name {
	font-size: 52rpx;
	color: #fff;
	font-weight: 700;
	line-height: 1.2;
}

.care-date {
	margin-top: 16rpx;
	display: inline-flex;
	background: rgba(0, 0, 0, 0.12);
	border-radius: 999rpx;
	padding: 8rpx 22rpx;
}

.care-date__text {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.92);
}

.care-sheet {
	position: relative;
	margin-top: -48rpx;
	padding: 0 24rpx 8rpx;
}

.care-sign {
	background: #fff;
	border-radius: 32rpx;
	padding: 36rpx 32rpx 32rpx;
	box-shadow: 0 16rpx 40rpx rgba(24, 175, 56, 0.12);
	margin-bottom: 24rpx;
}

.care-sign__top {
	display: flex;
	flex-direction: row;
	align-items: center;
	margin-bottom: 28rpx;
}

.care-sign__ring {
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	border: 10rpx solid #e7f7eb;
	background: linear-gradient(180deg, #f6fff8 0%, #e8f8ec 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	margin-right: 28rpx;
	flex-shrink: 0;
}

.care-sign__num {
	font-size: 56rpx;
	font-weight: 700;
	color: #18af38;
	line-height: 1;
}

.care-sign__unit {
	font-size: 22rpx;
	color: #6a9a74;
	margin-top: 4rpx;
}

.care-sign__meta {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.care-sign__title {
	font-size: 40rpx;
	font-weight: 700;
	color: #1c2b1e;
	line-height: 1.2;
}

.care-sign__desc {
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #7a8b7d;
}

.care-cta {
	height: 96rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #2bc24e 0%, #149a32 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 12rpx 24rpx rgba(24, 175, 56, 0.28);
}

.care-cta__text {
	font-size: 36rpx;
	font-weight: 700;
	color: #fff;
	letter-spacing: 2rpx;
}

.care-cta--done {
	background: linear-gradient(135deg, #c5d4c8 0%, #9aada0 100%);
	box-shadow: none;
}

.care-split {
	display: flex;
	flex-direction: row;
	margin-bottom: 24rpx;
}

.care-tile {
	flex: 1;
	background: #fff;
	border-radius: 28rpx;
	padding: 28rpx 24rpx 24rpx;
	box-shadow: 0 10rpx 28rpx rgba(16, 48, 24, 0.06);
}

.care-tile--action {
	margin-right: 20rpx;
}

.care-tile__icon {
	width: 72rpx;
	height: 72rpx;
	border-radius: 22rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 18rpx;
}

.care-tile__icon--mint {
	background: #e8f8ec;
}

.care-tile__icon--gold {
	background: #fff4d8;
}

.care-tile__name {
	font-size: 32rpx;
	font-weight: 700;
	color: #1c2b1e;
}

.care-tile__hint {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #8a9b8d;
}

.care-tile__btn {
	margin-top: 20rpx;
	height: 64rpx;
	border-radius: 999rpx;
	background: #18af38;
	display: flex;
	align-items: center;
	justify-content: center;
}

.care-tile__btn-text {
	font-size: 26rpx;
	font-weight: 600;
	color: #fff;
}

.care-tile__point {
	display: flex;
	flex-direction: row;
	align-items: baseline;
	margin-top: 12rpx;
}

.care-tile__point-num {
	font-size: 48rpx;
	font-weight: 700;
	color: #d4920a;
	line-height: 1;
}

.care-tile__point-unit {
	font-size: 24rpx;
	color: #b0892c;
	margin-left: 6rpx;
}

.care-tile__links {
	display: flex;
	flex-direction: row;
	align-items: center;
	margin-top: 16rpx;
}

.care-tile__link {
	font-size: 26rpx;
	color: #18af38;
	font-weight: 600;
}

.care-tile__sep {
	width: 2rpx;
	height: 22rpx;
	background: #dce8de;
	margin: 0 16rpx;
}

.care-quick {
	background: #fff;
	border-radius: 28rpx;
	padding: 32rpx 20rpx 20rpx;
	box-shadow: 0 10rpx 28rpx rgba(16, 48, 24, 0.06);
}

.care-quick__title {
	display: block;
	padding: 0 12rpx 20rpx;
	font-size: 32rpx;
	font-weight: 700;
	color: #1c2b1e;
}

.care-quick__grid {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
}

.care-quick__item {
	width: 50%;
	padding: 16rpx 12rpx 28rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.care-quick__icon {
	width: 96rpx;
	height: 96rpx;
	border-radius: 32rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 14rpx;
}

.care-quick__icon--a {
	background: #e8f8ec;
}

.care-quick__icon--b {
	background: #e8f2ff;
}

.care-quick__icon--c {
	background: #fff1e6;
}

.care-quick__icon--d {
	background: #f1ebff;
}

.care-quick__name {
	font-size: 28rpx;
	font-weight: 600;
	color: #2c3a2f;
}
</style>
