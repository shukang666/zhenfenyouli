<template>
	<view class="ham-stage" :class="['ham-stage--' + size, { 'ham-stage--speaking': speaking }]">
		<view class="ham-scene">
			<view class="ham-glow"></view>
			<view class="ham-orbit ham-orbit--a"></view>
			<view class="ham-orbit ham-orbit--b"></view>

			<view class="ham-figure-wrap" :class="{ 'ham-figure-wrap--speak': speaking }">
				<view class="ham-figure">
					<view class="ham-cap">
						<view class="ham-cap-cross"></view>
					</view>
					<view class="ham-head">
						<view class="ham-face">
							<view class="ham-eye ham-eye--l"></view>
							<view class="ham-eye ham-eye--r"></view>
							<view class="ham-blush ham-blush--l"></view>
							<view class="ham-blush ham-blush--r"></view>
							<view class="ham-mouth" :class="{ 'ham-mouth--open': speaking }"></view>
						</view>
					</view>
					<view class="ham-body">
						<view class="ham-coat"></view>
						<view class="ham-arm ham-arm--l"></view>
						<view class="ham-arm ham-arm--r"></view>
					</view>
				</view>
			</view>

			<view class="ham-pedestal">
				<view class="ham-pedestal-top"></view>
				<view class="ham-pedestal-side"></view>
			</view>

			<view class="ham-waves" v-if="speaking">
				<view class="ham-wave" v-for="i in 5" :key="'w' + i" :style="{ animationDelay: i * 0.08 + 's' }"></view>
			</view>
		</view>

		<view class="ham-bubble" v-if="line">
			<view class="ham-bubble-tail"></view>
			<text class="ham-bubble-text">{{ line }}</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'HealthAdvisorMascot',
	props: {
		speaking: {
			type: Boolean,
			default: false
		},
		line: {
			type: String,
			default: ''
		},
		size: {
			type: String,
			default: 'lg'
		}
	}
}
</script>

<style lang="scss" scoped>
.ham-stage {
	position: relative;
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.ham-scene {
	position: relative;
	width: 320rpx;
	height: 300rpx;
	perspective: 900rpx;
	transform-style: preserve-3d;
}

.ham-stage--sm .ham-scene {
	width: 220rpx;
	height: 200rpx;
}

.ham-glow {
	position: absolute;
	left: 50%;
	top: 58%;
	width: 280rpx;
	height: 120rpx;
	margin-left: -140rpx;
	background: radial-gradient(ellipse, rgba(45, 125, 118, 0.35) 0%, transparent 70%);
	transform: translateZ(-40rpx);
	filter: blur(8rpx);
}

.ham-orbit {
	position: absolute;
	left: 50%;
	top: 42%;
	border-radius: 50%;
	border: 2rpx dashed rgba(45, 125, 118, 0.22);
	transform: translate(-50%, -50%) rotateX(72deg);
}
.ham-orbit--a {
	width: 260rpx;
	height: 260rpx;
	animation: hamOrbitSpin 18s linear infinite;
}
.ham-orbit--b {
	width: 210rpx;
	height: 210rpx;
	border-color: rgba(58, 154, 143, 0.18);
	animation: hamOrbitSpin 24s linear infinite reverse;
}

.ham-figure-wrap {
	position: absolute;
	left: 50%;
	top: 38%;
	width: 200rpx;
	height: 220rpx;
	margin-left: -100rpx;
	transform-style: preserve-3d;
	transform: translateZ(48rpx) rotateX(-6deg);
	animation: hamIdleFloat 3.2s ease-in-out infinite;
}

.ham-figure-wrap--speak {
	animation: hamSpeakJitter 0.28s ease-in-out infinite;
}

.ham-figure {
	position: relative;
	width: 100%;
	height: 100%;
	transform-style: preserve-3d;
}

.ham-cap {
	position: absolute;
	top: 0;
	left: 50%;
	width: 120rpx;
	height: 36rpx;
	margin-left: -60rpx;
	background: linear-gradient(180deg, #fff 0%, #e8f5f2 100%);
	border-radius: 18rpx 18rpx 8rpx 8rpx;
	box-shadow: 0 6rpx 16rpx rgba(30, 80, 72, 0.2);
	transform: translateZ(20rpx);
	z-index: 5;
}
.ham-cap-cross {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 28rpx;
	height: 28rpx;
	margin: -14rpx 0 0 -14rpx;
}
.ham-cap-cross::before,
.ham-cap-cross::after {
	content: '';
	position: absolute;
	background: #e85d7a;
	border-radius: 4rpx;
}
.ham-cap-cross::before {
	left: 50%;
	top: 0;
	width: 8rpx;
	height: 100%;
	margin-left: -4rpx;
}
.ham-cap-cross::after {
	left: 0;
	top: 50%;
	width: 100%;
	height: 8rpx;
	margin-top: -4rpx;
}

.ham-head {
	position: absolute;
	top: 28rpx;
	left: 50%;
	width: 132rpx;
	height: 132rpx;
	margin-left: -66rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #ffe8d6 0%, #ffd4b8 100%);
	box-shadow:
		inset -8rpx -10rpx 20rpx rgba(200, 120, 80, 0.15),
		0 16rpx 32rpx rgba(45, 125, 118, 0.25);
	transform: translateZ(32rpx);
}

.ham-face {
	position: relative;
	width: 100%;
	height: 100%;
}

.ham-eye {
	position: absolute;
	top: 48rpx;
	width: 18rpx;
	height: 22rpx;
	background: #2a3d38;
	border-radius: 50%;
}
.ham-eye--l {
	left: 36rpx;
}
.ham-eye--r {
	right: 36rpx;
}

.ham-blush {
	position: absolute;
	top: 72rpx;
	width: 24rpx;
	height: 12rpx;
	background: rgba(255, 140, 120, 0.35);
	border-radius: 50%;
	filter: blur(2rpx);
}
.ham-blush--l {
	left: 24rpx;
}
.ham-blush--r {
	right: 24rpx;
}

.ham-mouth {
	position: absolute;
	left: 50%;
	bottom: 36rpx;
	width: 28rpx;
	height: 10rpx;
	margin-left: -14rpx;
	border-bottom: 4rpx solid #c9785a;
	border-radius: 0 0 20rpx 20rpx;
	transition: height 0.12s ease;
}
.ham-mouth--open {
	height: 22rpx;
	border: 4rpx solid #c9785a;
	border-radius: 50%;
	background: #8b4540;
	animation: hamMouthPulse 0.22s ease-in-out infinite alternate;
}

.ham-body {
	position: absolute;
	top: 148rpx;
	left: 50%;
	width: 116rpx;
	height: 88rpx;
	margin-left: -58rpx;
	transform: translateZ(16rpx);
}

.ham-coat {
	width: 100%;
	height: 100%;
	border-radius: 36rpx 36rpx 24rpx 24rpx;
	background: linear-gradient(165deg, #ffffff 0%, #e8f8f4 55%, #c5e8de 100%);
	box-shadow:
		inset 0 2rpx 0 rgba(255, 255, 255, 0.9),
		0 12rpx 24rpx rgba(45, 125, 118, 0.22);
}

.ham-arm {
	position: absolute;
	top: 12rpx;
	width: 28rpx;
	height: 64rpx;
	border-radius: 20rpx;
	background: linear-gradient(180deg, #ffe8d6, #ffd4b8);
	box-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.08);
}
.ham-arm--l {
	left: -18rpx;
	transform: rotate(18deg);
	transform-origin: top center;
	animation: hamArmL 3.2s ease-in-out infinite;
}
.ham-arm--r {
	right: -18rpx;
	transform: rotate(-18deg);
	transform-origin: top center;
}
.ham-figure-wrap--speak .ham-arm--r {
	animation: hamArmWave 0.45s ease-in-out infinite alternate;
}

.ham-pedestal {
	position: absolute;
	left: 50%;
	bottom: 0;
	width: 240rpx;
	height: 56rpx;
	margin-left: -120rpx;
	transform-style: preserve-3d;
	transform: rotateX(58deg) translateZ(-20rpx);
}
.ham-pedestal-top {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: radial-gradient(ellipse at 30% 30%, #5eb8ad 0%, #2d7d76 45%, #1f5f58 100%);
	box-shadow: 0 20rpx 40rpx rgba(31, 95, 88, 0.45);
}
.ham-pedestal-side {
	position: absolute;
	left: 10%;
	bottom: -18rpx;
	width: 80%;
	height: 28rpx;
	border-radius: 50%;
	background: rgba(31, 95, 88, 0.55);
	filter: blur(6rpx);
	transform: translateZ(-12rpx);
}

.ham-waves {
	position: absolute;
	right: -8rpx;
	top: 50%;
	display: flex;
	align-items: flex-end;
	gap: 6rpx;
	height: 48rpx;
	transform: translateY(-50%) translateZ(60rpx);
}
.ham-wave {
	width: 8rpx;
	height: 16rpx;
	border-radius: 8rpx;
	background: linear-gradient(180deg, #3a9a8f, #2d7d76);
	animation: hamWaveBar 0.5s ease-in-out infinite alternate;
}

.ham-bubble {
	position: relative;
	margin-top: 8rpx;
	max-width: 92%;
	padding: 20rpx 28rpx;
	background: rgba(255, 255, 255, 0.92);
	border-radius: 24rpx;
	box-shadow:
		0 12rpx 36rpx rgba(45, 125, 118, 0.12),
		inset 0 1rpx 0 rgba(255, 255, 255, 1);
	border: 2rpx solid rgba(45, 125, 118, 0.12);
}
.ham-bubble-tail {
	position: absolute;
	left: 50%;
	top: -14rpx;
	width: 24rpx;
	height: 24rpx;
	margin-left: -12rpx;
	background: rgba(255, 255, 255, 0.92);
	border-left: 2rpx solid rgba(45, 125, 118, 0.12);
	border-top: 2rpx solid rgba(45, 125, 118, 0.12);
	transform: rotate(45deg);
}
.ham-bubble-text {
	display: block;
	font-size: 26rpx;
	line-height: 1.55;
	color: #1a2e28;
	text-align: center;
}
.ham-stage--sm .ham-bubble-text {
	font-size: 22rpx;
}
.ham-stage--speaking .ham-bubble {
	animation: hamBubblePulse 0.6s ease-in-out infinite alternate;
}

@keyframes hamOrbitSpin {
	from {
		transform: translate(-50%, -50%) rotateX(72deg) rotateZ(0deg);
	}
	to {
		transform: translate(-50%, -50%) rotateX(72deg) rotateZ(360deg);
	}
}
@keyframes hamIdleFloat {
	0%,
	100% {
		transform: translateZ(48rpx) rotateX(-6deg) translateY(0) scale(1);
	}
	50% {
		transform: translateZ(52rpx) rotateX(-5deg) translateY(-8rpx) scale(1.02);
	}
}
@keyframes hamSpeakJitter {
	0% {
		transform: translateZ(52rpx) rotateX(-5deg) translateY(0) rotateZ(0deg) scale(1.02);
	}
	25% {
		transform: translateZ(54rpx) rotateX(-4deg) translateY(-3rpx) rotateZ(-1.2deg) scale(1.03);
	}
	50% {
		transform: translateZ(50rpx) rotateX(-6deg) translateY(2rpx) rotateZ(0.8deg) scale(1.01);
	}
	75% {
		transform: translateZ(53rpx) rotateX(-5deg) translateY(-2rpx) rotateZ(-0.6deg) scale(1.025);
	}
	100% {
		transform: translateZ(51rpx) rotateX(-5deg) translateY(1rpx) rotateZ(0.4deg) scale(1.02);
	}
}
@keyframes hamMouthPulse {
	from {
		transform: scaleY(0.85);
	}
	to {
		transform: scaleY(1.15);
	}
}
@keyframes hamArmL {
	0%,
	100% {
		transform: rotate(18deg);
	}
	50% {
		transform: rotate(12deg);
	}
}
@keyframes hamArmWave {
	from {
		transform: rotate(-28deg);
	}
	to {
		transform: rotate(-8deg);
	}
}
@keyframes hamWaveBar {
	from {
		height: 12rpx;
		opacity: 0.55;
	}
	to {
		height: 44rpx;
		opacity: 1;
	}
}
@keyframes hamBubblePulse {
	from {
		box-shadow: 0 12rpx 36rpx rgba(45, 125, 118, 0.12);
	}
	to {
		box-shadow: 0 16rpx 40rpx rgba(45, 125, 118, 0.22);
	}
}
</style>
