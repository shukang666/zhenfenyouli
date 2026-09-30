<template>
	<view class="cpl-page" v-if="!setting.app_test">
		<view class="cpl-head">
			<text class="cpl-title">打卡前学习</text>
			<text class="cpl-sub">阅读以下三部分，确认后即可参加分类小测验或返回继续打卡</text>
		</view>
		<scroll-view scroll-y class="cpl-scroll" :show-scrollbar="false">
			<view class="cpl-card" v-for="(sec, si) in introSections" :key="'sec-' + si">
				<text class="cpl-card-title">{{ sec.name }}</text>
				<view class="cpl-card-body" v-if="sec.contentHtml">
					<u-parse :html="sec.contentHtml"></u-parse>
				</view>
			</view>
			<view class="cpl-empty" v-if="introLoaded && !introSections.length">学习内容加载失败，请稍后重试</view>
		</scroll-view>
		<view class="cpl-foot">
			<view
				class="cpl-btn cpl-btn--ack"
				:class="{ 'cpl-btn--done': introAcknowledged }"
				hover-class="cpl-btn--press"
				@tap="ackIntro"
			>
				<text>{{ introAcknowledged ? '已完成学习' : '我已阅读以上内容' }}</text>
			</view>
			<view
				v-if="introAcknowledged && !examPassed"
				class="cpl-btn cpl-btn--primary"
				hover-class="cpl-btn--press"
				@tap="goExam"
			>
				<text>去答题</text>
			</view>
			<view
				v-if="introAcknowledged"
				class="cpl-btn cpl-btn--ghost"
				hover-class="cpl-btn--press"
				@tap="goBackAfterLearn"
			>
				<text>{{ examPassed ? '返回' : '稍后再答，先返回' }}</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				introSections: [],
				introLoaded: false,
				introAcknowledged: false,
			}
		},
		computed: {
			examPassed() {
				const u = this.userInfo || {}
				return Number(u.is_answer) === 1
			},
		},
		onShow() {
			if (!this.isLogin) {
				return this.$u.route('/pages/public/login')
			}
			this.refreshUser().then(() => {
				this.syncIntroAckFromUser()
				this.loadIntroSections()
			})
		},
		methods: {
			syncIntroAckFromUser() {
				const u = this.userInfo || {}
				this.introAcknowledged = Number(u.is_learn) === 1
			},
			loadIntroSections() {
				this.introLoaded = false
				return this.$u.api.get_clock_pre_learn().then(res => {
					const data = res.data || {}
					const list = data.sections || []
					this.introSections = list.map(sec => ({
						id: sec.id,
						name: sec.title || '',
						contentHtml: this.$u.func.formatRichText(sec.content || ''),
					}))
					if (Number(data.done) === 1) {
						this.introAcknowledged = true
					}
					this.introLoaded = true
				}).catch(() => {
					this.introLoaded = true
				})
			},
			ackIntro() {
				if (this.introAcknowledged) return
				this.$u.api.complete_clock_pre_learn().then(() => {
					this.introAcknowledged = true
					return this.refreshUser()
				}).then(() => {
					this.syncIntroAckFromUser()
					this.$u.toast('学习已完成')
				}).catch(err => {
					this.$u.toast((err && err.msg) || '提交失败，请重试')
				})
			},
			goExam() {
				uni.navigateTo({ url: '/pages/answer/examine?id=1' })
			},
			goBackAfterLearn() {
				const pages = getCurrentPages()
				if (pages.length > 1) {
					uni.navigateBack()
					return
				}
				uni.redirectTo({ url: '/pages/flhd/ljfldk' })
			},
			refreshUser() {
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
				}).catch(() => {})
			},
		},
	}
</script>

<style lang="scss" scoped>
.cpl-page {
	min-height: 100vh;
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	padding: 24rpx 28rpx;
	padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
	background: #e8f0ea;
}
.cpl-head {
	flex-shrink: 0;
	margin-bottom: 20rpx;
}
.cpl-title {
	display: block;
	font-size: 48rpx;
	font-weight: 800;
	color: #142018;
}
.cpl-sub {
	display: block;
	margin-top: 16rpx;
	font-size: 32rpx;
	line-height: 1.55;
	color: #5a665e;
}
.cpl-scroll {
	flex: 1;
	height: 0;
	min-height: 400rpx;
}
.cpl-card {
	margin-bottom: 24rpx;
	padding: 28rpx 26rpx;
	border-radius: 18rpx;
	background: #fff;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.06);
}
.cpl-card-title {
	display: block;
	font-size: 38rpx;
	font-weight: 800;
	color: #148a2e;
	margin-bottom: 16rpx;
}
.cpl-card-body {
	font-size: 34rpx;
	line-height: 1.75;
	color: #2a2a2a;
}
.cpl-card-body ::v-deep p {
	display: block;
	margin: 0 0 32rpx 0 !important;
	padding: 0 0 28rpx 0 !important;
	font-size: 34rpx !important;
	line-height: 1.75 !important;
	color: #2a2a2a;
	border-bottom: 1rpx solid #e8efe9;
}
.cpl-card-body ::v-deep p:last-child {
	margin-bottom: 0 !important;
	padding-bottom: 0 !important;
	border-bottom: none;
}
.cpl-card-body ::v-deep span,
.cpl-card-body ::v-deep div,
.cpl-card-body ::v-deep li {
	font-size: 34rpx !important;
	line-height: 1.75 !important;
	color: #2a2a2a;
}
.cpl-card-body ::v-deep strong {
	font-size: 34rpx !important;
	font-weight: 800;
	color: #148a2e;
}
.cpl-card-body ::v-deep p strong:first-child {
	display: inline-block;
	margin-bottom: 10rpx;
}
.cpl-empty {
	padding: 40rpx;
	text-align: center;
	font-size: 32rpx;
	color: #999;
}
.cpl-foot {
	flex-shrink: 0;
	margin-top: 16rpx;
}
.cpl-foot .cpl-btn + .cpl-btn {
	margin-top: 16rpx;
}
.cpl-btn {
	padding: 26rpx 22rpx;
	border-radius: 14rpx;
	text-align: center;
	font-size: 34rpx;
	font-weight: 700;
}
.cpl-btn--ack {
	color: #18af38;
	background: rgba(24, 175, 56, 0.1);
	border: 2rpx dashed rgba(24, 175, 56, 0.45);
}
.cpl-btn--done {
	background: rgba(24, 175, 56, 0.18);
	border-style: solid;
}
.cpl-btn--primary {
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
.cpl-btn--ghost {
	color: #5a665e;
	background: #fff;
	border: 2rpx solid #d8e0da;
}
.cpl-btn--press {
	opacity: 0.88;
}
</style>
