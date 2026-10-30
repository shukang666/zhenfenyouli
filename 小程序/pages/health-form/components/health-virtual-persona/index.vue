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

					<view class="hvp-char">
						<view class="hvp-arm hvp-arm--l">
							<view class="hvp-arm-bud"></view>
						</view>
						<view class="hvp-arm hvp-arm--r">
							<view class="hvp-arm-bud"></view>
						</view>

						<view class="hvp-heart">
							<view class="hvp-heart__lobe hvp-heart__lobe--l"></view>
							<view class="hvp-heart__lobe hvp-heart__lobe--r"></view>
							<view class="hvp-heart__tip"></view>
							<view class="hvp-heart__shine"></view>
							<view class="hvp-heart__glass"></view>

							<view class="hvp-heart__face">
								<view class="hvp-heart__eye hvp-heart__eye--l">
									<view class="hvp-heart__eye-ball">
										<view class="hvp-heart__pupil"></view>
									</view>
								</view>
								<view class="hvp-heart__eye hvp-heart__eye--r">
									<view class="hvp-heart__eye-ball">
										<view class="hvp-heart__pupil"></view>
									</view>
								</view>
								<view class="hvp-heart__smile">
									<view class="hvp-heart__smile-arc"></view>
								</view>
							</view>

							<view class="hvp-heart__badge">
								<view class="hvp-heart__cross-v"></view>
								<view class="hvp-heart__cross-h"></view>
							</view>
							<view class="hvp-heart__chip">
								<text class="hvp-heart__chip-text">AI+</text>
							</view>
						</view>
					</view>

					<view class="hvp-pedestal"></view>
				</view>
			</view>

			<view class="hvp-spark hvp-spark--1"></view>
			<view class="hvp-spark hvp-spark--2"></view>
			<view class="hvp-spark hvp-spark--3"></view>
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
	height: 560rpx;
}

.hvp--hero .hvp-figure {
	transform: scale(1);
}

.hvp--lg .hvp-stage {
	height: 460rpx;
}

.hvp--lg .hvp-figure {
	transform: scale(0.86);
}

.hvp--md .hvp-stage {
	height: 340rpx;
}

.hvp--md .hvp-figure {
	transform: scale(0.68);
}

.hvp--speaking .hvp-motion {
	animation: hvp-float-speak 2.6s ease-in-out infinite;
}

.hvp--speaking .hvp-heart {
	animation: hvp-heart-pulse 2.4s ease-in-out infinite;
}

.hvp--speaking .hvp-heart__smile-arc {
	height: 12rpx;
	border-bottom-width: 5rpx;
}

.hvp--speaking .hvp-orbit {
	animation: hvp-orbit-spin 8s linear infinite;
}

.hvp-stage {
	position: relative;
	width: 100%;
	height: 560rpx;
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
	top: 44%;
	transform: translate(-50%, -50%);
	border-radius: 50%;
	pointer-events: none;
}

.hvp-halo--tech {
	width: 460rpx;
	height: 460rpx;
	background: radial-gradient(
		circle,
		rgba(0, 229, 255, 0.1) 0%,
		rgba(66, 165, 245, 0.18) 40%,
		rgba(21, 101, 216, 0) 72%
	);
}

.hvp-halo--warm {
	width: 300rpx;
	height: 300rpx;
	background: radial-gradient(circle, rgba(244, 143, 177, 0.28) 0%, transparent 68%);
	animation: hvp-warm-pulse 5s ease-in-out infinite;
}

.hvp-speak-ring {
	position: absolute;
	left: 50%;
	top: 44%;
	border: 2rpx solid rgba(236, 64, 122, 0.35);
	border-radius: 50%;
	transform: translate(-50%, -50%);
	pointer-events: none;
	z-index: 1;
}

.hvp-speak-ring--outer {
	width: 400rpx;
	height: 400rpx;
	animation: hvp-ring-pulse 2.8s ease-out infinite;
}

.hvp-speak-ring--inner {
	width: 340rpx;
	height: 340rpx;
	border-color: rgba(255, 255, 255, 0.4);
	animation: hvp-ring-pulse 2.8s ease-out 0.7s infinite;
}

.hvp-figure {
	position: relative;
	width: 340rpx;
	height: 420rpx;
	transform-origin: center center;
}

.hvp-orbit {
	position: absolute;
	left: 50%;
	top: 72rpx;
	width: 280rpx;
	height: 280rpx;
	margin-left: -140rpx;
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
	border-top-color: rgba(0, 229, 255, 0.5);
	border-right-color: rgba(66, 165, 245, 0.18);
	transform: rotate(-20deg);
}

.hvp-orbit-arc--b {
	left: 16rpx;
	top: 16rpx;
	right: 16rpx;
	bottom: 16rpx;
	border-bottom-color: rgba(236, 64, 122, 0.35);
	border-left-color: rgba(255, 255, 255, 0.2);
	transform: rotate(16deg);
}

.hvp-orbit-dot {
	position: absolute;
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #00e5ff;
	box-shadow: 0 0 12rpx rgba(0, 229, 255, 0.75);
}

.hvp-orbit-dot--1 {
	right: 24rpx;
	top: 36rpx;
}

.hvp-orbit-dot--2 {
	left: 32rpx;
	bottom: 40rpx;
	opacity: 0.8;
}

.hvp-char {
	position: relative;
	z-index: 4;
	width: 280rpx;
	height: 320rpx;
	margin: 0 auto;
}

.hvp-heart {
	position: relative;
	left: 50%;
	top: 24rpx;
	width: 228rpx;
	height: 208rpx;
	margin-left: -114rpx;
	z-index: 3;
	filter: drop-shadow(0 16rpx 32rpx rgba(236, 64, 122, 0.28));
}

.hvp-heart__lobe {
	position: absolute;
	top: 0;
	width: 116rpx;
	height: 116rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #fce4ec 0%, #f48fb1 55%, #ec407a 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.55);
	box-shadow: inset 0 8rpx 16rpx rgba(255, 255, 255, 0.45);
}

.hvp-heart__lobe--l {
	left: 0;
}

.hvp-heart__lobe--r {
	right: 0;
}

.hvp-heart__tip {
	position: absolute;
	left: 50%;
	bottom: 2rpx;
	width: 118rpx;
	height: 118rpx;
	margin-left: -59rpx;
	background: linear-gradient(160deg, #f48fb1 0%, #ec407a 100%);
	transform: rotate(45deg);
	border-radius: 0 0 12rpx 0;
	border: 2rpx solid rgba(255, 255, 255, 0.4);
	box-shadow: inset 0 6rpx 12rpx rgba(255, 255, 255, 0.25);
}

.hvp-heart__shine {
	position: absolute;
	left: 28rpx;
	top: 24rpx;
	width: 56rpx;
	height: 40rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.55);
	filter: blur(8rpx);
	z-index: 4;
	pointer-events: none;
}

.hvp-heart__glass {
	position: absolute;
	left: 8rpx;
	top: 8rpx;
	right: 8rpx;
	height: 100rpx;
	border-radius: 50% 50% 40% 40%;
	background: linear-gradient(180deg, rgba(255, 255, 255, 0.35), transparent);
	z-index: 2;
	pointer-events: none;
}

.hvp-heart__face {
	position: absolute;
	left: 50%;
	top: 52rpx;
	width: 140rpx;
	height: 88rpx;
	margin-left: -70rpx;
	z-index: 5;
}

.hvp-heart__eye {
	position: absolute;
	top: 8rpx;
	width: 36rpx;
	height: 36rpx;
}

.hvp-heart__eye--l {
	left: 22rpx;
}

.hvp-heart__eye--r {
	right: 22rpx;
}

.hvp-heart__eye-ball {
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.95);
	box-shadow: 0 2rpx 8rpx rgba(21, 101, 216, 0.12);
	animation: hvp-blink 5.5s ease-in-out infinite;
}

.hvp-heart__pupil {
	position: absolute;
	left: 50%;
	top: 54%;
	width: 14rpx;
	height: 14rpx;
	margin-left: -7rpx;
	margin-top: -7rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #42a5f5, #1565d8);
}

.hvp-heart__smile {
	position: absolute;
	left: 50%;
	bottom: 4rpx;
	width: 44rpx;
	height: 22rpx;
	margin-left: -22rpx;
	display: flex;
	justify-content: center;
}

.hvp-heart__smile-arc {
	width: 32rpx;
	height: 10rpx;
	border-bottom: 4rpx solid rgba(255, 255, 255, 0.92);
	border-radius: 0 0 50% 50%;
	box-shadow: 0 2rpx 6rpx rgba(136, 14, 79, 0.15);
}

.hvp-heart__badge {
	position: absolute;
	right: 24rpx;
	bottom: 36rpx;
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.92);
	box-shadow: 0 4rpx 12rpx rgba(21, 101, 216, 0.2);
	z-index: 6;
}

.hvp-heart__cross-v,
.hvp-heart__cross-h {
	position: absolute;
	left: 50%;
	top: 50%;
	background: linear-gradient(180deg, #42a5f5, #1565d8);
	border-radius: 2rpx;
	transform: translate(-50%, -50%);
}

.hvp-heart__cross-v {
	width: 5rpx;
	height: 18rpx;
}

.hvp-heart__cross-h {
	width: 18rpx;
	height: 5rpx;
}

.hvp-heart__chip {
	position: absolute;
	left: 22rpx;
	bottom: 42rpx;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.88);
	border: 1rpx solid rgba(0, 229, 255, 0.45);
	z-index: 6;
}

.hvp-heart__chip-text {
	font-size: 18rpx;
	font-weight: 700;
	color: #1565d8;
	letter-spacing: 1rpx;
}

.hvp-arm {
	position: absolute;
	top: 108rpx;
	width: 56rpx;
	height: 88rpx;
	border-radius: 999rpx;
	background: linear-gradient(165deg, rgba(255, 255, 255, 0.95), rgba(248, 187, 208, 0.85));
	border: 2rpx solid rgba(255, 255, 255, 0.8);
	box-shadow: 0 8rpx 16rpx rgba(236, 64, 122, 0.15);
	z-index: 2;
}

.hvp-arm--l {
	left: -8rpx;
	transform: rotate(22deg);
}

.hvp-arm--r {
	right: -8rpx;
	transform: rotate(-18deg);
}

.hvp-arm-bud {
	position: absolute;
	left: 50%;
	bottom: -6rpx;
	width: 48rpx;
	height: 48rpx;
	margin-left: -24rpx;
	border-radius: 50%;
	background: linear-gradient(180deg, #ffffff, #fce4ec);
	border: 2rpx solid rgba(244, 143, 177, 0.45);
}

.hvp-pedestal {
	position: absolute;
	left: 50%;
	bottom: 8rpx;
	width: 220rpx;
	height: 26rpx;
	margin-left: -110rpx;
	border-radius: 50%;
	background: radial-gradient(ellipse, rgba(236, 64, 122, 0.18) 0%, transparent 72%);
}

.hvp-spark {
	position: absolute;
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: rgba(244, 143, 177, 0.85);
	box-shadow: 0 0 12rpx rgba(244, 143, 177, 0.6);
	pointer-events: none;
	z-index: 2;
}

.hvp-spark--1 {
	left: 12%;
	top: 30%;
	animation: hvp-spark 3.2s ease-in-out infinite;
}

.hvp-spark--2 {
	right: 14%;
	top: 38%;
	width: 8rpx;
	height: 8rpx;
	animation: hvp-spark 4s ease-in-out 0.6s infinite;
}

.hvp-spark--3 {
	right: 20%;
	bottom: 26%;
	width: 6rpx;
	height: 6rpx;
	background: rgba(0, 229, 255, 0.75);
	box-shadow: 0 0 10rpx rgba(0, 229, 255, 0.5);
	animation: hvp-spark 3.5s ease-in-out 1.2s infinite;
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

@keyframes hvp-heart-pulse {
	0%,
	100% {
		transform: scale(1);
	}
	50% {
		transform: scale(1.04);
	}
}

@keyframes hvp-ring-pulse {
	0% {
		opacity: 0.25;
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

@keyframes hvp-blink {
	0%,
	90%,
	100% {
		transform: scaleY(1);
	}
	93% {
		transform: scaleY(0.15);
	}
	96% {
		transform: scaleY(1);
	}
}

@keyframes hvp-spark {
	0%,
	100% {
		opacity: 0.4;
		transform: translateY(0) scale(0.85);
	}
	50% {
		opacity: 1;
		transform: translateY(-8rpx) scale(1.1);
	}
}
</style>
