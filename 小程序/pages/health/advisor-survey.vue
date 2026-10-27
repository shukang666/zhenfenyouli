<template>
	<view class="has-page" :class="{ 'has-page--large': largeTextEnabled }">
		<view class="has-page-waves" aria-hidden="true"></view>
		<view class="has-hub">
			<view class="has-hub-bg"></view>
			<health-advisor-mascot
				:speaking="mascotSpeaking"
				:line="mascotLine"
				size="lg"
			/>
		</view>

		<view class="has-body">
		<!-- 欢迎 -->
		<view v-if="step === 'welcome'" class="has-panel has-welcome">
			<text class="has-welcome-title">您好，想跟您轻松聊几分钟</text>
			<text class="has-welcome-desc">问卷分两部分：服务体验与最关心的健康问题。大约 2 分钟，可随时退出。</text>
			<view class="has-agree" @tap="agreed = !agreed">
				<view class="has-agree-box" :class="{ 'has-agree-box--on': agreed }">
					<u-icon v-if="agreed" name="checkmark" size="24" color="#fff"></u-icon>
				</view>
				<text class="has-agree-text">我已了解并自愿参与</text>
			</view>
			<button class="has-btn has-btn--primary" :disabled="!agreed" @tap="startSurvey">开始聊聊</button>
			<button class="has-btn has-btn--ghost" @tap="goBack">暂不参加</button>
		</view>

		<!-- 服务体验 -->
		<view v-else-if="step === 'service'" class="has-panel">
			<view class="has-topbar">
				<view class="has-back" @tap="onServiceBack">
					<u-icon name="arrow-left" size="36" color="#4A90E2"></u-icon>
				</view>
				<text class="has-step-label">服务体验 {{ serviceIndex + 1 }}/{{ serviceQuestions.length }}</text>
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
					<u-icon name="volume-fill" size="32" color="#4A90E2"></u-icon>
					<text>朗读此题</text>
				</view>
				<view class="has-tool has-tool--toggle">
					<text class="has-tool-label">大字</text>
					<switch :checked="largeTextEnabled" color="#4A90E2" @change="onLargeTextChange" />
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

		<!-- 健康关注 -->
		<view v-else-if="step === 'concerns'" class="has-panel">
			<view class="has-topbar">
				<view class="has-back" @tap="step = 'service'; serviceIndex = serviceQuestions.length - 1">
					<u-icon name="arrow-left" size="36" color="#4A90E2"></u-icon>
				</view>
				<text class="has-step-label">您最关心的健康问题</text>
			</view>
			<text class="has-subhint">最多选 2 项，便于我们推荐社区活动与科普</text>
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
				<button class="has-btn has-btn--ghost has-btn--half" @tap="skipConcerns">跳过此页</button>
				<button class="has-btn has-btn--primary has-btn--half" @tap="goSummary">下一步</button>
			</view>
		</view>

		<!-- 概要 -->
		<view v-else-if="step === 'summary'" class="has-panel">
			<text class="has-step-label has-step-label--solo">您的健康概要</text>
			<text class="has-subhint">根据本次填写整理，供您参考</text>
			<view class="has-summary-card">
				<view class="has-summary-row">
					<text class="has-summary-k">服务满意度</text>
					<text class="has-summary-v">{{ summaryServiceText }}</text>
				</view>
				<view class="has-summary-row">
					<text class="has-summary-k">当前关注</text>
					<text class="has-summary-v">{{ summaryConcernText }}</text>
				</view>
				<view class="has-summary-row">
					<text class="has-summary-k">填写状态</text>
					<text class="has-summary-v has-summary-v--ok">已完成</text>
				</view>
			</view>
			<view class="has-disclaimer">
				<u-icon name="info-circle" size="28" color="#8a958c"></u-icon>
				<text>本概要仅供社区健康服务参考，不能替代医生诊断与处方。</text>
			</view>
			<button class="has-btn has-btn--primary" :loading="submitting" @tap="goOptional">保存并继续</button>
		</view>

		<!-- 可选下一步 -->
		<view v-else-if="step === 'optional'" class="has-panel">
			<text class="has-step-label has-step-label--solo">下一步（可选）</text>
			<view class="has-rec-card" v-for="(rec, ri) in recommendCards" :key="ri">
				<text class="has-rec-title">{{ rec.title }}</text>
				<text class="has-rec-desc">{{ rec.desc }}</text>
				<button class="has-btn has-btn--outline" @tap="onRecTap(rec)">了解更多</button>
			</view>
			<view class="has-agree has-agree--compact" @tap="answers.contact_ok = !answers.contact_ok">
				<view class="has-agree-box" :class="{ 'has-agree-box--on': answers.contact_ok }">
					<u-icon v-if="answers.contact_ok" name="checkmark" size="24" color="#fff"></u-icon>
				</view>
				<text class="has-agree-text">愿意接受社区健康活动回访（可选）</text>
			</view>
			<view class="has-footer-actions">
				<button class="has-btn has-btn--ghost has-btn--half" @tap="finishSurvey(false)">以后再说</button>
				<button class="has-btn has-btn--primary has-btn--half" :loading="submitting" @tap="finishSurvey(true)">完成</button>
			</view>
		</view>

		<!-- 完成 -->
		<view v-else-if="step === 'done'" class="has-panel has-done">
			<u-icon name="checkmark-circle-fill" size="100" color="#4A90E2"></u-icon>
			<text class="has-done-title">感谢您的参与</text>
			<text class="has-done-desc">我们会根据社区情况安排健康科普与活动，请留意小程序通知。</text>
			<button class="has-btn has-btn--primary" @tap="goBack">返回</button>
		</view>
		</view>
	</view>
</template>

<script>
import HealthAdvisorMascot from '@/components/health-advisor-mascot/index.vue'

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
	{ key: 'bp', name: '血压', uIcon: 'heart-fill', iconColor: '#ef4444', iconBg: '#fee2e2' },
	{ key: 'sugar', name: '血糖', uIcon: 'tags-fill', iconColor: '#2563eb', iconBg: '#dbeafe' },
	{ key: 'sleep', name: '睡眠', uIcon: 'star-fill', iconColor: '#ca8a04', iconBg: '#fef9c3' },
	{ key: 'sport', name: '体力运动', uIcon: 'man-add-fill', iconColor: '#16a34a', iconBg: '#dcfce7' },
	{ key: 'diet', name: '体重饮食', uIcon: 'apple-fill', iconColor: '#dc2626', iconBg: '#ffe4e6' },
	{ key: 'none', name: '暂无特别关心', uIcon: 'more-dot-fill', iconColor: '#64748b', iconBg: '#f1f5f9' }
]

export default {
	components: { HealthAdvisorMascot },
	data() {
		return {
			surveyId: 0,
			mascotSpeaking: false,
			mascotLine: '您好，我想先了解一下您的健康。',
			speakTimer: null,
			step: 'welcome',
			agreed: false,
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
			submitting: false,
			recommendCards: [
				{
					key: 'lecture',
					title: '社区健康讲座',
					desc: '血压管理与生活方式小建议，线下/线上均可参与。',
					url: '/pages/sjhd/list?type=3'
				},
				{
					key: 'community',
					title: '社区健康服务',
					desc: '了解本社区可预约的公共卫生与养老资源。',
					url: '/pages/healthcare/participate?id=6'
				}
			]
		}
	},
	computed: {
		currentServiceQuestion() {
			return this.serviceQuestions[this.serviceIndex] || SERVICE_QUESTIONS[0]
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
		this.$nextTick(() => {
			this.speakLine(this.mascotLine, 3200)
		})
	},
	onUnload() {
		this.stopSpeaking()
	},
	watch: {
		step(val) {
			this.refreshMascotLine(val === 'service')
		},
		serviceIndex() {
			if (this.step === 'service') {
				this.refreshMascotLine(true)
			}
		}
	},
	methods: {
		stopSpeaking() {
			if (this.speakTimer) {
				clearTimeout(this.speakTimer)
				this.speakTimer = null
			}
			this.mascotSpeaking = false
		},
		speakDuration(text) {
			const len = (text || '').length
			return Math.min(9000, Math.max(2600, len * 180))
		},
		speakLine(text, duration) {
			const line = (text || '').trim()
			if (!line) return
			this.stopSpeaking()
			this.mascotLine = line
			this.mascotSpeaking = true
			const ms = duration || this.speakDuration(line)
			this.speakTimer = setTimeout(() => {
				this.mascotSpeaking = false
				this.speakTimer = null
			}, ms)
		},
		refreshMascotLine(autoSpeak) {
			let line = ''
			switch (this.step) {
				case 'welcome':
					line = '您好，我想先了解一下您的健康。'
					break
				case 'service':
					line = this.currentServiceQuestion.text
					break
				case 'concerns':
					line = '请选择您最关心的健康问题，最多选 2 项。'
					break
				case 'summary':
					line = '这是根据您刚才填写整理的概要，请您过目。'
					break
				case 'optional':
					line = '下面有一些可选的社区健康活动，感兴趣可以看看。'
					break
				case 'done':
					line = '感谢您的参与，祝您身体健康！'
					break
				default:
					line = this.mascotLine
			}
			this.mascotLine = line
			if (autoSpeak) {
				this.speakLine(line)
			}
		},
		startSurvey() {
			if (!this.agreed) return
			this.step = 'service'
			this.serviceIndex = 0
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
			this.step = 'welcome'
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
			this.speakLine(text)
		},
		onMicStart() {
			this.recording = true
			this.speakLine('请说出您的答案～', 1500)
		},
		onMicEnd() {
			this.recording = false
			this.voiceFallback = true
			this.stopSpeaking()
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
			this.goSummary()
		},
		goSummary() {
			this.step = 'summary'
		},
		goOptional() {
			this.submitDraft(false)
			this.step = 'optional'
		},
		onRecTap(rec) {
			if (rec.url) {
				this.$u.route(rec.url)
			}
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
	background: #f0f7ff;
}
.has-page {
	position: relative;
	min-height: 100vh;
	padding: 0 0 calc(48rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
	background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 38%, #f5faff 100%);
	overflow: hidden;
}
.has-page-waves {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	height: 280rpx;
	pointer-events: none;
	background:
		radial-gradient(ellipse 120% 80% at 50% 100%, rgba(112, 214, 255, 0.28) 0%, transparent 55%),
		repeating-radial-gradient(circle at 50% 120%, transparent 0, transparent 28rpx, rgba(74, 144, 226, 0.06) 29rpx, transparent 30rpx);
	opacity: 0.85;
}
.has-hub {
	position: relative;
	z-index: 1;
	padding: 16rpx 32rpx 8rpx;
	overflow: hidden;
}
.has-hub-bg {
	position: absolute;
	left: 50%;
	top: 20rpx;
	width: 520rpx;
	height: 420rpx;
	margin-left: -260rpx;
	border-radius: 50%;
	background: radial-gradient(ellipse at 50% 40%, rgba(255, 255, 255, 0.98) 0%, rgba(240, 247, 255, 0.75) 45%, transparent 72%);
	pointer-events: none;
}
.has-hub-bg::before {
	content: '';
	position: absolute;
	inset: 12%;
	border-radius: 50%;
	border: 2rpx solid rgba(74, 144, 226, 0.1);
	box-shadow: 0 40rpx 80rpx rgba(74, 144, 226, 0.12);
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
	background: #4A90E2;
	border-color: #4A90E2;
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
	background: linear-gradient(90deg, #70d6ff 0%, #4a90e2 100%);
	color: #fff;
	box-shadow: 0 12rpx 28rpx rgba(74, 144, 226, 0.35);
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
	color: #4A90E2;
}
.has-step-label--solo {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #1e4a7a;
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
	color: #1e4a7a;
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
	background: linear-gradient(90deg, #70d6ff 0%, #4a90e2 100%);
	color: #fff;
}
.has-opt--na {
	background: #fff;
	border-color: #d6e8ff;
	font-weight: 500;
}
.has-opt--active {
	box-shadow: 0 0 0 4rpx rgba(74, 144, 226, 0.35);
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
	color: #4A90E2;
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
	background: linear-gradient(145deg, #70d6ff, #4a90e2);
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
	border-color: #4A90E2;
	background: #f0f7ff;
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
	background: #4A90E2;
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
	color: #4A90E2;
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
	padding-top: 80rpx;
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
