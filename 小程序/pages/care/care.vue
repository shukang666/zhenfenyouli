<template>

	<view class="care-page">

		<u-navbar

			:is-back="true"

			back-text="返回"

			title="关怀版"

			title-size="38"

			title-color="#fff"

			back-icon-size="44"

			:background="{ background: '#12822A' }"

			:border-bottom="false"

			@back="goBack"

		></u-navbar>



		<scroll-view scroll-y class="care-scroll" :show-scrollbar="false">

			



			<view class="care-login flat-card" v-if="!isLogin">

				<text class="care-login__title">您还未登录</text>

				<text class="care-login__desc">登录后才能签到、拍照分类和换礼品</text>

				<view class="care-big-btn" @click="goLogin">

					<text class="care-big-btn__text">点这里登录</text>

				</view>

			</view>



			<view class="care-welcome flat-card" v-else>

				<text class="care-welcome__hi">{{ greeting }}，{{ displayName }}</text>

				<text class="care-welcome__date">{{ dateText }}</text>

				<view class="care-welcome__point">

					<text class="care-welcome__point-label">我的积分</text>

					<text class="care-welcome__point-num">{{ userPoint }}</text>

					<text class="care-welcome__point-unit">分</text>

				</view>

			</view>



			<!-- ① 签到 -->

			<view class="care-block flat-card">

				<view class="care-block__head">

					<text class="care-block__step">第 1 步</text>

					<text class="care-block__title">每天签到领积分</text>

				</view>

				<text class="care-block__hint">签到不花钱，点一下绿色按钮就行。</text>

				<view class="care-stat" v-if="isLogin">

					<text class="care-stat__label">已连续签到</text>

					<text class="care-stat__num">{{ signStatus.continue_days || 0 }}</text>

					<text class="care-stat__unit">天</text>

				</view>

				<view

					class="care-big-btn"

					:class="{ 'care-big-btn--muted': signStatus.today_signed }"

					@click="handleSign"

				>

					<text class="care-big-btn__text">{{ signBtnText }}</text>

				</view>

				<text class="care-block__foot" v-if="signStatus.today_signed">今天已经签过啦，明天再来。</text>

			</view>



			<!-- ② 垃圾分类 -->

			<view class="care-block flat-card">

				<view class="care-block__head">

					<text class="care-block__step">第 2 步</text>

					<text class="care-block__title">垃圾分类拍照</text>

				</view>

				<text class="care-block__hint">先把垃圾分成「可回收、厨余、其他」等，再拍一张照片上传。</text>

				<view class="care-big-btn care-big-btn--outline" @click="goClock">

					<text class="care-big-btn__text care-big-btn__text--green">点这里去拍照分类</text>

				</view>

				<text class="care-block__foot">上传成功后会加积分，和签到一样在上方「我的积分」里看。</text>

			</view>



			<!-- ③ 积分商城 -->

			<view class="care-block flat-card">

				<view class="care-block__head">

					<text class="care-block__step">第 3 步</text>

					<text class="care-block__title">积分换礼品</text>

				</view>

				<text class="care-block__hint">积分够了可以换米面油、日用品等，按页面提示选商品、填地址。</text>

				<view class="care-big-btn care-big-btn--gold" @click="goMall">

					<text class="care-big-btn__text">点这里去换礼品</text>

				</view>

			</view>



			<view class="care-more flat-card">

				<text class="care-more__title">其他常用</text>

				<view class="care-more__row" @click="goOrder">

					<text class="care-more__label">看我的兑换订单</text>

					<u-icon name="arrow-right" size="36" color="#666"></u-icon>

				</view>

				<view class="care-more__row" @click="goPointDetail">

					<text class="care-more__label">看积分明细</text>

					<u-icon name="arrow-right" size="36" color="#666"></u-icon>

				</view>

			</view>



			<view class="care-guide flat-card">

				<text class="care-guide__title">三步说明（给本人或家人看）</text>

				<view class="care-guide__item">

					<text class="care-guide__num">1</text>

					<text class="care-guide__text">打开本页，先点「登录」（若已登录可跳过）。</text>

				</view>

				<view class="care-guide__item">

					<text class="care-guide__num">2</text>

					<text class="care-guide__text">每天点一次「签到」；分好垃圾后点「去拍照分类」。</text>

				</view>

				<view class="care-guide__item">

					<text class="care-guide__num">3</text>

					<text class="care-guide__text">积分够了点「去换礼品」；不会选商品可让家人帮忙下单。</text>

				</view>

				<text class="care-guide__note">返回普通界面：点左上角「返回」。</text>

			</view>



			<view class="care-support">

				<button class="care-support__contact" open-type="contact" hover-class="care-support__contact--hover">

					<text class="care-support__contact-text">有问题？点这里问客服</text>

				</button>

				<view class="care-support__help" @click="goHelp">

					<text class="care-support__help-text">再看一遍文字说明</text>

				</view>

			</view>



			<view class="care-scroll__tail"></view>

		</scroll-view>

	</view>

</template>



<script>

	import { runSceneClockLearnExamGate } from '@/common/sceneClockEntry.js'



	export default {

		data() {

			return {

				signStatus: {

					today_signed: false,

					continue_days: 0

				},

				greeting: '',

				dateText: '',

				userPoint: 0

			}

		},

		computed: {

			displayName() {

				const u = this.userInfo || {}

				return u.nickname || u.true_name || '朋友'

			},

			signBtnText() {

				if (!this.isLogin) return '请先登录再签到'

				if (this.signStatus.today_signed) return '今天已签到'

				return '点这里签到'

			}

		},

		onLoad() {

			this.setGreeting()

			this.syncUserPoint()

			this.getSignStatus()

		},

		onShow() {

			this.syncUserPoint()

			this.getSignStatus()

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



			syncUserPoint() {

				if (!this.isLogin) {

					this.userPoint = 0

					return

				}

				const u = this.userInfo || {}

				if (u.point != null && u.point !== '') {

					this.userPoint = u.point

					return

				}

				this.$u.api.getUserInfo().then(res => {

					const data = res.data || {}

					this.userPoint = data.point || 0

				}).catch(() => {

					this.userPoint = 0

				})

			},



			getSignStatus() {

				if (!this.isLogin) {

					this.signStatus = { today_signed: false, continue_days: 0 }

					return

				}

				this.$u.api.getSignStatus().then(res => {

					this.signStatus = res.data || { today_signed: false, continue_days: 0 }

				}).catch(() => {})

			},



			goLogin() {

				this.$u.route('/pages/public/login')

			},



			handleSign() {

				if (!this.isLogin) {

					this.$u.toast('请先登录')

					this.goLogin()

					return

				}

				if (this.signStatus.today_signed) {

					uni.showToast({ title: '今天已签到，明天再来', icon: 'none', duration: 2500 })

					return

				}

				this.$u.api.dailySign().then(res => {

					this.signStatus.today_signed = true

					this.signStatus.continue_days = (this.signStatus.continue_days || 0) + 1

					const pt = res.data && res.data.point != null ? res.data.point : 0

					uni.showToast({

						title: `签到成功，加了 ${pt} 积分`,

						icon: 'none',

						duration: 2800

					})

					this.syncUserPoint()

				}).catch(err => {

					uni.showToast({

						title: (err && err.msg) || '签到没成功，稍后再点一次',

						icon: 'none',

						duration: 2800

					})

				})

			},



			goClock() {

				if (!this.isLogin) {

					uni.showModal({

						title: '需要先登录',

						content: '拍照分类前要登录账号，点「确定」去登录页。',

						confirmText: '去登录',

						cancelText: '先不登',

						success: (res) => {

							if (res.confirm) this.goLogin()

						}

					})

					return

				}

				runSceneClockLearnExamGate(this)

			},



			goPointDetail() {

				if (!this.isLogin) {

					this.goLogin()

					return

				}

				this.$u.route('/pages/points/points')

			},



			goMall() {

				this.$u.route({ type: 'switchTab', url: '/pages/mall/mall' })

			},



			goOrder() {

				if (!this.isLogin) {

					this.goLogin()

					return

				}

				this.$u.route('/pages/order_list/order_list')

			},



			goHelp() {

				uni.showModal({

					title: '关怀版怎么用',

					content:

						'① 每天在本页点「签到」领积分。\n\n' +

						'② 垃圾分好类后，点「去拍照分类」，按提示选场景、拍照上传。\n\n' +

						'③ 积分够了点「去换礼品」，在商城里选东西；不会操作请家人帮忙。\n\n' +

						'④ 左上角「返回」回到普通版；有问题点底部「问客服」。',

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

	height: 100vh;

	display: flex;

	flex-direction: column;

	background: #f2f2f7;

}



.care-scroll {

	flex: 1;

	height: 0;

	box-sizing: border-box;

}



.care-scroll__tail {

	height: 48rpx;

}



.care-tip-bar {

	margin: 20rpx 24rpx 0;

	padding: 28rpx 32rpx;

	background: #fff9e6;

	border-radius: 20rpx;

	border: 2rpx solid #f0dfa0;

}



.care-tip-bar__main {

	display: block;

	font-size: 36rpx;

	font-weight: 700;

	color: #3d3d3d;

	line-height: 1.45;

}



.care-tip-bar__sub {

	display: block;

	margin-top: 12rpx;

	font-size: 30rpx;

	color: #666;

	line-height: 1.5;

}



.flat-card {

	margin: 24rpx 24rpx 0;

	background: #fff;

	border-radius: 24rpx;

	padding: 32rpx 28rpx;

	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);

}



.care-login__title {

	display: block;

	font-size: 40rpx;

	font-weight: 700;

	color: #1d1d1f;

}



.care-login__desc {

	display: block;

	margin-top: 16rpx;

	font-size: 32rpx;

	color: #666;

	line-height: 1.5;

}



.care-welcome__hi {

	display: block;

	font-size: 40rpx;

	font-weight: 700;

	color: #1d1d1f;

	line-height: 1.35;

}



.care-welcome__date {

	display: block;

	margin-top: 12rpx;

	font-size: 30rpx;

	color: #666;

}



.care-welcome__point {

	margin-top: 28rpx;

	padding-top: 24rpx;

	border-top: 2rpx solid #eee;

	display: flex;

	flex-direction: row;

	align-items: baseline;

	flex-wrap: wrap;

}



.care-welcome__point-label {

	font-size: 32rpx;

	color: #444;

	margin-right: 16rpx;

}



.care-welcome__point-num {

	font-size: 56rpx;

	font-weight: 700;

	color: #c17a00;

	line-height: 1;

}



.care-welcome__point-unit {

	font-size: 32rpx;

	color: #c17a00;

	margin-left: 8rpx;

}



.care-block__head {

	display: flex;

	flex-direction: row;

	align-items: center;

	flex-wrap: wrap;

	margin-bottom: 16rpx;

}



.care-block__step {

	font-size: 28rpx;

	font-weight: 600;

	color: #fff;

	background: #12822a;

	padding: 6rpx 16rpx;

	border-radius: 8rpx;

	margin-right: 16rpx;

}



.care-block__title {

	font-size: 40rpx;

	font-weight: 700;

	color: #1d1d1f;

}



.care-block__hint {

	display: block;

	font-size: 32rpx;

	color: #555;

	line-height: 1.55;

	margin-bottom: 24rpx;

}



.care-block__foot {

	display: block;

	margin-top: 20rpx;

	font-size: 28rpx;

	color: #888;

	line-height: 1.5;

}



.care-stat {

	display: flex;

	flex-direction: row;

	align-items: baseline;

	margin-bottom: 20rpx;

}



.care-stat__label {

	font-size: 32rpx;

	color: #444;

	margin-right: 12rpx;

}



.care-stat__num {

	font-size: 48rpx;

	font-weight: 700;

	color: #12822a;

}



.care-stat__unit {

	font-size: 32rpx;

	color: #12822a;

	margin-left: 6rpx;

}



.care-big-btn {

	min-height: 112rpx;

	border-radius: 20rpx;

	background: #12822a;

	display: flex;

	align-items: center;

	justify-content: center;

	padding: 24rpx 32rpx;

}



.care-big-btn--muted {

	background: #b0b0b0;

}



.care-big-btn--outline {

	background: #fff;

	border: 4rpx solid #12822a;

}



.care-big-btn--gold {

	background: #d4920a;

}



.care-big-btn__text {

	font-size: 38rpx;

	font-weight: 700;

	color: #fff;

	text-align: center;

	line-height: 1.35;

}



.care-big-btn__text--green {

	color: #12822a;

}



.care-more__title {

	display: block;

	font-size: 36rpx;

	font-weight: 700;

	color: #1d1d1f;

	margin-bottom: 8rpx;

}



.care-more__row {

	min-height: 100rpx;

	display: flex;

	flex-direction: row;

	align-items: center;

	justify-content: space-between;

	border-top: 2rpx solid #f0f0f0;

	padding: 24rpx 0;

}



.care-more__row:first-of-type {

	border-top: none;

}



.care-more__label {

	font-size: 34rpx;

	color: #333;

	font-weight: 500;

}



.care-guide__title {

	display: block;

	font-size: 36rpx;

	font-weight: 700;

	color: #1d1d1f;

	margin-bottom: 24rpx;

}



.care-guide__item {

	display: flex;

	flex-direction: row;

	align-items: flex-start;

	margin-bottom: 20rpx;

}



.care-guide__num {

	width: 44rpx;

	height: 44rpx;

	border-radius: 50%;

	background: #12822a;

	color: #fff;

	font-size: 28rpx;

	font-weight: 700;

	text-align: center;

	line-height: 44rpx;

	flex-shrink: 0;

	margin-right: 16rpx;

	margin-top: 4rpx;

}



.care-guide__text {

	flex: 1;

	font-size: 32rpx;

	color: #444;

	line-height: 1.55;

}



.care-guide__note {

	display: block;

	margin-top: 16rpx;

	font-size: 30rpx;

	color: #888;

	line-height: 1.5;

}



.care-support {

	margin: 32rpx 24rpx 0;

}



.care-support__contact {

	width: 100%;

	min-height: 112rpx;

	border-radius: 20rpx;

	background: #12822a;

	border: none;

	padding: 24rpx 32rpx;

	line-height: 1.4;

}



.care-support__contact::after {

	border: none;

}



.care-support__contact--hover {

	opacity: 0.88;

}



.care-support__contact-text {

	font-size: 38rpx;

	font-weight: 700;

	color: #fff;

}



.care-support__help {

	margin-top: 24rpx;

	min-height: 88rpx;

	display: flex;

	align-items: center;

	justify-content: center;

}



.care-support__help-text {

	font-size: 32rpx;

	color: #12822a;

	font-weight: 600;

	text-decoration: underline;

}

</style>

