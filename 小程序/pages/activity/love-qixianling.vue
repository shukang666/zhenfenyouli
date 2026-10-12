<template>
	<view class="container">
		<view class="qxl-hero" :style="heroBgStyle">
			<view class="qxl-hero-content">
				<text class="qxl-hero-title">我的七贤岭</text>
				<text class="qxl-hero-subtitle">认识 · 发现</text>
			</view>
		</view>

		<view class="qxl-body">
			<!-- 统一专题区：认识（学考）与 发现（100瞬间）为同一路径两步，避免双主题对立 -->
			<view class="qxl-journey">
				<view class="qxl-journey-intro">
					<text class="qxl-journey-intro-text">先读懂七贤岭，再用镜头记录与分享</text>
				</view>

				<!-- 有「发现」步：网格对齐，左侧竖线随学考卡片增高 -->
				<view class="qxl-journey-grid" v-if="momentActive || momentLoading">
					<view class="qxl-grid-rail qxl-grid-rail--learn">
						<view class="qxl-step-badge">
							<text class="qxl-step-badge-text">认识</text>
						</view>
						<view class="qxl-step-line qxl-step-line--grow"></view>
					</view>
					<view class="qxl-grid-card-hit" @tap="goLearnExam">
						<view class="qxl-step-card qxl-step-card--learn qxl-step-card--learn-feature">
							<view class="qxl-learn-cover">
								<image
									v-if="learnCardCoverResolved"
									class="qxl-learn-cover-img"
									:src="learnCardCoverResolved"
									mode="aspectFill"
									lazy-load
								></image>
								<view v-else class="qxl-learn-cover-ph">
									<text class="qxl-learn-cover-ph-icon">📖</text>
									<text class="qxl-learn-cover-ph-text">七贤岭 · 读与测</text>
								</view>
							</view>
							<view class="qxl-learn-content">
								<text class="qxl-learn-kicker">七贤岭学考</text>
							<text class="qxl-learn-lead">人口、人文、地理等六大主题，按模块选你关心的内容。</text>
							<text class="qxl-learn-detail">每篇先读短文，读完在文末做本篇测验。</text>
							<view class="qxl-learn-tags">
								<text class="qxl-learn-tag">六大主题</text>
								<text class="qxl-learn-tag">边读边测</text>
								<text class="qxl-learn-tag">随选随学</text>
							</view>
								<view class="qxl-learn-foot">
									<view class="qxl-learn-progress">
										<view class="qxl-step-progress-track qxl-step-progress-track--lg">
											<view class="qxl-step-progress-fill" :style="{ width: learnProgressWidth }"></view>
										</view>
										<text class="qxl-step-progress-text">{{ learnProgressText }}</text>
									</view>
									<view class="qxl-learn-cta">
										<text>{{ learnCtaText }}</text>
										<text class="qxl-learn-cta-arrow">›</text>
									</view>
								</view>
							</view>
						</view>
					</view>

					<view class="qxl-grid-rail qxl-grid-rail--discover">
						<view class="qxl-step-line qxl-step-line--join"></view>
						<view class="qxl-step-badge qxl-step-badge--soft">
							<text class="qxl-step-badge-text">发现</text>
						</view>
					</view>
					<view class="qxl-grid-card-hit" @tap="gotoMomentActive">
						<view class="qxl-step-card qxl-step-card--moment" v-if="momentActive">
							<view class="qxl-moment-thumb" v-if="momentActive.thumb">
								<image class="qxl-moment-thumb-img" :src="momentActive.thumb" mode="aspectFill" lazy-load></image>
							</view>
							<view class="qxl-step-main">
								<text class="qxl-step-title">{{ momentActive.name }}</text>
								<text class="qxl-step-desc">参与征集，定格七贤岭的每一个瞬间</text>
								<text class="qxl-step-link">进入活动 ›</text>
							</view>
						</view>
						<view class="qxl-step-card qxl-step-card--placeholder" v-else-if="!momentLoading">
							<text class="qxl-empty-text">100个瞬间活动筹备中</text>
						</view>
						<view class="qxl-step-card qxl-step-card--placeholder" v-else>
							<text class="qxl-empty-text">加载中...</text>
						</view>
					</view>
				</view>

				<view class="qxl-journey-step qxl-journey-step--solo" v-else @tap="goLearnExam">
					<view class="qxl-step-rail">
						<view class="qxl-step-badge">
							<text class="qxl-step-badge-text">认识</text>
						</view>
					</view>
					<view class="qxl-step-card qxl-step-card--learn qxl-step-card--learn-feature">
						<view class="qxl-learn-cover">
							<image
								v-if="learnCardCoverResolved"
								class="qxl-learn-cover-img"
								:src="learnCardCoverResolved"
								mode="aspectFill"
								lazy-load
							></image>
							<view v-else class="qxl-learn-cover-ph">
								<text class="qxl-learn-cover-ph-icon">📖</text>
								<text class="qxl-learn-cover-ph-text">七贤岭 · 读与测</text>
							</view>
						</view>
						<view class="qxl-learn-content">
							<text class="qxl-learn-kicker">七贤岭学考</text>
							<text class="qxl-learn-lead">人口、人文、地理等六大主题，按模块选你关心的内容。</text>
							<text class="qxl-learn-detail">每篇先读短文，读完在文末做本篇测验。</text>
							<view class="qxl-learn-tags">
								<text class="qxl-learn-tag">六大主题</text>
								<text class="qxl-learn-tag">边读边测</text>
								<text class="qxl-learn-tag">随选随学</text>
							</view>
							<view class="qxl-learn-foot">
								<view class="qxl-learn-progress">
									<view class="qxl-step-progress-track qxl-step-progress-track--lg">
										<view class="qxl-step-progress-fill" :style="{ width: learnProgressWidth }"></view>
									</view>
									<text class="qxl-step-progress-text">{{ learnProgressText }}</text>
								</view>
								<view class="qxl-learn-cta">
									<text>{{ learnCtaText }}</text>
									<text class="qxl-learn-cta-arrow">›</text>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>

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
import settings from '@/common/settings.js'

// 学考卡片顶图（COS static/img/，换图后重跑 data/cos_upload_one_qxl_learn_cover.php 或覆盖桶内同名对象）
const QXL_LEARN_CARD_COVER = settings.staticAssetBase + 'img/qxl-learn-cover.png'

export default {
	data() {
		return {
			heroBgImage: '',
			learnCardCover: QXL_LEARN_CARD_COVER,
			learnPassed: 0,
			learnTotal: 0,
			momentActive: null,
			momentLoading: true,
		}
	},
	computed: {
		learnCardCoverResolved() {
			const raw = (this.learnCardCover || '').trim()
			if (!raw) return ''
			if (typeof this.$u !== 'undefined' && this.$u.func && typeof this.$u.func.resolveAssetUrl === 'function') {
				return this.$u.func.resolveAssetUrl(raw)
			}
			return raw
		},
		learnCtaText() {
			if (this.learnTotal && this.learnPassed >= this.learnTotal) return '回顾模块'
			if (this.learnPassed > 0) return '继续选学'
			return '进入学考'
		},
		heroBgStyle() {
			if (this.heroBgImage) {
				return `background-image: linear-gradient(180deg, rgba(15,110,86,0.55) 0%, rgba(15,110,86,0.2) 100%), url(${this.heroBgImage}); background-size: cover; background-position: center;`
			}
			return ''
		},
		learnProgressWidth() {
			if (!this.learnTotal) return '12%'
			return Math.max(12, Math.round((this.learnPassed / this.learnTotal) * 100)) + '%'
		},
		learnProgressText() {
			if (!this.learnTotal) return '六大板块 · 每模块抽 3 题'
			if (this.learnPassed >= this.learnTotal) return '六大板块已全部通关'
			return '板块通关 ' + this.learnPassed + '/' + this.learnTotal
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
			if (!this.momentActive || !this.momentActive.id) return
			const item = this.momentActive
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
			this.$u.api.getQxlLearnModules().then(res => {
				if (res.code == 1) {
					this.learnPassed = res.data.passed_count || 0
					this.learnTotal = res.data.module_count || res.data.article_count || 0
				}
			}).catch(() => {})
		},
	},
}
</script>

<style lang="scss" scoped>
$qxl-green: #0f6e56;
$qxl-green-mid: #1fa971;
$qxl-green-soft: #e8f5ef;
$qxl-green-border: #c5e8d8;

.container {
	min-height: 100vh;
	background: #eef3f0;
}

.qxl-hero {
	position: relative;
	height: 300rpx;
	background: linear-gradient(145deg, #0d5c48 0%, $qxl-green-mid 48%, #52b788 100%);
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
	text-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.2);
}
.qxl-hero-subtitle {
	font-size: 26rpx;
	opacity: 0.92;
	letter-spacing: 6rpx;
}

.qxl-body {
	padding: 0 20rpx 100rpx;
	margin-top: -28rpx;
	position: relative;
	z-index: 10;
}

.qxl-journey {
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx 24rpx 20rpx;
	box-shadow: 0 8rpx 32rpx rgba(15, 110, 86, 0.08);
	border: 1rpx solid $qxl-green-border;
}
.qxl-journey-intro {
	padding: 0 8rpx 24rpx;
	border-bottom: 1rpx solid #eef2ef;
	margin-bottom: 8rpx;
}
.qxl-journey-intro-text {
	font-size: 24rpx;
	color: #5a6b63;
	line-height: 1.5;
}

.qxl-journey-grid {
	display: grid;
	grid-template-columns: 56rpx 1fr;
	column-gap: 16rpx;
	row-gap: 16rpx;
	padding: 20rpx 0 8rpx;
}
.qxl-grid-rail {
	display: flex;
	flex-direction: column;
	align-items: center;
	min-height: 0;
}
.qxl-grid-rail--learn {
	align-self: stretch;
}
.qxl-grid-card-hit:active .qxl-step-card {
	opacity: 0.92;
}

.qxl-journey-step {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	gap: 16rpx;
	padding: 20rpx 0 8rpx;
}
.qxl-journey-step--solo:active .qxl-step-card {
	opacity: 0.92;
}

.qxl-step-rail {
	width: 56rpx;
	flex-shrink: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.qxl-step-badge {
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, $qxl-green 0%, $qxl-green-mid 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4rpx 12rpx rgba(15, 110, 86, 0.25);
}
.qxl-step-badge--soft {
	background: $qxl-green-soft;
	box-shadow: none;
	border: 2rpx solid $qxl-green-border;
}
.qxl-step-badge-text {
	font-size: 22rpx;
	font-weight: 700;
	color: #fff;
}
.qxl-step-badge--soft .qxl-step-badge-text {
	color: $qxl-green;
}
.qxl-step-line {
	flex-shrink: 0;
	width: 4rpx;
	border-radius: 4rpx;
	background: $qxl-green-mid;
}
.qxl-step-line--grow {
	flex: 1;
	min-height: 24rpx;
	margin-top: 8rpx;
	margin-bottom: -16rpx;
	padding-bottom: 16rpx;
	box-sizing: content-box;
}
.qxl-step-line--join {
	height: 16rpx;
	min-height: 16rpx;
	margin: 0;
}

.qxl-step-card {
	flex: 1;
	min-width: 0;
	border-radius: 20rpx;
	padding: 24rpx 22rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 16rpx;
}
.qxl-step-card--learn {
	background: $qxl-green-soft;
	border: 1rpx solid $qxl-green-border;
}
.qxl-step-card--learn-feature {
	flex-direction: column;
	align-items: stretch;
	padding: 0;
	overflow: hidden;
	gap: 0;
}

.qxl-learn-cover {
	width: 100%;
	height: 280rpx;
	background: linear-gradient(135deg, #0d5c48 0%, $qxl-green-mid 55%, #7fd4b0 100%);
	position: relative;
}
.qxl-learn-cover-img {
	width: 100%;
	height: 280rpx;
	display: block;
}
.qxl-learn-cover-ph {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	background: linear-gradient(135deg, rgba(13, 92, 72, 0.92) 0%, rgba(31, 169, 113, 0.85) 100%);
}
.qxl-learn-cover-ph-icon {
	font-size: 56rpx;
	line-height: 1;
	opacity: 0.95;
}
.qxl-learn-cover-ph-text {
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.92);
	letter-spacing: 4rpx;
	font-weight: 600;
}

.qxl-learn-content {
	padding: 28rpx 24rpx 24rpx;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.qxl-learn-kicker {
	font-size: 34rpx;
	font-weight: 800;
	color: #1a2e28;
	line-height: 1.35;
}
.qxl-learn-lead {
	font-size: 26rpx;
	color: #3d524a;
	line-height: 1.55;
	font-weight: 600;
}
.qxl-learn-detail {
	font-size: 24rpx;
	color: #5a6b63;
	line-height: 1.55;
}
.qxl-learn-tags {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	gap: 12rpx;
	margin-top: 4rpx;
}
.qxl-learn-tag {
	font-size: 22rpx;
	color: $qxl-green;
	background: #fff;
	border: 1rpx solid $qxl-green-border;
	padding: 8rpx 18rpx;
	border-radius: 999rpx;
	font-weight: 600;
}
.qxl-learn-foot {
	margin-top: 12rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid rgba(15, 110, 86, 0.12);
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.qxl-learn-progress {
	width: 100%;
}
.qxl-step-progress-track--lg {
	height: 12rpx;
}
.qxl-learn-cta {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	height: 80rpx;
	border-radius: 40rpx;
	background: linear-gradient(90deg, $qxl-green 0%, $qxl-green-mid 100%);
	color: #fff;
	font-size: 28rpx;
	font-weight: 700;
	box-shadow: 0 8rpx 20rpx rgba(15, 110, 86, 0.22);
}
.qxl-learn-cta-arrow {
	font-size: 36rpx;
	font-weight: 400;
	line-height: 1;
	margin-top: -2rpx;
}
.qxl-step-card--moment {
	background: #f8fbf9;
	border: 1rpx solid $qxl-green-border;
	flex-wrap: wrap;
}
.qxl-step-card--placeholder {
	background: #f8fbf9;
	border: 1rpx dashed $qxl-green-border;
	justify-content: center;
	min-height: 120rpx;
}

.qxl-step-main {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}
.qxl-step-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e28;
	line-height: 1.35;
}
.qxl-step-desc {
	font-size: 22rpx;
	color: #5a6b63;
	line-height: 1.45;
}
.qxl-step-link {
	font-size: 24rpx;
	font-weight: 600;
	color: $qxl-green;
	margin-top: 4rpx;
}
.qxl-step-progress {
	margin-top: 8rpx;
}
.qxl-step-progress-track {
	height: 8rpx;
	border-radius: 8rpx;
	background: rgba(15, 110, 86, 0.12);
	overflow: hidden;
}
.qxl-step-progress-fill {
	height: 100%;
	border-radius: 8rpx;
	background: linear-gradient(90deg, $qxl-green 0%, $qxl-green-mid 100%);
}
.qxl-step-progress-text {
	display: block;
	margin-top: 6rpx;
	font-size: 20rpx;
	color: #5a7a6e;
}

.qxl-moment-thumb {
	width: 120rpx;
	height: 120rpx;
	flex-shrink: 0;
	border-radius: 14rpx;
	overflow: hidden;
	border: 1rpx solid $qxl-green-border;
}
.qxl-moment-thumb-img {
	width: 120rpx;
	height: 120rpx;
	display: block;
}

.qxl-showcase-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 20rpx;
	padding: 26rpx 24rpx;
	background: #fff;
	border-radius: 20rpx;
	border: 1rpx solid #e4e6eb;
}
.qxl-showcase-bar:active {
	opacity: 0.9;
}
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
	color: #1a2e28;
}
.qxl-showcase-sub {
	font-size: 22rpx;
	color: #8a8a8a;
}
.qxl-showcase-arrow {
	font-size: 40rpx;
	color: #bcc0c4;
	padding-left: 12rpx;
}

.qxl-empty-text {
	font-size: 24rpx;
	color: #8a8a8a;
}
</style>
