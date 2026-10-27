<template>
	<view class="has-page" :class="{ 'has-page--large': largeTextEnabled }">
		<view class="has-top">
			<view class="has-top-back" @tap="onTopBack">
				<u-icon name="arrow-left" size="36" color="#1565D8"></u-icon>
			</view>
			<view class="has-top-copy">
				<text class="has-top-title">健康问答</text>
				<text class="has-top-sub" v-if="step !== 'done'">{{ qaProgressText }}</text>
			</view>
			<view class="has-top-accent"></view>
		</view>
		<view class="has-progress" v-if="step === 'service' || step === 'concerns'">
			<view class="has-progress-fill" :style="{ width: qaProgressPercent + '%' }"></view>
		</view>

		<view class="has-body">
		<!-- 问答：服务体验 -->
		<view v-if="step === 'service'" class="has-panel">
			<view class="has-topbar">
				<view class="has-back" @tap="onServiceBack">
					<u-icon name="arrow-left" size="36" color="#1565D8"></u-icon>
				</view>
				<text class="has-step-label">第 {{ serviceIndex + 1 }} 题</text>
			</view>

			<text class="has-voice-tip" v-if="inputMode === 'voice' && !voiceFallback">点击下方按钮说话，或直接点选答案</text>

			<text class="has-question">{{ currentServiceQuestion.text }}</text>

			<view v-if="voiceConfirmPending" class="has-voice-confirm">
				<text class="has-voice-confirm-q">您选的是：{{ pendingLabel }}，对吗？</text>
				<button class="has-btn has-btn--primary" @tap="confirmVoiceChoice">对，下一题</button>
				<button class="has-btn has-btn--ghost" @tap="voiceConfirmPending = false">重新选择</button>
			</view>

			<view v-else class="has-options">
				<view
					v-for="opt in currentServiceQuestion.options"
					:key="opt.value"
					class="has-opt"
					:class="[
						'has-opt--' + opt.tone,
						{ 'has-opt--active': answers.service[currentServiceQuestion.key] === opt.value }
					]"
					hover-class="has-opt--press"
					@tap="pickService(opt)"
				>{{ opt.label }}</view>
				<view
					v-if="currentServiceQuestion.allowNa"
					class="has-opt has-opt--na"
					:class="{ 'has-opt--active': answers.service[currentServiceQuestion.key] === 'na' }"
					@tap="pickService({ value: 'na', label: '不适用' })"
				>不适用</view>
			</view>

			<view class="has-toolbar">
				<view class="has-tool" @tap="readQuestionAloud">
					<u-icon name="volume-fill" size="32" color="#1565D8"></u-icon>
					<text>朗读此题</text>
				</view>
				<view class="has-tool has-tool--toggle">
					<text class="has-tool-label">大字</text>
					<switch :checked="largeTextEnabled" color="#1565D8" @change="onLargeTextChange" />
				</view>
			</view>

			<view class="has-voice-bar">
				<view class="has-mode-switch" @tap="toggleInputMode">
					<u-icon :name="inputMode === 'voice' ? 'mic' : 'edit-pen'" size="28" color="#5a6b66"></u-icon>
					<text>{{ inputMode === 'voice' ? '切换为文字点选' : '切换为语音辅助' }}</text>
				</view>
				<view
					v-if="inputMode === 'voice'"
					class="has-mic-btn"
					:class="{ 'has-mic-btn--active': recording }"
					@touchstart.prevent="onMicStart"
					@touchend.prevent="onMicEnd"
					@touchcancel.prevent="onMicEnd"
				>
					<u-icon name="mic" size="44" color="#fff"></u-icon>
					<text>{{ recording ? '松开结束' : '按住说话' }}</text>
				</view>
			</view>
		</view>

		<!-- 问答：健康关注 -->
		<view v-else-if="step === 'concerns'" class="has-panel">
			<text class="has-step-label has-step-label--solo">您当前关注的健康指标</text>
			<text class="has-subhint">最多选 2 项，用于生成个性化指导参考（非诊断）</text>
			<view class="has-concern-grid">
				<view
					v-for="item in concernOptions"
					:key="item.key"
					class="has-concern"
					:class="{ 'has-concern--on': isConcernSelected(item.key) }"
					hover-class="has-concern--press"
					@tap="toggleConcern(item.key)"
				>
					<view
						class="has-concern-icon-wrap"
						v-if="item.uIcon"
						:style="{ background: item.iconBg || '#f1f5f9' }"
					>
						<u-icon :name="item.uIcon" :size="44" :color="item.iconColor"></u-icon>
					</view>
					<text v-else class="has-concern-icon">{{ item.icon }}</text>
					<text class="has-concern-name">{{ item.name }}</text>
					<view v-if="isConcernSelected(item.key)" class="has-concern-check">
						<u-icon name="checkmark" size="22" color="#fff"></u-icon>
					</view>
				</view>
			</view>
			<text class="has-note">※ 仅用于了解关注方向，不作医疗诊断</text>
			<view class="has-footer-actions">
				<button class="has-btn has-btn--ghost has-btn--half" @tap="skipConcerns">跳过</button>
				<button class="has-btn has-btn--primary has-btn--half" :loading="submitting" @tap="finishSurvey(false)">提交并完成</button>
			</view>
		</view>

		<!-- 结束页 -->
		<view v-else-if="step === 'done'" class="has-panel has-done">
			<view class="has-done-badge">
				<u-icon name="checkmark-circle-fill" size="88" color="#1565D8"></u-icon>
			</view>
			<text class="has-done-title">问答已完成</text>
			<text class="has-done-desc">我们已记录您的服务体验与关注方向，您可在健康首页查看后续服务入口。本结果仅供健康管理参考，不能替代临床诊断。</text>
			<view class="has-done-summary" v-if="summaryServiceText || summaryConcernText">
				<view class="has-done-row">
					<text class="has-done-k">服务体验</text>
					<text class="has-done-v">{{ summaryServiceText }}</text>
				</view>
				<view class="has-done-row">
					<text class="has-done-k">关注指标</text>
					<text class="has-done-v">{{ summaryConcernText }}</text>
				</view>
			</view>
			<button class="has-btn has-btn--primary" @tap="goBack">返回健康首页</button>
			<button class="has-btn has-btn--ghost" @tap="openGrassroots">查看基层医疗机构</button>
		</view>
		</view>
	</view>
</template>

<script>
const SERVICE_QUESTIONS = [
	{
		key: 'overall',
		text: '您最近一次在社区或医院接受服务，整体满意吗？',
		allowNa: true,
		options: [
			{ value: '1', label: '非常不满意', tone: 'light' },
			{ value: '2', label: '不满意', tone: 'light-mid' },
			{ value: '3', label: '一般', tone: 'mid' },
			{ value: '4', label: '满意', tone: 'good' },
			{ value: '5', label: '非常满意', tone: 'best' }
		]
	},
	{
		key: 'wait_time',
		text: '等候与预约是否顺畅？',
		allowNa: true,
		options: [
			{ value: '1', label: '非常不顺畅', tone: 'light' },
			{ value: '2', label: '不太顺畅', tone: 'light-mid' },
			{ value: '3', label: '一般', tone: 'mid' },
			{ value: '4', label: '比较顺畅', tone: 'good' },
			{ value: '5', label: '非常顺畅', tone: 'best' }
		]
	}
]

const CONCERN_OPTIONS = [
	{ key: 'bp', name: '血压', uIcon: 'heart-fill', iconColor: '#e53935', iconBg: '#ffebee' },
	{ key: 'sugar', name: '血糖', uIcon: 'tags-fill', iconColor: '#1565d8', iconBg: '#e3f2fd' },
	{ key: 'sleep', name: '睡眠', uIcon: 'star-fill', iconColor: '#3949ab', iconBg: '#e8eaf6' },
	{ key: 'sport', name: '运动耐力', uIcon: 'man-add-fill', iconColor: '#1565d8', iconBg: '#e3f2fd' },
	{ key: 'diet', name: '体重饮食', uIcon: 'grid-fill', iconColor: '#c62828', iconBg: '#ffebee' },
	{ key: 'none', name: '暂无特别关注', uIcon: 'more-dot-fill', iconColor: '#64748b', iconBg: '#f1f5f9' }
]

export default {
	data() {
		return {
			surveyId: 0,
			step: 'service',
			serviceQuestions: SERVICE_QUESTIONS,
			serviceIndex: 0,
			concernOptions: CONCERN_OPTIONS,
			answers: {
				service: {},
				concerns: [],
				contact_ok: false
			},
			largeTextEnabled: true,
			inputMode: 'text',
			voiceFallback: false,
			recording: false,
			voiceConfirmPending: false,
			pendingLabel: '',
			pendingValue: '',
			submitting: false
		}
	},
	computed: {
		currentServiceQuestion() {
			return this.serviceQuestions[this.serviceIndex] || SERVICE_QUESTIONS[0]
		},
		qaProgressText() {
			if (this.step === 'service') {
				return `服务体验 · 第 ${this.serviceIndex + 1}/${this.serviceQuestions.length} 题`
			}
			if (this.step === 'concerns') {
				return '关注指标 · 请选择'
			}
			return ''
		},
		qaProgressPercent() {
			const total = this.serviceQuestions.length + 1
			if (this.step === 'service') {
				return Math.round(((this.serviceIndex + 1) / total) * 100)
			}
			if (this.step === 'concerns') {
				return Math.round((this.serviceQuestions.length / total) * 100) + Math.round(100 / total)
			}
			return 0
		},
		summaryServiceText() {
			const parts = []
			this.serviceQuestions.forEach((q) => {
				const v = this.answers.service[q.key]
				if (!v) return
				const opt = q.options.find((o) => o.value === v)
				if (v === 'na') {
					parts.push(q.key === 'overall' ? '整体：不适用' : '等候：不适用')
				} else if (opt) {
					parts.push(opt.label)
				}
			})
			return parts.length ? parts.join(' · ') : '未填写'
		},
		summaryConcernText() {
			if (!this.answers.concerns.length) return '未选择'
			if (this.answers.concerns.includes('none')) return '暂无特别关心'
			return this.concernOptions
				.filter((c) => this.answers.concerns.includes(c.key))
				.map((c) => c.name)
				.join('、')
		}
	},
	onLoad(option) {
		this.surveyId = parseInt(option.id, 10) || 0
	},
	methods: {
		onTopBack() {
			if (this.step === 'done') {
				return this.goBack()
			}
			if (this.step === 'concerns') {
				this.step = 'service'
				this.serviceIndex = this.serviceQuestions.length - 1
				return
			}
			if (this.step === 'service') {
				return this.onServiceBack()
			}
			this.goBack()
		},
		openGrassroots() {
			this.$u.route('/pages/healthcare/participate?id=6')
		},
		pickService(opt) {
			const key = this.currentServiceQuestion.key
			this.$set(this.answers.service, key, opt.value)
			this.advanceService()
		},
		advanceService() {
			if (this.serviceIndex < this.serviceQuestions.length - 1) {
				this.serviceIndex++
				this.voiceConfirmPending = false
				return
			}
			this.step = 'concerns'
		},
		onServiceBack() {
			if (this.serviceIndex > 0) {
				this.serviceIndex--
				this.voiceConfirmPending = false
				return
			}
			this.goBack()
		},
		toggleInputMode() {
			this.inputMode = this.inputMode === 'voice' ? 'text' : 'voice'
			this.voiceConfirmPending = false
		},
		onLargeTextChange(e) {
			this.largeTextEnabled = !!(e.detail && e.detail.value)
		},
		readQuestionAloud() {
			const text = this.currentServiceQuestion.text
			if (!text) return
			// #ifdef MP-WEIXIN
			uni.showToast({ title: '请阅读题目后点选答案', icon: 'none' })
			// #endif
		},
		onMicStart() {
			this.recording = true
		},
		onMicEnd() {
			this.recording = false
			this.voiceFallback = true
			uni.showToast({
				title: '请点选下方答案',
				icon: 'none',
				duration: 2000
			})
		},
		confirmVoiceChoice() {
			if (this.pendingValue) {
				this.$set(this.answers.service, this.currentServiceQuestion.key, this.pendingValue)
			}
			this.voiceConfirmPending = false
			this.advanceService()
		},
		isConcernSelected(key) {
			return this.answers.concerns.includes(key)
		},
		toggleConcern(key) {
			let list = [...this.answers.concerns]
			if (key === 'none') {
				this.answers.concerns = list.includes('none') ? [] : ['none']
				return
			}
			list = list.filter((k) => k !== 'none')
			const idx = list.indexOf(key)
			if (idx >= 0) {
				list.splice(idx, 1)
			} else if (list.length < 2) {
				list.push(key)
			} else {
				return this.$u.toast('最多选择 2 项')
			}
			this.answers.concerns = list
		},
		skipConcerns() {
			this.answers.concerns = []
			this.finishSurvey(false)
		},
		buildSubmitAnswers() {
			return {
				service_overall: this.answers.service.overall || '',
				service_wait_time: this.answers.service.wait_time || '',
				health_concerns: (this.answers.concerns || []).join(','),
				contact_ok: this.answers.contact_ok ? '1' : '0',
				flow: 'health_advisor_v1'
			}
		},
		submitDraft(showToast) {
			if (this.surveyId <= 0) return Promise.resolve()
			if (!this.isLogin) return Promise.resolve()
			const answers = this.buildSubmitAnswers()
			return this.$u.api
				.health_survey_submit({
					survey_id: this.surveyId,
					answers
				})
				.then(() => {
					if (showToast) this.$u.toast('已保存')
				})
				.catch((err) => {
					console.log('health_survey_submit', err)
				})
		},
		finishSurvey(withContact) {
			if (this.submitting) return
			this.submitting = true
			if (withContact) {
				this.answers.contact_ok = true
			}
			const done = () => {
				this.submitting = false
				this.step = 'done'
				try {
					uni.setStorageSync('health_advisor_completed_at', Date.now())
				} catch (e) {}
			}
			if (this.surveyId > 0 && this.isLogin) {
				this.submitDraft(false).finally(done)
			} else {
				done()
			}
		},
		goBack() {
			const pages = getCurrentPages()
			if (pages.length > 1) {
				uni.navigateBack()
			} else {
				uni.switchTab({ url: '/pages/health/health' })
			}
		}
	}
}
</script>

<style lang="scss">
page {
	background: #e8f1ff;
}
.has-page {
	position: relative;
	min-height: 100vh;
	padding: 0 0 calc(48rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
	background: linear-gradient(180deg, #e8f1ff 0%, #ffffff 42%, #f7faff 100%);
	overflow: hidden;
}
.has-top {
	position: relative;
	display: flex;
	align-items: center;
	padding: calc(env(safe-area-inset-top) + 12rpx) 28rpx 16rpx;
	gap: 12rpx;
}
.has-top-back {
	padding: 8rpx 8rpx 8rpx 0;
}
.has-top-copy {
	flex: 1;
	min-width: 0;
}
.has-top-title {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #0d47a1;
}
.has-top-sub {
	display: block;
	margin-top: 4rpx;
	font-size: 22rpx;
	color: #64748b;
}
.has-top-accent {
	width: 8rpx;
	height: 48rpx;
	border-radius: 4rpx;
	background: linear-gradient(180deg, #e53935, #ff5252);
	flex-shrink: 0;
}
.has-progress {
	height: 8rpx;
	margin: 0 28rpx 8rpx;
	border-radius: 999rpx;
	background: #dbeafe;
	overflow: hidden;
}
.has-progress-fill {
	height: 100%;
	border-radius: 999rpx;
	background: linear-gradient(90deg, #1565d8 0%, #0d47a1 72%, #e53935 100%);
	transition: width 0.25s ease;
}
.has-body {
	position: relative;
	z-index: 1;
	padding: 8rpx 32rpx 0;
}
.has-panel {
	animation: hasFade 0.25s ease;
}
@keyframes hasFade {
	from {
		opacity: 0;
		transform: translateY(12rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
.has-welcome {
	padding-top: 8rpx;
	text-align: center;
}
.has-voice-tip {
	display: block;
	font-size: 24rpx;
	color: #8a958c;
	text-align: center;
	margin-bottom: 16rpx;
}
.has-welcome-title {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #1e4a7a;
	line-height: 1.45;
}
.has-welcome-desc {
	display: block;
	margin-top: 20rpx;
	font-size: 26rpx;
	color: #64748b;
	line-height: 1.6;
	text-align: left;
}
.has-agree {
	display: flex;
	align-items: center;
	margin: 40rpx 0 28rpx;
}
.has-agree--compact {
	margin: 32rpx 0;
}
.has-agree-box {
	width: 36rpx;
	height: 36rpx;
	border-radius: 8rpx;
	border: 2rpx solid #c5d4cf;
	margin-right: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.has-agree-box--on {
	background: #1565d8;
	border-color: #1565d8;
}
.has-agree-text {
	font-size: 26rpx;
	color: #3d5245;
	text-align: left;
}
.has-btn {
	width: 100%;
	height: 88rpx;
	line-height: 88rpx;
	border-radius: 999rpx;
	font-size: 30rpx;
	font-weight: 600;
	margin-bottom: 20rpx;
	border: none;
}
.has-btn::after {
	border: none;
}
.has-btn--primary {
	background: linear-gradient(90deg, #1565d8 0%, #0d47a1 100%);
	color: #fff;
	box-shadow: 0 12rpx 28rpx rgba(21, 101, 216, 0.35);
	border-top: 4rpx solid rgba(229, 57, 53, 0.85);
}
.has-btn--primary[disabled] {
	background: #b8d4f0;
	box-shadow: none;
	color: rgba(255, 255, 255, 0.9);
}
.has-btn--ghost {
	background: rgba(255, 255, 255, 0.92);
	color: #64748b;
	border: 2rpx solid #d6e8ff;
}
.has-btn--outline {
	background: #fff;
	color: #4A90E2;
	border: 2rpx solid #4A90E2;
	height: 72rpx;
	line-height: 72rpx;
	font-size: 26rpx;
	margin-top: 16rpx;
}
.has-btn--half {
	width: 48%;
	display: inline-block;
	margin-bottom: 0;
}
.has-topbar {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
}
.has-back {
	padding: 8rpx 16rpx 8rpx 0;
}
.has-step-label {
	font-size: 28rpx;
	font-weight: 600;
	color: #1565d8;
}
.has-step-label--solo {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #0d47a1;
	margin-bottom: 8rpx;
}
.has-subhint {
	display: block;
	font-size: 24rpx;
	color: #8a958c;
	margin-bottom: 28rpx;
	line-height: 1.5;
}
.has-question {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #0d47a1;
	line-height: 1.45;
	margin-bottom: 28rpx;
}
.has-page--large .has-question {
	font-size: 40rpx;
}
.has-page--large .has-opt {
	font-size: 32rpx;
	min-height: 96rpx;
}
.has-options {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.has-opt {
	padding: 24rpx 28rpx;
	border-radius: 16rpx;
	font-size: 28rpx;
	font-weight: 600;
	text-align: center;
	background: #f0f7ff;
	color: #334155;
	border: 2rpx solid transparent;
}
.has-opt--light {
	background: #f5faff;
}
.has-opt--light-mid {
	background: #e8f2ff;
}
.has-opt--mid {
	background: #cce5ff;
}
.has-opt--good {
	background: #7eb8ff;
	color: #fff;
}
.has-opt--best {
	background: linear-gradient(90deg, #1565d8 0%, #0d47a1 100%);
	color: #fff;
}
.has-opt--na {
	background: #fff;
	border-color: #d6e8ff;
	font-weight: 500;
}
.has-opt--active {
	box-shadow: 0 0 0 4rpx rgba(21, 101, 216, 0.35);
	border-color: #e53935;
}
.has-opt--press {
	opacity: 0.9;
	transform: scale(0.99);
}
.has-toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 36rpx;
	padding-top: 24rpx;
	border-top: 1rpx solid #e3efff;
}
.has-tool {
	display: flex;
	align-items: center;
	gap: 8rpx;
	font-size: 24rpx;
	color: #1565d8;
}
.has-tool--toggle {
	color: #5a6b66;
}
.has-tool-label {
	margin-right: 8rpx;
}
.has-voice-bar {
	margin-top: 32rpx;
}
.has-mode-switch {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
	padding: 16rpx;
	font-size: 24rpx;
	color: #5a6b66;
}
.has-mic-btn {
	margin-top: 16rpx;
	height: 160rpx;
	border-radius: 24rpx;
	background: linear-gradient(145deg, #1565d8, #0d47a1);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 26rpx;
	gap: 12rpx;
}
.has-mic-btn--active {
	opacity: 0.88;
	transform: scale(0.98);
}
.has-voice-confirm {
	padding: 24rpx;
	background: #fff;
	border-radius: 20rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(74, 144, 226, 0.12);
}
.has-voice-confirm-q {
	display: block;
	font-size: 30rpx;
	font-weight: 600;
	color: #1a2e28;
	margin-bottom: 20rpx;
}
.has-concern-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20rpx;
}
.has-concern {
	position: relative;
	padding: 28rpx 20rpx;
	background: #fff;
	border-radius: 20rpx;
	border: 2rpx solid #e3efff;
	display: flex;
	flex-direction: column;
	align-items: center;
	min-height: 160rpx;
}
.has-concern--on {
	border-color: #1565d8;
	background: #e3f2fd;
}
.has-concern-icon-wrap {
	width: 88rpx;
	height: 88rpx;
	border-radius: 22rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.has-concern-icon {
	font-size: 48rpx;
}
.has-concern-name {
	margin-top: 12rpx;
	font-size: 26rpx;
	font-weight: 600;
	color: #1a2e28;
}
.has-concern-check {
	position: absolute;
	top: 12rpx;
	right: 12rpx;
	width: 36rpx;
	height: 36rpx;
	border-radius: 50%;
	background: #e53935;
	display: flex;
	align-items: center;
	justify-content: center;
}
.has-note {
	display: block;
	margin-top: 24rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.has-footer-actions {
	display: flex;
	justify-content: space-between;
	margin-top: 40rpx;
}
.has-summary-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 8rpx 0;
	margin-bottom: 24rpx;
}
.has-summary-row {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	padding: 24rpx 28rpx;
	border-bottom: 1rpx solid #f0f4f2;
}
.has-summary-row:last-child {
	border-bottom: none;
}
.has-summary-k {
	font-size: 26rpx;
	color: #8a958c;
	flex-shrink: 0;
	margin-right: 24rpx;
}
.has-summary-v {
	font-size: 28rpx;
	font-weight: 600;
	color: #1a2e28;
	text-align: right;
	flex: 1;
}
.has-summary-v--ok {
	color: #1565d8;
}
.has-disclaimer {
	display: flex;
	align-items: flex-start;
	gap: 12rpx;
	padding: 20rpx;
	background: #f0f7ff;
	border-radius: 16rpx;
	font-size: 22rpx;
	color: #5a6b66;
	line-height: 1.5;
	margin-bottom: 32rpx;
}
.has-rec-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
}
.has-rec-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e28;
}
.has-rec-desc {
	display: block;
	margin-top: 10rpx;
	font-size: 24rpx;
	color: #5a6b66;
	line-height: 1.5;
}
.has-done {
	text-align: center;
	padding-top: 48rpx;
}
.has-done-badge {
	display: flex;
	justify-content: center;
	margin-bottom: 8rpx;
}
.has-done-summary {
	margin: 32rpx 0;
	padding: 24rpx;
	border-radius: 20rpx;
	background: #fff;
	border: 1rpx solid #e3edff;
	text-align: left;
}
.has-done-row {
	display: flex;
	justify-content: space-between;
	gap: 16rpx;
	padding: 12rpx 0;
	border-bottom: 1rpx solid #f1f5f9;
}
.has-done-row:last-child {
	border-bottom: none;
}
.has-done-k {
	font-size: 24rpx;
	color: #64748b;
	flex-shrink: 0;
}
.has-done-v {
	font-size: 26rpx;
	font-weight: 600;
	color: #0d47a1;
	text-align: right;
	flex: 1;
}
.has-done-title {
	display: block;
	margin-top: 32rpx;
	font-size: 36rpx;
	font-weight: 700;
	color: #1a2e28;
}
.has-done-desc {
	display: block;
	margin: 20rpx 0 48rpx;
	font-size: 26rpx;
	color: #5a6b66;
	line-height: 1.55;
}
</style>
