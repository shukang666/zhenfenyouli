<template>
	<view class="discover">
		<view class="discover-nav glass-nav">
			<text class="discover-nav__title">发现活动</text>
			<view class="discover-search glass-search" hover-class="discover-search--on" @click="$emit('search')">
				<u-icon name="search" size="28" color="#18AF38"></u-icon>
				<text class="discover-search__ph">搜索活动、主题或主办方</text>
			</view>
		</view>

		<scroll-view scroll-x class="discover-chips" :show-scrollbar="false">
			<view class="discover-chips__inner">
				<view
					v-for="chip in filterOptions"
					:key="chip.key"
					class="discover-chip"
					:class="{ 'discover-chip--on': filter === chip.key }"
					@click="$emit('filter', chip.key)"
				>
					<text>{{ chip.label }}</text>
				</view>
			</view>
		</scroll-view>

		<view class="discover-cates">
			<view class="discover-cates__inner">
				<view
					class="discover-cate"
					v-for="cate in categories"
					:key="cate.id"
					hover-class="discover-cate--on"
					:hover-stay-time="80"
					@click="$emit('open-cate', cate)"
				>
					<text class="discover-cate__emoji">{{ cate.emoji }}</text>
					<text class="discover-cate__name">{{ cate.shortName || cate.name }}</text>
				</view>
			</view>
		</view>

		<view
			class="discover-month"
			v-if="monthTheme"
			hover-class="discover-month--on"
			:hover-stay-time="80"
			@click="$emit('open-cate', monthTheme)"
		>
			<view class="discover-month__base" aria-hidden="true"></view>
			<view class="discover-month__aurora discover-month__aurora--a" aria-hidden="true"></view>
			<view class="discover-month__aurora discover-month__aurora--b" aria-hidden="true"></view>
			<view class="discover-month__noise" aria-hidden="true"></view>
			<view class="discover-month__rings" aria-hidden="true">
				<view class="discover-month__ring discover-month__ring--1"></view>
				<view class="discover-month__ring discover-month__ring--2"></view>
				<view class="discover-month__ring discover-month__ring--3"></view>
			</view>
			<view class="discover-month__hero" aria-hidden="true">
				<view class="discover-month__hero-glow"></view>
				<view class="discover-month__hero-glass">
					<text class="discover-month__hero-emoji">{{ monthTheme.emoji || "🏃" }}</text>
				</view>
				<view class="discover-month__spark discover-month__spark--1">✦</view>
				<view class="discover-month__spark discover-month__spark--2">✦</view>
			</view>
			<view class="discover-month__copy">
				<view class="discover-month__badge">
					<view class="discover-month__badge-dot" aria-hidden="true"></view>
					<text>{{ monthBadgeText }}</text>
				</view>
				<text class="discover-month__title">本月运动健康季</text>
				<text class="discover-month__sub">下班后动一动，让身体和心情都松下来</text>
				<view class="discover-month__chips">
					<text class="discover-month__chip">夜跑</text>
					<text class="discover-month__chip">瑜伽</text>
					<text class="discover-month__chip">轻运动</text>
				</view>
				<view class="discover-month__cta">
					<text class="discover-month__cta-shine" aria-hidden="true"></text>
					<text class="discover-month__cta-label">查看专题</text>
					<u-icon name="arrow-right" size="22" color="#0d4a8f"></u-icon>
				</view>
			</view>
		</view>

		<view class="discover-section">
			<view class="discover-section__head">
				<text class="discover-section__title">正在报名</text>
				<view class="discover-section__filter" @click="$emit('more-filter')">
					<text>筛选</text>
				</view>
			</view>

			<view class="discover-card" v-for="item in list" :key="item.id" @click="$emit('open', item)">
				<view class="discover-card__thumb">
					<u-image
						v-if="item._thumb || item.thumb"
						width="128rpx"
						height="128rpx"
						:src="item._thumb || item.thumb"
						mode="aspectFill"
						border-radius="16"
					></u-image>
					<text v-else class="discover-card__thumb-ph">🎯</text>
				</view>
				<view class="discover-card__body">
					<text class="discover-card__name u-line-2">{{ item.name }}</text>
					<text class="discover-card__meta u-line-2">{{ item._date_line }}</text>
					<view class="discover-card__tags" v-if="item._tags && item._tags.length">
						<text class="discover-card__tag" v-for="(tag, idx) in item._tags" :key="idx">{{ tag }}</text>
					</view>
					<view class="discover-card__foot">
						<text class="discover-card__quota">{{ item._quota_hint }}</text>
						<view class="discover-card__btn" @click.stop="$emit('open', item)">
							<text>{{ item._btn_text || '去报名' }}</text>
						</view>
					</view>
				</view>
			</view>

			<view class="discover-load" v-if="loading">
				<u-loading mode="circle" color="#18AF38"></u-loading>
				<text>加载中…</text>
			</view>
			<view class="discover-load discover-load--muted" v-else-if="!list.length">
				<text>暂无符合条件的活动</text>
			</view>
			<view class="discover-load discover-load--muted" v-else-if="!hasMore">
				<text>没有更多了</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: "ActivityTabDiscover",
	props: {
		filter: { type: String, default: "recommend" },
		categories: { type: Array, default: () => [] },
		monthTheme: { type: Object, default: null },
		list: { type: Array, default: () => [] },
		loading: { type: Boolean, default: false },
		hasMore: { type: Boolean, default: true },
	},
	data() {
		return {
			filterOptions: [
				{ key: "recommend", label: "推荐" },
				{ key: "weekly", label: "本周" },
				{ key: "nearby", label: "附近" },
				{ key: "enrolling", label: "报名中" },
				{ key: "free", label: "免费" },
			],
		};
	},
	computed: {
		monthBadgeText() {
			const m = new Date().getMonth() + 1;
			return m + "月 · 运动健康专题";
		},
	},
};
</script>

<style lang="scss" scoped>
@import "@/static/styles/glass-ui.scss";

.discover {
	position: relative;
	z-index: 1;
	padding-bottom: 32rpx;
}

.discover-nav {
	padding: 16rpx 24rpx;
	padding-top: calc(env(safe-area-inset-top) + 16rpx);
}
.discover-nav__title {
	display: block;
	text-align: center;
	font-size: 36rpx;
	font-weight: 800;
	color: #1a2e22;
	margin-bottom: 16rpx;
}
.discover-search {
	display: flex;
	align-items: center;
	height: 76rpx;
	padding: 0 20rpx;
	border-radius: 999rpx;
}
.discover-search--on {
	opacity: 0.92;
}
.discover-search__ph {
	margin-left: 12rpx;
	font-size: 26rpx;
	color: #9aa39c;
}

.discover-chips {
	margin-top: 8rpx;
	white-space: nowrap;
}
.discover-chips__inner {
	display: inline-flex;
	padding: 0 24rpx 8rpx;
	gap: 12rpx;
}
.discover-chip {
	display: inline-flex;
	padding: 10rpx 26rpx;
	border-radius: 999rpx;
	background: #ffffff;
	font-size: 24rpx;
	font-weight: 600;
	color: #5f7166;
	box-shadow: 0 4rpx 12rpx rgba(22, 53, 36, 0.05);
}
.discover-chip--on {
	background: #1a2420;
	color: #ffffff;
}

.discover-cates {
	margin-top: 8rpx;
	padding: 8rpx 24rpx 16rpx;
}
.discover-cates__inner {
	display: flex;
	flex-direction: row;
	align-items: flex-start;
	justify-content: space-between;
}
.discover-cate {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.discover-cate--on {
	opacity: 0.85;
}
.discover-cate__emoji {
	font-size: 44rpx;
	line-height: 1.1;
}
.discover-cate__name {
	margin-top: 8rpx;
	font-size: 22rpx;
	font-weight: 600;
	color: #3d4f44;
	text-align: center;
	white-space: normal;
	width: 100%;
}

.discover-month {
	position: relative;
	margin: 12rpx 24rpx 24rpx;
	padding: 32rpx 28rpx 28rpx;
	border-radius: 28rpx;
	overflow: hidden;
	min-height: 268rpx;
	box-shadow:
		0 20rpx 48rpx rgba(15, 58, 120, 0.28),
		0 4rpx 0 rgba(255, 255, 255, 0.12) inset;
	transform: translateZ(0);
}
.discover-month--on {
	transform: scale(0.985);
	opacity: 0.96;
}
.discover-month__base {
	position: absolute;
	inset: 0;
	background: linear-gradient(145deg, #0a2f6b 0%, #1a5fbf 38%, #3d8fd9 72%, #7ec8ff 100%);
}
.discover-month__aurora {
	position: absolute;
	width: 140%;
	height: 80%;
	border-radius: 50%;
	filter: blur(40rpx);
	opacity: 0.55;
	pointer-events: none;
}
.discover-month__aurora--a {
	left: -30%;
	top: -20%;
	background: radial-gradient(circle, rgba(126, 200, 255, 0.9) 0%, transparent 65%);
	animation: discover-month-drift-a 8s ease-in-out infinite;
}
.discover-month__aurora--b {
	right: -35%;
	bottom: -35%;
	background: radial-gradient(circle, rgba(45, 220, 180, 0.55) 0%, transparent 60%);
	animation: discover-month-drift-b 10s ease-in-out infinite;
}
.discover-month__noise {
	position: absolute;
	inset: 0;
	opacity: 0.07;
	background-image: radial-gradient(rgba(255, 255, 255, 0.9) 0.6px, transparent 0.6px);
	background-size: 8rpx 8rpx;
	pointer-events: none;
}
.discover-month__rings {
	position: absolute;
	right: -8rpx;
	top: 50%;
	width: 240rpx;
	height: 240rpx;
	margin-top: -120rpx;
	pointer-events: none;
}
.discover-month__ring {
	position: absolute;
	inset: 0;
	border-radius: 50%;
	border: 2rpx solid rgba(255, 255, 255, 0.14);
}
.discover-month__ring--1 {
	inset: 0;
	animation: discover-month-spin 18s linear infinite;
}
.discover-month__ring--2 {
	inset: 24rpx;
	border-color: rgba(255, 255, 255, 0.1);
	animation: discover-month-spin 14s linear infinite reverse;
}
.discover-month__ring--3 {
	inset: 48rpx;
	border-color: rgba(255, 255, 255, 0.08);
	animation: discover-month-spin 22s linear infinite;
}
.discover-month__hero {
	position: absolute;
	right: 28rpx;
	top: 50%;
	width: 152rpx;
	height: 152rpx;
	margin-top: -76rpx;
	z-index: 2;
	pointer-events: none;
}
.discover-month__hero-glow {
	position: absolute;
	inset: -16rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, transparent 70%);
	animation: discover-month-pulse 3s ease-in-out infinite;
}
.discover-month__hero-glass {
	position: absolute;
	inset: 0;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(145deg, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0.08) 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.35);
	box-shadow:
		0 12rpx 32rpx rgba(0, 40, 90, 0.25),
		inset 0 2rpx 8rpx rgba(255, 255, 255, 0.45);
	backdrop-filter: blur(12px);
}
.discover-month__hero-emoji {
	font-size: 64rpx;
	line-height: 1;
	filter: drop-shadow(0 6rpx 12rpx rgba(0, 30, 70, 0.35));
	animation: discover-month-bob 2.8s ease-in-out infinite;
}
.discover-month__spark {
	position: absolute;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.85);
	text-shadow: 0 0 12rpx rgba(255, 255, 255, 0.8);
}
.discover-month__spark--1 {
	top: 4rpx;
	right: 8rpx;
	animation: discover-month-twinkle 2.2s ease-in-out infinite;
}
.discover-month__spark--2 {
	bottom: 12rpx;
	left: 0;
	font-size: 18rpx;
	animation: discover-month-twinkle 2.8s ease-in-out 0.6s infinite;
}
.discover-month__copy {
	position: relative;
	z-index: 3;
	max-width: 420rpx;
	padding-right: 120rpx;
}
.discover-month__badge {
	display: inline-flex;
	align-items: center;
	gap: 8rpx;
	padding: 6rpx 16rpx 6rpx 12rpx;
	margin-bottom: 14rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.16);
	border: 1rpx solid rgba(255, 255, 255, 0.28);
	font-size: 20rpx;
	font-weight: 600;
	color: rgba(255, 255, 255, 0.95);
	letter-spacing: 0.02em;
}
.discover-month__badge-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #5dffb8;
	box-shadow: 0 0 12rpx rgba(93, 255, 184, 0.9);
}
.discover-month__title {
	display: block;
	font-size: 38rpx;
	font-weight: 800;
	color: #ffffff;
	line-height: 1.25;
	text-shadow: 0 4rpx 16rpx rgba(0, 35, 80, 0.35);
	letter-spacing: 0.02em;
}
.discover-month__sub {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.9);
	line-height: 1.5;
}
.discover-month__chips {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 16rpx;
}
.discover-month__chip {
	padding: 4rpx 14rpx;
	border-radius: 8rpx;
	font-size: 20rpx;
	font-weight: 600;
	color: rgba(255, 255, 255, 0.92);
	background: rgba(0, 0, 0, 0.12);
	border: 1rpx solid rgba(255, 255, 255, 0.15);
}
.discover-month__cta {
	position: relative;
	display: inline-flex;
	align-items: center;
	gap: 8rpx;
	margin-top: 20rpx;
	padding: 12rpx 26rpx;
	border-radius: 999rpx;
	overflow: hidden;
	background: linear-gradient(180deg, #ffffff 0%, #e8f4ff 100%);
	box-shadow: 0 8rpx 20rpx rgba(0, 50, 110, 0.22);
}
.discover-month__cta-shine {
	position: absolute;
	top: 0;
	left: -60%;
	width: 50%;
	height: 100%;
	background: linear-gradient(
		90deg,
		transparent,
		rgba(255, 255, 255, 0.65),
		transparent
	);
	animation: discover-month-shine 3.5s ease-in-out infinite;
}
.discover-month__cta-label {
	position: relative;
	z-index: 1;
	font-size: 24rpx;
	font-weight: 800;
	color: #0d4a8f;
}

@keyframes discover-month-drift-a {
	0%,
	100% {
		transform: translate(0, 0) scale(1);
	}
	50% {
		transform: translate(6%, 8%) scale(1.08);
	}
}
@keyframes discover-month-drift-b {
	0%,
	100% {
		transform: translate(0, 0);
	}
	50% {
		transform: translate(-8%, -6%);
	}
}
@keyframes discover-month-spin {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}
@keyframes discover-month-pulse {
	0%,
	100% {
		opacity: 0.6;
		transform: scale(1);
	}
	50% {
		opacity: 1;
		transform: scale(1.06);
	}
}
@keyframes discover-month-bob {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-6rpx);
	}
}
@keyframes discover-month-twinkle {
	0%,
	100% {
		opacity: 0.35;
		transform: scale(0.85);
	}
	50% {
		opacity: 1;
		transform: scale(1.1);
	}
}
@keyframes discover-month-shine {
	0% {
		left: -60%;
	}
	45%,
	100% {
		left: 120%;
	}
}

.discover-section {
	padding: 0 24rpx;
}
.discover-section__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
}
.discover-section__title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a2e22;
}
.discover-section__filter text {
	font-size: 24rpx;
	font-weight: 600;
	color: #18af38;
}

.discover-card {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	padding: 20rpx;
	margin-bottom: 16rpx;
	border-radius: 20rpx;
	background: #ffffff;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.discover-card__thumb {
	flex: 0 0 128rpx;
	width: 128rpx;
	height: 128rpx;
	border-radius: 16rpx;
	overflow: hidden;
	background: #eef4f0;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}
.discover-card__thumb ::v-deep .u-image,
.discover-card__thumb ::v-deep image {
	width: 128rpx !important;
	height: 128rpx !important;
	display: block;
}
.discover-card__thumb-ph {
	font-size: 48rpx;
}
.discover-card__body {
	flex: 1;
	min-width: 0;
	margin-left: 18rpx;
	display: flex;
	flex-direction: column;
}
.discover-card__name {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
}
.discover-card__meta {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #8a9690;
	line-height: 1.4;
}
.discover-card__tags {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 10rpx;
}
.discover-card__tag {
	padding: 4rpx 12rpx;
	border-radius: 8rpx;
	background: #f0f2f5;
	font-size: 20rpx;
	color: #6a756f;
}
.discover-card__foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: auto;
	padding-top: 12rpx;
}
.discover-card__quota {
	font-size: 22rpx;
	font-weight: 600;
	color: #e86b20;
	flex: 1;
	min-width: 0;
	margin-right: 12rpx;
}
.discover-card__btn {
	flex-shrink: 0;
	padding: 10rpx 22rpx;
	border-radius: 999rpx;
	background: #18af38;
}
.discover-card__btn text {
	font-size: 24rpx;
	font-weight: 700;
	color: #ffffff;
}

.discover-load {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 24rpx 0 40rpx;
	font-size: 24rpx;
	color: #8a9690;
}
.discover-load--muted text {
	color: #aab4ae;
}
</style>
