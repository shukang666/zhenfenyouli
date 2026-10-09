<template>
	<view class="ch-page">
		<view class="ch-loading" v-if="loading">
			<u-loading mode="circle" color="#0F6E56" size="48"></u-loading>
		</view>

		<scroll-view scroll-y class="ch-scroll" v-else-if="chapter.id">
			<view class="ch-head">
				<text class="ch-cat">{{ chapter.category || '七贤岭' }}</text>
				<text class="ch-title">{{ chapter.title }}</text>
				<text class="ch-tip" v-if="chapter.passed">本篇已过关，可随时回看</text>
				<text class="ch-tip ch-tip--learn" v-else>先读下文，再答文末测验（题目均来自本篇）</text>
			</view>

			<image
				v-if="chapter.image"
				class="ch-cover"
				:src="chapter.image"
				mode="widthFix"
				lazy-load
			></image>

			<view class="ch-article">
				<rich-text :nodes="chapter.content || ''"></rich-text>
			</view>

			<view class="ch-quiz" v-if="!chapter.passed && chapter.questions && chapter.questions.length">
				<view class="ch-quiz-head">
					<view class="ch-quiz-bar"></view>
					<text class="ch-quiz-title">本篇测验</text>
					<text class="ch-quiz-sub">边学边考：对照刚才的内容选答案，全部答对即过关</text>
				</view>

				<view class="ch-q" v-for="(q, qIdx) in chapter.questions" :key="q.id">
					<text class="ch-q-title">{{ qIdx + 1 }}. {{ q.title }}</text>
					<view
						class="ch-opt"
						v-for="(opt, optIdx) in questionOptions(q)"
						:key="optIdx"
						:class="optionClassByIndex(qIdx, optIdx)"
						@tap="onSelectOpt(qIdx, optIdx)"
					>
						<text class="ch-opt-key">{{ opt.key }}</text>
						<text class="ch-opt-text">{{ opt.text }}</text>
					</view>
				</view>

				<view class="ch-fail" v-if="failHint">
					<text>{{ failHint }}</text>
				</view>

				<view class="ch-submit" @tap="submitChapter">
					<text>{{ submitting ? '提交中...' : '提交本篇测验' }}</text>
				</view>
			</view>

			<view class="ch-done" v-else-if="chapter.passed">
				<text class="ch-done-text">本篇测验已通过，感谢学习</text>
			</view>

			<view class="ch-bottom-space"></view>
		</scroll-view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			knowledgeId: 0,
			loading: true,
			submitting: false,
			chapter: {},
			answersMap: {},
			failHint: '',
		}
	},
	onLoad(query) {
		this.knowledgeId = parseInt(query.id, 10) || 0
	},
	onShow() {
		this.loadChapter()
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
		loadChapter() {
			if (!this.knowledgeId) {
				this.loading = false
				return
			}
			this.loading = true
			this.$u.api.getQxlChapterLearn({ id: this.knowledgeId }).then(res => {
				if (res.code == 1) {
					const data = res.data || {}
					this.chapter = {
						...data,
						content: this.formatArticleContent(data.content),
					}
					if (data.title) {
						uni.setNavigationBarTitle({ title: '学考 · ' + data.title })
					}
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
		optionClassByIndex(qIdx, optIdx) {
			const q = (this.chapter.questions || [])[qIdx]
			if (!q) return ''
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return ''
			return this.answersMap[q.id] === opt.key ? 'is-on' : ''
		},
		onSelectOpt(qIdx, optIdx) {
			const q = (this.chapter.questions || [])[qIdx]
			if (!q) return
			const opts = this.questionOptions(q)
			const opt = opts[optIdx]
			if (!opt) return
			this.$set(this.answersMap, q.id, opt.key)
		},
		submitChapter() {
			if (this.submitting) return
			const questions = this.chapter.questions || []
			for (let i = 0; i < questions.length; i++) {
				if (!this.answersMap[questions[i].id]) {
					uni.showToast({ title: '请答完第' + (i + 1) + '题', icon: 'none' })
					return
				}
			}
			if (!this.$u.func.checkLogin()) return

			const answers = questions.map(q => ({
				qid: q.id,
				answer: this.answersMap[q.id],
			}))
			this.submitting = true
			this.$u.api.submitQxlChapter({
				knowledge_id: this.chapter.id,
				answers,
			}).then(res => {
				this.submitting = false
				if (res.code != 1) {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' })
					return
				}
				const data = res.data || {}
				if (data.passed) {
					this.failHint = ''
					let msg = '本篇已过关'
					if (data.point > 0) msg = '过关 +' + data.point + '积分'
					uni.showToast({ title: msg, icon: 'success' })
					this.loadChapter()
				} else {
					const wrong = (data.total_count || 0) - (data.correct_count || 0)
					this.failHint = '答对 ' + data.correct_count + '/' + data.total_count + '，还有 ' + wrong + ' 题可对照上文再选'
					uni.showToast({ title: '尚未全部答对', icon: 'none' })
				}
			}).catch(err => {
				this.submitting = false
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
.ch-page {
	min-height: 100vh;
	background: #f3f6f4;
}
.ch-loading {
	padding: 100rpx 0;
	display: flex;
	justify-content: center;
}
.ch-scroll {
	height: 100vh;
}
.ch-head {
	padding: 28rpx 28rpx 16rpx;
	background: #fff;
}
.ch-cat {
	font-size: 22rpx;
	color: #0f6e56;
	font-weight: 700;
}
.ch-title {
	display: block;
	margin-top: 8rpx;
	font-size: 36rpx;
	font-weight: 800;
	color: #16332b;
	line-height: 1.35;
}
.ch-tip {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #6b7c75;
	line-height: 1.5;
}
.ch-tip--learn {
	color: #0f6e56;
}
.ch-cover {
	width: 100%;
	display: block;
	background: #eef3f0;
}
.ch-article {
	padding: 24rpx 28rpx 16rpx;
	background: #fff;
	font-size: 28rpx;
	color: #2c3d37;
	line-height: 1.85;
}
.ch-quiz {
	margin: 16rpx 20rpx 0;
	padding: 28rpx 24rpx;
	border-radius: 24rpx;
	background: #fff;
	border: 1rpx solid #dcede4;
	box-shadow: 0 10rpx 28rpx rgba(15, 110, 86, 0.06);
}
.ch-quiz-head {
	margin-bottom: 20rpx;
}
.ch-quiz-bar {
	width: 56rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #0f6e56;
	margin-bottom: 12rpx;
}
.ch-quiz-title {
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #0f6e56;
}
.ch-quiz-sub {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #6b7c75;
	line-height: 1.45;
}
.ch-q {
	margin-bottom: 28rpx;
}
.ch-q-title {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #16332b;
	line-height: 1.55;
	margin-bottom: 16rpx;
}
.ch-opt {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx 18rpx;
	margin-bottom: 12rpx;
	border-radius: 16rpx;
	background: #f7fbf9;
	border: 2rpx solid #e4ebe7;
}
.ch-opt.is-on {
	border-color: #0f6e56;
	background: #eaf7f0;
}
.ch-opt-key {
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
.ch-opt.is-on .ch-opt-key {
	background: #0f6e56;
	color: #fff;
}
.ch-opt-text {
	flex: 1;
	font-size: 26rpx;
	color: #2c3d37;
	line-height: 1.45;
}
.ch-fail {
	padding: 16rpx 18rpx;
	border-radius: 12rpx;
	background: #fff4e8;
	color: #b36b00;
	font-size: 24rpx;
	margin-bottom: 16rpx;
}
.ch-submit {
	height: 88rpx;
	border-radius: 44rpx;
	background: linear-gradient(141deg, #1fa971 0%, #0f6e56 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 30rpx;
	font-weight: 700;
}
.ch-done {
	margin: 16rpx 20rpx;
	padding: 28rpx;
	text-align: center;
	background: #e8f7ee;
	border-radius: 20rpx;
	color: #0f6e56;
	font-size: 26rpx;
}
.ch-bottom-space {
	height: 48rpx;
}
</style>
