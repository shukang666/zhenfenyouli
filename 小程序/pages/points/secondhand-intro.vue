<template>

	<view class="shi-page">

		<view class="shi-album">
			<image
				class="shi-album__img"
				:src="introHeroUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>
			<view class="shi-album__deco" aria-hidden="true">
				<view class="shi-album__deco-line"></view>
				<view class="shi-album__deco-badge">
					<view class="shi-album__deco-orn"></view>
					<view class="shi-album__deco-orn shi-album__deco-orn--on"></view>
					<view class="shi-album__deco-orn"></view>
				</view>
				<view class="shi-album__deco-line"></view>
			</view>
			<image
				class="shi-album__img"
				:src="introSummaryUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>
			<view class="shi-album__deco" aria-hidden="true">
				<view class="shi-album__deco-line"></view>
				<view class="shi-album__deco-badge">
					<view class="shi-album__deco-orn"></view>
					<view class="shi-album__deco-orn shi-album__deco-orn--on"></view>
					<view class="shi-album__deco-orn"></view>
				</view>
				<view class="shi-album__deco-line"></view>
			</view>
			<image
				class="shi-album__img"
				:src="introStepsUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>
			<view class="shi-album__deco" aria-hidden="true">
				<view class="shi-album__deco-line"></view>
				<view class="shi-album__deco-badge">
					<view class="shi-album__deco-orn"></view>
					<view class="shi-album__deco-orn shi-album__deco-orn--on"></view>
					<view class="shi-album__deco-orn"></view>
				</view>
				<view class="shi-album__deco-line"></view>
			</view>
			<image
				class="shi-album__img"
				:src="introNoticeUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>
		</view>

		<view class="shi-btns">
			<view class="shi-btns__row shi-btns__row--main" hover-class="shi-btns__row--press" @tap="goFleaHub">
				<view class="shi-btns__icon shi-btns__icon--main">
					<u-icon name="calendar-fill" size="36" color="#fff"></u-icon>
				</view>
				<text class="shi-btns__label">查看市集活动时间与地点</text>
				<u-icon name="arrow-right" size="26" color="#18af38"></u-icon>
			</view>
			<view class="shi-btns__row" hover-class="shi-btns__row--press" @tap="goList">
				<view class="shi-btns__icon">
					<u-icon name="bag-fill" size="36" color="#18af38"></u-icon>
				</view>
				<text class="shi-btns__label">浏览社区闲置</text>
				<u-icon name="arrow-right" size="26" color="#c7c7cc"></u-icon>
			</view>
			<view class="shi-btns__row" hover-class="shi-btns__row--press" @tap="goPublish">
				<view class="shi-btns__icon">
					<u-icon name="camera-fill" size="36" color="#18af38"></u-icon>
				</view>
				<text class="shi-btns__label">发布我的闲置</text>
				<u-icon name="arrow-right" size="26" color="#c7c7cc"></u-icon>
			</view>
		</view>



		<text class="shi-foot">官方积分商城兑换商品请仍在「积分权益」里下单；本功能仅针对居民之间的闲置交换。</text>

	</view>

</template>



<script>

import settings from '@/common/settings.js'

import {

	fleaMarketHubUrl,

	secondhandListUrl,

	secondhandPublishUrl,

} from '@/common/secondhandNav.js'



const INTRO_HERO_URL = settings.staticAssetBase + 'mall/secondhand-intro-hero.png'

const INTRO_NOTICE_URL = settings.staticAssetBase + 'mall/secondhand-intro-notice.png'

const INTRO_SUMMARY_URL = settings.staticAssetBase + 'mall/secondhand-intro-summary.png'

const INTRO_STEPS_URL = settings.staticAssetBase + 'mall/secondhand-intro-steps.png'



export default {

	data() {

		return {

			introHeroUrl: INTRO_HERO_URL,

			introNoticeUrl: INTRO_NOTICE_URL,

			introSummaryUrl: INTRO_SUMMARY_URL,

			introStepsUrl: INTRO_STEPS_URL,

		}

	},

	methods: {

		goFleaHub() {

			this.$u.route(fleaMarketHubUrl())

		},

		goList() {

			this.$u.route(secondhandListUrl())

		},

		goPublish() {

			if (!this.$u.func.checkLogin()) return

			this.$u.route(secondhandPublishUrl())

		},

	},

}

</script>



<style lang="scss" scoped>

.shi-page {

	min-height: 100vh;

	background: #e4e8e5;

	padding: 24rpx 30rpx calc(40rpx + env(safe-area-inset-bottom));

	box-sizing: border-box;

}



.shi-album {
	margin-top: 0;
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 32rpx rgba(7, 109, 61, 0.07);
	border: 2rpx solid rgba(24, 175, 56, 0.08);
}

.shi-album__img {
	width: 100%;
	display: block;
	vertical-align: top;
}

.shi-album__deco {
	height: 44rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	padding: 0 28rpx;
	background-color: #f3f8f5;
	background-image: repeating-linear-gradient(
		-48deg,
		transparent,
		transparent 10rpx,
		rgba(24, 175, 56, 0.05) 10rpx,
		rgba(24, 175, 56, 0.05) 20rpx
	);
}

.shi-album__deco-line {
	flex: 1;
	height: 2rpx;
	background: linear-gradient(90deg, transparent, #b8d9c4 40%, #b8d9c4 60%, transparent);
}

.shi-album__deco-badge {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	padding: 0 16rpx;
	flex-shrink: 0;
}

.shi-album__deco-orn {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #b8d9c4;
	margin: 0 10rpx;
}

.shi-album__deco-orn--on {
	width: 16rpx;
	height: 16rpx;
	border-radius: 4rpx;
	background: #18af38;
	transform: rotate(45deg);
	margin: 0 12rpx;
}

.shi-btns {
	margin-top: 28rpx;
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 28rpx rgba(7, 109, 61, 0.08);
	border: 2rpx solid rgba(24, 175, 56, 0.08);
}

.shi-btns__row {
	min-height: 104rpx;
	padding: 24rpx 28rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	border-top: 2rpx solid #eef2f0;
}

.shi-btns__row:first-child {
	border-top: none;
}

.shi-btns__row--main {
	background: linear-gradient(90deg, #f0faf4 0%, #fff 100%);
}

.shi-btns__row--press {
	background: #eef5f0;
}

.shi-btns__row--main.shi-btns__row--press {
	background: #e3f3ea;
}

.shi-btns__icon {
	width: 72rpx;
	height: 72rpx;
	border-radius: 20rpx;
	background: #e8f5ec;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 20rpx;
}

.shi-btns__icon--main {
	background: linear-gradient(145deg, #18af38, #0d8a2c);
}

.shi-btns__label {
	flex: 1;
	font-size: 30rpx;
	font-weight: 600;
	color: #1d1d1f;
	line-height: 1.35;
	padding-right: 12rpx;
}



.shi-foot {

	display: block;

	margin-top: 28rpx;

	font-size: 24rpx;

	color: #8e8e93;

	line-height: 1.55;

	text-align: center;

	padding: 0 12rpx;

}

</style>

