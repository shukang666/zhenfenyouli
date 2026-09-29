<template>
	<view class="le-page">
		<view class="le-hero">
			<view class="le-hero-deco"></view>
			<text class="le-hero-kicker">认识七贤岭 · 学考融合</text>
			<text class="le-hero-title">边读边测，过关即开下一篇</text>
			<text class="le-hero-desc">每篇学完立刻考本篇内容，全部答对方可解锁下文</text>
			<view class="le-progress-card">
				<view class="le-progress-top">
					<text class="le-progress-label">通关进度</text>
					<text class="le-progress-num">{{passedCount}} / {{totalCount}}</text>
				</view>
				<view class="le-progress-track">
					<view class="le-progress-fill" :style="{width: progressWidth}"></view>
				</view>
				<text class="le-progress-hint" v-if="allPassed">全部篇章已过关，感谢你守护七贤岭</text>
				<text class="le-progress-hint" v-else>当前进行：{{currentTitle}}</text>
			</view>
		</view>

		<view class="le-body">
			<view class="le-loading" v-if="loading">
				<u-loading mode="circle" color="#0F6E56" size="48"></u-loading>
				<text class="le-loading-text">正在准备学考内容</text>
			</view>

			<view class="le-empty" v-else-if="chapters.length === 0">
				<text class="le-empty-text">暂无学习内容</text>
			</view>

			<view class="le-done" v-if="!loading && allPassed">
				<text class="le-done-title">学考之旅已完成</text>
				<text class="le-done-desc">你已经读完并通关全部篇章，可以随时回看文章。</text>
			</view>

			<view
				class="le-chapter"
				v-for="(ch, idx) in chapters"
				:key="ch.id"
				:id="'ch-' + ch.id"
				:class="chapterClass(ch)"
			>
				<view class="le-ch-head" @click="toggleChapter(ch)">
					<view class="le-index" :class="indexClass(ch)">{{padIndex(idx)}}</view>
					<view class="le-ch-head-main">
						<text class="le-ch-title">{{ch.title}}</text>
						<text class="le-ch-cat">{{ch.category || '七贤岭'}} · {{ch.question_count}} 题</text>
					</view>
					<text class="le-badge" v-if="ch.passed">已过关</text>
					<text class="le-badge le-badge--now" v-else-if="ch.unlocked">进行中</text>
					<text class="le-badge le-badge--lock" v-else>未解锁</text>
				</view>

				<view class="le-lock-panel" v-if="!ch.unlocked">
					<text class="le-lock-text">完成上一篇测验后即可解锁</text>
				</view>

				<view class="le-ch-main" v-else-if="ch.passed && !isExpanded(ch.id)">
					<text class="le-passed-tip">本篇已通过，点上方标题可回看全文</text>
				</view>

				<view class="le-ch-main" v-else-if="ch.unlocked && (!ch.passed || isExpanded(ch.id))">
					<image class="le-cover" v-if="ch.image" :src="$u.func.resolveAssetUrl(ch.image)" mode="widthFix"></image>
					<view class="le-article">
						<rich-text :nodes="ch.content || ''"></rich-text>
					</view>

					<view class="le-quiz" v-if="!ch.passed && ch.questions && ch.questions.length">
						<view class="le-quiz-head">
							<view class="le-quiz-bar"></view>
							<text class="le-quiz-title">本篇测验</text>
							<text class="le-quiz-sub">题目都来自刚才这篇文章，全部答对即过关</text>
						</view>

						<view class="le-q" v-for="(q, qIdx) in ch.questions" :key="q.id">
							<text class="le-q-title">{{qIdx + 1}}. {{q.title}}</text>
							<view
								class="le-opt"
								v-for="opt in questionOptions(q)"
								:key="q.id + '-' + opt.key"
								:class="optionClass(ch.id, q.id, opt.key)"
								@click="selectAnswer(ch.id, q.id, opt.key)"
							>
								<text class="le-opt-key">{{opt.key}}</text>
								<text class="le-opt-text">{{opt.text}}</text>
							</view>
						</view>

						<view class="le-fail" v-if="failHint(ch.id)">
							<text>{{failHint(ch.id)}}</text>
						</view>

						<view class="le-submit" @click="submitChapter(ch)">
							<text>{{submittingId === ch.id ? '提交中...' : '提交本篇测验'}}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				loading: false,
				chapters: [],
				passedCount: 0,
				totalCount: 0,
				allPassed: false,
				currentId: 0,
				answersMap: {},
				expandedMap: {},
				failMap: {},
				submittingId: 0,
			};
		},
		computed: {
			progressWidth() {
				if (!this.totalCount) return '0%';
				const p = Math.round((this.passedCount / this.totalCount) * 100);
				return p + '%';
			},
			currentTitle() {
				const cur = this.chapters.find(c => c.id === this.currentId);
				if (cur) return cur.title;
				const first = this.chapters.find(c => c.unlocked && !c.passed);
				return first ? first.title : '全部完成';
			},
		},
		onShow() {
			this.loadPath();
		},
		methods: {
			padIndex(idx) {
				const n = idx + 1;
				return n < 10 ? '0' + n : '' + n;
			},
			formatArticleContent(html) {
				if (!html) return '';
				let s = String(html);
				if (s.indexOf('&lt;') !== -1 && s.indexOf('<') === -1) {
					s = s
						.replace(/&lt;/g, '<')
						.replace(/&gt;/g, '>')
						.replace(/&amp;/g, '&')
						.replace(/&quot;/g, '"')
						.replace(/&#39;/g, "'");
				}
				return s;
			},
			chapterClass(ch) {
				if (ch.passed) return 'is-passed';
				if (ch.unlocked) return 'is-current';
				return 'is-locked';
			},
			indexClass(ch) {
				if (ch.passed) return 'is-passed';
				if (ch.unlocked) return 'is-current';
				return '';
			},
			isExpanded(id) {
				return !!this.expandedMap[id];
			},
			toggleChapter(ch) {
				if (!ch.passed) return;
				const next = !this.expandedMap[ch.id];
				this.$set(this.expandedMap, ch.id, next);
			},
			questionOptions(q) {
				const list = [];
				if (q.option_a) list.push({ key: 'A', text: q.option_a });
				if (q.option_b) list.push({ key: 'B', text: q.option_b });
				if (q.option_c) list.push({ key: 'C', text: q.option_c });
				if (q.option_d) list.push({ key: 'D', text: q.option_d });
				return list;
			},
			optionClass(chapterId, qid, key) {
				const picked = this.answersMap[chapterId] && this.answersMap[chapterId][qid];
				return picked === key ? 'is-on' : '';
			},
			failHint(chapterId) {
				return this.failMap[chapterId] || '';
			},
			selectAnswer(chapterId, qid, key) {
				if (!this.answersMap[chapterId]) {
					this.$set(this.answersMap, chapterId, {});
				}
				this.$set(this.answersMap[chapterId], qid, key);
			},
			loadPath() {
				this.loading = true;
				this.$u.api.getQxlLearnPath().then(res => {
					this.loading = false;
					if (res.code == 1) {
						const data = res.data || {};
						this.chapters = (data.chapters || []).map(ch => ({
							...ch,
							content: this.formatArticleContent(ch.content),
						}));
						this.passedCount = data.passed_count || 0;
						this.totalCount = data.total_count || 0;
						this.allPassed = !!data.all_passed;
						this.currentId = data.current_id || 0;
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			submitChapter(ch) {
				if (this.submittingId) return;
				const questions = ch.questions || [];
				const picked = this.answersMap[ch.id] || {};
				for (let i = 0; i < questions.length; i++) {
					if (!picked[questions[i].id]) {
						uni.showToast({ title: '请答完第' + (i + 1) + '题', icon: 'none' });
						return;
					}
				}
				if (!this.$u.func.checkLogin()) {
					return;
				}
				const answers = questions.map(q => ({
					qid: q.id,
					answer: picked[q.id],
				}));
				this.submittingId = ch.id;
				this.$u.api.submitQxlChapter({
					knowledge_id: ch.id,
					answers: answers,
				}).then(res => {
					this.submittingId = 0;
					if (res.code != 1) {
						uni.showToast({ title: res.msg || '提交失败', icon: 'none' });
						return;
					}
					const data = res.data || {};
					if (data.passed) {
						this.$set(this.failMap, ch.id, '');
						let msg = '本篇已过关';
						if (data.point > 0) {
							msg = '过关 +' + data.point + '积分';
						}
						uni.showToast({ title: msg, icon: 'success' });
						this.loadPath();
					} else {
						const wrong = (data.total_count || 0) - (data.correct_count || 0);
						this.$set(this.failMap, ch.id, '答对 ' + data.correct_count + '/' + data.total_count + '，还有 ' + wrong + ' 题可对照上文再选一次');
						uni.showToast({ title: '尚未全部答对', icon: 'none' });
					}
				}).catch((err) => {
					this.submittingId = 0;
					const msg = err && err.msg;
					if (!this.isLogin || msg === '用户不存在') {
						this.$u.func.checkLogin();
						return;
					}
					uni.showToast({ title: msg || '网络错误，请重试', icon: 'none' });
				});
			},
		}
	}
</script>

<style lang="scss" scoped>
.le-page {
	min-height: 100vh;
	background: #F3F6F4;
}
.le-hero {
	position: relative;
	overflow: hidden;
	padding: 48rpx 36rpx 80rpx;
	background: linear-gradient(160deg, #0B4F3F 0%, #0F6E56 46%, #1FA971 100%);
}
.le-hero-deco {
	position: absolute;
	width: 420rpx;
	height: 420rpx;
	border-radius: 50%;
	right: -120rpx;
	top: -160rpx;
	background: rgba(255,255,255,0.12);
	pointer-events: none;
}
.le-hero-kicker {
	position: relative;
	font-size: 22rpx;
	letter-spacing: 4rpx;
	color: rgba(255,255,255,0.78);
}
.le-hero-title {
	position: relative;
	display: block;
	margin-top: 16rpx;
	font-size: 44rpx;
	font-weight: 800;
	color: #fff;
	line-height: 1.35;
}
.le-hero-desc {
	position: relative;
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: rgba(255,255,255,0.82);
	line-height: 1.6;
}
.le-progress-card {
	position: relative;
	margin-top: 36rpx;
	padding: 28rpx 28rpx 24rpx;
	border-radius: 24rpx;
	background: rgba(255,255,255,0.14);
	backdrop-filter: blur(8px);
}
.le-progress-top {
	display: flex;
	justify-content: space-between;
	align-items: baseline;
}
.le-progress-label {
	font-size: 24rpx;
	color: rgba(255,255,255,0.8);
}
.le-progress-num {
	font-size: 36rpx;
	font-weight: 800;
	color: #fff;
}
.le-progress-track {
	height: 12rpx;
	margin-top: 18rpx;
	border-radius: 12rpx;
	background: rgba(255,255,255,0.22);
	overflow: hidden;
}
.le-progress-fill {
	height: 100%;
	border-radius: 12rpx;
	background: linear-gradient(90deg, #D7F5C8 0%, #FFFFFF 100%);
}
.le-progress-hint {
	display: block;
	margin-top: 16rpx;
	font-size: 22rpx;
	color: rgba(255,255,255,0.82);
}
.le-body {
	margin-top: -36rpx;
	padding: 0 24rpx 80rpx;
	position: relative;
	z-index: 2;
}
.le-loading, .le-empty {
	padding: 80rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.le-loading-text, .le-empty-text {
	font-size: 24rpx;
	color: #7A8B84;
}
.le-done {
	background: #E8F7EE;
	border: 1rpx solid #CDEAD8;
	border-radius: 20rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
}
.le-done-title {
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #0F6E56;
}
.le-done-desc {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #4E6A5F;
}
.le-chapter {
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 28rpx rgba(15, 110, 86, 0.06);
	border: 1rpx solid #E6EEEA;
}
.le-chapter.is-current {
	border-color: #B7E0CB;
	box-shadow: 0 14rpx 36rpx rgba(15, 110, 86, 0.12);
}
.le-chapter.is-locked {
	opacity: 0.72;
}
.le-ch-head {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 28rpx 24rpx 24rpx;
}
.le-index {
	width: 72rpx;
	height: 72rpx;
	border-radius: 20rpx;
	background: #EEF3F0;
	color: #6B7C75;
	font-size: 26rpx;
	font-weight: 800;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.le-index.is-current {
	background: linear-gradient(141deg, #1FA971 0%, #0F6E56 100%);
	color: #fff;
}
.le-index.is-passed {
	background: #0F6E56;
	color: #fff;
}
.le-ch-head-main {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}
.le-ch-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #16332B;
	line-height: 1.4;
}
.le-ch-cat {
	font-size: 22rpx;
	color: #7A8B84;
}
.le-badge {
	flex-shrink: 0;
	height: 44rpx;
	line-height: 44rpx;
	padding: 0 16rpx;
	border-radius: 22rpx;
	font-size: 20rpx;
	background: #E8F7EE;
	color: #0F6E56;
	font-weight: 700;
}
.le-badge--now {
	background: #FFF4D6;
	color: #B36B00;
}
.le-badge--lock {
	background: #F1F3F2;
	color: #8A9691;
}
.le-lock-panel {
	padding: 0 24rpx 32rpx;
}
.le-lock-text {
	font-size: 24rpx;
	color: #8A9691;
}
.le-passed-tip {
	display: block;
	padding: 0 24rpx 28rpx;
	font-size: 24rpx;
	color: #5F7A70;
}
.le-cover {
	width: 100%;
	display: block;
	background: #F3F6F4;
}
.le-article {
	padding: 8rpx 28rpx 8rpx;
	font-size: 28rpx;
	color: #2C3D37;
	line-height: 1.85;
}
.le-quiz {
	margin: 8rpx 20rpx 28rpx;
	padding: 28rpx 24rpx 24rpx;
	border-radius: 20rpx;
	background: linear-gradient(180deg, #F7FBF8 0%, #FFFFFF 100%);
	border: 1rpx solid #DCEDE4;
}
.le-quiz-head {
	margin-bottom: 20rpx;
}
.le-quiz-bar {
	width: 56rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #0F6E56;
	margin-bottom: 14rpx;
}
.le-quiz-title {
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #0F6E56;
}
.le-quiz-sub {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #6B7C75;
}
.le-q {
	margin-bottom: 28rpx;
}
.le-q-title {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #16332B;
	line-height: 1.55;
	margin-bottom: 16rpx;
}
.le-opt {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx 18rpx;
	margin-bottom: 12rpx;
	border-radius: 16rpx;
	background: #fff;
	border: 2rpx solid #E4EBE7;
}
.le-opt.is-on {
	border-color: #0F6E56;
	background: #EAF7F0;
}
.le-opt-key {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	background: #EEF3F0;
	color: #0F6E56;
	font-size: 24rpx;
	font-weight: 800;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.le-opt.is-on .le-opt-key {
	background: #0F6E56;
	color: #fff;
}
.le-opt-text {
	flex: 1;
	font-size: 26rpx;
	color: #2C3D37;
	line-height: 1.45;
}
.le-fail {
	padding: 16rpx 18rpx;
	border-radius: 12rpx;
	background: #FFF4E8;
	color: #B36B00;
	font-size: 24rpx;
	margin-bottom: 16rpx;
}
.le-submit {
	height: 88rpx;
	border-radius: 44rpx;
	background: linear-gradient(141deg, #1FA971 0%, #0F6E56 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 30rpx;
	font-weight: 700;
	box-shadow: 0 12rpx 24rpx rgba(15, 110, 86, 0.22);
}
.le-submit:active {
	opacity: 0.88;
}
</style>
