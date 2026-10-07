<template>
	<view class="cle-page" v-if="!setting.app_test">
		<scroll-view scroll-y class="cle-scroll" :show-scrollbar="false">
			<block v-if="!completed">
				<view v-for="(block, bi) in blocks" :key="bi">
					<!-- 头图 -->
					<view v-if="block.type === 'hero'" class="cle-hero">
						<image
							class="cle-hero-img"
							:src="preLearnImg.welcome"
							mode="widthFix"
						/>
					</view>

					<view v-else-if="block.type === 'why'" class="cle-card cle-card--soft cle-card--why">
						<text class="cle-card-title cle-card-title--center">为什么要学习</text>
						<text class="cle-card-body cle-card-body--center">学习帮助你识别四类垃圾、避开常见误区，并把正确分类落实到真实行动中</text>
					</view>

					<view v-else-if="block.type === 'steps'" class="cle-steps-section">
						<view class="cle-steps-img-wrap">
							<image
								class="cle-steps-img"
								:src="preLearnImg.steps"
								mode="widthFix"
							/>
						</view>
						<view class="cle-start-exam">
							<view class="cle-start-exam__line" aria-hidden="true"></view>
							<text class="cle-start-exam__pill">开始学习和考试</text>
							<view class="cle-start-exam__line" aria-hidden="true"></view>
						</view>
					</view>

					<view v-else-if="block.type === 'learn' && block.id === 'basic'" class="cle-learn-img-wrap">
						<image
							class="cle-learn-img"
							:src="preLearnImg.basicKnowledge"
							mode="widthFix"
						/>
					</view>

					<view v-else-if="block.type === 'learn' && block.id === 'zhenfen'" class="cle-learn-img-wrap">
						<image
							class="cle-learn-img"
							:src="preLearnImg.zhenfen"
							mode="widthFix"
						/>
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
						<text class="cle-quiz-q">{{ block.question }}</text>
						<view
							class="cle-opt-grid"
							:class="{ 'cle-opt-grid--row4': block.id === 'q1' || block.id === 'q2' }"
						>
							<view
								v-for="(opt, oi) in block.options"
								:key="oi"
								class="cle-opt"
								:class="quizOptClassByIndex(bi, oi)"
								hover-class="cle-opt--press"
								@tap="pickAnswerByIndex(bi, oi)"
							>
								<text class="cle-opt-text">{{ opt.key }}. {{ opt.text }}</text>
							</view>
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
	import settings from '@/common/settings.js'

	const PRE_LEARN_COS = settings.staticAssetBase + 'flhd/'

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
			preLearnImg() {
				return {
					welcome: PRE_LEARN_COS + 'pre_learn_welcome.png',
					steps: PRE_LEARN_COS + 'pre_learn_steps.png',
					basicKnowledge: PRE_LEARN_COS + 'pre_learn_basic_knowledge.png',
					zhenfen: PRE_LEARN_COS + 'pre_learn_zhenfen.png',
				}
			},
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
					this.goToSceneClockPick()
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
					if (Number(data.completed) === 1) {
						this.goToSceneClockPick()
						return
					}
					this.completed = false
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
					if (!this.canSubmit) this.$u.toast('请答完所有题目')
					return
				}
				this.submitting = true
				this.wrongIds = []
				this.$u.api.submit_clock_learn_exam({ answers: this.answers }).then(() => {
					return this.refreshUser()
				}).then(() => {
					this.$u.toast('学考完成')
					this.goToSceneClockPick()
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
			goToSceneClockPick() {
				const target = '/pages/activity/scene-clock-flow?mode=theme'
				const pages = getCurrentPages()
				let backDelta = 0
				for (let i = pages.length - 1; i >= 0; i--) {
					const route = (pages[i] && pages[i].route) || ''
					if (route.indexOf('scene-clock-flow') >= 0) {
						backDelta = pages.length - 1 - i
						break
					}
				}
				if (backDelta > 0) {
					uni.navigateBack({ delta: backDelta })
					return
				}
				uni.redirectTo({ url: target })
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
	overflow: hidden;
	border-radius: 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 48, 32, 0.08);
}
.cle-hero-img {
	display: block;
	width: 100%;
	vertical-align: top;
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
.cle-card--why {
	text-align: center;
}
.cle-card-title {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #18af38;
	margin-bottom: 12rpx;
}
.cle-card-title--center {
	text-align: center;
}
.cle-card-body {
	font-size: 30rpx;
	line-height: 1.65;
	color: #3a4540;
}
.cle-card-body--center {
	display: block;
	text-align: center;
	font-size: 28rpx;
	line-height: 1.55;
	color: #3a4540;
}
.cle-steps-section {
	margin-top: 24rpx;
}
.cle-steps-img-wrap {
	overflow: hidden;
	border-radius: 20rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 48, 32, 0.08);
}
.cle-steps-img {
	display: block;
	width: 100%;
	vertical-align: top;
}
.cle-start-exam {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-top: 28rpx;
	padding: 0 4rpx;
}
.cle-start-exam__line {
	flex: 1;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, #c5d0ca 12%, #c5d0ca 88%, transparent 100%);
}
.cle-start-exam__pill {
	flex-shrink: 0;
	margin: 0 20rpx;
	padding: 16rpx 40rpx;
	border-radius: 999rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.2;
	background: linear-gradient(180deg, #5fe06f 0%, #2ec84a 38%, #18af38 72%, #128f2e 100%);
	box-shadow:
		0 8rpx 20rpx rgba(24, 175, 56, 0.32),
		inset 0 2rpx 0 rgba(255, 255, 255, 0.35);
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
.cle-learn-img-wrap {
	margin-top: 24rpx;
	overflow: hidden;
	border-radius: 20rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 48, 32, 0.08);
}
.cle-learn-img {
	display: block;
	width: 100%;
	vertical-align: top;
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
.cle-quiz-q {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #142018;
	line-height: 1.5;
	margin-bottom: 16rpx;
}
.cle-opt-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	row-gap: 12rpx;
}
.cle-opt-grid--row4 {
	flex-wrap: nowrap;
	justify-content: flex-start;
	column-gap: 8rpx;
	row-gap: 0;
}
.cle-opt {
	width: calc(50% - 8rpx);
	box-sizing: border-box;
	min-height: 72rpx;
	padding: 10rpx 8rpx;
	border-radius: 16rpx;
	border: 2rpx solid #c8d4cc;
	display: flex;
	align-items: center;
	justify-content: center;
}
.cle-opt-grid--row4 .cle-opt {
	flex: 1;
	width: 0;
	min-width: 0;
	min-height: 64rpx;
	padding: 8rpx 4rpx;
	border-radius: 12rpx;
}
.cle-opt-text {
	font-size: 26rpx;
	line-height: 1.35;
	color: #3a4540;
	text-align: center;
}
.cle-opt-grid--row4 .cle-opt-text {
	font-size: 22rpx;
	line-height: 1.25;
}
.cle-opt--on .cle-opt-text {
	color: #18af38;
}
.cle-opt--wrong .cle-opt-text {
	color: #c0392b;
}
.cle-opt--on {
	border-color: #18af38;
	background: rgba(24, 175, 56, 0.08);
}
.cle-opt--wrong {
	border-color: #e05a4f;
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
