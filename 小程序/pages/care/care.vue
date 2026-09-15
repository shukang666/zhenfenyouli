<template>
	<view class="care-page">
		<!-- 顶部导航：uView 标准 navbar -->
		<u-navbar :is-back="true" back-text="返回" title="关怀版" title-color="#fff"
			:background="{ background: '#18AF38' }" :border-bottom="false"
			@back="goBack"></u-navbar>

		<!-- 问候信息区 -->
		<view class="care-header">
			<view class="care-greeting">
				<text class="care-greeting-text">{{ greeting }}</text>
				<text class="care-greeting-name">，{{ nickname }}</text>
			</view>
			<text class="care-date">{{ dateText }}</text>
		</view>

		<!-- 今日签到卡片 -->
		<view class="care-card">
			<view class="care-card-title">
				<u-icon name="calendar-fill" size="40" color="#18AF38"></u-icon>
				<text>每日签到</text>
			</view>
			<view class="sign-info">
				<text class="sign-days">{{ signStatus.continue_days || 0 }}</text>
				<text class="sign-label">连续签到天数</text>
			</view>
			<view
				class="care-btn"
				:class="{ 'care-btn--gray': signStatus.today_signed }"
				@click="handleSign"
			>
				<text class="care-btn-text">{{ signStatus.today_signed ? '今日已签到 ✓' : '点击签到' }}</text>
			</view>
		</view>

		<!-- 垃圾分类打卡 -->
		<view class="care-card">
			<view class="care-card-title">
				<u-icon name="photo-fill" size="40" color="#18AF38"></u-icon>
				<text>垃圾分类打卡</text>
			</view>
			<view class="care-btn" @click="goClock">
				<text class="care-btn-text">去打卡</text>
			</view>
		</view>

		<!-- 我的积分 -->
		<view class="care-card">
			<view class="care-card-title">
				<u-icon name="diamond-fill" size="40" color="#18AF38"></u-icon>
				<text>我的积分</text>
			</view>
			<view class="point-row">
				<text class="point-num">{{ userInfo.points || 0 }}</text>
				<text class="point-unit">积分</text>
			</view>
			<view class="point-links">
				<text class="point-link" @click="goPointDetail">积分明细</text>
				<text class="point-link-line">|</text>
				<text class="point-link" @click="goMall">去兑换</text>
			</view>
		</view>

		<!-- 快捷功能 -->
		<view class="care-card">
			<view class="care-card-title">
				<u-icon name="grid-fill" size="40" color="#18AF38"></u-icon>
				<text>快捷功能</text>
			</view>
			<view class="quick-list">
				<view class="quick-row" @click="goCheck">
					<u-icon name="checkmark-circle-fill" size="36" color="#18AF38"></u-icon>
					<text class="quick-name">签到</text>
					<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
				</view>
				<view class="quick-row" @click="goRank">
					<u-icon name="list-dot" size="36" color="#18AF38"></u-icon>
					<text class="quick-name">排行榜</text>
					<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
				</view>
				<view class="quick-row" @click="goOrder">
					<u-icon name="shopping-cart-fill" size="36" color="#18AF38"></u-icon>
					<text class="quick-name">我的订单</text>
					<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
				</view>
				<view class="quick-row" @click="goHelp">
					<u-icon name="question-circle-fill" size="36" color="#18AF38"></u-icon>
					<text class="quick-name">帮助</text>
					<u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
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
			clockTodayCount: 0,
			nickname: '',
			greeting: '',
			dateText: ''
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
					this.nickname = res.data.nickname || res.data.user_nickname || '环保达人'
				}).catch(() => {
					this.nickname = '环保达人'
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
	background: #f5f7f5;
	padding: 0 24rpx 40rpx;
}

/* 问候信息区（绿色卡片） */
.care-header {
	background: #18AF38;
	border-radius: 24rpx;
	padding: 32rpx 28rpx 36rpx;
	margin-bottom: 24rpx;
	
	/* 问候信息 */
	.care-greeting {
		.care-greeting-text {
			font-size: 44rpx;
			color: #fff;
			font-weight: 600;
		}
		
		.care-greeting-name {
			font-size: 44rpx;
			color: #fff;
			font-weight: 600;
		}
	}
	
	.care-date {
		display: block;
		font-size: 26rpx;
		color: rgba(255, 255, 255, 0.8);
		margin-top: 10rpx;
	}
}

/* 卡片通用 */
.care-card {
	background: #fff;
	border-radius: 24rpx;
	padding: 36rpx 32rpx;
	margin-bottom: 24rpx;
	
	.care-card-title {
		display: flex;
		align-items: center;
		gap: 12rpx;
		font-size: 36rpx;
		font-weight: 600;
		color: #333;
		margin-bottom: 20rpx;
	}
	
}

/* 签到 */
.sign-info {
	margin-bottom: 24rpx;
	
	.sign-days {
		font-size: 72rpx;
		font-weight: 700;
		color: #18AF38;
		line-height: 1;
	}
	
	.sign-label {
		display: block;
		font-size: 26rpx;
		color: #888;
		margin-top: 6rpx;
	}
}

/* 积分 */
.point-row {
	margin-bottom: 16rpx;
	
	.point-num {
		font-size: 72rpx;
		font-weight: 700;
		color: #18AF38;
		line-height: 1;
	}
	
	.point-unit {
		font-size: 26rpx;
		color: #888;
		margin-left: 8rpx;
	}
}

.point-links {
	display: flex;
	align-items: center;
	gap: 16rpx;
	
	.point-link {
		font-size: 28rpx;
		color: #18AF38;
	}
	
	.point-link-line {
		color: #ddd;
	}
}

/* 快捷功能列表 */
.quick-list {
	.quick-row {
		display: flex;
		align-items: center;
		padding: 24rpx 0;
		border-bottom: 1rpx solid #f0f0f0;
		
		&:last-child {
			border-bottom: none;
		}
		
		.quick-name {
			flex: 1;
			font-size: 32rpx;
			color: #333;
			margin-left: 16rpx;
		}
	}
}

/* 通用按钮 */
.care-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #18AF38 0%, #128a2e 100%);
	border-radius: 50rpx;
	padding: 28rpx;
	
	.care-btn-text {
		font-size: 36rpx;
		font-weight: 600;
		color: #fff;
	}
	
	&--gray {
		background: linear-gradient(135deg, #b0b0b0 0%, #888 100%);
	}
}
</style>
