<template>
	<view class="container">
		<view class="qxl-hero" :style="heroBgStyle">
			<view class="qxl-hero-content">
				<text class="qxl-hero-title">我和七贤岭</text>
				<text class="qxl-hero-subtitle">认识 · 发现</text>
			</view>
		</view>

		<view class="qxl-body">
			<!-- 七贤岭学考（固定入口） -->
			<view class="qxl-section">
				<view class="qxl-section-head">
					<view class="qxl-section-dot" style="background:#0F6E56"></view>
					<text class="qxl-section-emoji">📖</text>
					<text class="qxl-section-name">认识七贤岭</text>
					<text class="qxl-section-sub">学考融合</text>
				</view>
				<view class="qxl-learn-hero" @tap="goLearnExam">
					<view class="qxl-learn-hero-copy">
						<text class="qxl-learn-kicker">学考融合</text>
						<text class="qxl-learn-name">七贤岭学考</text>
						<text class="qxl-learn-desc">读完本篇立刻测验，过关解锁下一篇</text>
						<view class="qxl-learn-progress">
							<view class="qxl-learn-progress-track">
								<view class="qxl-learn-progress-fill" :style="{width: learnProgressWidth}"></view>
							</view>
							<text class="qxl-learn-progress-text">{{ learnProgressText }}</text>
						</view>
					</view>
					<view class="qxl-learn-cta">
						<text>开始</text>
					</view>
				</view>
			</view>

			<!-- 100个瞬间 -->
			<view class="qxl-section" v-if="momentActive || momentLoading">
				<view class="qxl-section-head">
					<view class="qxl-section-dot" style="background:#FF9500"></view>
					<text class="qxl-section-emoji">📷</text>
					<text class="qxl-section-name">发现七贤岭</text>
					<text class="qxl-section-sub">记录美好</text>
				</view>
				<view class="qxl-moment-hero" v-if="momentActive" @tap="gotoMomentActive">
					<view class="qxl-moment-cover">
						<image class="qxl-moment-img" :src="momentActive.thumb" mode="aspectFill" lazy-load></image>
					</view>
					<view class="qxl-moment-copy">
						<text class="qxl-moment-title">{{ momentActive.name }}</text>
						<text class="qxl-moment-desc">参与征集，定格七贤岭的每一个瞬间</text>
						<text class="qxl-moment-go">进入活动 ›</text>
					</view>
				</view>
				<view class="qxl-empty" v-else-if="!momentLoading">
					<text class="qxl-empty-text">100个瞬间活动筹备中</text>
				</view>
				<view class="qxl-empty" v-else>
					<text class="qxl-empty-text">加载中...</text>
				</view>
			</view>

			<!-- 其余活动 → 活动风采 -->
			<view class="qxl-showcase-bar" @tap="gotoShowcase">
				<view class="qxl-showcase-copy">
					<text class="qxl-showcase-title">往期七贤岭活动</text>
					<text class="qxl-showcase-sub">已收录至活动风采，点击查看回顾</text>
				</view>
				<text class="qxl-showcase-arrow">›</text>
			</view>
		</view>
	</view>
</template>

<script>
import { openActiveDetailWithContext, isWebDisplayActive } from '@/common/activeOpen.js'

export default {
	data() {
		return {
			heroBgImage: '',
			learnPassed: 0,
			learnTotal: 0,
			momentActive: null,
			momentLoading: true,
		}
	},
	computed: {
		heroBgStyle() {
			if (this.heroBgImage) {
				return `background-image: linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.05) 100%), url(${this.heroBgImage}); background-size: cover; background-position: center;`
			}
			return ''
		},
		learnProgressWidth() {
			if (!this.learnTotal) return '12%'
			return Math.max(12, Math.round((this.learnPassed / this.learnTotal) * 100)) + '%'
		},
		learnProgressText() {
			if (!this.learnTotal) return '边学边考 · 过关解锁'
			if (this.learnPassed >= this.learnTotal) return '已全部通关'
			return '已通关 ' + this.learnPassed + '/' + this.learnTotal + ' 篇'
		},
	},
	onLoad() {
		this.loadHeroBg()
		this.loadHub()
	},
	onShow() {
		this.loadLearnProgress()
	},
	methods: {
		async loadHeroBg() {
			try {
				const res = await this.$u.api.getPageConfig()
				const qxlHeroBg = res.data?.qxl_hero_bg || ''
				if (qxlHeroBg) {
					this.heroBgImage = qxlHeroBg
				}
			} catch (e) {}
		},
		loadHub() {
			this.momentLoading = true
			this.$u.api.get_love_qixianling_hub().then(res => {
				this.momentActive = (res.data && res.data.moment_active) || null
			}).catch(() => {
				this.momentActive = null
			}).finally(() => {
				this.momentLoading = false
			})
		},
		goLearnExam() {
			this.$u.route('/pages/activity/qxl-learn-exam')
		},
		gotoMomentActive() {
			const item = this.momentActive
			if (!item || !item.id) return
			if (isWebDisplayActive(item)) {
				const issueContext = typeof this.$u.api.issue_web_context === 'function'
					? (id) => this.$u.api.issue_web_context({ active_id: id })
					: null
				openActiveDetailWithContext(item, null, issueContext)
				return
			}
			this.$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)
		},
		gotoShowcase() {
			this.$u.route('/pages/activity/showcase-list')
		},
		loadLearnProgress() {
			this.$u.api.getQxlLearnPath().then(res => {
				if (res.code == 1) {
					this.learnPassed = res.data.passed_count || 0
					this.learnTotal = res.data.total_count || 0
				}
			}).catch(() => {})
		},
	},
}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #F0F2F5;
}

.qxl-hero {
	position: relative;
	height: 320rpx;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #fff;
}
.qxl-hero-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
}
.qxl-hero-title {
	font-size: 44rpx;
	font-weight: 800;
	letter-spacing: 4rpx;
	text-shadow: 0 4rpx 8rpx rgba(0,0,0,0.25);
}
.qxl-hero-subtitle {
	font-size: 26rpx;
	margin-top: 10rpx;
	opacity: 0.9;
	letter-spacing: 2rpx;
}

.qxl-body {
	padding: 24rpx 16rpx 100rpx;
	margin-top: -24rpx;
	position: relative;
	z-index: 10;
}

.qxl-section {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx 20rpx 16rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.06);
	border: 1rpx solid #E4E6EB;
}
.qxl-section-head {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 4rpx 4rpx 16rpx;
	border-bottom: 1rpx solid #F0F2F5;
	margin-bottom: 16rpx;
}
.qxl-section-dot {
	width: 8rpx;
	height: 28rpx;
	border-radius: 4rpx;
}
.qxl-section-emoji {
	font-size: 32rpx;
}
.qxl-section-name {
	font-size: 30rpx;
	font-weight: 700;
	color: #050505;
}
.qxl-section-sub {
	font-size: 22rpx;
	color: #8A8A8A;
	margin-left: 4rpx;
}

.qxl-learn-hero {
	position: relative;
	overflow: hidden;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 20rpx;
	padding: 32rpx 28rpx;
	border-radius: 20rpx;
	background: linear-gradient(141deg, #0F6E56 0%, #1FA971 100%);
}
.qxl-learn-hero:active { transform: scale(0.98); }
.qxl-learn-hero-copy {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}
.qxl-learn-kicker {
	font-size: 20rpx;
	letter-spacing: 3rpx;
	color: rgba(255,255,255,0.75);
}
.qxl-learn-name {
	font-size: 36rpx;
	font-weight: 800;
	color: #fff;
}
.qxl-learn-desc {
	font-size: 22rpx;
	color: rgba(255,255,255,0.86);
	line-height: 1.45;
}
.qxl-learn-progress { margin-top: 12rpx; }
.qxl-learn-progress-track {
	height: 10rpx;
	border-radius: 10rpx;
	background: rgba(255,255,255,0.22);
	overflow: hidden;
}
.qxl-learn-progress-fill {
	height: 100%;
	border-radius: 10rpx;
	background: #fff;
}
.qxl-learn-progress-text {
	display: block;
	margin-top: 8rpx;
	font-size: 20rpx;
	color: rgba(255,255,255,0.86);
}
.qxl-learn-cta {
	flex-shrink: 0;
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.18);
	border: 2rpx solid rgba(255,255,255,0.35);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 26rpx;
	font-weight: 700;
}

.qxl-moment-hero {
	display: flex;
	align-items: stretch;
	border-radius: 16rpx;
	overflow: hidden;
	background: #FAFBFC;
	border: 1rpx solid #EDEEF0;
}
.qxl-moment-hero:active { opacity: 0.88; }
.qxl-moment-cover {
	width: 220rpx;
	flex-shrink: 0;
}
.qxl-moment-img {
	width: 220rpx;
	height: 180rpx;
	display: block;
}
.qxl-moment-copy {
	flex: 1;
	padding: 20rpx 18rpx;
	display: flex;
	flex-direction: column;
	justify-content: center;
	gap: 10rpx;
	min-width: 0;
}
.qxl-moment-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #050505;
	line-height: 1.35;
}
.qxl-moment-desc {
	font-size: 22rpx;
	color: #65676B;
	line-height: 1.45;
}
.qxl-moment-go {
	font-size: 24rpx;
	font-weight: 600;
	color: #FF9500;
	margin-top: 4rpx;
}

.qxl-showcase-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 28rpx 24rpx;
	background: #fff;
	border-radius: 20rpx;
	border: 1rpx solid #E4E6EB;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.05);
}
.qxl-showcase-bar:active { opacity: 0.9; }
.qxl-showcase-copy {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}
.qxl-showcase-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #050505;
}
.qxl-showcase-sub {
	font-size: 22rpx;
	color: #8A8A8A;
}
.qxl-showcase-arrow {
	font-size: 40rpx;
	color: #BCC0C4;
	padding-left: 12rpx;
}

.qxl-empty {
	padding: 24rpx 0;
	text-align: center;
}
.qxl-empty-text {
	font-size: 24rpx;
	color: #8A8A8A;
}
</style>
