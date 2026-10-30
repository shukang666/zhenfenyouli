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
			<view class="hvp-css-mascot">
				<view class="hvp-halo hvp-halo--a"></view>
				<view class="hvp-halo hvp-halo--b"></view>
				<view class="hvp-motion">
					<view class="hvp-mascot">
						<view class="hvp-mascot__figure">
							<view class="hvp-mascot__float-ring">
								<view class="hvp-mascot__float-ring-glow"></view>
							</view>

							<view class="hvp-mascot__arm hvp-mascot__arm--l">
								<view class="hvp-mascot__arm-oval"></view>
							</view>
							<view class="hvp-mascot__arm hvp-mascot__arm--r">
								<view class="hvp-mascot__arm-oval"></view>
							</view>

							<view class="hvp-mascot__body">
								<view class="hvp-mascot__body-shine"></view>
								<view class="hvp-mascot__panel hvp-mascot__panel--v"></view>
								<view class="hvp-mascot__panel hvp-mascot__panel--h"></view>
								<view class="hvp-mascot__circuit hvp-mascot__circuit--a"></view>
								<view class="hvp-mascot__circuit hvp-mascot__circuit--b"></view>
								<view class="hvp-mascot__chest">
									<view class="hvp-mascot__chest-glow"></view>
									<view class="hvp-mascot__chest-ring"></view>
									<view class="hvp-mascot__heart">
										<view class="hvp-mascot__heart-lobe hvp-mascot__heart-lobe--l"></view>
										<view class="hvp-mascot__heart-lobe hvp-mascot__heart-lobe--r"></view>
										<view class="hvp-mascot__heart-tip"></view>
									</view>
								</view>
							</view>

							<view class="hvp-mascot__head-block">
								<view class="hvp-mascot__helmet">
									<view class="hvp-mascot__helmet-dome"></view>
									<view class="hvp-mascot__helmet-band">
										<view class="hvp-mascot__helmet-light"></view>
									</view>
								</view>
								<view class="hvp-mascot__face">
									<view class="hvp-mascot__face-glow"></view>
									<view class="hvp-mascot__brow hvp-mascot__brow--l">
										<view class="hvp-mascot__brow-arc"></view>
									</view>
									<view class="hvp-mascot__brow hvp-mascot__brow--r">
										<view class="hvp-mascot__brow-arc"></view>
									</view>
									<view class="hvp-mascot__eye hvp-mascot__eye--l">
										<view class="hvp-mascot__eye-white">
											<view class="hvp-mascot__pupil"></view>
											<view class="hvp-mascot__eye-spark"></view>
											<view class="hvp-mascot__eye-spark hvp-mascot__eye-spark--sm"></view>
										</view>
									</view>
									<view class="hvp-mascot__eye hvp-mascot__eye--r">
										<view class="hvp-mascot__eye-white">
											<view class="hvp-mascot__pupil"></view>
											<view class="hvp-mascot__eye-spark"></view>
											<view class="hvp-mascot__eye-spark hvp-mascot__eye-spark--sm"></view>
										</view>
									</view>
									<view class="hvp-mascot__cheek hvp-mascot__cheek--l"></view>
									<view class="hvp-mascot__cheek hvp-mascot__cheek--r"></view>
									<view class="hvp-mascot__mouth">
										<view class="hvp-mascot__mouth-smile"></view>
										<view class="hvp-mascot__mouth-talk" v-if="speaking"></view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="hvp-speak-ring" v-if="speaking"></view>
				<view class="hvp-speak-ring hvp-speak-ring--delay" v-if="speaking"></view>
			</view>
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
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.hvp--embedded {
	margin: 0;
}

.hvp--embedded .hvp-stage {
	pointer-events: none;
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

.hvp-css-mascot {
	position: relative;
	z-index: 1;
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
	animation: hvpHaloDrift 6s ease-in-out infinite;
}

.hvp-halo--a {
	width: 88%;
	height: 72%;
	background: radial-gradient(circle, rgba(144, 202, 249, 0.45) 0%, transparent 70%);
}

.hvp-halo--b {
	width: 70%;
	height: 62%;
	background: radial-gradient(circle, rgba(0, 188, 212, 0.12) 0%, transparent 72%);
	animation-delay: -2.8s;
	animation-duration: 7.2s;
}

.hvp-motion {
	position: relative;
	z-index: 2;
	animation: hvpIdleBob 3.2s ease-in-out infinite;
}

.hvp--speaking .hvp-motion {
	animation-duration: 2s;
}

/* —— 3D 数字健康助手「小健」—— */
.hvp-mascot {
	position: relative;
	width: 340rpx;
	height: 460rpx;
}

.hvp--hero .hvp-mascot {
	width: 400rpx;
	height: 540rpx;
}

.hvp--md .hvp-mascot {
	width: 280rpx;
	height: 380rpx;
}

.hvp-mascot__figure {
	position: relative;
	width: 100%;
	height: 100%;
}

.hvp-mascot__float-ring {
	position: absolute;
	left: 50%;
	bottom: 0;
	width: 72%;
	height: 28rpx;
	margin-left: -36%;
	border-radius: 50%;
	background: radial-gradient(
		ellipse,
		rgba(79, 195, 247, 0.55) 0%,
		rgba(33, 150, 243, 0.35) 45%,
		rgba(33, 150, 243, 0) 72%
	);
	box-shadow:
		0 0 20rpx rgba(79, 195, 247, 0.65),
		0 0 48rpx rgba(41, 182, 246, 0.35);
	animation: hvpRingPulse 2.8s ease-in-out infinite;
}

.hvp-mascot__float-ring-glow {
	position: absolute;
	left: 10%;
	top: -6rpx;
	width: 80%;
	height: 12rpx;
	border-radius: 50%;
	border: 3rpx solid rgba(129, 212, 250, 0.85);
	box-shadow: 0 0 16rpx rgba(129, 212, 250, 0.9);
}

.hvp-mascot__body {
	position: absolute;
	left: 50%;
	top: 38%;
	width: 58%;
	height: 46%;
	margin-left: -29%;
	border-radius: 50%;
	background: radial-gradient(
		circle at 32% 28%,
		#ffffff 0%,
		#f4f9ff 28%,
		#dceefb 62%,
		#b8d4f0 100%
	);
	box-shadow:
		0 22rpx 40rpx rgba(21, 101, 216, 0.22),
		0 8rpx 16rpx rgba(255, 255, 255, 0.9) inset,
		0 -14rpx 28rpx rgba(144, 202, 249, 0.35) inset,
		-6rpx -6rpx 12rpx rgba(255, 255, 255, 0.6) inset;
	z-index: 2;
}

.hvp-mascot__body-shine {
	position: absolute;
	left: 14%;
	top: 10%;
	width: 38%;
	height: 32%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.92) 0%, transparent 70%);
	filter: blur(2rpx);
}

.hvp-mascot__panel {
	position: absolute;
	background: rgba(144, 202, 249, 0.22);
	border-radius: 2rpx;
}

.hvp-mascot__panel--v {
	left: 50%;
	top: 18%;
	width: 2rpx;
	height: 52%;
	margin-left: -1rpx;
}

.hvp-mascot__panel--h {
	left: 22%;
	top: 42%;
	width: 56%;
	height: 2rpx;
}

.hvp-mascot__circuit {
	position: absolute;
	width: 24rpx;
	height: 2rpx;
	background: rgba(0, 188, 212, 0.45);
	box-shadow: 0 0 8rpx rgba(0, 188, 212, 0.5);
}

.hvp-mascot__circuit--a {
	left: 18%;
	top: 28%;
	transform: rotate(-24deg);
}

.hvp-mascot__circuit--b {
	right: 16%;
	top: 52%;
	transform: rotate(18deg);
}

.hvp-mascot__chest {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 38%;
	height: 38%;
	margin-left: -19%;
	margin-top: -19%;
}

.hvp-mascot__chest-glow {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(79, 195, 247, 0.55) 0%, rgba(33, 150, 243, 0) 72%);
	animation: hvpChestGlow 2.4s ease-in-out infinite;
}

.hvp-mascot__chest-ring {
	position: absolute;
	left: 8%;
	top: 8%;
	width: 84%;
	height: 84%;
	border-radius: 50%;
	border: 4rpx solid rgba(100, 181, 246, 0.85);
	box-shadow:
		0 0 20rpx rgba(79, 195, 247, 0.7),
		inset 0 0 16rpx rgba(255, 255, 255, 0.35);
	background: radial-gradient(circle at 50% 40%, rgba(227, 242, 253, 0.95) 0%, rgba(144, 202, 249, 0.45) 100%);
}

.hvp-mascot__heart {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 28rpx;
	height: 26rpx;
	margin-left: -14rpx;
	margin-top: -13rpx;
}

.hvp-mascot__heart-lobe {
	position: absolute;
	width: 14rpx;
	height: 18rpx;
	top: 0;
	background: linear-gradient(145deg, #ff8a80, #e53935);
	border-radius: 14rpx 14rpx 0 0;
	box-shadow: 0 2rpx 6rpx rgba(229, 57, 53, 0.35);
}

.hvp-mascot__heart-lobe--l {
	left: 0;
	transform: rotate(-45deg);
	transform-origin: 100% 100%;
}

.hvp-mascot__heart-lobe--r {
	right: 0;
	transform: rotate(45deg);
	transform-origin: 0 100%;
}

.hvp-mascot__heart-tip {
	position: absolute;
	width: 16rpx;
	height: 16rpx;
	left: 6rpx;
	top: 9rpx;
	transform: rotate(45deg);
	background: #e53935;
	border-radius: 0 0 4rpx 0;
}

.hvp-mascot__arm {
	position: absolute;
	top: 51%;
	width: 48rpx;
	z-index: 3;
	transform-origin: 50% 0;
}

.hvp-mascot__arm--l {
	left: 7%;
	transform: rotate(13deg);
	animation: hvpArmSwayL 4.2s ease-in-out infinite;
	animation-delay: -1s;
}

.hvp-mascot__arm--r {
	right: 7%;
	transform: rotate(-13deg);
	animation: hvpArmSwayR 4.2s ease-in-out infinite;
	animation-delay: -2.2s;
}

.hvp--speaking .hvp-mascot__arm--r {
	animation: hvpArmWave 1.2s ease-in-out infinite;
}

/* 单段长椭圆臂：上白下略收，末端肉色掌 */
.hvp-mascot__arm-oval {
	width: 44rpx;
	height: 152rpx;
	border-radius: 50%;
	background: linear-gradient(
		175deg,
		#ffffff 0%,
		#f4f9ff 22%,
		#e3f2fd 55%,
		#cfe6f9 78%,
		#fde2d2 92%,
		#f5c4b8 100%
	);
	box-shadow:
		0 10rpx 22rpx rgba(21, 101, 216, 0.14),
		inset 0 8rpx 16rpx rgba(255, 255, 255, 0.92),
		inset 0 -10rpx 18rpx rgba(100, 181, 246, 0.22);
}

.hvp-mascot__head-block {
	position: absolute;
	left: 50%;
	top: 0;
	width: 68%;
	height: 52%;
	margin-left: -34%;
	z-index: 4;
}

.hvp-mascot__helmet {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 52%;
	z-index: 3;
}

.hvp-mascot__helmet-dome {
	position: absolute;
	left: 6%;
	top: 0;
	width: 88%;
	height: 88%;
	border-radius: 50% 50% 42% 42%;
	background: radial-gradient(circle at 35% 25%, #ffffff 0%, #eef6ff 40%, #d4e8fa 100%);
	box-shadow:
		0 14rpx 28rpx rgba(21, 101, 216, 0.18),
		inset 0 8rpx 14rpx rgba(255, 255, 255, 0.95),
		inset 0 -10rpx 18rpx rgba(144, 202, 249, 0.25);
}

.hvp-mascot__helmet-band {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 34%;
	border-radius: 0 0 40rpx 40rpx;
	background: linear-gradient(180deg, #42a5f5 0%, #1565c0 55%, #0d47a1 100%);
	box-shadow:
		0 6rpx 14rpx rgba(13, 71, 161, 0.35),
		inset 0 2rpx 6rpx rgba(255, 255, 255, 0.35);
}

.hvp-mascot__helmet-light {
	position: absolute;
	right: 14%;
	top: 50%;
	width: 10rpx;
	height: 18rpx;
	margin-top: -9rpx;
	border-radius: 4rpx;
	background: linear-gradient(180deg, #ff8a65, #e64a19);
	box-shadow: 0 0 10rpx rgba(255, 138, 101, 0.85);
}

.hvp-mascot__face {
	position: absolute;
	left: 50%;
	bottom: 0;
	width: 92%;
	height: 78%;
	margin-left: -46%;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #fffdfb 0%, #fff0e8 38%, #fde2d2 72%, #f5c4b8 100%);
	box-shadow:
		0 16rpx 32rpx rgba(21, 101, 216, 0.12),
		inset 0 10rpx 20rpx rgba(255, 255, 255, 0.85),
		inset 0 -8rpx 16rpx rgba(244, 143, 177, 0.12);
	overflow: hidden;
	z-index: 2;
}

.hvp-mascot__face-glow {
	position: absolute;
	left: 20%;
	top: 14%;
	width: 48%;
	height: 36%;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.5);
	filter: blur(12rpx);
}

.hvp-mascot__brow {
	position: absolute;
	top: 24%;
	width: 16%;
	height: 12rpx;
	overflow: hidden;
	z-index: 2;
}

.hvp-mascot__brow--l {
	left: 22%;
	transform: rotate(-5deg);
}

.hvp-mascot__brow--r {
	right: 22%;
	transform: rotate(5deg) scaleX(-1);
}

.hvp-mascot__brow-arc {
	width: 100%;
	height: 200%;
	border-top: 3rpx solid rgba(188, 170, 164, 0.55);
	border-radius: 50% 50% 0 0;
	box-sizing: border-box;
}

.hvp-mascot__eye {
	position: absolute;
	top: 38%;
	width: 22%;
	height: 26%;
	z-index: 2;
}

.hvp-mascot__eye--l {
	left: 20%;
}

.hvp-mascot__eye--r {
	right: 20%;
}

.hvp-mascot__eye-white {
	position: relative;
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: #fff;
	border: 2rpx solid rgba(188, 170, 164, 0.15);
	box-shadow: inset 0 -4rpx 8rpx rgba(0, 0, 0, 0.04);
	animation: hvpBlink 6.5s ease-in-out infinite;
}

.hvp-mascot__pupil {
	position: absolute;
	left: 50%;
	top: 52%;
	width: 50%;
	height: 50%;
	margin-left: -25%;
	margin-top: -25%;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 30%, #8d6e63, #5d4037);
}

.hvp-mascot__eye-spark {
	position: absolute;
	right: 22%;
	top: 22%;
	width: 22%;
	height: 22%;
	border-radius: 50%;
	background: #fff;
}

.hvp-mascot__eye-spark--sm {
	right: auto;
	left: 26%;
	top: 36%;
	width: 10%;
	height: 10%;
}

.hvp-mascot__cheek {
	position: absolute;
	top: 56%;
	width: 20%;
	height: 14%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 171, 145, 0.45) 0%, transparent 72%);
}

.hvp-mascot__cheek--l {
	left: 12%;
}

.hvp-mascot__cheek--r {
	right: 12%;
}

.hvp-mascot__mouth {
	position: absolute;
	left: 50%;
	bottom: 17%;
	width: 26%;
	height: 22rpx;
	margin-left: -13%;
	z-index: 2;
	overflow: visible;
}

.hvp-mascot__mouth-smile {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 22rpx;
	border-bottom: 3rpx solid rgba(230, 180, 168, 0.82);
	border-radius: 0 0 50% 50% / 0 0 100% 100%;
	box-sizing: border-box;
}

.hvp-mascot__mouth-talk {
	position: absolute;
	left: 50%;
	bottom: -10rpx;
	width: 18rpx;
	height: 12rpx;
	margin-left: -9rpx;
	border-radius: 50%;
	background: rgba(248, 180, 168, 0.75);
	border: none;
	animation: hvpMouthTalk 0.5s ease-in-out infinite alternate;
}

.hvp-speak-ring {
	position: absolute;
	left: 50%;
	top: 48%;
	width: 54%;
	height: 50%;
	margin-left: -27%;
	margin-top: -25%;
	border: 3rpx solid rgba(129, 212, 250, 0.55);
	border-radius: 50%;
	pointer-events: none;
	animation: hvpSpeakRing 2s ease-out infinite;
}

.hvp-speak-ring--delay {
	animation-delay: 0.65s;
	border-color: rgba(100, 181, 246, 0.35);
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

@keyframes hvpIdleBob {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-12rpx);
	}
}

@keyframes hvpRingPulse {
	0%,
	100% {
		transform: scaleX(1);
		opacity: 0.9;
	}
	50% {
		transform: scaleX(1.06);
		opacity: 1;
	}
}

@keyframes hvpChestGlow {
	0%,
	100% {
		opacity: 0.75;
		transform: scale(1);
	}
	50% {
		opacity: 1;
		transform: scale(1.06);
	}
}

@keyframes hvpArmSwayL {
	0%,
	100% {
		transform: rotate(11deg);
	}
	50% {
		transform: rotate(15deg);
	}
}

@keyframes hvpArmSwayR {
	0%,
	100% {
		transform: rotate(-11deg);
	}
	50% {
		transform: rotate(-15deg);
	}
}

@keyframes hvpArmWave {
	0%,
	100% {
		transform: rotate(-11deg);
	}
	50% {
		transform: rotate(-24deg);
	}
}

@keyframes hvpBlink {
	0%,
	92%,
	100% {
		transform: scaleY(1);
	}
	94% {
		transform: scaleY(0.1);
	}
	96% {
		transform: scaleY(1);
	}
}

@keyframes hvpMouthTalk {
	0% {
		transform: scaleY(0.65);
	}
	100% {
		transform: scaleY(1.08);
	}
}

@keyframes hvpHaloDrift {
	0%,
	100% {
		transform: scale(1) translate(0, 0);
		opacity: 0.7;
	}
	50% {
		transform: scale(1.06) translate(4rpx, -6rpx);
		opacity: 0.95;
	}
}

@keyframes hvpSpeakRing {
	0% {
		opacity: 0.5;
		transform: scale(0.92);
	}
	100% {
		opacity: 0;
		transform: scale(1.22);
	}
}
</style>
