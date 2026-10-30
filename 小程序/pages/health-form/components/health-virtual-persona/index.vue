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
			<view class="hvp-halo hvp-halo--tech"></view>
			<view class="hvp-halo hvp-halo--warm"></view>

			<view v-if="speaking" class="hvp-speak-ring hvp-speak-ring--outer"></view>
			<view v-if="speaking" class="hvp-speak-ring hvp-speak-ring--inner"></view>

			<view class="hvp-motion">
				<view class="hvp-figure">
					<view class="hvp-orbit">
						<view class="hvp-orbit-arc hvp-orbit-arc--a"></view>
						<view class="hvp-orbit-arc hvp-orbit-arc--b"></view>
						<view class="hvp-orbit-dot hvp-orbit-dot--1"></view>
						<view class="hvp-orbit-dot hvp-orbit-dot--2"></view>
					</view>

					<view class="hvp-head">
						<view class="hvp-visor">
							<view class="hvp-visor-glass"></view>
							<view class="hvp-visor-line hvp-visor-line--l"></view>
							<view class="hvp-visor-line hvp-visor-line--r"></view>
							<view class="hvp-visor-badge">
								<view class="hvp-visor-cross-v"></view>
								<view class="hvp-visor-cross-h"></view>
							</view>
						</view>

						<view class="hvp-face">
							<view class="hvp-face-warm"></view>
							<view class="hvp-brow hvp-brow--l"></view>
							<view class="hvp-brow hvp-brow--r"></view>
							<view class="hvp-eye hvp-eye--l">
								<view class="hvp-eye-arc"></view>
							</view>
							<view class="hvp-eye hvp-eye--r">
								<view class="hvp-eye-arc"></view>
							</view>
							<view class="hvp-blush hvp-blush--l"></view>
							<view class="hvp-blush hvp-blush--r"></view>
							<view class="hvp-smile">
								<view class="hvp-smile-line"></view>
							</view>
						</view>
					</view>

					<view class="hvp-neck"></view>

					<view class="hvp-torso">
						<view class="hvp-torso-glass"></view>
						<view class="hvp-torso-shine"></view>
						<view class="hvp-collar-notch"></view>
						<view class="hvp-med-chip">
							<text class="hvp-med-chip-text">AI+</text>
						</view>
						<view class="hvp-stetho">
							<view class="hvp-stetho-cord"></view>
							<view class="hvp-stetho-head"></view>
						</view>
						<view class="hvp-cross-badge">
							<view class="hvp-cross-badge-v"></view>
							<view class="hvp-cross-badge-h"></view>
						</view>
					</view>

					<view class="hvp-arm hvp-arm--l">
						<view class="hvp-arm-cap"></view>
					</view>
					<view class="hvp-arm hvp-arm--r">
						<view class="hvp-arm-cap"></view>
					</view>

					<view class="hvp-pedestal"></view>
				</view>
			</view>

			<view class="hvp-particle hvp-particle--1"></view>
			<view class="hvp-particle hvp-particle--2"></view>
			<view class="hvp-particle hvp-particle--3"></view>
		</view>
		<text class="hvp-caption" v-if="caption && !hideCaption">{{ caption }}</text>
	</view>
</template>

<script>
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
		lottieUrl: {
			type: String,
			default: ''
		}
	}
}
</script>

<style lang="scss" scoped>
.hvp {
	position: relative;
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	box-sizing: border-box;
}

.hvp--embedded {
	margin: 0;
}

.hvp--embedded .hvp-stage {
	pointer-events: none;
}

.hvp--hero .hvp-stage {
	height: 580rpx;
}

.hvp--hero .hvp-figure {
	transform: scale(1);
}

.hvp--lg .hvp-stage {
	height: 480rpx;
}

.hvp--lg .hvp-figure {
	transform: scale(0.86);
}

.hvp--md .hvp-stage {
	height: 360rpx;
}

.hvp--md .hvp-figure {
	transform: scale(0.68);
}

.hvp--speaking .hvp-motion {
	animation: hvp-float-speak 2.8s ease-in-out infinite;
}

.hvp--speaking .hvp-smile-line {
	border-bottom-width: 6rpx;
	height: 14rpx;
}

.hvp--speaking .hvp-eye-arc {
	border-top-color: #1565d8;
	animation: hvp-eye-glow 2s ease-in-out infinite;
}

.hvp--speaking .hvp-orbit {
	animation: hvp-orbit-spin 8s linear infinite;
}

.hvp-stage {
	position: relative;
	width: 100%;
	height: 580rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
	box-sizing: border-box;
}

.hvp-motion {
	position: relative;
	z-index: 3;
	animation: hvp-float 4.2s ease-in-out infinite;
}

.hvp-halo {
	position: absolute;
	left: 50%;
	top: 46%;
	transform: translate(-50%, -50%);
	border-radius: 50%;
	pointer-events: none;
}

.hvp-halo--tech {
	width: 480rpx;
	height: 480rpx;
	background: radial-gradient(
		circle,
		rgba(0, 229, 255, 0.12) 0%,
		rgba(66, 165, 245, 0.2) 38%,
		rgba(21, 101, 216, 0) 72%
	);
}

.hvp-halo--warm {
	width: 320rpx;
	height: 320rpx;
	background: radial-gradient(circle, rgba(255, 204, 188, 0.22) 0%, transparent 68%);
	animation: hvp-warm-pulse 5s ease-in-out infinite;
}

.hvp-speak-ring {
	position: absolute;
	left: 50%;
	top: 46%;
	border: 2rpx solid rgba(0, 229, 255, 0.45);
	border-radius: 50%;
	transform: translate(-50%, -50%);
	pointer-events: none;
	z-index: 1;
}

.hvp-speak-ring--outer {
	width: 420rpx;
	height: 420rpx;
	animation: hvp-ring-pulse 2.8s ease-out infinite;
}

.hvp-speak-ring--inner {
	width: 360rpx;
	height: 360rpx;
	border-color: rgba(255, 255, 255, 0.35);
	animation: hvp-ring-pulse 2.8s ease-out 0.7s infinite;
}

.hvp-figure {
	position: relative;
	width: 360rpx;
	height: 460rpx;
	transform-origin: center center;
}

.hvp-orbit {
	position: absolute;
	left: 50%;
	top: 88rpx;
	width: 300rpx;
	height: 300rpx;
	margin-left: -150rpx;
	z-index: 1;
	pointer-events: none;
}

.hvp-orbit-arc {
	position: absolute;
	border: 3rpx solid transparent;
	border-radius: 50%;
}

.hvp-orbit-arc--a {
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	border-top-color: rgba(0, 229, 255, 0.55);
	border-right-color: rgba(66, 165, 245, 0.2);
	transform: rotate(-24deg);
}

.hvp-orbit-arc--b {
	left: 18rpx;
	top: 18rpx;
	right: 18rpx;
	bottom: 18rpx;
	border-bottom-color: rgba(21, 101, 216, 0.35);
	border-left-color: rgba(255, 255, 255, 0.15);
	transform: rotate(18deg);
}

.hvp-orbit-dot {
	position: absolute;
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #00e5ff;
	box-shadow: 0 0 12rpx rgba(0, 229, 255, 0.8);
}

.hvp-orbit-dot--1 {
	right: 28rpx;
	top: 42rpx;
}

.hvp-orbit-dot--2 {
	left: 36rpx;
	bottom: 48rpx;
	opacity: 0.75;
}

.hvp-head {
	position: relative;
	z-index: 4;
	width: 240rpx;
	height: 240rpx;
	margin: 0 auto;
}

.hvp-visor {
	position: absolute;
	left: 50%;
	top: 0;
	width: 228rpx;
	height: 72rpx;
	margin-left: -114rpx;
	z-index: 6;
}

.hvp-visor-glass {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	border-radius: 36rpx 36rpx 8rpx 8rpx;
	background: linear-gradient(
		180deg,
		rgba(255, 255, 255, 0.95) 0%,
		rgba(187, 222, 251, 0.75) 100%
	);
	border: 2rpx solid rgba(255, 255, 255, 0.9);
	box-shadow:
		0 8rpx 24rpx rgba(21, 101, 216, 0.15),
		inset 0 2rpx 8rpx rgba(255, 255, 255, 0.9);
}

.hvp-visor-line {
	position: absolute;
	top: 22rpx;
	width: 48rpx;
	height: 4rpx;
	border-radius: 4rpx;
	background: linear-gradient(90deg, transparent, rgba(0, 229, 255, 0.7), transparent);
}

.hvp-visor-line--l {
	left: 16rpx;
}

.hvp-visor-line--r {
	right: 16rpx;
}

.hvp-visor-badge {
	position: absolute;
	left: 50%;
	top: 14rpx;
	width: 28rpx;
	height: 28rpx;
	margin-left: -14rpx;
}

.hvp-visor-cross-v,
.hvp-visor-cross-h {
	position: absolute;
	left: 50%;
	top: 50%;
	background: linear-gradient(180deg, #42a5f5, #1565d8);
	border-radius: 3rpx;
	transform: translate(-50%, -50%);
}

.hvp-visor-cross-v {
	width: 6rpx;
	height: 20rpx;
}

.hvp-visor-cross-h {
	width: 20rpx;
	height: 6rpx;
}

.hvp-face {
	position: absolute;
	left: 50%;
	top: 36rpx;
	width: 220rpx;
	height: 200rpx;
	margin-left: -110rpx;
	border-radius: 50%;
	background: linear-gradient(165deg, #fffaf7 0%, #ffe8dc 48%, #ffd4c4 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.85);
	box-shadow:
		0 16rpx 36rpx rgba(21, 101, 216, 0.12),
		inset 0 8rpx 20rpx rgba(255, 255, 255, 0.65);
	overflow: hidden;
}

.hvp-face-warm {
	position: absolute;
	left: 10%;
	top: 12%;
	right: 10%;
	bottom: 20%;
	border-radius: 50%;
	background: radial-gradient(circle at 50% 40%, rgba(255, 171, 145, 0.28) 0%, transparent 70%);
}

.hvp-brow {
	position: absolute;
	top: 72rpx;
	width: 36rpx;
	height: 8rpx;
	border-top: 3rpx solid #b0a090;
	border-radius: 50% 50% 0 0;
	opacity: 0.85;
}

.hvp-brow--l {
	left: 52rpx;
	transform: rotate(-6deg);
}

.hvp-brow--r {
	right: 52rpx;
	transform: rotate(6deg) scaleX(-1);
}

.hvp-eye {
	position: absolute;
	top: 88rpx;
	width: 44rpx;
	height: 28rpx;
	display: flex;
	justify-content: center;
	align-items: center;
}

.hvp-eye--l {
	left: 48rpx;
}

.hvp-eye--r {
	right: 48rpx;
}

.hvp-eye-arc {
	width: 36rpx;
	height: 18rpx;
	border-top: 5rpx solid #4a6572;
	border-radius: 50% 50% 0 0;
	transform: translateY(6rpx);
}

.hvp-blush {
	position: absolute;
	top: 118rpx;
	width: 40rpx;
	height: 22rpx;
	border-radius: 50%;
	background: rgba(255, 138, 128, 0.35);
	filter: blur(3rpx);
}

.hvp-blush--l {
	left: 28rpx;
}

.hvp-blush--r {
	right: 28rpx;
}

.hvp-smile {
	position: absolute;
	left: 50%;
	bottom: 48rpx;
	width: 56rpx;
	height: 28rpx;
	margin-left: -28rpx;
	display: flex;
	justify-content: center;
}

.hvp-smile-line {
	width: 40rpx;
	height: 12rpx;
	border-bottom: 4rpx solid #c97b7b;
	border-radius: 0 0 50% 50%;
	transition: all 0.25s ease;
}

.hvp-neck {
	position: relative;
	z-index: 3;
	width: 56rpx;
	height: 28rpx;
	margin: -8rpx auto 0;
	border-radius: 0 0 20rpx 20rpx;
	background: linear-gradient(180deg, #ffd4c4, #ffe8dc);
}

.hvp-torso {
	position: relative;
	z-index: 2;
	width: 280rpx;
	height: 200rpx;
	margin: -4rpx auto 0;
	border-radius: 48% 48% 36% 36% / 42% 42% 58% 58%;
	overflow: hidden;
	background: linear-gradient(155deg, #ffffff 0%, #e8f4fc 45%, #bbdefb 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.92);
	box-shadow:
		0 20rpx 40rpx rgba(21, 101, 216, 0.16),
		inset 0 6rpx 16rpx rgba(255, 255, 255, 0.9);
}

.hvp-torso-glass {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	height: 55%;
	background: linear-gradient(180deg, rgba(255, 255, 255, 0.55), transparent);
}

.hvp-torso-shine {
	position: absolute;
	left: 24rpx;
	top: 20rpx;
	width: 80rpx;
	height: 140rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.45);
	filter: blur(16rpx);
}

.hvp-collar-notch {
	position: absolute;
	left: 50%;
	top: 0;
	width: 72rpx;
	height: 36rpx;
	margin-left: -36rpx;
	border-radius: 0 0 36rpx 36rpx;
	background: #e3f2fd;
	border: 2rpx solid rgba(144, 202, 249, 0.5);
	border-top: none;
}

.hvp-med-chip {
	position: absolute;
	left: 28rpx;
	top: 52rpx;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, rgba(0, 229, 255, 0.15), rgba(21, 101, 216, 0.08));
	border: 1rpx solid rgba(0, 229, 255, 0.35);
}

.hvp-med-chip-text {
	font-size: 20rpx;
	font-weight: 700;
	color: #1565d8;
	letter-spacing: 1rpx;
}

.hvp-stetho {
	position: absolute;
	right: 32rpx;
	top: 48rpx;
	width: 64rpx;
	height: 72rpx;
}

.hvp-stetho-cord {
	position: absolute;
	right: 0;
	top: 0;
	width: 48rpx;
	height: 48rpx;
	border: 3rpx solid #90a4ae;
	border-bottom: none;
	border-left: none;
	border-radius: 0 100% 0 0;
}

.hvp-stetho-head {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 26rpx;
	height: 26rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #eceff1, #90a4ae);
	border: 2rpx solid #fff;
}

.hvp-cross-badge {
	position: absolute;
	left: 50%;
	top: 88rpx;
	width: 52rpx;
	height: 52rpx;
	margin-left: -26rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #42a5f5, #1565d8);
	box-shadow: 0 6rpx 16rpx rgba(21, 101, 216, 0.35);
}

.hvp-cross-badge-v,
.hvp-cross-badge-h {
	position: absolute;
	left: 50%;
	top: 50%;
	background: #fff;
	border-radius: 2rpx;
	transform: translate(-50%, -50%);
}

.hvp-cross-badge-v {
	width: 6rpx;
	height: 24rpx;
}

.hvp-cross-badge-h {
	width: 24rpx;
	height: 6rpx;
}

.hvp-arm {
	position: absolute;
	top: 248rpx;
	width: 72rpx;
	height: 100rpx;
	border-radius: 40rpx;
	background: linear-gradient(165deg, #ffffff, #e3f2fd);
	border: 2rpx solid rgba(255, 255, 255, 0.9);
	box-shadow: 0 8rpx 18rpx rgba(21, 101, 216, 0.1);
	z-index: 1;
}

.hvp-arm--l {
	left: 8rpx;
	transform: rotate(14deg);
}

.hvp-arm--r {
	right: 8rpx;
	transform: rotate(-10deg);
}

.hvp-arm-cap {
	position: absolute;
	left: 50%;
	bottom: -4rpx;
	width: 64rpx;
	height: 64rpx;
	margin-left: -32rpx;
	border-radius: 50%;
	background: linear-gradient(180deg, #ffffff, #e1f0fa);
	border: 2rpx solid rgba(144, 202, 249, 0.4);
}

.hvp-pedestal {
	position: absolute;
	left: 50%;
	bottom: 8rpx;
	width: 240rpx;
	height: 28rpx;
	margin-left: -120rpx;
	border-radius: 50%;
	background: radial-gradient(ellipse, rgba(21, 101, 216, 0.2) 0%, transparent 72%);
}

.hvp-particle {
	position: absolute;
	border-radius: 4rpx;
	background: rgba(0, 229, 255, 0.65);
	box-shadow: 0 0 10rpx rgba(0, 229, 255, 0.5);
	pointer-events: none;
	z-index: 2;
}

.hvp-particle--1 {
	width: 8rpx;
	height: 8rpx;
	left: 14%;
	top: 32%;
	animation: hvp-particle 3.2s ease-in-out infinite;
}

.hvp-particle--2 {
	width: 6rpx;
	height: 6rpx;
	right: 16%;
	top: 40%;
	animation: hvp-particle 4s ease-in-out 0.8s infinite;
}

.hvp-particle--3 {
	width: 5rpx;
	height: 5rpx;
	right: 22%;
	bottom: 28%;
	background: rgba(255, 171, 145, 0.8);
	box-shadow: 0 0 8rpx rgba(255, 171, 145, 0.5);
	animation: hvp-particle 3.6s ease-in-out 1.4s infinite;
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

@keyframes hvp-float {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-12rpx);
	}
}

@keyframes hvp-float-speak {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-8rpx);
	}
}

@keyframes hvp-ring-pulse {
	0% {
		opacity: 0.2;
		transform: translate(-50%, -50%) scale(0.94);
	}
	100% {
		opacity: 0;
		transform: translate(-50%, -50%) scale(1.1);
	}
}

@keyframes hvp-warm-pulse {
	0%,
	100% {
		opacity: 0.75;
		transform: translate(-50%, -50%) scale(1);
	}
	50% {
		opacity: 1;
		transform: translate(-50%, -50%) scale(1.05);
	}
}

@keyframes hvp-orbit-spin {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}

@keyframes hvp-eye-glow {
	0%,
	100% {
		border-top-color: #4a6572;
	}
	50% {
		border-top-color: #1565d8;
	}
}

@keyframes hvp-particle {
	0%,
	100% {
		opacity: 0.35;
		transform: translateY(0);
	}
	50% {
		opacity: 1;
		transform: translateY(-8rpx);
	}
}
</style>
