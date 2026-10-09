<template>
	<view class="feed-page">
		<view class="feed-banner">
			<image class="feed-banner-img" :src="bannerUrl" mode="widthFix" lazy-load></image>
		</view>
		<view class="feed-progress-card">
			<text class="feed-progress-label">板块通关</text>
			<text class="feed-progress-num">{{ passedCount }}/{{ moduleCount }}</text>
			<view class="feed-progress-track">
				<view class="feed-progress-fill" :style="{ width: progressWidth }"></view>
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
			<view class="feed-unit" v-for="(unit, uIdx) in units" :key="unit.category">
				<view class="feed-unit-head">
					<view class="feed-mod-tag" :style="modTagStyle(unit.category)">
						<text class="feed-mod-emoji">{{ modEmoji(unit.category) }}</text>
						<text class="feed-mod-name">{{ unit.category }}</text>
					</view>
					<text class="feed-unit-idx">{{ unit.subtitle }}</text>
				</view>

				<view class="feed-learn" :style="learnBorderStyle(unit.category)">
					<view class="feed-phase-row">
						<text class="feed-phase feed-phase--learn">学习</text>
						<text class="feed-phase-sub">后台配置 · {{ unit.articles.length }} 篇</text>
					</view>
					<block v-if="unit.articles && unit.articles.length">
						<view class="feed-art-block" v-for="(art, aIdx) in unit.articles" :key="art.id">
							<text class="feed-art-title">{{ art.title }}</text>
							<image
								v-if="art.image"
								class="feed-learn-cover"
								:src="art.image"
								mode="widthFix"
								lazy-load
							></image>
							<view class="feed-learn-body">
								<rich-text :nodes="art.content || ''"></rich-text>
							</view>
							<view class="feed-art-divider" v-if="aIdx < unit.articles.length - 1"></view>
						</view>
					</block>
					<view class="feed-learn-empty" v-else>
						<text>该模块学习正文尚未配置</text>
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
						<text class="feed-phase-sub" v-if="unit.passed">本模块已过关</text>
						<text class="feed-phase-sub" v-else>随机 {{ drawCount }} 题 · 未全对将重新抽题</text>
					</view>

					<view class="feed-exam-done" v-if="unit.passed">
						<text class="feed-exam-done-txt">本模块测验已通过，可随时回看学习内容</text>
					</view>

					<view class="feed-exam-loading" v-else-if="quizLoadingMap[unit.category]">
						<text>正在抽题...</text>
					</view>

					<block v-else-if="moduleQuestions(unit.category).length">
						<view class="feed-q" v-for="(q, qIdx) in moduleQuestions(unit.category)" :key="q.id">
							<text class="feed-q-title">{{ qIdx + 1 }}. {{ q.title }}</text>
							<view
								class="feed-opt"
								v-for="(opt, optIdx) in questionOptions(q)"
								:key="optIdx"
								:class="optionClass(unit.category, qIdx, optIdx)"
								@tap="onSelectOpt(uIdx, qIdx, optIdx)"
							>
								<text class="feed-opt-key">{{ opt.key }}</text>
								<text class="feed-opt-text">{{ opt.text }}</text>
							</view>
						</view>
						<view class="feed-fail" v-if="failHint(unit.category)">
							<text>{{ failHint(unit.category) }}</text>
						</view>
						<view class="feed-submit" @tap="onSubmitModule(uIdx)">
							<text>{{ submittingCat === unit.category ? '提交中...' : '提交本模块测验' }}</text>
						</view>
					</block>

					<view class="feed-exam-empty" v-else>
						<text>题库不足 {{ drawCount }} 题（当前 {{ unit.pool_count }} 题），请联系管理员</text>
					</view>
				</view>
			</view>
			<view class="feed-footer-space"></view>
		</view>
	</view>
</template>

<script>
import { getModuleUi } from '@/common/qxlLearnModules.js'
import settings from '@/common/settings.js'

const QXL_LEARN_BANNER = settings.staticAssetBase + 'img/qxl-learn-banner.png'

export default {
	data() {
		return {
			bannerUrl: QXL_LEARN_BANNER,
			loading: true,
			units: [],
			passedCount: 0,
			moduleCount: 6,
			drawCount: 3,
			quizMap: {},
			quizLoadingMap: {},
			answersMap: {},
			failMap: {},
			submittingCat: '',
		}
	},
	computed: {
		progressWidth() {
			if (!this.moduleCount) return '0%'
			return Math.min(100, Math.round((this.passedCount / this.moduleCount) * 100)) + '%'
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
		moduleQuestions(category) {
			return this.quizMap[category] || []
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
					this.units = (data.units || data.modules || []).map(u => ({
						...u,
						articles: (u.articles || []).map(a => ({
							...a,
							content: this.formatArticleContent(a.content),
						})),
					}))
					this.passedCount = data.passed_count || 0
					this.moduleCount = data.module_count || this.units.length || 6
					this.drawCount = (this.units[0] && this.units[0].draw_count) || 3
					this.quizMap = {}
					this.answersMap = {}
					this.failMap = {}
					this.units.forEach(u => {
						if (!u.passed) {
							this.fetchModuleQuiz(u.category)
						}
					})
				}
			}).finally(() => {
				this.loading = false
			})
		},
		fetchModuleQuiz(category) {
			if (!category) return
			this.$set(this.quizLoadingMap, category, true)
			this.$set(this.quizMap, category, [])
			this.$set(this.answersMap, category, {})
			this.$set(this.failMap, category, '')
			this.$u.api.getQxlModuleQuiz({ category }).then(res => {
				if (res.code == 1 && res.data) {
					if (res.data.passed) {
						this.refreshUnitPassed(category)
					} else {
						this.$set(this.quizMap, category, res.data.questions || [])
					}
				}
			}).catch(err => {
				uni.showToast({ title: (err && err.msg) || '抽题失败', icon: 'none' })
			}).finally(() => {
				this.$set(this.quizLoadingMap, category, false)
			})
		},
		refreshUnitPassed(category) {
			const idx = this.units.findIndex(u => u.category === category)
			if (idx >= 0) {
				this.$set(this.units[idx], 'passed', 1)
			}
		},
		questionOptions(q) {
			const list = []
			if (q.option_a) list.push({ key: 'A', text: q.option_a })
			if (q.option_b) list.push({ key: 'B', text: q.option_b })
			if (q.option_c) list.push({ key: 'C', text: q.option_c })
			if (q.option_d) list.push({ key: 'D', text: q.option_d })
			return list
		},
		unitAnswers(category) {
			return this.answersMap[category] || {}
		},
		optionClass(category, qIdx, optIdx) {
			const questions = this.moduleQuestions(category)
			const q = questions[qIdx]
			if (!q) return ''
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return ''
			const picked = this.unitAnswers(category)[q.id]
			return picked === opt.key ? 'is-on' : ''
		},
		onSelectOpt(uIdx, qIdx, optIdx) {
			const unit = (this.units || [])[uIdx]
			if (!unit || unit.passed) return
			const questions = this.moduleQuestions(unit.category)
			const q = questions[qIdx]
			if (!q) return
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return
			if (!this.answersMap[unit.category]) {
				this.$set(this.answersMap, unit.category, {})
			}
			this.$set(this.answersMap[unit.category], q.id, opt.key)
		},
		failHint(category) {
			return this.failMap[category] || ''
		},
		onSubmitModule(uIdx) {
			const unit = (this.units || [])[uIdx]
			if (!unit || unit.passed || this.submittingCat) return
			const category = unit.category
			const questions = this.moduleQuestions(category)
			const picked = this.unitAnswers(category)
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
			this.submittingCat = category
			this.$u.api.submitQxlModuleQuiz({
				category,
				answers,
			}).then(res => {
				this.submittingCat = ''
				if (res.code != 1) {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' })
					return
				}
				const data = res.data || {}
				if (data.passed) {
					this.$set(this.failMap, category, '')
					let msg = '本模块已过关'
					if (data.point > 0) msg = '过关 +' + data.point + '积分'
					uni.showToast({ title: msg, icon: 'success' })
					this.loadFeed()
				} else {
					const wrong = (data.total_count || 0) - (data.correct_count || 0)
					this.$set(
						this.failMap,
						category,
						'答对 ' + data.correct_count + '/' + data.total_count + (wrong > 0 ? '，已为你换一批新题' : '')
					)
					uni.showToast({ title: '尚未全部答对', icon: 'none' })
					this.fetchModuleQuiz(category)
				}
			}).catch(err => {
				this.submittingCat = ''
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
	width: 100%;
	overflow: hidden;
	background: #0f6e56;
}
.feed-banner-img {
	width: 100%;
	display: block;
	vertical-align: top;
}
.feed-progress-card {
	margin: -16rpx 20rpx 0;
	padding: 22rpx 24rpx 20rpx;
	position: relative;
	z-index: 4;
	background: #fff;
	border-radius: 20rpx;
	box-shadow: 0 10rpx 28rpx rgba(15, 110, 86, 0.1);
	border: 1rpx solid #dceee4;
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	align-items: center;
	gap: 12rpx 16rpx;
}
.feed-progress-label {
	font-size: 26rpx;
	font-weight: 700;
	color: #16332b;
}
.feed-progress-num {
	font-size: 28rpx;
	font-weight: 800;
	color: #0f6e56;
	margin-left: auto;
}
.feed-progress-track {
	width: 100%;
	height: 12rpx;
	border-radius: 12rpx;
	background: #e8f0ec;
	overflow: hidden;
}
.feed-progress-fill {
	height: 100%;
	border-radius: 12rpx;
	background: linear-gradient(90deg, #0f6e56, #1fa971);
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
	margin-top: 20rpx;
	padding: 0 20rpx;
	position: relative;
	z-index: 3;
}
.feed-unit {
	margin-bottom: 36rpx;
}
.feed-unit-head {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 10rpx;
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
}
.feed-mod-name {
	font-size: 26rpx;
	font-weight: 800;
	color: #fff;
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
.feed-phase-sub {
	font-size: 22rpx;
	color: #6b7c75;
}
.feed-art-block {
	padding-bottom: 8rpx;
}
.feed-art-title {
	display: block;
	padding: 0 28rpx 12rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #16332b;
}
.feed-learn-cover {
	width: 100%;
	display: block;
	background: #eef3f0;
}
.feed-learn-body {
	padding: 8rpx 28rpx 20rpx;
	font-size: 28rpx;
	color: #2c3d37;
	line-height: 1.85;
}
.feed-art-divider {
	height: 1rpx;
	margin: 8rpx 28rpx 16rpx;
	background: linear-gradient(90deg, transparent, #dceee4, transparent);
}
.feed-learn-empty {
	padding: 24rpx 28rpx 32rpx;
	color: #8a9691;
	font-size: 24rpx;
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
.feed-exam-done,
.feed-exam-loading {
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
}
.feed-footer-space {
	height: 48rpx;
}
</style>
