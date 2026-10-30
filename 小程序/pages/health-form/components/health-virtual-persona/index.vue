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
				<view class="hvp-halo hvp-halo--warm"></view>
				<view class="hvp-care-heart hvp-care-heart--1">
					<view class="hvp-care-heart__lobe hvp-care-heart__lobe--a"></view>
					<view class="hvp-care-heart__lobe hvp-care-heart__lobe--b"></view>
				</view>
				<view class="hvp-care-heart hvp-care-heart--2">
					<view class="hvp-care-heart__lobe hvp-care-heart__lobe--a"></view>
					<view class="hvp-care-heart__lobe hvp-care-heart__lobe--b"></view>
				</view>
				<view class="hvp-motion">
					<view class="hvp-mascot">
						<view class="hvp-mascot__shadow"></view>
						<view class="hvp-mascot__figure">
							<view class="hvp-mascot__arm hvp-mascot__arm--l">
								<view class="hvp-mascot__sleeve"></view>
								<view class="hvp-mascot__glove"></view>
							</view>
							<view class="hvp-mascot__arm hvp-mascot__arm--r">
								<view class="hvp-mascot__sleeve"></view>
								<view class="hvp-mascot__glove"></view>
							</view>
							<view class="hvp-mascot__torso">
								<view class="hvp-mascot__neck-bridge"></view>
								<view class="hvp-mascot__scrub-neck"></view>
								<view class="hvp-mascot__coat-shine"></view>
								<view class="hvp-mascot__stetho">
									<view class="hvp-mascot__stetho-yoke"></view>
									<view class="hvp-mascot__stetho-tube hvp-mascot__stetho-tube--a"></view>
									<view class="hvp-mascot__stetho-disk"></view>
								</view>
								<view class="hvp-mascot__badge">
									<view class="hvp-mascot__badge-v"></view>
									<view class="hvp-mascot__badge-h"></view>
								</view>
							</view>
							<view class="hvp-mascot__head">
								<view class="hvp-mascot__cap">
									<view class="hvp-mascot__cap-band"></view>
									<view class="hvp-mascot__cap-cross">
										<view class="hvp-mascot__cap-cross-v"></view>
										<view class="hvp-mascot__cap-cross-h"></view>
									</view>
								</view>
								<view class="hvp-mascot__face">
									<view class="hvp-mascot__face-glow"></view>
									<view class="hvp-mascot__brow hvp-mascot__brow--l"></view>
									<view class="hvp-mascot__brow hvp-mascot__brow--r"></view>
									<view class="hvp-mascot__eye hvp-mascot__eye--l">
										<view class="hvp-mascot__eye-ball">
											<view class="hvp-mascot__eye-pupil"></view>
											<view class="hvp-mascot__eye-shine"></view>
										</view>
									</view>
									<view class="hvp-mascot__eye hvp-mascot__eye--r">
										<view class="hvp-mascot__eye-ball">
											<view class="hvp-mascot__eye-pupil"></view>
											<view class="hvp-mascot__eye-shine"></view>
										</view>
									</view>
									<view class="hvp-mascot__cheek hvp-mascot__cheek--l"></view>
									<view class="hvp-mascot__cheek hvp-mascot__cheek--r"></view>
									<view class="hvp-mascot__smile">
										<view class="hvp-mascot__smile-arc"></view>
										<view class="hvp-mascot__smile-talk"></view>
									</view>
								</view>
								<view class="hvp-mascot__head-shine"></view>
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
	background: radial-gradient(circle, rgba(144, 202, 249, 0.5) 0%, transparent 70%);
}

.hvp-halo--b {
	width: 70%;
	height: 62%;
	background: radial-gradient(circle, rgba(76, 175, 80, 0.14) 0%, transparent 72%);
	animation-delay: -2.8s;
	animation-duration: 7.2s;
}

.hvp-halo--warm {
	width: 54%;
	height: 48%;
	background: radial-gradient(circle, rgba(255, 183, 178, 0.28) 0%, transparent 72%);
	animation-delay: -1.2s;
	animation-duration: 5.4s;
}

.hvp-care-heart {
	position: absolute;
	width: 22rpx;
	height: 20rpx;
	pointer-events: none;
	opacity: 0.75;
	animation: hvpCareSpark 4.2s ease-in-out infinite;
}

.hvp-care-heart--1 {
	left: 14%;
	top: 28%;
	transform: rotate(-18deg);
}

.hvp-care-heart--2 {
	right: 12%;
	top: 34%;
	width: 18rpx;
	height: 16rpx;
	animation-delay: -2s;
	transform: rotate(12deg);
}

.hvp-care-heart__lobe {
	position: absolute;
	width: 50%;
	height: 80%;
	background: #ff8a80;
	border-radius: 4rpx 4rpx 0 4rpx;
}

.hvp-care-heart__lobe--a {
	left: 0;
	top: 0;
	transform: rotate(45deg);
	transform-origin: 100% 100%;
}

.hvp-care-heart__lobe--b {
	right: 0;
	top: 0;
	transform: rotate(-45deg);
	transform-origin: 0 100%;
}

.hvp-motion {
	position: relative;
	z-index: 2;
	animation: hvpIdleBob 3.2s ease-in-out infinite;
}

.hvp--speaking .hvp-motion {
	animation-duration: 2s;
}

/* —— 拟人吉祥物「小健」：护士帽 + 听诊器 + 白大褂 —— */
.hvp-mascot {
	position: relative;
	width: 320rpx;
	height: 380rpx;
}

.hvp--hero .hvp-mascot {
	width: 380rpx;
	height: 440rpx;
}

.hvp--md .hvp-mascot {
	width: 260rpx;
	height: 310rpx;
}

.hvp-mascot__shadow {
	position: absolute;
	left: 50%;
	bottom: 8%;
	width: 62%;
	height: 22rpx;
	margin-left: -31%;
	background: radial-gradient(ellipse, rgba(21, 101, 216, 0.2) 0%, transparent 72%);
	border-radius: 50%;
	animation: hvpShadowPulse 3.2s ease-in-out infinite;
}

.hvp-mascot__figure {
	position: relative;
	width: 100%;
	height: 100%;
}

.hvp-mascot__torso {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 58%;
	height: 44%;
	margin-left: -29%;
	border-radius: 38% 38% 46% 46% / 32% 32% 52% 52%;
	background: linear-gradient(
		165deg,
		#ffffff 0%,
		#f0f7ff 35%,
		#dceeff 70%,
		#c5e1fb 100%
	);
	border: 2rpx solid rgba(255, 255, 255, 0.95);
	box-shadow:
		0 16rpx 36rpx rgba(21, 101, 216, 0.18),
		inset 0 6rpx 16rpx rgba(255, 255, 255, 0.9),
		inset 0 -8rpx 20rpx rgba(144, 202, 249, 0.35);
	overflow: visible;
}

.hvp-mascot__neck-bridge {
	position: absolute;
	left: 50%;
	top: -14rpx;
	width: 28%;
	height: 28rpx;
	margin-left: -14%;
	border-radius: 50%;
	background: linear-gradient(180deg, #ffece3, #ffdfd0);
	border: 2rpx solid rgba(255, 255, 255, 0.65);
	z-index: 1;
}

.hvp-mascot__scrub-neck {
	position: absolute;
	left: 50%;
	top: 6rpx;
	width: 50%;
	height: 22rpx;
	margin-left: -25%;
	border-radius: 0 0 18rpx 18rpx;
	border: 2rpx solid rgba(144, 202, 249, 0.45);
	border-top: none;
	background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(227, 242, 253, 0.5));
	z-index: 2;
}

.hvp-mascot__coat-shine {
	position: absolute;
	left: 12%;
	top: 8%;
	width: 28%;
	height: 55%;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.85), transparent);
}

.hvp-mascot__stetho {
	position: absolute;
	right: 4%;
	top: 12%;
	width: 42%;
	height: 56%;
}

.hvp-mascot__stetho-yoke {
	position: absolute;
	right: 18%;
	top: 0;
	width: 36rpx;
	height: 22rpx;
	border: 3rpx solid #78909c;
	border-bottom: none;
	border-radius: 18rpx 18rpx 0 0;
}

.hvp-mascot__stetho-tube {
	position: absolute;
	border: 3rpx solid #78909c;
	border-radius: 0 80% 0 0;
}

.hvp-mascot__stetho-tube--a {
	right: 8%;
	top: 16%;
	width: 58%;
	height: 48%;
	transform: rotate(-14deg);
}

.hvp-mascot__stetho-disk {
	position: absolute;
	right: 52%;
	bottom: 0;
	width: 28rpx;
	height: 28rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #cfd8dc, #90a4ae);
	border: 2rpx solid #eceff1;
	box-shadow: inset 0 2rpx 4rpx rgba(255, 255, 255, 0.8);
}

.hvp-mascot__badge {
	position: absolute;
	left: 50%;
	top: 28%;
	width: 44rpx;
	height: 44rpx;
	margin-left: -22rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #42a5f5, #1565c0);
	box-shadow: 0 4rpx 10rpx rgba(21, 101, 192, 0.35);
}

.hvp-mascot__badge-v,
.hvp-mascot__badge-h {
	position: absolute;
	left: 50%;
	top: 50%;
	background: #fff;
	border-radius: 2rpx;
	transform: translate(-50%, -50%);
}

.hvp-mascot__badge-v {
	width: 6rpx;
	height: 22rpx;
}

.hvp-mascot__badge-h {
	width: 22rpx;
	height: 6rpx;
}

.hvp-mascot__arm {
	position: absolute;
	top: 52%;
	width: 14%;
	height: 26%;
	transform-origin: 50% 8%;
	z-index: 2;
}

.hvp-mascot__sleeve {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 72%;
	border-radius: 999rpx;
	background: linear-gradient(165deg, #ffffff 0%, #e8f4fc 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.95);
	box-shadow: 0 8rpx 16rpx rgba(21, 101, 216, 0.1);
}

.hvp-mascot__glove {
	position: absolute;
	left: 50%;
	bottom: 0;
	width: 130%;
	height: 38%;
	margin-left: -65%;
	border-radius: 999rpx;
	background: linear-gradient(180deg, #ffffff, #f5fafd);
	border: 2rpx solid rgba(144, 202, 249, 0.35);
	box-shadow: 0 6rpx 12rpx rgba(21, 101, 216, 0.08);
}

.hvp-mascot__arm--l {
	left: 10%;
	animation: hvpArmWelcome 4.5s ease-in-out infinite;
	animation-delay: -0.8s;
}

.hvp-mascot__arm--r {
	right: 10%;
	animation: hvpArmSwayR 4s ease-in-out infinite;
	animation-delay: -2.2s;
}

.hvp--speaking .hvp-mascot__arm--r {
	animation: hvpArmWave 1.2s ease-in-out infinite;
}

.hvp-mascot__head {
	position: absolute;
	left: 50%;
	top: 2%;
	width: 66%;
	height: 52%;
	margin-left: -33%;
	z-index: 4;
	animation: hvpHeadTilt 5s ease-in-out infinite;
}

.hvp-mascot__cap {
	position: absolute;
	left: 50%;
	top: -6%;
	width: 88%;
	height: 38%;
	margin-left: -44%;
	border-radius: 50% 50% 12% 12% / 60% 60% 20% 20%;
	background: linear-gradient(180deg, #ffffff 0%, #e8f4fc 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.95);
	box-shadow: 0 8rpx 20rpx rgba(21, 101, 216, 0.12);
	z-index: 2;
}

.hvp-mascot__cap-band {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 28%;
	background: linear-gradient(90deg, #1565c0, #42a5f5, #1565c0);
	border-radius: 0 0 8rpx 8rpx;
}

.hvp-mascot__cap-cross {
	position: absolute;
	left: 50%;
	top: 22%;
	width: 28rpx;
	height: 28rpx;
	margin-left: -14rpx;
}

.hvp-mascot__cap-cross-v,
.hvp-mascot__cap-cross-h {
	position: absolute;
	left: 50%;
	top: 50%;
	background: #fff;
	border-radius: 2rpx;
	transform: translate(-50%, -50%);
}

.hvp-mascot__cap-cross-v {
	width: 5rpx;
	height: 18rpx;
}

.hvp-mascot__cap-cross-h {
	width: 18rpx;
	height: 5rpx;
}

.hvp-mascot__face {
	position: absolute;
	left: 50%;
	top: 22%;
	width: 84%;
	height: 72%;
	margin-left: -42%;
	border-radius: 50%;
	background: linear-gradient(
		168deg,
		#fff8f3 0%,
		#ffece3 45%,
		#ffdfd0 100%
	);
	border: 2rpx solid rgba(255, 255, 255, 0.75);
	box-shadow:
		0 10rpx 24rpx rgba(255, 171, 145, 0.16),
		inset 0 6rpx 16rpx rgba(255, 255, 255, 0.5);
	z-index: 1;
}

.hvp-mascot__face-glow {
	position: absolute;
	left: 8%;
	top: 8%;
	right: 8%;
	bottom: 8%;
	border-radius: inherit;
	background: radial-gradient(circle at 50% 35%, rgba(255, 205, 210, 0.35) 0%, transparent 62%);
	pointer-events: none;
}

.hvp-mascot__brow {
	position: absolute;
	top: 30%;
	width: 18%;
	height: 8rpx;
	border: none;
	border-top: 3rpx solid #bcaaa4;
	border-radius: 50% 50% 0 0 / 100% 100% 0 0;
	background: transparent;
	opacity: 0.85;
}

.hvp-mascot__brow--l {
	left: 22%;
	transform: rotate(-4deg);
}

.hvp-mascot__brow--r {
	right: 22%;
	transform: rotate(4deg) scaleX(-1);
}

.hvp-mascot__eye {
	position: absolute;
	top: 38%;
	width: 16%;
	height: 16%;
}

.hvp-mascot__eye--l {
	left: 26%;
}

.hvp-mascot__eye--r {
	right: 26%;
}

.hvp-mascot__eye-ball {
	position: relative;
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #ffffff 0%, #fff5f5 55%, #f8bbd0 100%);
	border: 2rpx solid rgba(188, 170, 164, 0.25);
	box-shadow: inset 0 -4rpx 6rpx rgba(244, 143, 177, 0.15);
	animation: hvpBlinkSoft 6s ease-in-out infinite;
}

.hvp-mascot__eye-pupil {
	position: absolute;
	left: 50%;
	top: 54%;
	width: 42%;
	height: 42%;
	margin-left: -21%;
	margin-top: -21%;
	border-radius: 50%;
	background: radial-gradient(circle at 35% 30%, #8d6e63, #5d4037);
}

.hvp-mascot__eye-shine {
	position: absolute;
	right: 22%;
	top: 20%;
	width: 22%;
	height: 22%;
	border-radius: 50%;
	background: #fff;
}

.hvp-mascot__cheek {
	position: absolute;
	top: 48%;
	width: 18%;
	height: 12%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 138, 128, 0.4) 0%, transparent 72%);
}

.hvp-mascot__cheek--l {
	left: 12%;
}

.hvp-mascot__cheek--r {
	right: 12%;
}

.hvp-mascot__smile {
	position: absolute;
	left: 50%;
	bottom: 22%;
	width: 30%;
	height: 12%;
	margin-left: -15%;
}

.hvp-mascot__smile-arc {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
	border: 3rpx solid #bcaaa4;
	border-top: none;
	border-radius: 0 0 50% 50% / 0 0 100% 100%;
	box-sizing: border-box;
}

.hvp-mascot__smile-talk {
	position: absolute;
	left: 50%;
	bottom: 2rpx;
	width: 36%;
	height: 0;
	margin-left: -18%;
	border-radius: 50%;
	background: #8d6e63;
	opacity: 0;
	transform: scale(0.6);
}

.hvp--speaking .hvp-mascot__smile-arc {
	border-width: 3rpx;
	transform: scaleY(0.82);
	opacity: 0.65;
}

.hvp--speaking .hvp-mascot__smile-talk {
	opacity: 1;
	height: 38%;
	animation: hvpMouthTalk 0.5s ease-in-out infinite alternate;
}

.hvp--speaking .hvp-mascot__eye-ball {
	animation: hvpBlinkSoft 6s ease-in-out infinite, hvpEyeBright 2s ease-in-out infinite;
}

.hvp-mascot__head-shine {
	position: absolute;
	left: 22%;
	top: 28%;
	width: 22%;
	height: 14%;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.55);
	filter: blur(2rpx);
	pointer-events: none;
}

.hvp-speak-ring {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 52%;
	height: 48%;
	margin-left: -26%;
	margin-top: -24%;
	border: 3rpx solid rgba(144, 202, 249, 0.5);
	border-radius: 48% 52% 50% 50%;
	pointer-events: none;
	animation: hvpSpeakRing 2s ease-out infinite;
}

.hvp-speak-ring--delay {
	animation-delay: 0.65s;
	border-color: rgba(129, 199, 132, 0.35);
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
		transform: translateY(-10rpx);
	}
}

@keyframes hvpShadowPulse {
	0%,
	100% {
		transform: scaleX(1);
		opacity: 0.85;
	}
	50% {
		transform: scaleX(0.92);
		opacity: 0.65;
	}
}

@keyframes hvpArmWelcome {
	0%,
	100% {
		transform: rotate(18deg);
	}
	50% {
		transform: rotate(24deg);
	}
}

@keyframes hvpHeadTilt {
	0%,
	100% {
		transform: rotate(0deg);
	}
	50% {
		transform: rotate(2.5deg);
	}
}

@keyframes hvpCareSpark {
	0%,
	100% {
		transform: translateY(0) scale(1);
		opacity: 0.55;
	}
	50% {
		transform: translateY(-10rpx) scale(1.08);
		opacity: 0.9;
	}
}

@keyframes hvpBlinkSoft {
	0%,
	90%,
	100% {
		transform: scaleY(1);
	}
	93% {
		transform: scaleY(0.2);
	}
	96% {
		transform: scaleY(1);
	}
}

@keyframes hvpEyeBright {
	0%,
	100% {
		box-shadow: inset 0 -6rpx 10rpx rgba(255, 183, 178, 0.2);
	}
	50% {
		box-shadow: inset 0 -4rpx 14rpx rgba(255, 183, 178, 0.38);
	}
}

@keyframes hvpArmSwayR {
	0%,
	100% {
		transform: rotate(-12deg);
	}
	50% {
		transform: rotate(-18deg);
	}
}

@keyframes hvpArmWave {
	0%,
	100% {
		transform: rotate(-14deg);
	}
	50% {
		transform: rotate(-34deg);
	}
}

/* hvpBlink 已替换为 hvpBlinkSoft */

@keyframes hvpMouthTalk {
	0% {
		transform: scale(0.85, 0.75);
	}
	100% {
		transform: scale(1, 1.1);
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
		transform: scale(0.94);
	}
	100% {
		opacity: 0;
		transform: scale(1.28);
	}
}
</style>
