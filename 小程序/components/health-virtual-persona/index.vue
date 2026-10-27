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
			<view class="hvp-aura"></view>
			<view class="hvp-aura hvp-aura--b"></view>

			<template v-if="speaking">
				<view class="hvp-wave hvp-wave--1"></view>
				<view class="hvp-wave hvp-wave--2"></view>
				<view class="hvp-wave hvp-wave--3"></view>
				<view class="hvp-wave hvp-wave--4"></view>
				<view class="hvp-wave hvp-wave--5"></view>
			</template>

			<view class="hvp-orbit">
				<view class="hvp-ring hvp-ring--a"></view>
				<view class="hvp-ring hvp-ring--b"></view>
				<view class="hvp-ring hvp-ring--c"></view>
			</view>

			<view class="hvp-core"></view>
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
	height: 480rpx;
}

.hvp--lg .hvp-stage {
	height: 360rpx;
}

.hvp--md .hvp-stage {
	height: 280rpx;
}

.hvp-aura {
	position: absolute;
	width: 76%;
	height: 76%;
	border-radius: 50%;
	background: radial-gradient(
		circle,
		rgba(79, 195, 247, 0.4) 0%,
		rgba(21, 101, 216, 0.14) 48%,
		transparent 72%
	);
	filter: blur(10rpx);
	animation: hvpAura 4.5s ease-in-out infinite;
}

.hvp-aura--b {
	width: 92%;
	height: 92%;
	animation-delay: -2.2s;
	opacity: 0.75;
}

.hvp-orbit {
	position: relative;
	width: 80%;
	height: 80%;
	max-width: 560rpx;
	max-height: 560rpx;
}

.hvp-ring {
	position: absolute;
	inset: 0;
	border: 6rpx solid rgba(129, 212, 250, 0.9);
	box-shadow:
		0 0 28rpx rgba(79, 195, 247, 0.6),
		0 0 72rpx rgba(21, 101, 216, 0.38),
		inset 0 0 32rpx rgba(144, 202, 249, 0.28);
	animation: hvpMorph 5.5s ease-in-out infinite;
}

.hvp-ring--a {
	border-color: rgba(187, 222, 251, 0.98);
	animation-duration: 5s;
}

.hvp-ring--b {
	inset: 9%;
	border-width: 4rpx;
	border-color: rgba(66, 165, 245, 0.8);
	opacity: 0.88;
	animation-duration: 6.8s;
	animation-direction: reverse;
}

.hvp-ring--c {
	inset: 18%;
	border-width: 3rpx;
	border-color: rgba(229, 57, 53, 0.5);
	opacity: 0.7;
	animation-duration: 4.5s;
}

.hvp--speaking .hvp-ring {
	animation-name: hvpMorphSpeak;
}

.hvp--speaking .hvp-ring--a {
	animation-duration: 1.6s;
	box-shadow:
		0 0 40rpx rgba(79, 195, 247, 0.85),
		0 0 100rpx rgba(21, 101, 216, 0.5),
		0 0 32rpx rgba(229, 57, 53, 0.35);
}

.hvp--speaking .hvp-ring--b {
	animation-duration: 2.1s;
}

.hvp--speaking .hvp-ring--c {
	animation-duration: 1.3s;
}

.hvp-core {
	position: absolute;
	width: 40%;
	height: 40%;
	border-radius: 50%;
	background: radial-gradient(
		circle at 35% 30%,
		rgba(13, 71, 161, 0.2) 0%,
		rgba(0, 0, 0, 0.6) 55%,
		rgba(0, 0, 0, 0.88) 100%
	);
	box-shadow: inset 0 0 48rpx rgba(79, 195, 247, 0.18);
}

.hvp-wave {
	position: absolute;
	width: 62%;
	height: 62%;
	border: 4rpx solid rgba(144, 202, 249, 0.55);
	pointer-events: none;
	animation:
		hvpWaveBurst 2.4s cubic-bezier(0.22, 0.61, 0.36, 1) infinite,
		hvpMorphSpeak 1.1s ease-in-out infinite;
}

.hvp-wave--1 {
	animation-delay: 0s, 0s;
}

.hvp-wave--2 {
	animation-delay: 0.35s, -0.2s;
	border-color: rgba(100, 181, 246, 0.45);
}

.hvp-wave--3 {
	animation-delay: 0.7s, -0.45s;
	border-width: 3rpx;
}

.hvp-wave--4 {
	animation-delay: 1.05s, -0.7s;
	border-color: rgba(229, 57, 53, 0.35);
}

.hvp-wave--5 {
	animation-delay: 1.4s, -0.95s;
	border-width: 2rpx;
	opacity: 0.85;
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

@keyframes hvpMorph {
	0%,
	100% {
		border-radius: 46% 54% 51% 49% / 49% 46% 54% 51%;
		transform: rotate(0deg) scale(1);
	}
	25% {
		border-radius: 58% 42% 48% 52% / 44% 56% 43% 57%;
		transform: rotate(2deg) scale(1.02);
	}
	50% {
		border-radius: 43% 57% 55% 45% / 52% 43% 57% 48%;
		transform: rotate(-2deg) scale(0.98);
	}
	75% {
		border-radius: 52% 48% 42% 58% / 57% 49% 51% 43%;
		transform: rotate(1deg) scale(1.01);
	}
}

@keyframes hvpMorphSpeak {
	0%,
	100% {
		border-radius: 40% 60% 58% 42% / 42% 58% 40% 60%;
		transform: rotate(-3deg) scale(1.04);
	}
	20% {
		border-radius: 62% 38% 44% 56% / 48% 52% 60% 40%;
		transform: rotate(4deg) scale(1.08);
	}
	45% {
		border-radius: 36% 64% 62% 38% / 60% 38% 62% 40%;
		transform: rotate(-5deg) scale(0.96);
	}
	70% {
		border-radius: 55% 45% 38% 62% / 44% 66% 34% 56%;
		transform: rotate(3deg) scale(1.06);
	}
}

@keyframes hvpAura {
	0%,
	100% {
		opacity: 0.65;
		transform: scale(1);
	}
	50% {
		opacity: 1;
		transform: scale(1.08);
	}
}

@keyframes hvpWaveBurst {
	0% {
		opacity: 0.75;
		transform: scale(0.82);
	}
	70% {
		opacity: 0.15;
	}
	100% {
		opacity: 0;
		transform: scale(1.55);
	}
}
</style>
