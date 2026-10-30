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
							<view class="hvp-mascot__leg hvp-mascot__leg--l"></view>
							<view class="hvp-mascot__leg hvp-mascot__leg--r"></view>
							<view class="hvp-mascot__shoe hvp-mascot__shoe--l"></view>
							<view class="hvp-mascot__shoe hvp-mascot__shoe--r"></view>
							<view class="hvp-mascot__arm hvp-mascot__arm--l">
								<view class="hvp-mascot__cuff"></view>
								<view class="hvp-mascot__hand hvp-mascot__hand--welcome">
									<view class="hvp-mascot__palm"></view>
									<view class="hvp-mascot__finger hvp-mascot__finger--1"></view>
									<view class="hvp-mascot__finger hvp-mascot__finger--2"></view>
									<view class="hvp-mascot__finger hvp-mascot__finger--3"></view>
								</view>
							</view>
							<view class="hvp-mascot__arm hvp-mascot__arm--r">
								<view class="hvp-mascot__cuff"></view>
								<view class="hvp-mascot__hand"></view>
							</view>
							<view class="hvp-mascot__torso">
								<view class="hvp-mascot__coat-shine"></view>
								<view class="hvp-mascot__collar">
									<view class="hvp-mascot__collar-l"></view>
									<view class="hvp-mascot__collar-r"></view>
								</view>
								<view class="hvp-mascot__stetho">
									<view class="hvp-mascot__stetho-yoke"></view>
									<view class="hvp-mascot__stetho-tube hvp-mascot__stetho-tube--a"></view>
									<view class="hvp-mascot__stetho-tube hvp-mascot__stetho-tube--b"></view>
									<view class="hvp-mascot__stetho-disk"></view>
								</view>
								<view class="hvp-mascot__pocket"></view>
								<view class="hvp-mascot__badge">
									<view class="hvp-mascot__badge-v"></view>
									<view class="hvp-mascot__badge-h"></view>
								</view>
								<view class="hvp-mascot__care-pin">
									<view class="hvp-mascot__care-pin-heart">
										<view class="hvp-mascot__care-pin-lobe hvp-mascot__care-pin-lobe--a"></view>
										<view class="hvp-mascot__care-pin-lobe hvp-mascot__care-pin-lobe--b"></view>
									</view>
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
								<view class="hvp-mascot__hair-bang"></view>
								<view class="hvp-mascot__face">
									<view class="hvp-mascot__face-glow"></view>
									<view class="hvp-mascot__brow hvp-mascot__brow--l"></view>
									<view class="hvp-mascot__brow hvp-mascot__brow--r"></view>
									<view class="hvp-mascot__eye hvp-mascot__eye--l">
										<view class="hvp-mascot__eye-lid hvp-mascot__eye-lid--top"></view>
										<view class="hvp-mascot__eye-soft">
											<view class="hvp-mascot__pupil"></view>
											<view class="hvp-mascot__eye-spark"></view>
											<view class="hvp-mascot__eye-spark hvp-mascot__eye-spark--sm"></view>
										</view>
										<view class="hvp-mascot__eye-lid hvp-mascot__eye-lid--bottom"></view>
									</view>
									<view class="hvp-mascot__eye hvp-mascot__eye--r">
										<view class="hvp-mascot__eye-lid hvp-mascot__eye-lid--top"></view>
										<view class="hvp-mascot__eye-soft">
											<view class="hvp-mascot__pupil"></view>
											<view class="hvp-mascot__eye-spark"></view>
											<view class="hvp-mascot__eye-spark hvp-mascot__eye-spark--sm"></view>
										</view>
										<view class="hvp-mascot__eye-lid hvp-mascot__eye-lid--bottom"></view>
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
	height: 448rpx;
}

.hvp--hero .hvp-mascot {
	width: 380rpx;
	height: 528rpx;
}

.hvp--md .hvp-mascot {
	width: 260rpx;
	height: 364rpx;
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
	top: 44%;
	width: 54%;
	height: 36%;
	margin-left: -27%;
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
	overflow: visible;
}

.hvp-mascot__collar {
	position: absolute;
	left: 50%;
	top: -4rpx;
	width: 72%;
	height: 28%;
	margin-left: -36%;
	z-index: 2;
}

.hvp-mascot__collar-l,
.hvp-mascot__collar-r {
	position: absolute;
	top: 0;
	width: 52%;
	height: 100%;
	background: linear-gradient(180deg, #ffffff, #e8f4fc);
	border: 2rpx solid rgba(255, 255, 255, 0.9);
	box-shadow: 0 4rpx 10rpx rgba(21, 101, 216, 0.08);
}

.hvp-mascot__collar-l {
	left: 0;
	border-radius: 8rpx 0 40rpx 8rpx;
	transform: skewY(-6deg) rotate(-4deg);
}

.hvp-mascot__collar-r {
	right: 0;
	border-radius: 0 8rpx 8rpx 40rpx;
	transform: skewY(6deg) rotate(4deg);
}

.hvp-mascot__pocket {
	position: absolute;
	left: 14%;
	bottom: 16%;
	width: 28%;
	height: 22%;
	border-radius: 0 0 12rpx 12rpx;
	border: 2rpx solid rgba(144, 202, 249, 0.55);
	border-top: 4rpx solid rgba(144, 202, 249, 0.65);
	background: rgba(255, 255, 255, 0.35);
}

.hvp-mascot__care-pin {
	position: absolute;
	right: 16%;
	top: 22%;
	width: 28rpx;
	height: 28rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #ffab91, #ff8a65);
	box-shadow: 0 4rpx 8rpx rgba(255, 138, 101, 0.35);
}

.hvp-mascot__care-pin-heart {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 16rpx;
	height: 14rpx;
	margin-left: -8rpx;
	margin-top: -9rpx;
}

.hvp-mascot__care-pin-lobe {
	position: absolute;
	width: 50%;
	height: 100%;
	background: #fff;
	border-radius: 3rpx 3rpx 0 3rpx;
}

.hvp-mascot__care-pin-lobe--a {
	left: 0;
	transform: rotate(45deg);
	transform-origin: 100% 100%;
}

.hvp-mascot__care-pin-lobe--b {
	right: 0;
	transform: rotate(-45deg);
	transform-origin: 0 100%;
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

.hvp-mascot__stetho-tube--b {
	right: 28%;
	top: 18%;
	width: 42%;
	height: 42%;
	transform: rotate(-32deg);
	opacity: 0.85;
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
	top: 46%;
	width: 18%;
	height: 30%;
	border-radius: 40rpx;
	background: linear-gradient(160deg, #ffffff, #e3f2fd);
	border: 2rpx solid rgba(255, 255, 255, 0.9);
	box-shadow: 0 6rpx 14rpx rgba(21, 101, 216, 0.12);
	transform-origin: 50% 12%;
}

.hvp-mascot__cuff {
	position: absolute;
	left: 50%;
	bottom: 18%;
	width: 108%;
	height: 22%;
	margin-left: -54%;
	border-radius: 8rpx;
	background: linear-gradient(90deg, #1565c0, #42a5f5, #1565c0);
	opacity: 0.92;
}

.hvp-mascot__arm--l {
	left: 4%;
	animation: hvpArmWelcome 4.5s ease-in-out infinite;
	animation-delay: -0.8s;
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
	background: linear-gradient(160deg, #ffe8dc 0%, #ffd5c8 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.65);
}

.hvp-mascot__hand--welcome {
	width: 44rpx;
	height: 40rpx;
	margin-left: -22rpx;
	border-radius: 46% 46% 42% 42%;
	background: linear-gradient(165deg, #ffe8dc, #ffcbb8);
}

.hvp-mascot__palm {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	border-radius: inherit;
}

.hvp-mascot__finger {
	position: absolute;
	top: -6rpx;
	width: 8rpx;
	height: 14rpx;
	border-radius: 6rpx;
	background: #ffd5c8;
}

.hvp-mascot__finger--1 {
	left: 8rpx;
	transform: rotate(-8deg);
}

.hvp-mascot__finger--2 {
	left: 50%;
	margin-left: -4rpx;
}

.hvp-mascot__finger--3 {
	right: 8rpx;
	transform: rotate(8deg);
}

.hvp-mascot__leg {
	position: absolute;
	top: 76%;
	width: 16%;
	height: 14%;
	border-radius: 0 0 12rpx 12rpx;
	background: linear-gradient(180deg, #546e7a, #455a64);
}

.hvp-mascot__leg--l {
	left: 34%;
}

.hvp-mascot__leg--r {
	right: 34%;
}

.hvp-mascot__shoe {
	position: absolute;
	top: 88%;
	width: 22%;
	height: 8%;
	border-radius: 50% 50% 12rpx 12rpx;
	background: linear-gradient(180deg, #ffffff, #eceff1);
	border: 2rpx solid rgba(144, 202, 249, 0.45);
	box-shadow: 0 6rpx 12rpx rgba(21, 101, 216, 0.12);
}

.hvp-mascot__shoe--l {
	left: 30%;
}

.hvp-mascot__shoe--r {
	right: 30%;
}

.hvp-mascot__head {
	position: absolute;
	left: 50%;
	top: 0;
	width: 64%;
	height: 46%;
	margin-left: -32%;
	z-index: 3;
	animation: hvpHeadTilt 5s ease-in-out infinite;
}

.hvp-mascot__hair-bang {
	position: absolute;
	left: 50%;
	bottom: 32%;
	width: 36%;
	height: 18%;
	margin-left: -18%;
	background: #6d4c41;
	border-radius: 50% 50% 40% 40%;
	z-index: 1;
	opacity: 0.85;
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
	width: 24%;
	height: 34%;
	background: linear-gradient(180deg, #6d4c41, #5d4037);
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
	top: 20%;
	width: 88%;
	height: 76%;
	margin-left: -44%;
	border-radius: 50% 50% 46% 46% / 54% 54% 44% 44%;
	background: linear-gradient(
		168deg,
		#fff8f3 0%,
		#ffece3 42%,
		#ffdfd0 78%,
		#ffd0bc 100%
	);
	border: 2rpx solid rgba(255, 255, 255, 0.72);
	box-shadow:
		0 12rpx 28rpx rgba(255, 171, 145, 0.18),
		inset 0 8rpx 20rpx rgba(255, 255, 255, 0.55),
		inset 0 -8rpx 18rpx rgba(255, 183, 160, 0.15);
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
	top: 26%;
	width: 20%;
	height: 10rpx;
	border: none;
	border-top: 4rpx solid #a1887f;
	border-radius: 50% 50% 0 0 / 100% 100% 0 0;
	background: transparent;
	opacity: 0.9;
}

.hvp-mascot__brow--l {
	left: 20%;
	transform: rotate(-6deg);
}

.hvp-mascot__brow--r {
	right: 20%;
	transform: rotate(6deg) scaleX(-1);
}

.hvp-mascot__eye {
	position: absolute;
	top: 32%;
	width: 24%;
	height: 28%;
}

.hvp-mascot__eye--l {
	left: 18%;
}

.hvp-mascot__eye--r {
	right: 18%;
}

.hvp-mascot__eye-lid {
	position: absolute;
	left: 0;
	width: 100%;
	z-index: 2;
	pointer-events: none;
}

.hvp-mascot__eye-lid--top {
	top: -4%;
	height: 42%;
	border-radius: 50% 50% 40% 40%;
	background: linear-gradient(180deg, rgba(255, 223, 208, 0.95), transparent);
}

.hvp-mascot__eye-lid--bottom {
	bottom: 8%;
	height: 28%;
	border-radius: 0 0 50% 50%;
	border-bottom: 3rpx solid rgba(161, 136, 127, 0.35);
}

.hvp-mascot__eye-soft {
	position: relative;
	width: 100%;
	height: 88%;
	margin-top: 6%;
	border-radius: 48% 48% 44% 44%;
	background: radial-gradient(circle at 50% 40%, #ffffff 0%, #fff5f5 100%);
	border: 2rpx solid rgba(161, 136, 127, 0.12);
	box-shadow: inset 0 -6rpx 10rpx rgba(255, 183, 178, 0.2);
	animation: hvpBlinkSoft 6s ease-in-out infinite;
}

.hvp-mascot__pupil {
	position: absolute;
	left: 50%;
	top: 56%;
	width: 46%;
	height: 46%;
	margin-left: -23%;
	margin-top: -23%;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #8d6e63, #5d4037 72%, #4e342e);
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
	width: 20%;
	height: 14%;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 138, 128, 0.55) 0%, rgba(255, 138, 128, 0) 72%);
}

.hvp-mascot__cheek--l {
	left: 10%;
}

.hvp-mascot__cheek--r {
	right: 10%;
}

.hvp-mascot__smile {
	position: absolute;
	left: 50%;
	bottom: 16%;
	width: 34%;
	height: 18%;
	margin-left: -17%;
}

.hvp-mascot__smile-arc {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
	border: 4rpx solid #bcaaa4;
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

.hvp--speaking .hvp-mascot__eye-soft {
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
		transform: rotate(28deg);
	}
	50% {
		transform: rotate(34deg);
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
