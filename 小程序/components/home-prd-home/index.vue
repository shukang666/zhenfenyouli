<template>
	<view class="prd-home">
		<home-prd-header embedded :location-label="locationLabel" />
		<!-- PRD view-home：hero + below-hero（摘自 甄分友礼-首页设计/页面源码/index.html） -->
		<view class="prd-hero">
			<view class="prd-hero-copy">
				<view class="prd-eyebrow"><text class="prd-eyebrow-dot"></text> 生活的可能，就在附近</view>
				<view class="prd-h1">
					<text class="prd-h1-line">在身边，</text>
					<view class="prd-h1-line">
						<text class="prd-h1-phrase">遇见更多</text>
						<text class="prd-h1-possible">可能</text>
						<text class="prd-h1-phrase">。</text>
					</view>
				</view>
				<view class="prd-desc">一场活动，一次相遇。</view>
				<view class="prd-desc">有些美好，正在日常附近发生。</view>
				<view class="prd-cta" hover-class="prd-cta--on" @tap="$emit('activities')">
					<text>看看身边的活动</text>
					<view class="prd-cta-icon"><u-icon name="arrow-rightward" size="28" color="#fff"></u-icon></view>
				</view>
				<view class="prd-footnote"><text class="prd-spark">✳</text> 不用去远方，也有新发现。</view>
			</view>
			<view class="prd-hero-visual">
				<image class="prd-hero-image" :src="heroImageUrl" mode="aspectFill"></image>
				<view class="prd-image-label"><text class="prd-live-dot"></text> 此刻，生活在发生</view>
				<text class="prd-corner-note">TAKE A LITTLE LOOK AROUND.</text>
				<view class="prd-photo-caption">
					<view class="prd-photo-icon"><u-icon name="tags" size="32" color="#668044"></u-icon></view>
					<view>
						<text class="prd-photo-small">散步、交流，也参与一点新鲜事</text>
						<text class="prd-photo-strong">一片公共空间，许多种日常。</text>
					</view>
				</view>
				<view class="prd-visual-index"><text>01</text> / NEARBY MOMENTS</view>
			</view>
		</view>

		<view class="prd-below">
			<view class="prd-personal">
				<view class="prd-personal-intro">
					<text class="prd-flower">✳</text>
					<view>
						<view class="prd-personal-title">
							<text>我的日常</text>
							<text class="prd-demo-pill" v-if="!isLogin">体验账户</text>
						</view>
						<text class="prd-personal-sub">让每一次参与，都有回响。</text>
					</view>
				</view>
				<view class="prd-stats">
					<view class="prd-stat" @tap="$emit('my-activities')">
						<text class="prd-stat-num">{{ joinedCount }}</text>
						<view class="prd-stat-label">我的活动<u-icon name="arrow-right" size="16" color="#8b927f"></u-icon></view>
					</view>
					<view class="prd-stat" @tap="$emit('points')">
						<text class="prd-stat-num">{{ pointsDisplay }}</text>
						<view class="prd-stat-label">可用积分<u-icon name="arrow-right" size="16" color="#8b927f"></u-icon></view>
					</view>
					<view class="prd-stat" @tap="$emit('following')">
						<text class="prd-stat-num">{{ followingCount }}</text>
						<view class="prd-stat-label">我的关注<u-icon name="arrow-right" size="16" color="#8b927f"></u-icon></view>
					</view>
				</view>
			</view>

			<view class="prd-actions">
				<view class="prd-action" @tap="$emit('activities')">
					<view class="prd-action-icon prd-action-icon--green"><u-icon name="map" size="40" color="#627f3a"></u-icon></view>
					<view class="prd-action-text">
						<text class="prd-action-title">找个活动</text>
						<text class="prd-action-sub">看看附近，正在发生什么</text>
					</view>
				</view>
				<view class="prd-action" @tap="$emit('create')">
					<view class="prd-action-icon prd-action-icon--cream"><u-icon name="plus" size="40" color="#8a8256"></u-icon></view>
					<view class="prd-action-text">
						<text class="prd-action-title">发起一场活动</text>
						<text class="prd-action-sub">把你的热爱，变成相聚</text>
					</view>
				</view>
				<view class="prd-action" @tap="$emit('health')">
					<view class="prd-action-icon prd-action-icon--blue"><u-icon name="heart" size="40" color="#658b85"></u-icon></view>
					<view class="prd-action-text">
						<text class="prd-action-title">测测健康</text>
						<text class="prd-action-sub">花一点时间，了解自己</text>
					</view>
				</view>
				<view class="prd-action" @tap="$emit('benefits')">
					<view class="prd-action-icon prd-action-icon--peach"><u-icon name="gift" size="40" color="#af8867"></u-icon></view>
					<view class="prd-action-text">
						<text class="prd-action-title">用用积分</text>
						<text class="prd-action-sub">参与的心意，日常的好礼</text>
					</view>
				</view>
			</view>

			<view class="prd-featured">
				<view class="prd-section-head">
					<view>
						<text class="prd-eyebrow-sm">IN THE SPOTLIGHT</text>
						<view class="prd-section-title">
							<text>正在主推</text>
							<text class="prd-section-sub">从身边的一次参与开始。</text>
						</view>
					</view>
					<view class="prd-text-link" @tap="$emit('activities')">
						<text>全部活动</text>
						<u-icon name="arrow-right" size="24" color="#26382d"></u-icon>
					</view>
				</view>
				<view class="prd-campaign" @tap="$emit('featured')">
					<view class="prd-campaign-art" id="prd-campaign-art-slot">
						<!-- #ifdef H5 -->
						<view class="prd-campaign-svg-host" v-html="campaignSvgHtml"></view>
						<!-- #endif -->
						<!-- #ifdef MP-WEIXIN -->
						<canvas
							type="2d"
							id="prd-campaign-canvas"
							class="prd-campaign-canvas"
						></canvas>
						<!-- #endif -->
						<image
							v-if="campaignImageSrc"
							class="prd-campaign-img"
							:src="campaignImageSrc"
							mode="widthFix"
							@error="onCampaignImageError"
						></image>
						<text class="prd-event-tag">当前主推</text>
					</view>
					<view class="prd-campaign-copy">
						<view class="prd-campaign-label"><text class="prd-eyebrow-dot"></text> 公益环保 · 当前主推</view>
						<text class="prd-campaign-name">绿动生活领航员</text>
						<text class="prd-campaign-summary">把一件小事，认真做下去。</text>
						<view class="prd-tags">
							<text class="prd-tag">真分打卡</text>
							<text class="prd-tag">点位巡查</text>
							<text class="prd-tag">积分权益</text>
						</view>
						<view class="prd-campaign-bottom">
							<view class="prd-campaign-loc">
								<u-icon name="map" size="24" color="#87957a"></u-icon>
								<text>七贤岭街道</text>
							</view>
							<view class="prd-solid-btn">了解并申请</view>
						</view>
					</view>
				</view>
			</view>

			<view class="prd-site-footer">
				<text class="prd-footer-brand">甄分友礼 <text class="prd-footer-sub">在身边，遇见更多可能。</text></text>
			</view>
			<view class="prd-contact-wrap">
				<button class="prd-contact-link" open-type="contact" hover-class="prd-contact-link--hover">联系客服</button>
			</view>
		</view>
	</view>
</template>

<script>
import settings from '@/common/settings.js'
import prdNavigatorCampaignImage from '@/common/home-prd-navigator-campaign-image.js'
import {
	buildCampaignArtworkSvgMarkup,
	renderCampaignArtworkForMp,
	getCampaignArtworkFallbackSrc,
} from '@/common/prdCampaignArtwork.js'
import HomePrdHeader from '@/components/home-prd-header/index.vue'

const IMG_BASE = settings.staticAssetBase + 'planning/'

export default {
	name: 'HomePrdHome',
	components: { HomePrdHeader },
	props: {
		isLogin: { type: Boolean, default: false },
		points: { type: [Number, String], default: 0 },
		joinedCount: { type: Number, default: 0 },
		followingCount: { type: Number, default: 0 },
		locationLabel: { type: String, default: '' },
	},
	data() {
		return {
			campaignImageSrc: '',
			campaignSvgHtml: buildCampaignArtworkSvgMarkup(prdNavigatorCampaignImage),
		}
	},
	mounted() {
		this.initCampaignArtwork()
	},
	computed: {
		heroImageUrl() {
			return IMG_BASE + 'home-public-community-life.webp'
		},
		pointsDisplay() {
			const n = Number(this.points)
			return Number.isFinite(n) ? Math.floor(n) : 0
		},
	},
	methods: {
		initCampaignArtwork() {
			// #ifdef H5
			return
			// #endif
			// #ifdef MP-WEIXIN
			this.$nextTick(() => {
				setTimeout(() => this.renderMpCampaignArt(), 80)
			})
			// #endif
			// #ifndef MP-WEIXIN
			// #ifndef H5
			this.campaignImageSrc = getCampaignArtworkFallbackSrc()
			// #endif
			// #endif
		},
		async renderMpCampaignArt() {
			try {
				const path = await renderCampaignArtworkForMp(
					this,
					'#prd-campaign-canvas',
					'#prd-campaign-art-slot'
				)
				this.campaignImageSrc = path
			} catch (e) {
				console.warn('home-prd campaign canvas render fail', e)
				this.campaignImageSrc = getCampaignArtworkFallbackSrc()
			}
		},
		onCampaignImageError(e) {
			console.warn('home-prd campaign image error', e)
			const fallback = getCampaignArtworkFallbackSrc()
			if (this.campaignImageSrc !== fallback) {
				this.campaignImageSrc = fallback
			}
		},
	},
}
</script>

<style lang="scss" scoped>
$ink: #26382d;
$green: #365940;
$green-dark: #294c34;
$line: #e5e8dd;
$bg: #f7f8f3;

.prd-home {
	position: relative;
	z-index: 1;
	padding: 0 40rpx calc(200rpx + env(safe-area-inset-bottom));
	color: $ink;
	background: transparent;
}

.prd-eyebrow {
	display: flex;
	align-items: center;
	gap: 18rpx;
	font-size: 16rpx;
	letter-spacing: 4rpx;
	font-weight: 600;
	color: #738264;
	margin-bottom: 36rpx;
}
.prd-eyebrow-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #8b9e61;
}
.prd-eyebrow-sm {
	display: block;
	font-size: 12rpx;
	letter-spacing: 3.2rpx;
	color: #8a957b;
	margin-bottom: 10rpx;
}

.prd-hero {
	display: flex;
	flex-direction: column;
	gap: 52rpx;
	padding-top: 24rpx;
	padding-bottom: 72rpx;
}
.prd-h1-line {
	display: block;
	font-size: 86rpx;
	line-height: 1.34;
	font-weight: 600;
	letter-spacing: -2.8rpx;
	color: $ink;
}
.prd-h1-phrase {
	font-size: 86rpx;
	line-height: 1.34;
	font-weight: 600;
	letter-spacing: -2.8rpx;
	color: $ink;
}
.prd-h1-possible {
	position: relative;
	display: inline;
	color: $green;
	font-size: 86rpx;
	font-weight: 600;
	border-bottom: 8rpx solid #b8ce83;
}
.prd-desc {
	font-size: 22rpx;
	line-height: 1.9;
	letter-spacing: 2rpx;
	color: #75806d;
	margin-top: 8rpx;
}
.prd-cta {
	display: inline-flex;
	align-items: center;
	justify-content: space-between;
	gap: 50rpx;
	background: $green;
	color: #fff;
	border-radius: 999rpx;
	padding: 14rpx 14rpx 14rpx 38rpx;
	font-size: 24rpx;
	letter-spacing: 2rpx;
	margin-top: 44rpx;
	min-height: 92rpx;
	box-shadow: 0 10rpx 24rpx rgba(49, 77, 52, 0.06);
}
.prd-cta--on {
	opacity: 0.92;
}
.prd-cta-icon {
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.1);
	display: flex;
	align-items: center;
	justify-content: center;
}
.prd-footnote {
	display: flex;
	align-items: center;
	gap: 16rpx;
	font-size: 20rpx;
	color: #859074;
	margin-top: 44rpx;
}
.prd-spark {
	font-size: 36rpx;
	color: #a0ad83;
}

.prd-hero-visual {
	height: 570rpx;
	position: relative;
	border-radius: 14rpx 116rpx 14rpx 14rpx;
	margin: 0 0 24rpx;
	background: #dce6df;
	overflow: visible;
}
.prd-hero-image {
	width: 100%;
	height: 100%;
	border-radius: inherit;
}
.prd-image-label {
	position: absolute;
	left: 30rpx;
	top: 34rpx;
	display: flex;
	align-items: center;
	gap: 14rpx;
	background: rgba(255, 255, 255, 0.86);
	border: 1rpx solid rgba(255, 255, 255, 0.36);
	border-radius: 40rpx;
	font-size: 16rpx;
	letter-spacing: 2.4rpx;
	padding: 12rpx 20rpx;
	color: #456345;
	z-index: 1;
}
.prd-live-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #75905c;
}
.prd-corner-note {
	position: absolute;
	top: 66rpx;
	right: -26rpx;
	font-size: 10rpx;
	letter-spacing: 3.2rpx;
	writing-mode: vertical-rl;
	color: #859375;
	z-index: 2;
}
.prd-photo-caption {
	display: flex;
	align-items: center;
	gap: 20rpx;
	position: absolute;
	left: 26rpx;
	bottom: 34rpx;
	border: 1rpx solid rgba(255, 255, 255, 0.66);
	border-radius: 14rpx;
	padding: 22rpx 24rpx;
	background: rgba(255, 253, 243, 0.94);
	box-shadow: 0 16rpx 50rpx rgba(39, 63, 37, 0.08);
	z-index: 2;
}
.prd-photo-icon {
	width: 64rpx;
	height: 64rpx;
	background: #e9efda;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}
.prd-photo-small {
	display: block;
	font-size: 14rpx;
	letter-spacing: 1.4rpx;
	color: #8b927f;
}
.prd-photo-strong {
	display: block;
	font-size: 22rpx;
	letter-spacing: 1rpx;
	font-weight: 500;
	color: $ink;
	margin-top: 4rpx;
}
.prd-visual-index {
	position: absolute;
	right: 0;
	bottom: -40rpx;
	font-size: 10rpx;
	letter-spacing: 2.6rpx;
	color: #9a9e91;
}
.prd-visual-index text {
	font-size: 16rpx;
	color: #59684a;
	margin-right: 14rpx;
}

.prd-personal {
	display: flex;
	flex-direction: column;
	gap: 46rpx;
	background: #edf0e5;
	border: 1rpx solid #e3e7da;
	padding: 36rpx;
	border-radius: 16rpx;
}
.prd-personal-intro {
	display: flex;
	align-items: center;
	gap: 24rpx;
}
.prd-flower {
	font-size: 60rpx;
	line-height: 1;
	color: #9caf77;
}
.prd-personal-title {
	display: flex;
	align-items: center;
	gap: 22rpx;
	font-size: 26rpx;
	font-weight: 600;
	letter-spacing: 2rpx;
}
.prd-demo-pill {
	font-size: 14rpx;
	border: 1rpx solid #d7ddcd;
	color: #8d9680;
	border-radius: 8rpx;
	padding: 2rpx 8rpx;
	font-weight: 400;
}
.prd-personal-sub {
	display: block;
	font-size: 18rpx;
	color: #77836a;
	margin-top: 6rpx;
}
.prd-stats {
	display: flex;
	width: 100%;
}
.prd-stat {
	flex: 1;
	text-align: center;
	border-left: 1rpx solid #dde2d4;
	padding: 0 8rpx;
}
.prd-stat:first-child {
	border-left: none;
}
.prd-stat-num {
	display: block;
	font-size: 48rpx;
	font-weight: 500;
	color: #425938;
	line-height: 1.1;
}
.prd-stat-label {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	font-size: 16rpx;
	color: #77836a;
	margin-top: 14rpx;
}

.prd-actions {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 18rpx;
	padding: 50rpx 0 48rpx;
}
.prd-action {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	gap: 22rpx;
}
.prd-action-icon {
	width: 86rpx;
	height: 86rpx;
	border-radius: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.prd-action-icon--green {
	background: #e8efdb;
}
.prd-action-icon--cream {
	background: #f0eddf;
}
.prd-action-icon--blue {
	background: #e5edeb;
}
.prd-action-icon--peach {
	background: #f2e8de;
}
.prd-action-title {
	display: block;
	font-size: 20rpx;
	font-weight: 500;
	color: $ink;
}
.prd-action-sub {
	display: none;
}

.prd-featured {
	padding-bottom: 60rpx;
}
.prd-section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 34rpx;
	gap: 20rpx;
}
.prd-section-title {
	display: flex;
	flex-direction: column;
	gap: 8rpx;
	font-size: 40rpx;
	font-weight: 500;
	letter-spacing: 2rpx;
}
.prd-section-sub {
	font-size: 20rpx;
	font-weight: 400;
	color: #7f8a74;
}
.prd-text-link {
	display: flex;
	align-items: center;
	gap: 16rpx;
	font-size: 18rpx;
	color: $ink;
}

.prd-campaign {
	border: 1rpx solid #e0e6d6;
	background: #fff;
	border-radius: 18rpx;
	overflow: hidden;
}
.prd-campaign-art {
	position: relative;
	overflow: hidden;
	width: 100%;
	background: #e6edda;
	min-height: 426rpx;
}
.prd-campaign-svg-host {
	position: absolute;
	inset: 0;
}
.prd-campaign-svg-host ::v-deep svg,
.prd-campaign-svg-host ::v-deep .campaign-source-image {
	display: block;
	width: 100%;
	height: 100%;
}
.prd-campaign-canvas {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 426rpx;
	opacity: 0;
	z-index: 0;
	pointer-events: none;
}
.prd-campaign-img {
	display: block;
	width: 100%;
	vertical-align: top;
	position: relative;
	z-index: 1;
}
.prd-event-tag {
	position: absolute;
	top: 20rpx;
	left: 18rpx;
	z-index: 2;
	background: rgba(255, 255, 255, 0.93);
	font-size: 14rpx;
	color: #506244;
	border-radius: 8rpx;
	padding: 6rpx 14rpx;
}
.prd-campaign-copy {
	padding: 46rpx;
}
.prd-campaign-label {
	display: flex;
	align-items: center;
	gap: 16rpx;
	font-size: 18rpx;
	letter-spacing: 3.2rpx;
	color: #7a8c63;
}
.prd-campaign-name {
	display: block;
	font-size: 50rpx;
	font-weight: 500;
	letter-spacing: 2rpx;
	margin-top: 20rpx;
	line-height: 1.5;
}
.prd-campaign-summary {
	display: block;
	font-size: 22rpx;
	color: #7d8a70;
	margin-top: 18rpx;
}
.prd-tags {
	display: flex;
	flex-wrap: wrap;
	gap: 18rpx;
	margin-top: 28rpx;
}
.prd-tag {
	font-size: 18rpx;
	padding: 8rpx 18rpx;
	background: #f0f4e8;
	color: #82976a;
	border-radius: 8rpx;
}
.prd-campaign-bottom {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 42rpx;
	gap: 24rpx;
}
.prd-campaign-loc {
	display: flex;
	align-items: center;
	gap: 12rpx;
	font-size: 20rpx;
	color: #87957a;
}
.prd-solid-btn {
	background: $green;
	color: #fff;
	border-radius: 52rpx;
	padding: 18rpx 38rpx;
	font-size: 22rpx;
	letter-spacing: 1rpx;
}

.prd-site-footer {
	padding: 34rpx 0 20rpx;
	border-top: 1rpx solid #e5e8df;
	font-size: 16rpx;
	color: #b1b6a7;
}
.prd-footer-brand {
	font-size: 18rpx;
	font-weight: 500;
	color: #88947a;
}
.prd-footer-sub {
	font-size: 14rpx;
	color: #a2aa97;
	margin-left: 12rpx;
}
.prd-contact-wrap {
	text-align: center;
	padding-bottom: 16rpx;
}
.prd-contact-link {
	display: inline-block;
	margin: 0;
	padding: 8rpx 24rpx;
	background: transparent;
	border: none;
	line-height: 1.4;
	font-size: 24rpx;
	color: #3974f2;
	text-decoration: underline;
}
.prd-contact-link::after {
	border: none;
}
.prd-contact-link--hover {
	opacity: 0.75;
}
</style>
