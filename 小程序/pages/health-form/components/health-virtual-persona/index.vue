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
				<view class="hvp-tech-orbit">
					<view class="hvp-tech-orbit__spin">
						<view class="hvp-light-pillar hvp-light-pillar--1"></view>
						<view class="hvp-light-pillar hvp-light-pillar--2"></view>
						<view class="hvp-light-pillar hvp-light-pillar--3"></view>
						<view class="hvp-light-pillar hvp-light-pillar--4"></view>
						<view class="hvp-light-pillar hvp-light-pillar--5"></view>
						<view class="hvp-light-pillar hvp-light-pillar--6"></view>
					</view>
					<view class="hvp-tech-orbit__spin hvp-tech-orbit__spin--reverse">
						<view class="hvp-light-pillar hvp-light-pillar--thin hvp-light-pillar--7"></view>
						<view class="hvp-light-pillar hvp-light-pillar--thin hvp-light-pillar--8"></view>
						<view class="hvp-light-pillar hvp-light-pillar--thin hvp-light-pillar--9"></view>
					</view>
				</view>
				<view class="hvp-motion">
					<view class="hvp-mascot">
						<view class="hvp-mascot__shadow"></view>
						<view class="hvp-mascot__figure">
							<view class="hvp-mascot__arm hvp-mascot__arm--l">
								<view class="hvp-mascot__hand"></view>
							</view>
							<view class="hvp-mascot__arm hvp-mascot__arm--r">
								<view class="hvp-mascot__hand"></view>
							</view>
							<view class="hvp-mascot__torso">
								<view class="hvp-mascot__coat-shine"></view>
								<view class="hvp-mascot__stetho">
									<view class="hvp-mascot__stetho-tube"></view>
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
								<view class="hvp-mascot__hair hvp-mascot__hair--l"></view>
								<view class="hvp-mascot__hair hvp-mascot__hair--r"></view>
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
	background: radial-gradient(circle, rgba(144, 202, 249, 0.5) 0%, transparent 70%);
}

.hvp-halo--b {
	width: 70%;
	height: 62%;
	background: radial-gradient(circle, rgba(76, 175, 80, 0.14) 0%, transparent 72%);
	animation-delay: -2.8s;
	animation-duration: 7.2s;
}

.hvp-tech-orbit {
	position: absolute;
	left: 50%;
	top: 48%;
	width: 520rpx;
	height: 520rpx;
	margin-left: -260rpx;
	margin-top: -260rpx;
	z-index: 1;
	pointer-events: none;
}

.hvp-tech-orbit__spin {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	animation: hvpOrbitSpin 14s linear infinite;
}

.hvp-tech-orbit__spin--reverse {
	animation: hvpOrbitSpinRev 20s linear infinite;
	opacity: 0.65;
}

.hvp-light-pillar {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 10rpx;
	height: 140rpx;
	margin-left: -5rpx;
	margin-top: -228rpx;
	border-radius: 6rpx;
	background: linear-gradient(
		180deg,
		rgba(255, 255, 255, 0.95) 0%,
		rgba(144, 202, 249, 0.75) 35%,
		rgba(66, 165, 245, 0.35) 65%,
		rgba(66, 165, 245, 0) 100%
	);
	box-shadow: 0 0 20rpx rgba(144, 202, 249, 0.45);
	transform-origin: 50% 228rpx;
	opacity: 0.85;
}

.hvp-light-pillar--thin {
	width: 6rpx;
	height: 100rpx;
	margin-top: -210rpx;
	transform-origin: 50% 210rpx;
	opacity: 0.55;
	background: linear-gradient(
		180deg,
		rgba(255, 255, 255, 0.7) 0%,
		rgba(129, 212, 250, 0.5) 50%,
		rgba(66, 165, 245, 0) 100%
	);
}

.hvp-light-pillar--1 {
	transform: rotate(0deg);
}
.hvp-light-pillar--2 {
	transform: rotate(60deg);
}
.hvp-light-pillar--3 {
	transform: rotate(120deg);
}
.hvp-light-pillar--4 {
	transform: rotate(180deg);
}
.hvp-light-pillar--5 {
	transform: rotate(240deg);
}
.hvp-light-pillar--6 {
	transform: rotate(300deg);
}
.hvp-light-pillar--7 {
	transform: rotate(30deg);
}
.hvp-light-pillar--8 {
	transform: rotate(150deg);
}
.hvp-light-pillar--9 {
	transform: rotate(270deg);
}

.hvp--speaking .hvp-light-pillar {
	opacity: 1;
	box-shadow: 0 0 28rpx rgba(144, 202, 249, 0.65);
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
	height: 420rpx;
}

.hvp--hero .hvp-mascot {
	width: 380rpx;
	height: 500rpx;
}

.hvp--md .hvp-mascot {
	width: 260rpx;
	height: 340rpx;
}

.hvp-mascot__shadow {
	position: absolute;
	left: 50%;
	bottom: 4rpx;
	width: 58%;
	height: 20rpx;
	margin-left: -29%;
	background: radial-gradient(ellipse, rgba(21, 101, 216, 0.22) 0%, transparent 72%);
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
	top: 46%;
	width: 52%;
	height: 38%;
	margin-left: -26%;
	border-radius: 42% 42% 38% 38% / 36% 36% 48% 48%;
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
	overflow: hidden;
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
	right: 8%;
	top: 18%;
	width: 36%;
	height: 50%;
}

.hvp-mascot__stetho-tube {
	position: absolute;
	right: 0;
	top: 0;
	width: 70%;
	height: 55%;
	border: 3rpx solid transparent;
	border-top-color: #78909c;
	border-right-color: #78909c;
	border-radius: 0 80% 0 0;
	transform: rotate(-12deg);
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
	top: 48%;
	width: 18%;
	height: 28%;
	border-radius: 40rpx;
	background: linear-gradient(160deg, #ffffff, #e3f2fd);
	border: 2rpx solid rgba(255, 255, 255, 0.9);
	box-shadow: 0 6rpx 14rpx rgba(21, 101, 216, 0.12);
	transform-origin: 50% 12%;
}

.hvp-mascot__arm--l {
	left: 6%;
	animation: hvpArmSwayL 4s ease-in-out infinite;
	animation-delay: -1s;
}

.hvp-mascot__arm--r {
	right: 6%;
	animation: hvpArmSwayR 4s ease-in-out infinite;
	animation-delay: -2.2s;
}

.hvp--speaking .hvp-mascot__arm--r {
	animation: hvpArmWave 1.2s ease-in-out infinite;
}

.hvp-mascot__hand {
	position: absolute;
	left: 50%;
	bottom: -6rpx;
	width: 36rpx;
	height: 36rpx;
	margin-left: -18rpx;
	border-radius: 50%;
	background: linear-gradient(160deg, #fff5ef 0%, #ffe8dc 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.6);
}

.hvp-mascot__head {
	position: absolute;
	left: 50%;
	top: 0;
	width: 62%;
	height: 48%;
	margin-left: -31%;
	z-index: 3;
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

.hvp-mascot__hair {
	position: absolute;
	bottom: 18%;
	width: 22%;
	height: 32%;
	background: #5d4037;
	border-radius: 50% 50% 40% 40%;
	z-index: 0;
}

.hvp-mascot__hair--l {
	left: 8%;
	transform: rotate(-8deg);
}

.hvp-mascot__hair--r {
	right: 8%;
	transform: rotate(8deg);
}

.hvp-mascot__face {
	position: absolute;
	left: 50%;
	top: 18%;
	width: 86%;
	height: 78%;
	margin-left: -43%;
	border-radius: 48% 48% 44% 44% / 52% 52% 46% 46%;
	background: linear-gradient(165deg, #fffdfb 0%, #fff0e8 42%, #ffe4d9 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.72);
	box-shadow:
		0 12rpx 28rpx rgba(144, 202, 249, 0.1),
		inset 0 8rpx 18rpx rgba(255, 255, 255, 0.85),
		inset 0 -4rpx 12rpx rgba(255, 200, 180, 0.08);
	overflow: hidden;
}

.hvp-mascot__face-glow {
	position: absolute;
	left: 18%;
	top: 12%;
	width: 52%;
	height: 38%;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.45);
	filter: blur(14rpx);
	z-index: 0;
}

.hvp-mascot__brow {
	position: absolute;
	top: 26%;
	width: 20%;
	height: 14rpx;
	overflow: hidden;
	z-index: 2;
}

.hvp-mascot__brow--l {
	left: 20%;
	transform: rotate(-6deg);
}

.hvp-mascot__brow--r {
	right: 20%;
	transform: rotate(6deg) scaleX(-1);
}

.hvp-mascot__brow-arc {
	width: 100%;
	height: 200%;
	border-top: 4rpx solid #bcaaa4;
	border-radius: 50% 50% 0 0;
	box-sizing: border-box;
}

.hvp-mascot__eye {
	position: absolute;
	top: 36%;
	width: 21%;
	height: 24%;
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
	border-radius: 46% 46% 44% 44% / 48% 48% 42% 42%;
	background: #fff;
	border: 2rpx solid rgba(188, 170, 164, 0.2);
	box-shadow: inset 0 -3rpx 6rpx rgba(0, 0, 0, 0.03);
	animation: hvpBlink 6.5s ease-in-out infinite;
}

.hvp-mascot__pupil {
	position: absolute;
	left: 50%;
	top: 54%;
	width: 46%;
	height: 46%;
	margin-left: -23%;
	margin-top: -23%;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #8d6e63, #6d4c41);
}

.hvp-mascot__eye-spark {
	position: absolute;
	right: 24%;
	top: 24%;
	width: 20%;
	height: 20%;
	border-radius: 50%;
	background: #fff;
}

.hvp-mascot__eye-spark--sm {
	right: auto;
	left: 28%;
	top: 38%;
	width: 10%;
	height: 10%;
	opacity: 0.85;
}

.hvp-mascot__cheek {
	position: absolute;
	top: 54%;
	width: 18%;
	height: 13%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 171, 145, 0.42) 0%, transparent 72%);
	z-index: 1;
}

.hvp-mascot__cheek--l {
	left: 14%;
}

.hvp-mascot__cheek--r {
	right: 14%;
}

.hvp-mascot__mouth {
	position: absolute;
	left: 50%;
	bottom: 20%;
	width: 30%;
	height: 16%;
	margin-left: -15%;
	z-index: 2;
}

.hvp-mascot__mouth-smile {
	position: absolute;
	left: 50%;
	bottom: 0;
	width: 72%;
	height: 50%;
	margin-left: -36%;
	border-bottom: 4rpx solid #c9a89a;
	border-radius: 0 0 50% 50% / 0 0 100% 100%;
	box-sizing: border-box;
}

.hvp-mascot__mouth-talk {
	position: absolute;
	left: 50%;
	bottom: 18%;
	width: 22%;
	height: 14%;
	margin-left: -11%;
	border-radius: 50%;
	background: #a1887f;
	border: 2rpx solid #bcaaa4;
	animation: hvpMouthTalk 0.5s ease-in-out infinite alternate;
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

@keyframes hvpArmSwayL {
	0%,
	100% {
		transform: rotate(16deg);
	}
	50% {
		transform: rotate(22deg);
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

@keyframes hvpBlink {
	0%,
	92%,
	100% {
		transform: scaleY(1);
	}
	94% {
		transform: scaleY(0.12);
	}
	96% {
		transform: scaleY(1);
	}
}

@keyframes hvpMouthTalk {
	0% {
		transform: scaleY(0.65);
		opacity: 0.85;
	}
	100% {
		transform: scaleY(1.05);
		opacity: 1;
	}
}

@keyframes hvpOrbitSpin {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}

@keyframes hvpOrbitSpinRev {
	0% {
		transform: rotate(360deg);
	}
	100% {
		transform: rotate(0deg);
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
