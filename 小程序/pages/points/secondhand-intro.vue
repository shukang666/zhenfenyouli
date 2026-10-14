<template>

	<view class="shi-page">

		<view class="shi-hero">

			<image
				class="shi-hero__img"
				:src="introHeroUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-summary">

			<image
				class="shi-summary__img"
				:src="introSummaryUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-steps">

			<image
				class="shi-steps__img"
				:src="introStepsUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-notice">

			<image
				class="shi-notice__img"
				:src="introNoticeUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-acts">
			<text class="shi-acts__kicker">快捷入口</text>
			<view class="shi-acts__panel">
				<view class="shi-acts__hero" hover-class="shi-acts__hero--press" @tap="goFleaHub">
					<view class="shi-acts__hero-icon" aria-hidden="true">
						<u-icon name="calendar-fill" size="44" color="#fff"></u-icon>
					</view>
					<view class="shi-acts__hero-text">
						<text class="shi-acts__hero-title">查看市集时间与地点</text>
						<text class="shi-acts__hero-sub">七贤岭绿色二手集市 · 线下交接</text>
					</view>
					<u-icon name="arrow-right" size="28" color="rgba(255,255,255,0.85)"></u-icon>
				</view>
				<view class="shi-acts__duo">
					<view class="shi-acts__tile" hover-class="shi-acts__tile--press" @tap="goList">
						<view class="shi-acts__tile-icon shi-acts__tile-icon--browse">
							<u-icon name="bag-fill" size="40" color="#076d3d"></u-icon>
						</view>
						<text class="shi-acts__tile-title">浏览闲置</text>
						<text class="shi-acts__tile-sub">看看邻居发布</text>
					</view>
					<view class="shi-acts__tile" hover-class="shi-acts__tile--press" @tap="goPublish">
						<view class="shi-acts__tile-icon shi-acts__tile-icon--publish">
							<u-icon name="camera-fill" size="40" color="#18af38"></u-icon>
						</view>
						<text class="shi-acts__tile-title">发布闲置</text>
						<text class="shi-acts__tile-sub">上传照片与积分价</text>
					</view>
				</view>
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



.shi-hero {

	border-radius: 24rpx;

	overflow: hidden;

	background: #076d3d;

}



.shi-hero__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-summary {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-summary__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-steps {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-steps__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-notice {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-notice__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-acts {
	margin-top: 32rpx;
}

.shi-acts__kicker {
	display: block;
	margin-bottom: 16rpx;
	padding-left: 8rpx;
	font-size: 28rpx;
	font-weight: 600;
	color: #3a3a3c;
}

.shi-acts__panel {
	background: #fff;
	border-radius: 24rpx;
	padding: 20rpx;
	box-shadow: 0 8rpx 28rpx rgba(7, 109, 61, 0.08);
}

.shi-acts__hero {
	display: flex;
	flex-direction: row;
	align-items: center;
	padding: 28rpx 24rpx;
	border-radius: 20rpx;
	background: linear-gradient(135deg, #0a7a3e 0%, #18af38 55%, #3ec96a 100%);
	box-shadow: 0 10rpx 24rpx rgba(24, 175, 56, 0.28);
}

.shi-acts__hero--press {
	opacity: 0.92;
	transform: scale(0.99);
}

.shi-acts__hero-icon {
	width: 80rpx;
	height: 80rpx;
	border-radius: 22rpx;
	background: rgba(255, 255, 255, 0.22);
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 20rpx;
}

.shi-acts__hero-text {
	flex: 1;
	min-width: 0;
	padding-right: 12rpx;
}

.shi-acts__hero-title {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.35;
}

.shi-acts__hero-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.88);
	line-height: 1.4;
}

.shi-acts__duo {
	display: flex;
	flex-direction: row;
	gap: 16rpx;
	margin-top: 20rpx;
}

.shi-acts__tile {
	flex: 1;
	padding: 24rpx 16rpx 22rpx;
	border-radius: 20rpx;
	background: #f4f7f5;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.shi-acts__tile--press {
	background: #e8f0eb;
}

.shi-acts__tile-icon {
	width: 88rpx;
	height: 88rpx;
	border-radius: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 14rpx;
}

.shi-acts__tile-icon--browse {
	background: #e3f3ea;
}

.shi-acts__tile-icon--publish {
	background: #eaf8ee;
}

.shi-acts__tile-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #1d1d1f;
	line-height: 1.3;
}

.shi-acts__tile-sub {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8e8e93;
	line-height: 1.35;
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

