<template>
	<view class="feed-page">
		<view class="feed-banner">
			<view class="feed-banner-bg">
				<view class="feed-banner-orb feed-banner-orb--1"></view>
				<view class="feed-banner-orb feed-banner-orb--2"></view>
				<view class="feed-banner-grid"></view>
			</view>
			<view class="feed-banner-inner">
				<text class="feed-banner-ph">Banner 占位 · 后续可替换为生成图</text>
				<text class="feed-banner-title">七贤岭学考</text>
				<text class="feed-banner-lead">人文 · 地理 · 景点 · 七贤由来 · 人口 · 小区信息</text>
				<text class="feed-banner-tip">先读再考，板块标签标在每段学考前</text>
				<view class="feed-banner-progress">
					<text class="feed-banner-progress-txt">通关 {{ passedCount }}/{{ articleCount }}</text>
					<view class="feed-banner-track">
						<view class="feed-banner-fill" :style="{ width: progressWidth }"></view>
					</view>
				</view>
			</view>
		</view>

		<view class="feed-loading" v-if="loading">
			<u-loading mode="circle" color="#0F6E56" size="48"></u-loading>
			<text class="feed-loading-txt">加载学考内容</text>
		</view>

		<view class="feed-empty" v-else-if="units.length === 0">
			<text class="feed-empty-txt">内容筹备中</text>
		</view>

		<view class="feed-stream" v-else>
			<view class="feed-unit" v-for="(unit, uIdx) in units" :key="unit.id">
				<view class="feed-unit-head">
					<view class="feed-mod-tag" :style="modTagStyle(unit.category)">
						<text class="feed-mod-emoji">{{ modEmoji(unit.category) }}</text>
						<text class="feed-mod-name">{{ unit.category }}</text>
					</view>
					<text class="feed-unit-idx">第 {{ uIdx + 1 }} 组</text>
				</view>

				<view class="feed-learn" :style="learnBorderStyle(unit.category)">
					<view class="feed-phase-row">
						<text class="feed-phase feed-phase--learn">学习</text>
						<text class="feed-phase-title">{{ unit.title }}</text>
					</view>
					<image
						v-if="unit.image"
						class="feed-learn-cover"
						:src="unit.image"
						mode="widthFix"
						lazy-load
					></image>
					<view class="feed-learn-body">
						<rich-text :nodes="unit.content || ''"></rich-text>
					</view>
				</view>

				<view class="feed-connector">
					<view class="feed-connector-line"></view>
					<text class="feed-connector-txt">↓ 考试</text>
					<view class="feed-connector-line"></view>
				</view>

				<view class="feed-exam">
					<view class="feed-phase-row">
						<text class="feed-phase feed-phase--exam">考试</text>
						<text class="feed-phase-sub" v-if="unit.passed">本篇已通过</text>
						<text class="feed-phase-sub" v-else>{{ unit.question_count }} 题 · 对照上文作答</text>
					</view>

					<view class="feed-exam-done" v-if="unit.passed">
						<text class="feed-exam-done-txt">测验已过关，内容可随时回看</text>
					</view>

					<block v-else-if="unit.questions && unit.questions.length">
						<view class="feed-q" v-for="(q, qIdx) in unit.questions" :key="q.id">
							<text class="feed-q-title">{{ qIdx + 1 }}. {{ q.title }}</text>
							<view
								class="feed-opt"
								v-for="(opt, optIdx) in questionOptions(q)"
								:key="optIdx"
								:class="optionClass(uIdx, qIdx, optIdx)"
								@tap="onSelectOpt(uIdx, qIdx, optIdx)"
							>
								<text class="feed-opt-key">{{ opt.key }}</text>
								<text class="feed-opt-text">{{ opt.text }}</text>
							</view>
						</view>
						<view class="feed-fail" v-if="failHint(unit.id)">
							<text>{{ failHint(unit.id) }}</text>
						</view>
						<view class="feed-submit" @tap="onSubmitUnit(uIdx)">
							<text>{{ submittingId === unit.id ? '提交中...' : '提交本篇测验' }}</text>
						</view>
					</block>

					<view class="feed-exam-empty" v-else>
						<text>本篇暂未配置题目</text>
					</view>
				</view>
			</view>
			<view class="feed-footer-space"></view>
		</view>
	</view>
</template>

<script>
import { getModuleUi } from '@/common/qxlLearnModules.js'

export default {
	data() {
		return {
			loading: true,
			units: [],
			passedCount: 0,
			articleCount: 0,
			answersMap: {},
			failMap: {},
			submittingId: 0,
		}
	},
	computed: {
		progressWidth() {
			if (!this.articleCount) return '0%'
			return Math.min(100, Math.round((this.passedCount / this.articleCount) * 100)) + '%'
		},
	},
	onShow() {
		this.loadFeed()
	},
	methods: {
		formatArticleContent(html) {
			if (!html) return ''
			let s = String(html)
			if (s.indexOf('&lt;') !== -1 && s.indexOf('<') === -1) {
				s = s
					.replace(/&lt;/g, '<')
					.replace(/&gt;/g, '>')
					.replace(/&amp;/g, '&')
					.replace(/&quot;/g, '"')
					.replace(/&#39;/g, "'")
			}
			return s
		},
		modEmoji(category) {
			return getModuleUi(category).emoji || '📖'
		},
		modTagStyle(category) {
			const ui = getModuleUi(category)
			return 'background:' + (ui.gradient || 'linear-gradient(145deg,#0f6e56,#1fa971)')
		},
		learnBorderStyle(category) {
			const ui = getModuleUi(category)
			return 'border-left-color:' + (ui.accent || '#0f6e56')
		},
		loadFeed() {
			this.loading = true
			this.$u.api.getQxlLearnFeed().then(res => {
				if (res.code == 1) {
					const data = res.data || {}
					this.units = (data.units || []).map(u => ({
						...u,
						content: this.formatArticleContent(u.content),
					}))
					this.passedCount = data.passed_count || 0
					this.articleCount = data.article_count || 0
				}
			}).finally(() => {
				this.loading = false
			})
		},
		questionOptions(q) {
			const list = []
			if (q.option_a) list.push({ key: 'A', text: q.option_a })
			if (q.option_b) list.push({ key: 'B', text: q.option_b })
			if (q.option_c) list.push({ key: 'C', text: q.option_c })
			if (q.option_d) list.push({ key: 'D', text: q.option_d })
			return list
		},
		unitAnswers(unitId) {
			return this.answersMap[unitId] || {}
		},
		optionClass(uIdx, qIdx, optIdx) {
			const unit = (this.units || [])[uIdx]
			if (!unit) return ''
			const q = (unit.questions || [])[qIdx]
			if (!q) return ''
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return ''
			const picked = this.unitAnswers(unit.id)[q.id]
			return picked === opt.key ? 'is-on' : ''
		},
		onSelectOpt(uIdx, qIdx, optIdx) {
			const unit = (this.units || [])[uIdx]
			if (!unit) return
			const q = (unit.questions || [])[qIdx]
			if (!q) return
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return
			if (!this.answersMap[unit.id]) {
				this.$set(this.answersMap, unit.id, {})
			}
			this.$set(this.answersMap[unit.id], q.id, opt.key)
		},
		failHint(unitId) {
			return this.failMap[unitId] || ''
		},
		onSubmitUnit(uIdx) {
			const unit = (this.units || [])[uIdx]
			if (!unit || this.submittingId) return
			const questions = unit.questions || []
			const picked = this.unitAnswers(unit.id)
			for (let i = 0; i < questions.length; i++) {
				if (!picked[questions[i].id]) {
					uni.showToast({ title: '请答完第' + (i + 1) + '题', icon: 'none' })
					return
				}
			}
			if (!this.$u.func.checkLogin()) return

			const answers = questions.map(q => ({
				qid: q.id,
				answer: picked[q.id],
			}))
			this.submittingId = unit.id
			this.$u.api.submitQxlChapter({
				knowledge_id: unit.id,
				answers,
			}).then(res => {
				this.submittingId = 0
				if (res.code != 1) {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' })
					return
				}
				const data = res.data || {}
				if (data.passed) {
					this.$set(this.failMap, unit.id, '')
					let msg = '本篇已过关'
					if (data.point > 0) msg = '过关 +' + data.point + '积分'
					uni.showToast({ title: msg, icon: 'success' })
					this.loadFeed()
				} else {
					const wrong = (data.total_count || 0) - (data.correct_count || 0)
					this.$set(
						this.failMap,
						unit.id,
						'答对 ' + data.correct_count + '/' + data.total_count + '，还有 ' + wrong + ' 题可对照上文再选'
					)
					uni.showToast({ title: '尚未全部答对', icon: 'none' })
				}
			}).catch(err => {
				this.submittingId = 0
				const msg = err && err.msg
				if (msg === '用户不存在') {
					this.$u.func.checkLogin()
					return
				}
				uni.showToast({ title: msg || '网络错误', icon: 'none' })
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.feed-page {
	min-height: 100vh;
	background: #e8efeb;
}

.feed-banner {
	position: relative;
	min-height: 360rpx;
	overflow: hidden;
}
.feed-banner-bg {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(155deg, #062a22 0%, #0f6e56 42%, #1fa971 78%, #52b788 100%);
}
.feed-banner-orb {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.08);
}
.feed-banner-orb--1 {
	width: 320rpx;
	height: 320rpx;
	right: -80rpx;
	top: -100rpx;
}
.feed-banner-orb--2 {
	width: 200rpx;
	height: 200rpx;
	left: -40rpx;
	bottom: -60rpx;
}
.feed-banner-grid {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	opacity: 0.12;
	background-image: linear-gradient(rgba(255, 255, 255, 0.35) 1px, transparent 1px),
		linear-gradient(90deg, rgba(255, 255, 255, 0.35) 1px, transparent 1px);
	background-size: 32rpx 32rpx;
}
.feed-banner-inner {
	position: relative;
	z-index: 2;
	padding: 36rpx 32rpx 40rpx;
	color: #fff;
}
.feed-banner-ph {
	display: block;
	font-size: 20rpx;
	opacity: 0.72;
	letter-spacing: 2rpx;
	margin-bottom: 16rpx;
}
.feed-banner-title {
	display: block;
	font-size: 48rpx;
	font-weight: 800;
	letter-spacing: 4rpx;
	text-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.18);
}
.feed-banner-lead {
	display: block;
	margin-top: 16rpx;
	font-size: 26rpx;
	line-height: 1.55;
	opacity: 0.95;
	font-weight: 600;
}
.feed-banner-tip {
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	opacity: 0.78;
}
.feed-banner-progress {
	margin-top: 28rpx;
}
.feed-banner-progress-txt {
	font-size: 22rpx;
	opacity: 0.88;
}
.feed-banner-track {
	height: 10rpx;
	margin-top: 10rpx;
	border-radius: 10rpx;
	background: rgba(255, 255, 255, 0.22);
	overflow: hidden;
}
.feed-banner-fill {
	height: 100%;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #fff5cc, #ffffff);
}

.feed-loading,
.feed-empty {
	padding: 80rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.feed-loading-txt,
.feed-empty-txt {
	font-size: 26rpx;
	color: #6b7c75;
}

.feed-stream {
	margin-top: -24rpx;
	padding: 0 20rpx;
	position: relative;
	z-index: 3;
}
.feed-unit {
	margin-bottom: 36rpx;
}
.feed-unit-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
	padding: 0 4rpx;
}
.feed-mod-tag {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 10rpx;
	padding: 10rpx 22rpx;
	border-radius: 999rpx;
	box-shadow: 0 8rpx 20rpx rgba(15, 110, 86, 0.2);
}
.feed-mod-emoji {
	font-size: 28rpx;
	line-height: 1;
}
.feed-mod-name {
	font-size: 26rpx;
	font-weight: 800;
	color: #fff;
	letter-spacing: 2rpx;
}
.feed-unit-idx {
	font-size: 22rpx;
	color: #8a9691;
}

.feed-learn {
	background: #fff;
	border-radius: 24rpx;
	border-left: 8rpx solid #0f6e56;
	box-shadow: 0 16rpx 40rpx rgba(15, 110, 86, 0.08);
	overflow: hidden;
}
.feed-phase-row {
	display: flex;
	flex-direction: row;
	align-items: center;
	flex-wrap: wrap;
	gap: 12rpx;
	padding: 24rpx 24rpx 12rpx;
}
.feed-phase {
	font-size: 22rpx;
	font-weight: 800;
	padding: 6rpx 16rpx;
	border-radius: 8rpx;
	letter-spacing: 2rpx;
}
.feed-phase--learn {
	color: #9a6700;
	background: #fff8e6;
	border: 1rpx solid #ffe4a8;
}
.feed-phase--exam {
	color: #fff;
	background: linear-gradient(90deg, #0f6e56, #1fa971);
}
.feed-phase-title {
	flex: 1;
	min-width: 0;
	font-size: 30rpx;
	font-weight: 700;
	color: #16332b;
	line-height: 1.4;
}
.feed-phase-sub {
	font-size: 22rpx;
	color: #6b7c75;
}
.feed-learn-cover {
	width: 100%;
	display: block;
	background: #eef3f0;
}
.feed-learn-body {
	padding: 8rpx 28rpx 28rpx;
	font-size: 28rpx;
	color: #2c3d37;
	line-height: 1.85;
}

.feed-connector {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	padding: 16rpx 0;
}
.feed-connector-line {
	flex: 1;
	height: 2rpx;
	background: linear-gradient(90deg, transparent, #b7e0cb, transparent);
}
.feed-connector-txt {
	font-size: 22rpx;
	color: #0f6e56;
	font-weight: 700;
}

.feed-exam {
	background: linear-gradient(180deg, #f7fbf9 0%, #ffffff 100%);
	border-radius: 24rpx;
	border: 2rpx solid #c5e8d8;
	box-shadow: 0 12rpx 32rpx rgba(15, 110, 86, 0.06);
	padding-bottom: 24rpx;
}
.feed-exam-done {
	margin: 0 24rpx 24rpx;
	padding: 24rpx;
	text-align: center;
	background: #e8f7ee;
	border-radius: 16rpx;
	color: #0f6e56;
	font-size: 26rpx;
}
.feed-exam-empty {
	padding: 24rpx;
	text-align: center;
	color: #8a9691;
	font-size: 24rpx;
}
.feed-q {
	padding: 0 24rpx 8rpx;
}
.feed-q-title {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #16332b;
	line-height: 1.55;
	margin: 16rpx 0 12rpx;
}
.feed-opt {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx 18rpx;
	margin-bottom: 12rpx;
	border-radius: 16rpx;
	background: #fff;
	border: 2rpx solid #e4ebe7;
}
.feed-opt.is-on {
	border-color: #0f6e56;
	background: #eaf7f0;
}
.feed-opt-key {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	background: #eef3f0;
	color: #0f6e56;
	font-size: 24rpx;
	font-weight: 800;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.feed-opt.is-on .feed-opt-key {
	background: #0f6e56;
	color: #fff;
}
.feed-opt-text {
	flex: 1;
	font-size: 26rpx;
	color: #2c3d37;
	line-height: 1.45;
}
.feed-fail {
	margin: 8rpx 24rpx 16rpx;
	padding: 16rpx 18rpx;
	border-radius: 12rpx;
	background: #fff4e8;
	color: #b36b00;
	font-size: 24rpx;
}
.feed-submit {
	margin: 8rpx 24rpx 0;
	height: 88rpx;
	border-radius: 44rpx;
	background: linear-gradient(90deg, #0f6e56, #1fa971);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 30rpx;
	font-weight: 700;
	box-shadow: 0 10rpx 24rpx rgba(15, 110, 86, 0.22);
}
.feed-footer-space {
	height: 48rpx;
}
</style>
