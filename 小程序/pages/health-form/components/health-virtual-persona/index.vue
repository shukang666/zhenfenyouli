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
			<view class="hvp-halo"></view>
			<view class="hvp-halo hvp-halo--small"></view>

			<view v-if="speaking" class="hvp-speak-ring hvp-speak-ring--outer"></view>
			<view v-if="speaking" class="hvp-speak-ring hvp-speak-ring--inner"></view>

			<view class="hvp-motion">
				<view class="hvp-mascot">
					<view class="hvp-mascot__head-wrap">
						<view class="hvp-mascot__hat">
							<view class="hvp-mascot__hat-top"></view>
							<view class="hvp-mascot__hat-front">
								<view class="hvp-mascot__hat-cross">
									<view class="hvp-mascot__cross-v"></view>
									<view class="hvp-mascot__cross-h"></view>
								</view>
							</view>
							<view class="hvp-mascot__hat-shadow"></view>
						</view>

						<view class="hvp-mascot__face">
							<view class="hvp-mascot__face-light"></view>
							<view class="hvp-mascot__eyes">
								<view class="hvp-mascot__eye">
									<view class="hvp-mascot__eye-curve"></view>
								</view>
								<view class="hvp-mascot__eye">
									<view class="hvp-mascot__eye-curve"></view>
								</view>
							</view>
							<view class="hvp-mascot__blush hvp-mascot__blush--left"></view>
							<view class="hvp-mascot__blush hvp-mascot__blush--right"></view>
							<view class="hvp-mascot__mouth">
								<view class="hvp-mascot__mouth-smile"></view>
							</view>
						</view>

						<view class="hvp-mascot__ear hvp-mascot__ear--left"></view>
						<view class="hvp-mascot__ear hvp-mascot__ear--right"></view>
					</view>

					<view class="hvp-mascot__body">
						<view class="hvp-mascot__shoulder hvp-mascot__shoulder--left"></view>
						<view class="hvp-mascot__shoulder hvp-mascot__shoulder--right"></view>

						<view class="hvp-mascot__uniform">
							<view class="hvp-mascot__uniform-light"></view>
							<view class="hvp-mascot__collar">
								<view class="hvp-mascot__collar-inner"></view>
							</view>
							<view class="hvp-mascot__badge">
								<view class="hvp-mascot__badge-heart">
									<view class="hvp-mascot__heart-left"></view>
									<view class="hvp-mascot__heart-right"></view>
									<view class="hvp-mascot__heart-bottom"></view>
								</view>
							</view>
							<view class="hvp-mascot__uniform-line"></view>
						</view>

						<view class="hvp-mascot__arm hvp-mascot__arm--left">
							<view class="hvp-mascot__hand"></view>
						</view>
						<view class="hvp-mascot__arm hvp-mascot__arm--right">
							<view class="hvp-mascot__hand"></view>
						</view>
					</view>

					<view class="hvp-mascot__base-light"></view>
				</view>
			</view>

			<view class="hvp-decoration hvp-decoration--one"></view>
			<view class="hvp-decoration hvp-decoration--two"></view>
			<view class="hvp-decoration hvp-decoration--three"></view>
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
	justify-content: center;
	box-sizing: border-box;
}

.hvp--embedded {
	margin: 0;
}

.hvp--embedded .hvp-stage {
	pointer-events: none;
}

.hvp--hero {
	.hvp-stage {
		height: 620rpx;
	}

	.hvp-mascot {
		transform: scale(1);
	}
}

.hvp--lg {
	.hvp-stage {
		height: 500rpx;
	}

	.hvp-mascot {
		transform: scale(0.82);
	}
}

.hvp--md {
	.hvp-stage {
		height: 380rpx;
	}

	.hvp-mascot {
		transform: scale(0.64);
	}
}

.hvp--speaking {
	.hvp-motion {
		animation: hvp-float-speaking 3s ease-in-out infinite;
	}

	.hvp-mascot__mouth-smile {
		height: 17rpx;
		border-radius: 0 0 30rpx 30rpx;
		border-bottom-width: 6rpx;
	}

	.hvp-mascot__eyes {
		animation: hvp-eye-talk 2.8s ease-in-out infinite;
	}
}

.hvp-stage {
	position: relative;
	width: 100%;
	height: 620rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
	box-sizing: border-box;
}

.hvp-motion {
	position: relative;
	z-index: 3;
	animation: hvp-float 4s ease-in-out infinite;
}

.hvp-halo {
	position: absolute;
	width: 520rpx;
	height: 520rpx;
	border-radius: 50%;
	background: radial-gradient(
		circle,
		rgba(255, 255, 255, 0.48) 0%,
		rgba(187, 222, 251, 0.22) 42%,
		rgba(66, 165, 245, 0.02) 72%,
		rgba(66, 165, 245, 0) 100%
	);
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
	z-index: 0;
	pointer-events: none;
}

.hvp-halo--small {
	width: 340rpx;
	height: 340rpx;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 72%);
}

.hvp-speak-ring {
	position: absolute;
	left: 50%;
	top: 50%;
	border: 2rpx solid rgba(255, 255, 255, 0.6);
	border-radius: 50%;
	transform: translate(-50%, -50%);
	z-index: 1;
	pointer-events: none;
}

.hvp-speak-ring--outer {
	width: 460rpx;
	height: 460rpx;
	animation: hvp-ring-pulse 3s ease-out infinite;
}

.hvp-speak-ring--inner {
	width: 390rpx;
	height: 390rpx;
	border-color: rgba(255, 255, 255, 0.38);
	animation: hvp-ring-pulse 3s ease-out 1s infinite;
}

.hvp-mascot {
	position: relative;
	z-index: 3;
	width: 440rpx;
	height: 550rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	transform-origin: center center;
}

.hvp-mascot__head-wrap {
	position: relative;
	z-index: 5;
	width: 310rpx;
	height: 300rpx;
	margin-top: 12rpx;
	display: flex;
	justify-content: center;
	align-items: flex-end;
}

.hvp-mascot__hat {
	position: absolute;
	width: 188rpx;
	height: 94rpx;
	top: 0;
	left: 50%;
	transform: translateX(-50%);
	z-index: 8;
}

.hvp-mascot__hat-top {
	position: absolute;
	width: 176rpx;
	height: 76rpx;
	left: 6rpx;
	top: 0;
	border-radius: 88rpx 88rpx 20rpx 20rpx;
	background: linear-gradient(145deg, #ffffff 0%, #f5fbff 55%, #dceeff 100%);
	box-shadow:
		0 8rpx 18rpx rgba(21, 101, 216, 0.12),
		inset 0 5rpx 8rpx rgba(255, 255, 255, 0.95);
}

.hvp-mascot__hat-front {
	position: absolute;
	left: 0;
	bottom: 0;
	width: 188rpx;
	height: 32rpx;
	border-radius: 50%;
	background: linear-gradient(180deg, #ffffff 0%, #e7f4ff 100%);
	box-shadow:
		0 5rpx 10rpx rgba(21, 101, 216, 0.12),
		inset 0 2rpx 5rpx rgba(255, 255, 255, 0.9);
	z-index: 2;
}

.hvp-mascot__hat-cross {
	position: absolute;
	left: 50%;
	top: -30rpx;
	width: 36rpx;
	height: 36rpx;
	transform: translateX(-50%);
	z-index: 3;
}

.hvp-mascot__cross-v {
	position: absolute;
	width: 10rpx;
	height: 34rpx;
	border-radius: 5rpx;
	background: linear-gradient(180deg, #64b5f6, #1976d2);
	left: 13rpx;
	top: 1rpx;
}

.hvp-mascot__cross-h {
	position: absolute;
	width: 34rpx;
	height: 10rpx;
	border-radius: 5rpx;
	background: linear-gradient(90deg, #64b5f6, #1976d2);
	left: 1rpx;
	top: 13rpx;
}

.hvp-mascot__hat-shadow {
	position: absolute;
	width: 150rpx;
	height: 12rpx;
	left: 19rpx;
	bottom: -5rpx;
	border-radius: 50%;
	background: rgba(21, 101, 216, 0.1);
	filter: blur(5rpx);
}

.hvp-mascot__face {
	position: absolute;
	width: 276rpx;
	height: 252rpx;
	left: 50%;
	bottom: 0;
	transform: translateX(-50%);
	border-radius: 46% 46% 48% 48%;
	background: linear-gradient(145deg, #fffdfb 0%, #ffefea 45%, #f8dcd6 100%);
	box-shadow:
		0 14rpx 30rpx rgba(21, 101, 216, 0.1),
		inset 0 8rpx 16rpx rgba(255, 255, 255, 0.85),
		inset 0 -8rpx 15rpx rgba(232, 176, 164, 0.12);
	overflow: hidden;
	z-index: 4;
}

.hvp-mascot__face-light {
	position: absolute;
	width: 160rpx;
	height: 90rpx;
	left: 40rpx;
	top: 10rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.48);
	filter: blur(18rpx);
}

.hvp-mascot__eyes {
	position: absolute;
	width: 164rpx;
	left: 50%;
	top: 104rpx;
	transform: translateX(-50%);
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	z-index: 3;
}

.hvp-mascot__eye {
	width: 48rpx;
	height: 24rpx;
	display: flex;
	justify-content: center;
	align-items: center;
	overflow: hidden;
}

.hvp-mascot__eye-curve {
	width: 38rpx;
	height: 19rpx;
	border-top: 6rpx solid #526b82;
	border-radius: 50% 50% 0 0;
	transform: translateY(7rpx);
}

.hvp-mascot__blush {
	position: absolute;
	width: 36rpx;
	height: 19rpx;
	border-radius: 50%;
	background: rgba(255, 171, 145, 0.4);
	filter: blur(4rpx);
	top: 145rpx;
	z-index: 2;
}

.hvp-mascot__blush--left {
	left: 32rpx;
}

.hvp-mascot__blush--right {
	right: 32rpx;
}

.hvp-mascot__mouth {
	position: absolute;
	width: 48rpx;
	height: 26rpx;
	left: 50%;
	top: 168rpx;
	transform: translateX(-50%);
	display: flex;
	justify-content: center;
	overflow: hidden;
	z-index: 4;
}

.hvp-mascot__mouth-smile {
	width: 34rpx;
	height: 17rpx;
	border-bottom: 5rpx solid #c77976;
	border-radius: 0 0 50% 50%;
	transition: all 0.3s ease;
}

.hvp-mascot__ear {
	position: absolute;
	width: 35rpx;
	height: 52rpx;
	bottom: 85rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #fff4ef, #f2d4cd);
	box-shadow: inset 0 4rpx 8rpx rgba(255, 255, 255, 0.7);
	z-index: 2;
}

.hvp-mascot__ear--left {
	left: 4rpx;
}

.hvp-mascot__ear--right {
	right: 4rpx;
}

.hvp-mascot__body {
	position: relative;
	width: 390rpx;
	height: 260rpx;
	margin-top: -12rpx;
	z-index: 2;
}

.hvp-mascot__shoulder {
	position: absolute;
	width: 170rpx;
	height: 170rpx;
	top: 18rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #ffffff 0%, #e8f5ff 65%, #d1e8fb 100%);
	box-shadow: 0 10rpx 22rpx rgba(21, 101, 216, 0.1);
}

.hvp-mascot__shoulder--left {
	left: 4rpx;
}

.hvp-mascot__shoulder--right {
	right: 4rpx;
}

.hvp-mascot__uniform {
	position: absolute;
	left: 50%;
	top: 32rpx;
	transform: translateX(-50%);
	width: 278rpx;
	height: 250rpx;
	border-radius: 110rpx 110rpx 30rpx 30rpx;
	overflow: hidden;
	background: linear-gradient(135deg, #ffffff 0%, #f2f9ff 45%, #d8edff 100%);
	box-shadow:
		0 16rpx 28rpx rgba(21, 101, 216, 0.14),
		inset 0 6rpx 12rpx rgba(255, 255, 255, 0.95);
	z-index: 3;
}

.hvp-mascot__uniform-light {
	position: absolute;
	width: 130rpx;
	height: 200rpx;
	left: 22rpx;
	top: -20rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.55);
	filter: blur(20rpx);
}

.hvp-mascot__collar {
	position: absolute;
	width: 80rpx;
	height: 45rpx;
	left: 50%;
	top: -10rpx;
	transform: translateX(-50%);
	border-radius: 0 0 40rpx 40rpx;
	background: #b9e0fb;
	z-index: 4;
}

.hvp-mascot__collar-inner {
	position: absolute;
	width: 56rpx;
	height: 28rpx;
	left: 12rpx;
	top: -2rpx;
	border-radius: 0 0 28rpx 28rpx;
	background: #eaf6ff;
}

.hvp-mascot__badge {
	position: absolute;
	width: 48rpx;
	height: 48rpx;
	right: 46rpx;
	top: 100rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #ffffff, #e5f3ff);
	box-shadow:
		0 5rpx 12rpx rgba(21, 101, 216, 0.12),
		inset 0 2rpx 5rpx rgba(255, 255, 255, 0.95);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 5;
}

.hvp-mascot__badge-heart {
	position: relative;
	width: 24rpx;
	height: 24rpx;
}

.hvp-mascot__heart-left,
.hvp-mascot__heart-right {
	position: absolute;
	width: 13rpx;
	height: 17rpx;
	top: 1rpx;
	background: #f5a99d;
	border-radius: 12rpx 12rpx 0 0;
}

.hvp-mascot__heart-left {
	left: 1rpx;
	transform: rotate(-45deg);
	transform-origin: 100% 100%;
}

.hvp-mascot__heart-right {
	right: 1rpx;
	transform: rotate(45deg);
	transform-origin: 0 100%;
}

.hvp-mascot__heart-bottom {
	position: absolute;
	width: 16rpx;
	height: 16rpx;
	left: 4rpx;
	top: 8rpx;
	transform: rotate(45deg);
	background: #f5a99d;
	border-radius: 0 0 4rpx 0;
}

.hvp-mascot__uniform-line {
	position: absolute;
	width: 2rpx;
	height: 90rpx;
	left: 50%;
	top: 80rpx;
	transform: translateX(-50%);
	background: rgba(66, 165, 245, 0.12);
}

.hvp-mascot__arm {
	position: absolute;
	width: 84rpx;
	height: 126rpx;
	top: 80rpx;
	border-radius: 45rpx;
	background: linear-gradient(145deg, #ffffff 0%, #e7f4ff 100%);
	box-shadow: 0 8rpx 18rpx rgba(21, 101, 216, 0.1);
	z-index: 2;
}

.hvp-mascot__arm--left {
	left: 0;
	transform: rotate(8deg);
}

.hvp-mascot__arm--right {
	right: 0;
	transform: rotate(-8deg);
}

.hvp-mascot__hand {
	position: absolute;
	width: 74rpx;
	height: 72rpx;
	left: 50%;
	bottom: -5rpx;
	transform: translateX(-50%);
	border-radius: 50%;
	background: linear-gradient(145deg, #ffffff 0%, #e3f2fd 100%);
	box-shadow:
		inset 0 4rpx 8rpx rgba(255, 255, 255, 0.9),
		0 4rpx 10rpx rgba(21, 101, 216, 0.08);
}

.hvp-mascot__base-light {
	position: absolute;
	width: 300rpx;
	height: 34rpx;
	left: 50%;
	bottom: 0;
	transform: translateX(-50%);
	border-radius: 50%;
	background: rgba(21, 101, 216, 0.12);
	filter: blur(16rpx);
	z-index: -1;
}

.hvp-decoration {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.82);
	box-shadow: 0 0 16rpx rgba(255, 255, 255, 0.7);
	z-index: 2;
	pointer-events: none;
}

.hvp-decoration--one {
	width: 12rpx;
	height: 12rpx;
	left: 18%;
	top: 30%;
	animation: hvp-glow 3s ease-in-out infinite;
}

.hvp-decoration--two {
	width: 9rpx;
	height: 9rpx;
	right: 18%;
	top: 38%;
	animation: hvp-glow 4s ease-in-out 1s infinite;
}

.hvp-decoration--three {
	width: 7rpx;
	height: 7rpx;
	right: 24%;
	bottom: 22%;
	animation: hvp-glow 3.5s ease-in-out 0.5s infinite;
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
		transform: translateY(-10rpx);
	}
}

@keyframes hvp-float-speaking {
	0%,
	100% {
		transform: translateY(0);
	}

	50% {
		transform: translateY(-7rpx);
	}
}

@keyframes hvp-ring-pulse {
	0% {
		opacity: 0.15;
		transform: translate(-50%, -50%) scale(0.92);
	}

	50% {
		opacity: 0.55;
	}

	100% {
		opacity: 0;
		transform: translate(-50%, -50%) scale(1.12);
	}
}

@keyframes hvp-glow {
	0%,
	100% {
		opacity: 0.35;
		transform: scale(0.8);
	}

	50% {
		opacity: 1;
		transform: scale(1.2);
	}
}

@keyframes hvp-eye-talk {
	0%,
	100% {
		opacity: 1;
	}

	50% {
		opacity: 0.7;
	}
}
</style>
