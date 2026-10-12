<template>
	<view class="intro-page">
		<view class="intro-head">
			<text class="intro-title">{{ pageTitle }}</text>
			<text class="intro-sub">{{ pageSubtitle }}</text>
		</view>

		<view class="intro-loading" v-if="loading">
			<u-loading mode="circle" color="#0F6E56" size="48"></u-loading>
			<text class="intro-loading-txt">加载中</text>
		</view>

		<block v-else>
			<view class="intro-video-wrap">
				<video
					v-if="videoUrl"
					class="intro-video"
					:src="videoUrl"
					controls
					show-center-play-btn
					object-fit="contain"
					:poster="posterUrl"
				></video>
				<view class="intro-video-ph" v-else>
					<text>视频加载中，请稍后下拉刷新</text>
				</view>
			</view>

			<view class="intro-progress" v-if="moduleCount">
				<text class="intro-progress-label">{{ passed ? '已完成认识' : '完成小互动即可继续探索七贤岭' }}</text>
				<view class="intro-progress-track">
					<view class="intro-progress-fill" :style="{ width: progressWidth }"></view>
				</view>
			</view>

			<view class="intro-done" v-if="passed">
				<text class="intro-done-icon">✓</text>
				<text class="intro-done-txt">您已完成「认识七贤岭」，可随时回看视频</text>
			</view>

			<view class="intro-quiz" v-else-if="questions.length">
				<text class="intro-quiz-head">看完视频，点一点下面 3 个小问题</text>
				<view class="intro-q" v-for="(q, qIdx) in questions" :key="q.id">
					<text class="intro-q-title">{{ qIdx + 1 }}. {{ q.title }}</text>
					<view
						class="intro-opt"
						v-for="(opt, optIdx) in questionOptions(q)"
						:key="optIdx"
						:class="optionClass(qIdx, optIdx)"
						@tap="onSelectOpt(qIdx, optIdx)"
					>
						<text class="intro-opt-key">{{ opt.key }}</text>
						<text class="intro-opt-text">{{ opt.text }}</text>
					</view>
				</view>
				<view class="intro-fail" v-if="failHint">
					<text>{{ failHint }}</text>
				</view>
				<view class="intro-submit" @tap="onSubmit">
					<text>{{ submitting ? '提交中...' : '提交' }}</text>
				</view>
			</view>
		</block>
	</view>
</template>

<script>
import settings from '@/common/settings.js'

const INTRO_CATEGORY = '认识七贤岭'
const POSTER = settings.staticAssetBase + 'img/qxl-learn-banner.png'

export default {
	data() {
		return {
			loading: true,
			pageTitle: '认识七贤岭',
			pageSubtitle: '看视频，用几道小互动快速了解本地人文与地理',
			videoUrl: '',
			posterUrl: POSTER,
			passed: false,
			moduleCount: 1,
			passedCount: 0,
			questions: [],
			answers: {},
			failHint: '',
			submitting: false,
		}
	},
	computed: {
		progressWidth() {
			if (this.passed) return '100%'
			return '20%'
		},
	},
	onShow() {
		this.loadFeed()
	},
	methods: {
		resolveUrl(raw) {
			const s = (raw || '').trim()
			if (!s) return ''
			if (typeof this.$u !== 'undefined' && this.$u.func && typeof this.$u.func.resolveAssetUrl === 'function') {
				return this.$u.func.resolveAssetUrl(s)
			}
			return s
		},
		questionOptions(q) {
			const keys = ['A', 'B', 'C', 'D']
			const fields = ['option_a', 'option_b', 'option_c', 'option_d']
			const list = []
			fields.forEach((f, i) => {
				const text = (q[f] || '').trim()
				if (text) list.push({ key: keys[i], text })
			})
			return list
		},
		optionClass(qIdx, optIdx) {
			const sel = this.answers[qIdx]
			if (sel === undefined) return ''
			return sel === optIdx ? 'is-selected' : ''
		},
		onSelectOpt(qIdx, optIdx) {
			if (this.passed) return
			this.$set(this.answers, qIdx, optIdx)
			this.failHint = ''
		},
		loadFeed() {
			this.loading = true
			this.$u.api.getQxlLearnFeed().then(res => {
				if (res.code != 1) return
				const data = res.data || {}
				this.pageTitle = data.title || this.pageTitle
				this.pageSubtitle = data.subtitle || this.pageSubtitle
				this.videoUrl = this.resolveUrl(data.video_url || '')
				this.passed = !!(data.passed || data.all_passed)
				this.passedCount = data.passed_count || (this.passed ? 1 : 0)
				this.moduleCount = data.module_count || 1
				this.questions = data.questions || []
				this.answers = {}
				this.failHint = ''
			}).finally(() => {
				this.loading = false
			})
		},
		buildAnswerPayload() {
			const keys = ['A', 'B', 'C', 'D']
			const payload = []
			for (let i = 0; i < this.questions.length; i++) {
				const q = this.questions[i]
				const optIdx = this.answers[i]
				if (optIdx === undefined) return null
				payload.push({
					qid: q.id,
					answer: keys[optIdx] || '',
				})
			}
			return payload
		},
		onSubmit() {
			if (this.submitting || this.passed) return
			const answers = this.buildAnswerPayload()
			if (!answers || answers.length < this.questions.length) {
				this.$u.toast('请先答完全部题目')
				return
			}
			this.submitting = true
			this.failHint = ''
			this.$u.api.submitQxlModuleQuiz({
				category: INTRO_CATEGORY,
				answers,
			}).then(res => {
				if (res.code != 1) {
					this.$u.toast(res.msg || '提交失败')
					return
				}
				const d = res.data || {}
				if (d.passed) {
					this.passed = true
					this.passedCount = 1
					this.questions = []
					if (d.point > 0) {
						this.$u.toast('完成啦，获得' + d.point + '积分')
					} else {
						this.$u.toast('认识七贤岭已完成')
					}
				} else {
					this.failHint = res.msg || '还有题目没选对，请结合视频再试'
					this.answers = {}
				}
			}).catch(() => {
				this.$u.toast('提交失败，请稍后再试')
			}).finally(() => {
				this.submitting = false
			})
		},
	},
}
</script>

<style lang="scss" scoped>
$green: #0f6e56;
$green-mid: #1fa971;

.intro-page {
	min-height: 100vh;
	background: #eef3f0;
	padding: 24rpx 24rpx 80rpx;
	box-sizing: border-box;
}

.intro-head {
	padding: 8rpx 8rpx 24rpx;
}
.intro-title {
	display: block;
	font-size: 40rpx;
	font-weight: 800;
	color: #1a2e28;
	line-height: 1.35;
}
.intro-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #5a6b63;
	line-height: 1.55;
}

.intro-loading {
	padding: 120rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 24rpx;
}
.intro-loading-txt {
	font-size: 26rpx;
	color: #5a6b63;
}

.intro-video-wrap {
	background: #000;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 28rpx rgba(15, 110, 86, 0.12);
}
.intro-video {
	width: 100%;
	height: 420rpx;
	display: block;
}
.intro-video-ph {
	height: 420rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 32rpx;
	text-align: center;
	font-size: 26rpx;
	color: #ccc;
}

.intro-progress {
	margin-top: 28rpx;
	padding: 24rpx;
	background: #fff;
	border-radius: 16rpx;
	border: 1rpx solid #c5e8d8;
}
.intro-progress-label {
	font-size: 24rpx;
	color: #5a6b63;
	display: block;
	margin-bottom: 16rpx;
}
.intro-progress-track {
	height: 12rpx;
	border-radius: 12rpx;
	background: rgba(15, 110, 86, 0.12);
	overflow: hidden;
}
.intro-progress-fill {
	height: 100%;
	border-radius: 12rpx;
	background: linear-gradient(90deg, $green, $green-mid);
	transition: width 0.3s ease;
}

.intro-done {
	margin-top: 28rpx;
	padding: 32rpx 28rpx;
	background: #e8f5ef;
	border-radius: 16rpx;
	border: 1rpx solid #c5e8d8;
	display: flex;
	flex-direction: row;
	align-items: flex-start;
	gap: 16rpx;
}
.intro-done-icon {
	font-size: 36rpx;
	color: $green;
	font-weight: 700;
}
.intro-done-txt {
	flex: 1;
	font-size: 28rpx;
	color: #1a2e28;
	line-height: 1.5;
}

.intro-quiz {
	margin-top: 28rpx;
	padding: 28rpx 24rpx 32rpx;
	background: #fff;
	border-radius: 20rpx;
	border: 1rpx solid #e4e6eb;
}
.intro-quiz-head {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #1a2e28;
	margin-bottom: 24rpx;
}

.intro-q {
	margin-bottom: 32rpx;
}
.intro-q-title {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #1a2e28;
	line-height: 1.45;
	margin-bottom: 16rpx;
}

.intro-opt {
	display: flex;
	flex-direction: row;
	align-items: flex-start;
	gap: 16rpx;
	padding: 22rpx 20rpx;
	margin-bottom: 12rpx;
	border-radius: 14rpx;
	border: 2rpx solid #e8ece9;
	background: #fafbfa;
}
.intro-opt.is-selected {
	border-color: $green;
	background: #e8f5ef;
}
.intro-opt-key {
	width: 44rpx;
	height: 44rpx;
	border-radius: 50%;
	background: #fff;
	border: 2rpx solid #c5e8d8;
	font-size: 24rpx;
	font-weight: 700;
	color: $green;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.intro-opt.is-selected .intro-opt-key {
	background: $green;
	color: #fff;
	border-color: $green;
}
.intro-opt-text {
	flex: 1;
	font-size: 26rpx;
	color: #3d524a;
	line-height: 1.5;
	padding-top: 4rpx;
}

.intro-fail {
	margin-bottom: 16rpx;
	padding: 16rpx 20rpx;
	background: #fff5f5;
	border-radius: 12rpx;
	font-size: 24rpx;
	color: #c0392b;
	line-height: 1.45;
}

.intro-submit {
	margin-top: 8rpx;
	height: 88rpx;
	border-radius: 44rpx;
	background: linear-gradient(90deg, $green, $green-mid);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 30rpx;
	font-weight: 700;
	box-shadow: 0 8rpx 20rpx rgba(15, 110, 86, 0.22);
}
.intro-submit:active {
	opacity: 0.92;
}
</style>
