<template>
	<view class="sk-shell">
		<view class="sk-shell__ambient"></view>

		<view class="sk-nav" :style="{ paddingTop: statusBarPx + 'px' }">
			<view class="sk-nav__inner">
				<view class="sk-nav__back" hover-class="sk-nav__back--on" :hover-stay-time="80" @click="goBack">
					<u-icon name="arrow-left" size="36" color="#1d1d1f"></u-icon>
				</view>
				<text class="sk-nav__title">关于数康</text>
				<view class="sk-nav__ph"></view>
			</view>
		</view>

		<scroll-view scroll-y class="sk-scroll" :enhanced="true" :bounces="true" :show-scrollbar="false">
			<view class="sk-hero">
				<view class="sk-hero__grid"></view>
				<view class="sk-hero__orb sk-hero__orb--1"></view>
				<view class="sk-hero__orb sk-hero__orb--2"></view>
				<view class="sk-hero__orb sk-hero__orb--3"></view>
				<view class="sk-hero__content" :class="{ 'sk-in': heroReady }">
					<view class="sk-mark">
						<view class="sk-mark__glyph">
							<text class="sk-mark__glyph-t">SK</text>
						</view>
						<view class="sk-mark__ring"></view>
					</view>
					<text class="sk-hero__en">SHUKANG DATA TECHNOLOGY</text>
					<text class="sk-hero__cn">数康（大连）数据科技有限公司</text>
					<text class="sk-hero__tag">把低碳做成可度量、可运营的社区日常</text>
				</view>
				<view class="sk-hero__fade"></view>
			</view>

			<view class="sk-body">
				<view class="sk-stats glass-card" :class="{ 'sk-in': statsReady }">
					<view class="sk-stat" v-for="item in statItems" :key="item.key">
						<text class="sk-stat__num">{{ displayStats[item.key] }}</text>
						<text class="sk-stat__unit">{{ item.unit }}</text>
						<text class="sk-stat__label">{{ item.label }}</text>
					</view>
				</view>

				<view class="sk-section">
					<text class="sk-section__kicker">CAPABILITIES</text>
					<text class="sk-section__title">我们擅长什么</text>
					<scroll-view scroll-x class="sk-cap-scroll" :show-scrollbar="false">
						<view class="sk-cap-row">
							<view
								class="sk-cap"
								v-for="(cap, idx) in capabilities"
								:key="cap.id"
								:class="{ 'sk-in': capVisible[idx] }"
								:style="{ transitionDelay: idx * 80 + 'ms' }"
							>
								<view class="sk-cap__icon" :style="{ background: cap.tint }">
									<u-icon :name="cap.icon" size="40" color="#18AF38"></u-icon>
								</view>
								<text class="sk-cap__name">{{ cap.name }}</text>
								<text class="sk-cap__desc">{{ cap.desc }}</text>
								<view class="sk-cap__bar">
									<view class="sk-cap__bar-fill" :style="{ width: cap.level + '%' }"></view>
								</view>
							</view>
							<view class="sk-cap-row__tail"></view>
						</view>
					</scroll-view>
				</view>

				<view class="sk-section">
					<text class="sk-section__kicker">ARCHITECTURE</text>
					<text class="sk-section__title">产品与技术栈</text>
					<view class="sk-arch glass-card">
						<view class="sk-arch__layer" v-for="layer in archLayers" :key="layer.id">
							<view class="sk-arch__head">
								<text class="sk-arch__name">{{ layer.name }}</text>
								<text class="sk-arch__hint">{{ layer.hint }}</text>
							</view>
							<view class="sk-arch__tags">
								<text class="sk-arch__tag" v-for="tag in layer.tags" :key="tag">{{ tag }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="sk-section">
					<text class="sk-section__kicker">MILESTONES</text>
					<text class="sk-section__title">成长轨迹</text>
					<view class="sk-timeline">
						<view class="sk-timeline__line"></view>
						<view
							class="sk-timeline__item"
							v-for="(node, idx) in milestones"
							:key="node.year"
							:class="{ 'sk-in': timelineVisible[idx] }"
						>
							<view class="sk-timeline__dot"></view>
							<view class="sk-timeline__card glass-card">
								<text class="sk-timeline__year">{{ node.year }}</text>
								<text class="sk-timeline__text">{{ node.text }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="sk-section">
					<text class="sk-section__kicker">VALUES</text>
					<text class="sk-section__title">做事原则</text>
					<view class="sk-values">
						<view class="sk-value glass-card" v-for="val in values" :key="val.title">
							<text class="sk-value__idx">{{ val.idx }}</text>
							<text class="sk-value__title">{{ val.title }}</text>
							<text class="sk-value__desc">{{ val.desc }}</text>
						</view>
					</view>
				</view>

				<view class="sk-marquee-wrap">
					<view class="sk-marquee" :style="{ transform: 'translateX(' + marqueeOffset + 'px)' }">
						<text class="sk-marquee__item" v-for="(t, i) in marqueeDoubled" :key="'m' + i">{{ t }}</text>
					</view>
				</view>

				<view class="sk-footer">
					<text class="sk-footer__line">本小程序由数康团队设计与研发</text>
					<text class="sk-footer__copy">© Shukang Data · 专注社区低碳数字化</text>
				</view>
				<view class="sk-safe-bottom"></view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				statusBarPx: 20,
				heroReady: false,
				statsReady: false,
				capVisible: [false, false, false, false],
				timelineVisible: [false, false, false, false],
				statTargets: {
					communities: 120,
					users: 86000,
					events: 2400,
					points: 980,
				},
				displayStats: {
					communities: 0,
					users: 0,
					events: 0,
					points: 0,
				},
				statItems: [
					{ key: 'communities', unit: '+', label: '服务社区' },
					{ key: 'users', unit: '', label: '累计用户' },
					{ key: 'events', unit: '+', label: '低碳活动' },
					{ key: 'points', unit: '万+', label: '积分流转' },
				],
				capabilities: [
					{
						id: 1,
						icon: 'grid-fill',
						name: '场景化产品',
						desc: '垃圾分类、活动运营、积分商城与督导监测一体化',
						level: 92,
						tint: 'rgba(24,175,56,0.12)',
					},
					{
						id: 2,
						icon: 'server-fill',
						name: '数据中台',
						desc: '指标看板、导出与审计链路，支撑街道与社区决策',
						level: 88,
						tint: 'rgba(57,116,242,0.1)',
					},
					{
						id: 3,
						icon: 'photo-fill',
						name: '视觉与体验',
						desc: '小程序原生体验 + 后台协同，兼顾长辈与年轻用户',
						level: 90,
						tint: 'rgba(24,175,56,0.1)',
					},
					{
						id: 4,
						icon: 'lock-fill',
						name: '稳定交付',
						desc: '云存储、权限隔离与可观测运维，长期在线运行',
						level: 86,
						tint: 'rgba(0,0,0,0.04)',
					},
				],
				archLayers: [
					{
						id: 1,
						name: '体验层',
						hint: '触达居民',
						tags: ['微信小程序', '关怀版', '原生地图', '消息触达'],
					},
					{
						id: 2,
						name: '业务层',
						hint: '运营闭环',
						tags: ['活动签到', '垃圾分类真分', '积分权益', '志愿督导'],
					},
					{
						id: 3,
						name: '平台层',
						hint: '治理与扩展',
						tags: ['ThinkCMF', 'COS 图床', '数据导出', '多端工作台'],
					},
				],
				milestones: [
					{ year: '2017', text: '数康成立，聚焦数据应用与行业信息化' },
					{ year: '2020', text: '深入社区治理场景，探索低碳与垃圾分类数字化' },
					{ year: '2023', text: '一体化小程序 + 运营后台落地，服务多街道社区' },
					{ year: '2025', text: '持续迭代督导监测、积分商城与活动运营能力' },
				],
				values: [
					{ idx: '01', title: '可验证', desc: '每一次打卡、活动与积分变动都可追溯，拒绝「假数据好看」' },
					{ idx: '02', title: '可运营', desc: '产品为街道与社区同事设计，配置灵活、上手成本低' },
					{ idx: '03', title: '可长期', desc: '架构克制、文档齐全，让系统能陪社区一起长大' },
				],
				marqueeTags: [
					'uni-app',
					'Vue',
					'uView',
					'低碳活动',
					'积分体系',
					'云存储 COS',
					'数据可视化',
					'社区运营',
					'UX 设计',
					'可持续',
				],
				marqueeOffset: 0,
				marqueeTimer: null,
				counterTimer: null,
			}
		},
		computed: {
			marqueeDoubled() {
				return this.marqueeTags.concat(this.marqueeTags)
			},
		},
		onLoad() {
			const sys = uni.getSystemInfoSync()
			this.statusBarPx = sys.statusBarHeight || 20
		},
		onReady() {
			setTimeout(() => {
				this.heroReady = true
			}, 60)
			setTimeout(() => {
				this.statsReady = true
				this.runCounters()
			}, 280)
			setTimeout(() => {
				this.capVisible = this.capabilities.map(() => true)
			}, 420)
			setTimeout(() => {
				this.timelineVisible = this.milestones.map(() => true)
			}, 520)
			this.startMarquee()
		},
		onUnload() {
			this.stopMarquee()
			if (this.counterTimer) {
				clearInterval(this.counterTimer)
				this.counterTimer = null
			}
		},
		methods: {
			goBack() {
				uni.navigateBack({ fail: () => uni.switchTab({ url: '/pages/user/index' }) })
			},
			runCounters() {
				const duration = 1400
				const start = Date.now()
				const from = { ...this.displayStats }
				const to = { ...this.statTargets }
				if (this.counterTimer) {
					clearInterval(this.counterTimer)
				}
				this.counterTimer = setInterval(() => {
					const t = Math.min(1, (Date.now() - start) / duration)
					const ease = 1 - Math.pow(1 - t, 3)
					this.displayStats.communities = Math.round(from.communities + (to.communities - from.communities) * ease)
					this.displayStats.users = Math.round(from.users + (to.users - from.users) * ease)
					this.displayStats.events = Math.round(from.events + (to.events - from.events) * ease)
					this.displayStats.points = Math.round(from.points + (to.points - from.points) * ease)
					if (t >= 1) {
						clearInterval(this.counterTimer)
						this.counterTimer = null
						this.displayStats = { ...to }
					}
				}, 16)
			},
			startMarquee() {
				let x = 0
				this.marqueeTimer = setInterval(() => {
					x -= 0.6
					if (x < -1200) {
						x = 0
					}
					this.marqueeOffset = x
				}, 16)
			},
			stopMarquee() {
				if (this.marqueeTimer) {
					clearInterval(this.marqueeTimer)
					this.marqueeTimer = null
				}
			},
		},
	}
</script>

<style lang="scss" scoped>
	@import '@/static/styles/glass-ui.scss';

	.sk-shell {
		position: relative;
		height: 100vh;
		display: flex;
		flex-direction: column;
		overflow: hidden;
		background: $glass-page-bg;
		box-sizing: border-box;
	}
	.sk-shell__ambient {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 560rpx;
		background: linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(228, 232, 229, 0) 100%);
		pointer-events: none;
		z-index: 0;
	}

	.sk-nav {
		position: relative;
		z-index: 20;
		flex-shrink: 0;
		@include glass-nav-bar;
	}
	.sk-nav__inner {
		height: 44px;
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
		padding: 0 12rpx 0 8rpx;
	}
	.sk-nav__back {
		width: 72rpx;
		height: 72rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 50%;
		transition: background 0.2s ease;
	}
	.sk-nav__back--on {
		background: rgba(0, 0, 0, 0.05);
	}
	.sk-nav__title {
		font-size: 32rpx;
		font-weight: 600;
		color: $glass-text;
		letter-spacing: 1rpx;
	}
	.sk-nav__ph {
		width: 72rpx;
	}

	.sk-scroll {
		position: relative;
		z-index: 1;
		flex: 1;
		width: 100%;
		height: 0;
	}

	.sk-hero {
		position: relative;
		height: 520rpx;
		overflow: hidden;
		background: linear-gradient(155deg, #0f3d22 0%, #18af38 42%, #2d8f4e 100%);
	}
	.sk-hero__grid {
		position: absolute;
		inset: 0;
		opacity: 0.18;
		background-image: linear-gradient(rgba(255, 255, 255, 0.35) 1px, transparent 1px),
			linear-gradient(90deg, rgba(255, 255, 255, 0.35) 1px, transparent 1px);
		background-size: 48rpx 48rpx;
	}
	.sk-hero__orb {
		position: absolute;
		border-radius: 50%;
		filter: blur(2px);
		pointer-events: none;
	}
	.sk-hero__orb--1 {
		width: 320rpx;
		height: 320rpx;
		top: -80rpx;
		right: -60rpx;
		background: rgba(255, 255, 255, 0.14);
		animation: sk-float 8s ease-in-out infinite;
	}
	.sk-hero__orb--2 {
		width: 180rpx;
		height: 180rpx;
		bottom: 60rpx;
		left: -40rpx;
		background: rgba(255, 220, 120, 0.2);
		animation: sk-float 6s ease-in-out infinite reverse;
	}
	.sk-hero__orb--3 {
		width: 100rpx;
		height: 100rpx;
		top: 120rpx;
		left: 45%;
		background: rgba(255, 255, 255, 0.1);
		animation: sk-float 5s ease-in-out infinite;
	}
	.sk-hero__content {
		position: relative;
		z-index: 2;
		padding: 48rpx 40rpx 0;
		opacity: 0;
		transform: translateY(24rpx);
		transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
	}
	.sk-hero__content.sk-in {
		opacity: 1;
		transform: translateY(0);
	}
	.sk-mark {
		position: relative;
		width: 112rpx;
		height: 112rpx;
		margin-bottom: 28rpx;
	}
	.sk-mark__glyph {
		position: relative;
		z-index: 2;
		display: flex;
		width: 112rpx;
		height: 112rpx;
		align-items: center;
		justify-content: center;
		border-radius: 28rpx;
		background: rgba(255, 255, 255, 0.16);
		border: 1rpx solid rgba(255, 255, 255, 0.35);
		backdrop-filter: blur(12px);
	}
	.sk-mark__glyph-t {
		font-size: 40rpx;
		font-weight: 800;
		color: #fff;
		letter-spacing: 4rpx;
	}
	.sk-mark__ring {
		position: absolute;
		left: -8rpx;
		top: -8rpx;
		right: -8rpx;
		bottom: -8rpx;
		border-radius: 36rpx;
		border: 2rpx solid rgba(255, 255, 255, 0.35);
		animation: sk-pulse 2.8s ease-in-out infinite;
	}
	.sk-hero__en {
		display: block;
		font-size: 20rpx;
		color: rgba(255, 255, 255, 0.72);
		letter-spacing: 6rpx;
		margin-bottom: 12rpx;
	}
	.sk-hero__cn {
		display: block;
		font-size: 40rpx;
		font-weight: 700;
		color: #fff;
		line-height: 1.35;
		margin-bottom: 16rpx;
	}
	.sk-hero__tag {
		display: block;
		font-size: 26rpx;
		color: rgba(255, 255, 255, 0.88);
		line-height: 1.5;
		max-width: 620rpx;
	}
	.sk-hero__fade {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 0;
		height: 80rpx;
		background: linear-gradient(to bottom, transparent, $glass-page-bg);
	}

	.sk-body {
		position: relative;
		margin-top: -32rpx;
		padding: 0 24rpx;
		z-index: 3;
	}

	.sk-stats {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		padding: 28rpx 12rpx 8rpx;
		margin-bottom: 36rpx;
		opacity: 0;
		transform: translateY(20rpx);
		transition: opacity 0.65s ease, transform 0.65s ease;
	}
	.sk-stats.sk-in {
		opacity: 1;
		transform: translateY(0);
	}
	.sk-stat {
		width: 50%;
		padding: 16rpx 20rpx 24rpx;
		box-sizing: border-box;
	}
	.sk-stat__num {
		font-size: 44rpx;
		font-weight: 700;
		color: $glass-brand;
		font-variant-numeric: tabular-nums;
	}
	.sk-stat__unit {
		font-size: 22rpx;
		color: $glass-brand;
		margin-left: 4rpx;
		font-weight: 600;
	}
	.sk-stat__label {
		display: block;
		margin-top: 8rpx;
		font-size: 24rpx;
		color: $glass-text-tertiary;
	}

	.sk-section {
		margin-bottom: 40rpx;
	}
	.sk-section__kicker {
		display: block;
		font-size: 20rpx;
		color: $glass-brand;
		letter-spacing: 8rpx;
		font-weight: 600;
		margin-bottom: 8rpx;
	}
	.sk-section__title {
		display: block;
		font-size: 36rpx;
		font-weight: 700;
		color: $glass-text;
		margin-bottom: 24rpx;
	}

	.sk-cap-scroll {
		width: 100%;
		white-space: nowrap;
	}
	.sk-cap-row {
		display: inline-flex;
		flex-direction: row;
		padding-bottom: 8rpx;
	}
	.sk-cap-row__tail {
		width: 24rpx;
		flex-shrink: 0;
	}
	.sk-cap {
		display: inline-flex;
		flex-direction: column;
		width: 420rpx;
		margin-right: 20rpx;
		padding: 28rpx;
		border-radius: $glass-radius-xl;
		@include glass-surface-strong(40px);
		vertical-align: top;
		white-space: normal;
		opacity: 0;
		transform: translateY(16rpx);
		transition: opacity 0.55s ease, transform 0.55s ease;
	}
	.sk-cap.sk-in {
		opacity: 1;
		transform: translateY(0);
	}
	.sk-cap__icon {
		width: 72rpx;
		height: 72rpx;
		border-radius: 20rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 20rpx;
	}
	.sk-cap__name {
		font-size: 30rpx;
		font-weight: 700;
		color: $glass-text;
		margin-bottom: 10rpx;
	}
	.sk-cap__desc {
		font-size: 24rpx;
		color: $glass-text-secondary;
		line-height: 1.55;
		flex: 1;
		margin-bottom: 20rpx;
	}
	.sk-cap__bar {
		height: 8rpx;
		border-radius: 8rpx;
		background: rgba(24, 175, 56, 0.12);
		overflow: hidden;
	}
	.sk-cap__bar-fill {
		height: 100%;
		border-radius: 8rpx;
		background: linear-gradient(90deg, #18af38, #5cd87a);
		transition: width 1s cubic-bezier(0.22, 1, 0.36, 1);
	}

	.sk-arch {
		padding: 8rpx 28rpx 20rpx;
	}
	.sk-arch__layer {
		padding: 24rpx 0;
		border-bottom: 1rpx solid $glass-hairline;
	}
	.sk-arch__layer:last-child {
		border-bottom: none;
	}
	.sk-arch__head {
		display: flex;
		flex-direction: row;
		align-items: baseline;
		justify-content: space-between;
		margin-bottom: 16rpx;
	}
	.sk-arch__name {
		font-size: 30rpx;
		font-weight: 700;
		color: $glass-text;
	}
	.sk-arch__hint {
		font-size: 22rpx;
		color: $glass-text-tertiary;
	}
	.sk-arch__tags {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
	}
	.sk-arch__tag {
		font-size: 22rpx;
		color: $glass-text-secondary;
		padding: 10rpx 20rpx;
		margin: 0 12rpx 12rpx 0;
		border-radius: 999rpx;
		background: rgba(255, 255, 255, 0.65);
		border: 1rpx solid rgba(0, 0, 0, 0.05);
	}

	.sk-timeline {
		position: relative;
		padding-left: 8rpx;
	}
	.sk-timeline__line {
		position: absolute;
		left: 18rpx;
		top: 12rpx;
		bottom: 12rpx;
		width: 2rpx;
		background: linear-gradient(to bottom, $glass-brand, rgba(24, 175, 56, 0.15));
	}
	.sk-timeline__item {
		position: relative;
		padding-left: 48rpx;
		margin-bottom: 24rpx;
		opacity: 0;
		transform: translateX(-12rpx);
		transition: opacity 0.5s ease, transform 0.5s ease;
	}
	.sk-timeline__item.sk-in {
		opacity: 1;
		transform: translateX(0);
	}
	.sk-timeline__dot {
		position: absolute;
		left: 8rpx;
		top: 28rpx;
		width: 22rpx;
		height: 22rpx;
		border-radius: 50%;
		background: #fff;
		border: 4rpx solid $glass-brand;
		box-shadow: 0 0 0 6rpx rgba(24, 175, 56, 0.15);
	}
	.sk-timeline__card {
		padding: 24rpx 28rpx;
	}
	.sk-timeline__year {
		display: block;
		font-size: 26rpx;
		font-weight: 700;
		color: $glass-brand;
		margin-bottom: 8rpx;
	}
	.sk-timeline__text {
		font-size: 26rpx;
		color: $glass-text-secondary;
		line-height: 1.55;
	}

	.sk-values {
		display: flex;
		flex-direction: column;
	}
	.sk-value {
		padding: 28rpx 32rpx;
		margin-bottom: 16rpx;
	}
	.sk-value__idx {
		font-size: 22rpx;
		font-weight: 700;
		color: rgba(24, 175, 56, 0.55);
		letter-spacing: 4rpx;
	}
	.sk-value__title {
		display: block;
		margin-top: 8rpx;
		font-size: 32rpx;
		font-weight: 700;
		color: $glass-text;
	}
	.sk-value__desc {
		display: block;
		margin-top: 12rpx;
		font-size: 26rpx;
		color: $glass-text-secondary;
		line-height: 1.55;
	}

	.sk-marquee-wrap {
		overflow: hidden;
		margin: 16rpx 0 32rpx;
		padding: 20rpx 0;
		border-radius: $glass-radius-lg;
		background: rgba(255, 255, 255, 0.45);
		border: 1rpx solid rgba(255, 255, 255, 0.7);
	}
	.sk-marquee {
		display: flex;
		flex-direction: row;
		white-space: nowrap;
		will-change: transform;
	}
	.sk-marquee__item {
		display: inline-block;
		padding: 0 28rpx;
		font-size: 24rpx;
		color: $glass-text-tertiary;
		letter-spacing: 2rpx;
	}

	.sk-footer {
		text-align: center;
		padding: 16rpx 0 24rpx;
	}
	.sk-footer__line {
		display: block;
		font-size: 24rpx;
		color: $glass-text-secondary;
	}
	.sk-footer__copy {
		display: block;
		margin-top: 10rpx;
		font-size: 22rpx;
		color: $glass-text-tertiary;
	}
	.sk-safe-bottom {
		height: calc(32rpx + env(safe-area-inset-bottom));
	}

	@keyframes sk-float {
		0%,
		100% {
			transform: translate(0, 0);
		}
		50% {
			transform: translate(12rpx, -16rpx);
		}
	}
	@keyframes sk-pulse {
		0%,
		100% {
			opacity: 0.45;
			transform: scale(1);
		}
		50% {
			opacity: 1;
			transform: scale(1.04);
		}
	}
</style>

<style lang="scss">
	page {
		height: 100%;
		overflow: hidden;
		background: #e4e8e5;
	}
</style>
