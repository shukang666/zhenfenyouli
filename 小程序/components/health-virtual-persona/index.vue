<template>
	<view
		class="hvp"
		:class="[
			'hvp--' + size,
			{
				'hvp--speaking': speaking,
				'hvp--dark-caption': darkCaption,
				'hvp--embedded': embedded
			}
		]"
	>
		<view class="hvp-stage">
			<!-- #ifdef MP-WEIXIN -->
			<canvas
				v-if="!useCssFallback"
				class="hvp-lottie"
				type="2d"
				:id="canvasId"
			></canvas>
			<!-- #endif -->
			<view v-if="showCssHeart" class="hvp-css-fallback">
				<view class="hvp-halo hvp-halo--a"></view>
				<view class="hvp-halo hvp-halo--b"></view>
				<view class="hvp-motion">
					<view class="hvp-heart">
						<view class="hvp-heart-shell">
							<view class="hvp-heart-glass"></view>
							<view class="hvp-heart-glass hvp-heart-glass--inner"></view>
							<view class="hvp-heart-shine"></view>
							<view class="hvp-heart-tint"></view>
						</view>
					</view>
				</view>
				<view class="hvp-speak-ring" v-if="speaking"></view>
			</view>
		</view>
		<text class="hvp-caption" v-if="caption && !hideCaption">{{ caption }}</text>
	</view>
</template>

<script>
import settings from '@/common/settings.js'

// #ifdef MP-WEIXIN
import lottie from 'lottie-miniprogram'
// #endif

let hvpCanvasSeq = 0

export default {
	name: 'HealthVirtualPersona',
	props: {
		speaking: {
			type: Boolean,
			default: false
		},
		caption: {
			type: String,
			default: ''
		},
		size: {
			type: String,
			default: 'lg'
		},
		darkCaption: {
			type: Boolean,
			default: false
		},
		embedded: {
			type: Boolean,
			default: false
		},
		hideCaption: {
			type: Boolean,
			default: false
		},
		/** 覆盖默认 Lottie 地址（完整 URL） */
		lottieUrl: {
			type: String,
			default: ''
		}
	},
	data() {
		hvpCanvasSeq += 1
		return {
			canvasId: 'hvpLottie' + hvpCanvasSeq,
			useCssFallback: false,
			lottieAnim: null,
			lottieInited: false
		}
	},
	computed: {
		resolvedLottieUrl() {
			if (this.lottieUrl && String(this.lottieUrl).trim()) {
				return String(this.lottieUrl).trim()
			}
			const base = (settings.sitePublicUrl || 'https://admin.circular-economy.cn').replace(/\/$/, '')
			const path = settings.healthAdvisorLottiePath || '/static/lottie/health-advisor-idle.json'
			return base + path
		},
		showCssHeart() {
			// #ifdef MP-WEIXIN
			return this.useCssFallback
			// #endif
			// #ifndef MP-WEIXIN
			return true
			// #endif
		}
	},
	watch: {
		speaking(val) {
			this.applyLottieSpeed(val)
		}
	},
	mounted() {
		// #ifdef MP-WEIXIN
		this.$nextTick(() => {
			setTimeout(() => this.initLottie(), 80)
		})
		// #endif
	},
	beforeDestroy() {
		this.destroyLottie()
	},
	methods: {
		applyLottieSpeed(speaking) {
			if (!this.lottieAnim) return
			try {
				this.lottieAnim.setSpeed(speaking ? 1.35 : 1)
			} catch (e) {}
		},
		destroyLottie() {
			if (this.lottieAnim) {
				try {
					this.lottieAnim.destroy()
				} catch (e) {}
				this.lottieAnim = null
			}
		},
		initLottie() {
			// #ifdef MP-WEIXIN
			if (this.lottieInited || this.useCssFallback) return
			this.lottieInited = true
			const selector = '#' + this.canvasId
			const query = uni.createSelectorQuery().in(this)
			query
				.select(selector)
				.fields({ node: true, size: true })
				.exec((res) => {
					const box = res && res[0]
					if (!box || !box.node) {
						this.useCssFallback = true
						return
					}
					const canvas = box.node
					const dpr = uni.getSystemInfoSync().pixelRatio || 2
					const w = box.width || 300
					const h = box.height || 300
					canvas.width = w * dpr
					canvas.height = h * dpr
					const ctx = canvas.getContext('2d')
					ctx.scale(dpr, dpr)
					lottie.setup(canvas)
					this.lottieAnim = lottie.loadAnimation({
						loop: true,
						autoplay: true,
						path: this.resolvedLottieUrl,
						rendererSettings: {
							context: ctx,
							clearCanvas: true
						}
					})
					this.lottieAnim.addEventListener('DOMLoaded', () => {
						this.applyLottieSpeed(this.speaking)
					})
					this.lottieAnim.addEventListener('data_failed', () => {
						this.destroyLottie()
						this.useCssFallback = true
					})
				})
			// #endif
		}
	}
}
</script>

<style lang="scss" scoped>
.hvp {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.hvp--embedded {
	margin: 0;
}

.hvp-stage {
	position: relative;
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.hvp--hero .hvp-stage {
	height: 620rpx;
}

.hvp--lg .hvp-stage {
	height: 500rpx;
}

.hvp--md .hvp-stage {
	height: 380rpx;
}

.hvp-lottie {
	width: 92%;
	height: 100%;
	display: block;
}

.hvp-css-fallback {
	position: relative;
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.hvp-halo {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
	filter: blur(24rpx);
	animation: hvpHaloDrift 5.5s ease-in-out infinite;
}

.hvp-halo--a {
	width: 88%;
	height: 72%;
	background: radial-gradient(circle, rgba(144, 202, 249, 0.55) 0%, transparent 70%);
}

.hvp-halo--b {
	width: 70%;
	height: 62%;
	background: radial-gradient(circle, rgba(229, 57, 53, 0.18) 0%, transparent 72%);
	animation-delay: -2.4s;
	animation-duration: 6.8s;
}

.hvp-motion {
	position: relative;
	z-index: 2;
	animation: hvpMotionJitter 2.4s ease-in-out infinite;
}

.hvp--speaking .hvp-motion {
	animation-duration: 1.35s;
}

.hvp-heart {
	position: relative;
	width: 360rpx;
	height: 338rpx;
	animation: hvpHeartBob 3.6s ease-in-out infinite;
}

.hvp--hero .hvp-heart {
	width: 420rpx;
	height: 394rpx;
}

.hvp--md .hvp-heart {
	width: 300rpx;
	height: 282rpx;
}

.hvp-heart-shell {
	position: relative;
	width: 100%;
	height: 100%;
	animation: hvpHeartMorph 4.2s ease-in-out infinite;
}

.hvp-heart-glass {
	position: absolute;
	inset: 0;
	clip-path: polygon(
		50% 93%,
		10% 52%,
		16% 30%,
		32% 18%,
		50% 34%,
		68% 17%,
		84% 29%,
		90% 51%
	);
	background: linear-gradient(
		160deg,
		rgba(255, 255, 255, 0.82) 0%,
		rgba(179, 229, 252, 0.52) 38%,
		rgba(66, 165, 245, 0.38) 68%,
		rgba(21, 101, 216, 0.28) 100%
	);
	border: 2rpx solid rgba(255, 255, 255, 0.7);
	box-shadow:
		0 20rpx 48rpx rgba(21, 101, 216, 0.22),
		0 4rpx 16rpx rgba(255, 255, 255, 0.6) inset,
		0 -8rpx 24rpx rgba(144, 202, 249, 0.35) inset;
	backdrop-filter: blur(18px);
}

.hvp-heart-glass--inner {
	inset: 14%;
	clip-path: polygon(50% 88%, 22% 54%, 28% 36%, 50% 46%, 72% 35%, 78% 53%);
	opacity: 0.55;
	border: none;
	box-shadow: inset 0 0 28rpx rgba(255, 255, 255, 0.65);
	animation: hvpInnerPulse 2.8s ease-in-out infinite;
}

.hvp-heart-shine {
	position: absolute;
	width: 42%;
	height: 28%;
	left: 18%;
	top: 22%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.95) 0%, transparent 72%);
	filter: blur(2rpx);
	animation: hvpShineMove 3.3s ease-in-out infinite;
}

.hvp-heart-tint {
	position: absolute;
	width: 36%;
	height: 32%;
	right: 14%;
	bottom: 26%;
	border-radius: 45% 55% 60% 40%;
	background: radial-gradient(circle, rgba(229, 57, 53, 0.22) 0%, transparent 70%);
	animation: hvpTintMove 4.5s ease-in-out infinite;
}

.hvp-speak-ring {
	position: absolute;
	width: 58%;
	height: 52%;
	border: 3rpx solid rgba(144, 202, 249, 0.45);
	border-radius: 48% 52% 50% 50%;
	pointer-events: none;
	animation: hvpSpeakRing 1.8s ease-out infinite;
}

.hvp-caption {
	margin-top: 8rpx;
	max-width: 92%;
	font-size: 24rpx;
	line-height: 1.45;
	color: #64748b;
	text-align: center;
}

.hvp--hero .hvp-caption,
.hvp--dark-caption .hvp-caption {
	color: rgba(255, 255, 255, 0.88);
	font-size: 26rpx;
}

@keyframes hvpMotionJitter {
	0%,
	100% {
		transform: translate(0, 0) rotate(0deg);
	}
	20% {
		transform: translate(3rpx, -4rpx) rotate(0.9deg);
	}
	40% {
		transform: translate(-4rpx, 2rpx) rotate(-0.7deg);
	}
	60% {
		transform: translate(2rpx, 4rpx) rotate(0.5deg);
	}
	80% {
		transform: translate(-3rpx, -2rpx) rotate(-0.4deg);
	}
}

@keyframes hvpHeartBob {
	0%,
	100% {
		transform: translateY(0) scale(1);
	}
	50% {
		transform: translateY(-6rpx) scale(1.03);
	}
}

@keyframes hvpHeartMorph {
	0%,
	100% {
		transform: scale(1) skew(0deg, 0deg) rotate(0deg);
	}
	25% {
		transform: scale(1.04, 0.97) skew(1.2deg, -0.8deg) rotate(1.2deg);
	}
	50% {
		transform: scale(0.97, 1.03) skew(-1deg, 1deg) rotate(-0.8deg);
	}
	75% {
		transform: scale(1.02, 0.98) skew(0.6deg, 0.5deg) rotate(0.6deg);
	}
}

@keyframes hvpInnerPulse {
	0%,
	100% {
		opacity: 0.5;
		transform: scale(1);
	}
	50% {
		opacity: 0.72;
		transform: scale(1.06);
	}
}

@keyframes hvpShineMove {
	0%,
	100% {
		transform: translate(0, 0);
		opacity: 0.85;
	}
	50% {
		transform: translate(8rpx, 6rpx);
		opacity: 1;
	}
}

@keyframes hvpTintMove {
	0%,
	100% {
		transform: translate(0, 0) scale(1);
		opacity: 0.7;
	}
	50% {
		transform: translate(-6rpx, 4rpx) scale(1.08);
		opacity: 0.95;
	}
}

@keyframes hvpHaloDrift {
	0%,
	100% {
		transform: scale(1) translate(0, 0);
		opacity: 0.75;
	}
	50% {
		transform: scale(1.1) translate(6rpx, -8rpx);
		opacity: 1;
	}
}

@keyframes hvpSpeakRing {
	0% {
		opacity: 0.55;
		transform: scale(0.92);
	}
	100% {
		opacity: 0;
		transform: scale(1.35);
	}
}
</style>
