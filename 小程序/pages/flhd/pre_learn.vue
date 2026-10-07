<template>
	<view class="cle-page" v-if="!setting.app_test">
		<scroll-view scroll-y class="cle-scroll" :show-scrollbar="false">
			<!-- 已完成：老用户 / 已通过 -->
			<view v-if="completed" class="cle-done">
				<text class="cle-done-ico">✅</text>
				<text class="cle-done-title">您已完成学考</text>
				<text class="cle-done-sub">可以直接去真分打卡了</text>
				<view class="cle-done-btn" hover-class="cle-btn--press" @tap="goBackAfterLearn">返回继续</view>
			</view>

			<block v-else>
				<view v-for="(block, bi) in blocks" :key="bi">
					<!-- 头图 -->
					<view v-if="block.type === 'hero'" class="cle-hero">
						<view class="cle-hero-icon-wrap">
							<text class="cle-hero-icon">{{ block.icon || '🌍' }}</text>
						</view>
						<text class="cle-hero-title">{{ block.title }}</text>
						<text class="cle-hero-sub">{{ block.subtitle }}</text>
					</view>

					<view v-else-if="block.type === 'why'" class="cle-card cle-card--soft">
						<text class="cle-card-title">{{ block.title }}</text>
						<text class="cle-card-body">{{ block.body }}</text>
					</view>

					<view v-else-if="block.type === 'steps'" class="cle-steps-wrap">
						<text class="cle-steps-k">{{ block.title }}</text>
						<view class="cle-steps">
							<view class="cle-step" v-for="(st, si) in block.items" :key="'st' + si">
								<view class="cle-step-ico-wrap">
									<text class="cle-step-ico">{{ st.icon }}</text>
								</view>
								<text class="cle-step-lab">{{ st.label }}</text>
								<text v-if="si < block.items.length - 1" class="cle-step-arrow">›</text>
							</view>
						</view>
					</view>

					<view v-else-if="block.type === 'learn'" class="cle-learn">
						<view class="cle-learn-icon-wrap">
							<text class="cle-learn-icon">{{ block.icon || '📗' }}</text>
						</view>
						<view class="cle-learn-body">
							<text class="cle-learn-title">{{ block.title }}</text>
							<view class="cle-bullet" v-for="(bu, ui) in block.bullets" :key="'bu' + ui">
								<text class="cle-bullet-mark">{{ bu.icon }}</text>
								<view class="cle-bullet-text">
									<text class="cle-bullet-title">{{ bu.title }}</text>
									<text class="cle-bullet-desc">{{ bu.text }}</text>
								</view>
							</view>
						</view>
					</view>

					<view v-else-if="block.type === 'quiz'" class="cle-quiz">
						<text class="cle-quiz-tag">{{ block.tag || '练一练' }}</text>
						<text class="cle-quiz-q">{{ block.question }}</text>
						<view
							v-for="(opt, oi) in block.options"
							:key="oi"
							class="cle-opt"
							:class="quizOptClassByIndex(bi, oi)"
							hover-class="cle-opt--press"
							@tap="pickAnswerByIndex(bi, oi)"
						>
							<text>{{ opt.key }}. {{ opt.text }}</text>
						</view>
						<text v-if="quizWrongAt(bi)" class="cle-quiz-wrong">这题不对，请再选一次</text>
					</view>
				</view>
				<view class="cle-scroll-pad"></view>
			</block>
		</scroll-view>

		<view class="cle-foot" v-if="!completed">
			<view
				class="cle-foot-btn"
				:class="{ 'cle-foot-btn--disabled': !canSubmit }"
				hover-class="cle-btn--press"
				@tap="submitExam"
			>
				<text>{{ submitting ? '提交中…' : '提交答案，开始真分' }}</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				blocks: [],
				answers: {},
				wrongIds: [],
				completed: false,
				submitting: false,
				loaded: false,
			}
		},
		computed: {
			quizIds() {
				return (this.blocks || [])
					.filter(b => b && b.type === 'quiz' && b.id)
					.map(b => b.id)
			},
			canSubmit() {
				if (this.completed || this.submitting) return false
				const ids = this.quizIds
				if (!ids.length) return false
				return ids.every(id => !!String(this.answers[id] || '').trim())
			},
		},
		onShow() {
			if (!this.isLogin) {
				return this.$u.route('/pages/public/login')
			}
			this.refreshUser().then(() => {
				const u = this.userInfo || {}
				if (Number(u.is_learn) === 1 && Number(u.is_answer) === 1) {
					this.completed = true
					return
				}
				this.loadBlocks()
			})
		},
		methods: {
			loadBlocks() {
				return this.$u.api.get_clock_learn_exam().then(res => {
					const data = res.data || {}
					this.blocks = data.blocks || []
					this.completed = Number(data.completed) === 1
					this.loaded = true
				}).catch(() => {
					this.loaded = true
				})
			},
			pickAnswerByIndex(bi, oi) {
				if (this.completed) return
				const block = (this.blocks || [])[bi]
				if (!block || block.type !== 'quiz' || !block.id) return
				const opt = (block.options || [])[oi]
				if (!opt || !opt.key) return
				this.pickAnswer(block.id, opt.key)
			},
			pickAnswer(qid, key) {
				if (this.completed) return
				this.$set(this.answers, qid, key)
				const wi = this.wrongIds.indexOf(qid)
				if (wi >= 0) {
					this.wrongIds.splice(wi, 1)
				}
			},
			quizWrongAt(bi) {
				const block = (this.blocks || [])[bi]
				if (!block || !block.id) return false
				return this.wrongIds.indexOf(block.id) >= 0
			},
			quizOptClassByIndex(bi, oi) {
				const block = (this.blocks || [])[bi]
				if (!block || block.type !== 'quiz') return ''
				const opt = (block.options || [])[oi]
				if (!opt) return ''
				const qid = block.id
				const key = opt.key
				const picked = this.answers[qid]
				const wrong = this.wrongIds.indexOf(qid) >= 0
				const cls = []
				if (picked === key) cls.push('cle-opt--on')
				if (wrong && picked === key) cls.push('cle-opt--wrong')
				return cls.join(' ')
			},
			submitExam() {
				if (!this.canSubmit || this.submitting) {
					if (!this.canSubmit) this.$u.toast('请答完所有练一练题目')
					return
				}
				this.submitting = true
				this.wrongIds = []
				this.$u.api.submit_clock_learn_exam({ answers: this.answers }).then(() => {
					this.completed = true
					return this.refreshUser()
				}).then(() => {
					this.$u.toast('学考完成')
				}).catch(err => {
					const data = (err && err.data) || {}
					const wrong = data.wrong_ids || data.wrongIds || []
					if (Array.isArray(wrong) && wrong.length) {
						this.wrongIds = wrong
					}
					this.$u.toast((err && err.msg) || '提交失败')
				}).finally(() => {
					this.submitting = false
				})
			},
			goBackAfterLearn() {
				const pages = getCurrentPages()
				if (pages.length > 1) {
					uni.navigateBack()
					return
				}
				uni.redirectTo({ url: '/pages/activity/scene-clock-flow' })
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
.cle-page {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
	background: linear-gradient(180deg, #dff5e8 0%, #eef5f0 320rpx, #f4f7f5 100%);
}
.cle-scroll {
	flex: 1;
	height: 0;
	padding: 0 28rpx;
	box-sizing: border-box;
}
.cle-scroll-pad {
	height: calc(140rpx + env(safe-area-inset-bottom));
}
.cle-hero {
	margin-top: 16rpx;
	padding: 32rpx 24rpx 28rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 48, 32, 0.08);
	text-align: center;
}
.cle-hero-icon-wrap {
	width: 160rpx;
	height: 160rpx;
	margin: 0 auto 20rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #e8f8ec 0%, #c8ecd4 100%);
	display: flex;
	align-items: center;
	justify-content: center;
}
.cle-hero-icon {
	font-size: 88rpx;
	line-height: 1;
}
.cle-hero-title {
	display: block;
	font-size: 44rpx;
	font-weight: 800;
	color: #142018;
}
.cle-hero-sub {
	display: block;
	margin-top: 16rpx;
	font-size: 28rpx;
	line-height: 1.6;
	color: #5a6b62;
	text-align: left;
}
.cle-card {
	margin-top: 24rpx;
	padding: 28rpx 24rpx;
	border-radius: 20rpx;
	background: #fff;
}
.cle-card--soft {
	background: rgba(255, 255, 255, 0.92);
}
.cle-card-title {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #18af38;
	margin-bottom: 12rpx;
}
.cle-card-body {
	font-size: 30rpx;
	line-height: 1.65;
	color: #3a4540;
}
.cle-steps-wrap {
	margin-top: 24rpx;
	padding: 24rpx 16rpx;
	background: #fff;
	border-radius: 20rpx;
}
.cle-steps-k {
	display: block;
	text-align: center;
	font-size: 28rpx;
	font-weight: 700;
	color: #18af38;
	margin-bottom: 20rpx;
}
.cle-steps {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 8rpx 4rpx;
}
.cle-step {
	position: relative;
	width: 22%;
	min-width: 140rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.cle-step-ico-wrap {
	width: 72rpx;
	height: 72rpx;
	border-radius: 18rpx;
	background: #eef8f0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.cle-step-ico {
	font-size: 36rpx;
}
.cle-step-lab {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #4a564e;
	text-align: center;
	line-height: 1.3;
}
.cle-step-arrow {
	display: none;
}
.cle-learn {
	margin-top: 24rpx;
	padding: 24rpx;
	background: #fff;
	border-radius: 20rpx;
	display: flex;
	gap: 20rpx;
}
.cle-learn-icon-wrap {
	flex-shrink: 0;
	width: 100rpx;
	height: 100rpx;
	border-radius: 20rpx;
	background: #eef8f0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.cle-learn-icon {
	font-size: 52rpx;
}
.cle-learn-body {
	flex: 1;
	min-width: 0;
}
.cle-learn-title {
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #142018;
	margin-bottom: 16rpx;
}
.cle-bullet {
	display: flex;
	gap: 12rpx;
	margin-bottom: 14rpx;
}
.cle-bullet-mark {
	font-size: 28rpx;
	color: #18af38;
	font-weight: 700;
	line-height: 1.4;
}
.cle-bullet-title {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #243028;
}
.cle-bullet-desc {
	display: block;
	font-size: 26rpx;
	color: #5a6b62;
	line-height: 1.5;
	margin-top: 4rpx;
}
.cle-quiz {
	margin-top: 24rpx;
	padding: 28rpx 24rpx;
	background: #fff;
	border-radius: 20rpx;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.05);
}
.cle-quiz-tag {
	display: inline-block;
	padding: 6rpx 16rpx;
	border-radius: 999rpx;
	background: rgba(24, 175, 56, 0.12);
	color: #18af38;
	font-size: 24rpx;
	font-weight: 700;
	margin-bottom: 16rpx;
}
.cle-quiz-q {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #142018;
	line-height: 1.5;
	margin-bottom: 20rpx;
}
.cle-opt {
	padding: 22rpx 24rpx;
	margin-bottom: 16rpx;
	border-radius: 48rpx;
	border: 2rpx solid #c8d4cc;
	font-size: 28rpx;
	color: #3a4540;
	text-align: center;
}
.cle-opt--on {
	border-color: #18af38;
	color: #18af38;
	background: rgba(24, 175, 56, 0.08);
}
.cle-opt--wrong {
	border-color: #e05a4f;
	color: #c0392b;
	background: rgba(224, 90, 79, 0.08);
}
.cle-opt--press {
	opacity: 0.85;
}
.cle-quiz-wrong {
	display: block;
	font-size: 26rpx;
	color: #c0392b;
	margin-top: 4rpx;
}
.cle-foot {
	flex-shrink: 0;
	padding: 16rpx 28rpx calc(16rpx + env(safe-area-inset-bottom));
	background: rgba(244, 247, 245, 0.96);
	border-top: 1rpx solid #e0e8e3;
}
.cle-foot-btn {
	padding: 28rpx;
	border-radius: 16rpx;
	text-align: center;
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
.cle-foot-btn--disabled {
	opacity: 0.45;
}
.cle-btn--press {
	opacity: 0.88;
}
.cle-done {
	margin-top: 80rpx;
	padding: 48rpx 32rpx;
	text-align: center;
	background: #fff;
	border-radius: 24rpx;
}
.cle-done-ico {
	font-size: 80rpx;
}
.cle-done-title {
	display: block;
	margin-top: 20rpx;
	font-size: 40rpx;
	font-weight: 800;
	color: #142018;
}
.cle-done-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 28rpx;
	color: #6a756c;
}
.cle-done-btn {
	margin-top: 32rpx;
	padding: 24rpx;
	border-radius: 14rpx;
	color: #fff;
	font-size: 32rpx;
	font-weight: 700;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
</style>
