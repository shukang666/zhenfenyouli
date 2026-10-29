<template>

	<view class="home-promo">

		<view class="home-promo__bg" aria-hidden="true">

			<view class="home-promo__orb home-promo__orb--a"></view>

			<view class="home-promo__orb home-promo__orb--b"></view>

			<view class="home-promo__orb home-promo__orb--c"></view>

		</view>

		<view class="home-promo__inner">

			<view class="home-promo__copy">

				<text class="home-promo__title">发现身边好活动</text>

				<text class="home-promo__title">一起创造好生活</text>

				<text class="home-promo__sub">运动、健康、文化、公益，也可以发起你的好想法</text>

				<view class="home-promo__actions">

					<view

						class="home-promo__btn home-promo__btn--primary"

						hover-class="home-promo__btn--on"

						:hover-stay-time="80"

						@click="onJoin"

					>

						<text>去参加活动</text>

					</view>

					<view

						class="home-promo__btn home-promo__btn--ghost"

						hover-class="home-promo__btn--on-ghost"

						:hover-stay-time="80"

						@click="onPublish"

					>

						<text>发起活动</text>

					</view>

				</view>

			</view>

			<!-- 家庭/社群抽象图形：圆头 + 胶囊身体 + 中间小圆（纯 CSS） -->

			<view class="home-promo__art" aria-hidden="true">

				<view class="home-promo__family">

					<view class="home-promo__body home-promo__body--white"></view>

					<view class="home-promo__body home-promo__body--yellow"></view>

					<view class="home-promo__head home-promo__head--left"></view>

					<view class="home-promo__head home-promo__head--right"></view>

					<view class="home-promo__child"></view>

				</view>

			</view>

		</view>

	</view>

</template>



<script>

export default {

	name: 'HomeActivityPromoBanner',

	methods: {

		onJoin() {

			this.$emit('join')

		},

		onPublish() {

			this.$emit('publish')

		},

	},

}

</script>



<style lang="scss" scoped>

.home-promo {

	position: relative;

	overflow: hidden;

	border-radius: 20rpx;

	background: linear-gradient(118deg, #1a9a34 0%, #22b03f 42%, #2ec24a 100%);

	box-shadow: 0 12rpx 32rpx rgba(24, 175, 56, 0.28);

}



.home-promo__bg {

	position: absolute;

	inset: 0;

	pointer-events: none;

}



.home-promo__orb {

	position: absolute;

	border-radius: 50%;

	background: rgba(255, 255, 255, 0.08);

}

.home-promo__orb--a {

	width: 220rpx;

	height: 220rpx;

	right: -40rpx;

	top: -60rpx;

}

.home-promo__orb--b {

	width: 160rpx;

	height: 160rpx;

	left: 48%;

	top: 20rpx;

	opacity: 0.65;

}

.home-promo__orb--c {

	width: 120rpx;

	height: 120rpx;

	left: -30rpx;

	bottom: -20rpx;

}



.home-promo__inner {

	position: relative;

	z-index: 1;

	display: flex;

	flex-direction: row;

	align-items: stretch;

	min-height: 280rpx;

	padding: 28rpx 24rpx 26rpx 28rpx;

	box-sizing: border-box;

}



.home-promo__copy {

	flex: 1;

	min-width: 0;

	display: flex;

	flex-direction: column;

	justify-content: center;

	padding-right: 8rpx;

}



.home-promo__title {

	display: block;

	font-size: 36rpx;

	font-weight: 800;

	color: #ffffff;

	line-height: 1.32;

	letter-spacing: 0.5rpx;

}



.home-promo__sub {

	display: block;

	margin-top: 12rpx;

	font-size: 22rpx;

	font-weight: 500;

	color: rgba(255, 255, 255, 0.88);

	line-height: 1.45;
	//不允许换行
	white-space: nowrap;

}



.home-promo__actions {

	display: flex;

	flex-direction: row;

	flex-wrap: wrap;

	align-items: center;

	gap: 14rpx;

	margin-top: 22rpx;

}



.home-promo__btn {

	display: inline-flex;

	align-items: center;

	justify-content: center;

	padding: 12rpx 26rpx;

	border-radius: 999rpx;

	font-size: 24rpx;

	font-weight: 700;

	line-height: 1.2;

	transition: transform 0.16s ease, opacity 0.16s ease;

}

.home-promo__btn--primary {

	background: #ffffff;

	color: #12962e;

	box-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.08);

}

.home-promo__btn--ghost {

	background: transparent;

	border: 2rpx solid rgba(255, 255, 255, 0.92);

	color: #ffffff;

	padding: 10rpx 24rpx;

}

.home-promo__btn--on {

	transform: scale(0.96);

	opacity: 0.9;

}

.home-promo__btn--on-ghost {

	transform: scale(0.96);

	opacity: 0.82;

}



.home-promo__art {

	position: relative;

	flex: 0 0 188rpx;

	width: 188rpx;

	align-self: center;

	height: 188rpx;

	margin: -4rpx 0 -4rpx 4rpx;

}



.home-promo__family {

	position: relative;

	width: 100%;

	height: 100%;

}



/* 身体（胶囊）在下层 */

.home-promo__body {

	position: absolute;

	border-radius: 999rpx;

	z-index: 1;

}

.home-promo__body--white {

	left: 28rpx;

	top: 72rpx;

	width: 44rpx;

	height: 88rpx;

	background: #ffffff;

	box-shadow: 0 4rpx 10rpx rgba(0, 0, 0, 0.06);

}

.home-promo__body--yellow {

	right: 22rpx;

	top: 82rpx;

	width: 40rpx;

	height: 68rpx;

	background: #ffe27a;

	box-shadow: 0 4rpx 10rpx rgba(0, 0, 0, 0.05);

}



/* 头部（圆） */

.home-promo__head {

	position: absolute;

	border-radius: 50%;

	z-index: 2;

	background: #fcd2bc;

}

.home-promo__head--left {

	left: 18rpx;

	top: 28rpx;

	width: 58rpx;

	height: 58rpx;

}

.home-promo__head--right {

	right: 16rpx;

	top: 38rpx;

	width: 48rpx;

	height: 48rpx;

	background: #f7c4a8;

}



/* 中间小孩 */

.home-promo__child {

	position: absolute;

	left: 50%;

	bottom: 28rpx;

	width: 26rpx;

	height: 26rpx;

	margin-left: -8rpx;

	border-radius: 50%;

	background: #ff9a2e;

	z-index: 3;

	box-shadow: 0 3rpx 8rpx rgba(0, 0, 0, 0.08);

}

</style>


