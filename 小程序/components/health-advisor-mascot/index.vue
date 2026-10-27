<template>
	<view class="ham-stage" :class="['ham-stage--' + size, { 'ham-stage--speaking': speaking }]">
		<view class="ham-scene">
			<view class="ham-floor-glow"></view>

			<view class="ham-orbit-ring">
				<view class="ham-orbit-sat"></view>
			</view>

			<view class="ham-orb-wrap" :class="{ 'ham-orb-wrap--speak': speaking }">
				<view class="ham-orb-shell">
					<view class="ham-orb-glass"></view>
					<view class="ham-orb-swirl ham-orb-swirl--a"></view>
					<view class="ham-orb-swirl ham-orb-swirl--b"></view>
					<view class="ham-orb-core"></view>
					<view class="ham-orb-highlight"></view>
				</view>
				<view class="ham-heart-shadow"></view>
			</view>

			<view class="ham-speak-rings" v-if="speaking">
				<view class="ham-speak-ring ham-speak-ring--1"></view>
				<view class="ham-speak-ring ham-speak-ring--2"></view>
			</view>
		</view>

		<view class="ham-bubble" v-if="line && showBubble">
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
		},
		showBubble: {
			type: Boolean,
			default: true
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
	width: 340rpx;
	height: 280rpx;
}

.ham-stage--sm .ham-scene {
	width: 200rpx;
	height: 168rpx;
}

.ham-floor-glow {
	position: absolute;
	left: 50%;
	bottom: 8rpx;
	width: 220rpx;
	height: 48rpx;
	margin-left: -110rpx;
	background: radial-gradient(ellipse, rgba(56, 189, 248, 0.45) 0%, transparent 72%);
	filter: blur(6rpx);
}

.ham-orbit-ring {
	position: absolute;
	left: 50%;
	top: 46%;
	width: 260rpx;
	height: 88rpx;
	margin-left: -130rpx;
	border: 2rpx solid rgba(125, 211, 252, 0.55);
	border-radius: 50%;
	transform: rotateX(68deg);
	animation: hamOrbitSpin 12s linear infinite;
}
.ham-stage--sm .ham-orbit-ring {
	width: 160rpx;
	height: 52rpx;
	margin-left: -80rpx;
}
.ham-orbit-sat {
	position: absolute;
	top: -6rpx;
	left: 18%;
	width: 14rpx;
	height: 14rpx;
	border-radius: 50%;
	background: radial-gradient(circle at 30% 30%, #fff 0%, #38bdf8 55%, #0284c7 100%);
	box-shadow: 0 0 16rpx rgba(56, 189, 248, 0.9);
}

.ham-orb-wrap {
	position: absolute;
	left: 50%;
	top: 42%;
	width: 168rpx;
	height: 168rpx;
	margin-left: -84rpx;
	margin-top: -84rpx;
	animation: hamOrbFloat 3.6s ease-in-out infinite;
}
.ham-stage--sm .ham-orb-wrap {
	width: 108rpx;
	height: 108rpx;
	margin-left: -54rpx;
	margin-top: -54rpx;
}
.ham-orb-wrap--speak {
	animation: hamOrbSpeak 0.85s ease-in-out infinite;
}

.ham-orb-shell {
	position: relative;
	width: 100%;
	height: 100%;
	transform: rotate(-45deg);
}

/* 玻璃心形：两圆 + 方形 */
.ham-orb-glass,
.ham-orb-glass::before,
.ham-orb-glass::after {
	position: absolute;
	background: linear-gradient(
		145deg,
		rgba(255, 255, 255, 0.75) 0%,
		rgba(186, 230, 253, 0.45) 35%,
		rgba(56, 189, 248, 0.35) 70%,
		rgba(14, 165, 233, 0.25) 100%
	);
	box-shadow:
		inset 0 0 40rpx rgba(255, 255, 255, 0.65),
		0 16rpx 48rpx rgba(14, 165, 233, 0.28);
	border: 2rpx solid rgba(255, 255, 255, 0.85);
}
.ham-orb-glass {
	width: 52%;
	height: 52%;
	left: 24%;
	top: 24%;
	border-radius: 8rpx;
}
.ham-orb-glass::before,
.ham-orb-glass::after {
	content: '';
	width: 52%;
	height: 52%;
	border-radius: 50%;
}
.ham-orb-glass::before {
	left: -26%;
	top: -26%;
}
.ham-orb-glass::after {
	left: 26%;
	top: -26%;
}

.ham-orb-swirl {
	position: absolute;
	border-radius: 50%;
	filter: blur(2rpx);
	opacity: 0.55;
}
.ham-orb-swirl--a {
	width: 70%;
	height: 70%;
	left: 15%;
	top: 12%;
	background: radial-gradient(circle, rgba(224, 242, 254, 0.9) 0%, transparent 68%);
	animation: hamSwirl 4s ease-in-out infinite;
}
.ham-orb-swirl--b {
	width: 45%;
	height: 45%;
	right: 8%;
	bottom: 18%;
	background: radial-gradient(circle, rgba(125, 211, 252, 0.7) 0%, transparent 70%);
	animation: hamSwirl 5s ease-in-out infinite reverse;
}

.ham-orb-core {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 28%;
	height: 28%;
	margin: -14% 0 0 -14%;
	border-radius: 50%;
	background: radial-gradient(circle at 35% 35%, #fff 0%, #7dd3fc 45%, #0ea5e9 100%);
	box-shadow: 0 0 28rpx rgba(56, 189, 248, 0.95);
	animation: hamCorePulse 2.8s ease-in-out infinite;
}
.ham-orb-wrap--speak .ham-orb-core {
	animation: hamCoreSpeak 0.55s ease-in-out infinite;
}

.ham-orb-highlight {
	position: absolute;
	left: 22%;
	top: 18%;
	width: 22%;
	height: 14%;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.75);
	filter: blur(2rpx);
	transform: rotate(45deg);
}

.ham-heart-shadow {
	position: absolute;
	left: 50%;
	bottom: -18rpx;
	width: 70%;
	height: 16rpx;
	margin-left: -35%;
	background: rgba(14, 165, 233, 0.2);
	border-radius: 50%;
	filter: blur(8rpx);
}

.ham-speak-rings {
	position: absolute;
	left: 50%;
	top: 42%;
	width: 200rpx;
	height: 200rpx;
	margin: -100rpx 0 0 -100rpx;
	pointer-events: none;
}
.ham-speak-ring {
	position: absolute;
	inset: 0;
	border-radius: 50%;
	border: 2rpx solid rgba(56, 189, 248, 0.35);
	animation: hamRingOut 1.6s ease-out infinite;
}
.ham-speak-ring--2 {
	animation-delay: 0.55s;
}

.ham-bubble {
	position: relative;
	margin-top: 4rpx;
	max-width: 94%;
	padding: 22rpx 32rpx;
	background: rgba(255, 255, 255, 0.82);
	border-radius: 20rpx;
	border: 1rpx solid rgba(186, 230, 253, 0.9);
	box-shadow: 0 12rpx 40rpx rgba(14, 165, 233, 0.1);
}
.ham-bubble-text {
	display: block;
	font-size: 28rpx;
	line-height: 1.55;
	color: #334155;
	text-align: center;
	font-weight: 500;
}
.ham-stage--sm .ham-bubble-text {
	font-size: 22rpx;
}
.ham-stage--speaking .ham-bubble {
	box-shadow: 0 14rpx 44rpx rgba(14, 165, 233, 0.18);
}

@keyframes hamOrbitSpin {
	from {
		transform: rotateX(68deg) rotateZ(0deg);
	}
	to {
		transform: rotateX(68deg) rotateZ(360deg);
	}
}
@keyframes hamOrbFloat {
	0%,
	100% {
		transform: translateY(0) scale(1);
	}
	50% {
		transform: translateY(-10rpx) scale(1.02);
	}
}
@keyframes hamOrbSpeak {
	0%,
	100% {
		transform: translateY(0) scale(1.03);
	}
	50% {
		transform: translateY(-4rpx) scale(1.06);
	}
}
@keyframes hamCorePulse {
	0%,
	100% {
		opacity: 0.92;
		transform: scale(1);
	}
	50% {
		opacity: 1;
		transform: scale(1.08);
	}
}
@keyframes hamCoreSpeak {
	0%,
	100% {
		transform: scale(1.05);
		box-shadow: 0 0 28rpx rgba(56, 189, 248, 0.95);
	}
	50% {
		transform: scale(1.18);
		box-shadow: 0 0 40rpx rgba(56, 189, 248, 1);
	}
}
@keyframes hamSwirl {
	0%,
	100% {
		opacity: 0.45;
		transform: scale(1);
	}
	50% {
		opacity: 0.75;
		transform: scale(1.06);
	}
}
@keyframes hamRingOut {
	0% {
		transform: scale(0.75);
		opacity: 0.55;
	}
	100% {
		transform: scale(1.15);
		opacity: 0;
	}
}
</style>
